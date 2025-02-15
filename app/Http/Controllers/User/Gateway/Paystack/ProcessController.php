<?php

namespace App\Http\Controllers\User\Gateway\Paystack;

use App\Http\Controllers\Controller;
use App\Models\Invoice;
use App\Models\Transaction;
use App\Notifications\InvoicePayment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ProcessController extends Controller {

	/**
	 * Create a new controller instance.
	 *
	 * @return void
	 */
	public function __construct() {
		ini_set('error_reporting', E_ALL);
		ini_set('display_errors', '1');
		ini_set('display_startup_errors', '1');

		date_default_timezone_set(get_option('timezone', 'Asia/Dhaka'));
	}

	/**
	 * Process Payment Gateway
	 *
	 * @return \Illuminate\Http\Response
	 */
	public static function process($invoice, $slug) {
		$data = array();
		$data['callback_url'] = route('callback.' . $slug);
		$data['custom'] = $invoice->id;
		$data['view'] = 'backend.guest.invoice.gateway.' . $slug;

		return json_encode($data);
	}

	/**
	 * Callback function from Payment Gateway
	 *
	 * @return \Illuminate\Http\Response
	 */
	public function callback(Request $request) {
		@ini_set('max_execution_time', 0);
		@set_time_limit(0);

		$invoice = Invoice::withoutGlobalScopes()
			->with('business')
			->where('id', $request->invoice_id)
			->where('status', '!=', 2)
			->where('status', '!=', 0)
			->first();

		$gateway = json_decode(get_business_option($request->slug, null, $invoice->business_id));

		$curl = curl_init();

		curl_setopt_array($curl, array(
			CURLOPT_URL => "https://api.paystack.co/transaction/verify/" . $request->reference,
			CURLOPT_RETURNTRANSFER => true,
			CURLOPT_ENCODING => "",
			CURLOPT_MAXREDIRS => 10,
			CURLOPT_TIMEOUT => 30,
			CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
			CURLOPT_CUSTOMREQUEST => "GET",
			CURLOPT_HTTPHEADER => array(
				"Authorization: Bearer " . $gateway->paystack_secret_key,
				"Cache-Control: no-cache",
			),
		));

		$response = curl_exec($curl);
		$err = curl_error($curl);
		curl_close($curl);

		if ($err) {
			return redirect()->route('invoice.payment_methods', $invoice->short_code)->with('error', $err);
		}

		$charge = json_decode($response);

		if ($charge->status == true) {

			$amount = $charge->data->amount / 100;

			//Update Transaction
			$deuAmount = $invoice->grand_total - $invoice->paid;
			if ($deuAmount <= $amount) {
				DB::beginTransaction();

				$transaction = new Transaction();
				$transaction->trans_date = now();
				$transaction->account_id = $gateway->account;
				$transaction->method = $request->slug;
				$transaction->dr_cr = 'cr';
				$transaction->type = 'income';
				$transaction->amount = convert_currency($invoice->business->currency, $transaction->account->currency, $amount);
				$transaction->ref_amount = $amount;
				$transaction->currency_rate = $transaction->ref_amount / $transaction->amount;
				$transaction->description = _lang('Invoice Payment') . ' #' . $invoice->invoice_number;
				$transaction->ref_id = $invoice->id;
				$transaction->ref_type = 'invoice';
				$transaction->user_id = $invoice->user_id;
				$transaction->business_id = $invoice->business_id;

				$transaction->saveQuietly();

				$invoice->paid = $invoice->paid + $transaction->ref_amount;
				$invoice->status = 3; //Partially Paid
				if ($invoice->paid >= $invoice->grand_total) {
					$invoice->status = 2; //Paid
				}
				$invoice->save();

				DB::commit();
			}

			try {
				$invoice->customer->notify(new InvoicePayment($transaction));
			} catch (\Exception $e) {}

			return redirect()->route('invoices.show_public_invoice', $invoice->short_code)->with('success', _lang('Payment made successfully'));

		} else {
			return redirect()->route('invoices.payment_methods', $invoice->short_code)->with('error', _lang('Sorry, Payment not completed !'));
		}
	}

}