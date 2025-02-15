<?php

namespace App\Http\Controllers\User\Gateway\Mollie;

use App\Http\Controllers\Controller;
use App\Models\Invoice;
use App\Models\Transaction;
use App\Notifications\InvoicePayment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Mollie\Api\Exceptions\ApiException;
use Mollie\Api\MollieApiClient;

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

		$gateway = json_decode(get_business_option($slug, null, $invoice->business_id));

		$data = array();

		$mollie = new MollieApiClient();
		$mollie->setApiKey($gateway->api_key);

		try {
			$payment = $mollie->payments->create([
				'amount' => [
					'currency' => $invoice->business->currency,
					'value' => '' . sprintf('%0.2f', round($invoice->grand_total - $invoice->paid, 2)) . '',
				],
				'description' => _lang('Invoice Payment') . ' #' . $invoice->invoice_number,
				'redirectUrl' => route('callback.' . $slug),
				'metadata' => [
					"invoice_id" => $invoice->id,
				],
			]);
		} catch (ApiException $e) {
			$data['error'] = true;
			$data['error_message'] = $e->getPlainMessage();
			return json_encode($data);
		}

		session()->put('payment_id', $payment->id);
		session()->put('invoice_id', $invoice->id);
		session()->put('slug', $slug);

		$data['redirect'] = true;
		$data['redirect_url'] = $payment->getCheckoutUrl();

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

		$payment_id = session()->get('payment_id');
		$invoice_id = session()->get('invoice_id');
		$slug = session()->get('slug');

		$invoice = Invoice::withoutGlobalScopes()
			->with('business')
			->where('id', $invoice_id)
			->where('status', '!=', 2)
			->where('status', '!=', 0)
			->first();

		$gateway = json_decode(get_business_option($slug, null, $invoice->business_id));

		$mollie = new MollieApiClient();
		$mollie->setApiKey($gateway->api_key);
		$payment = $mollie->payments->get($payment_id);

		if ($payment->isPaid()) {
			$amount = $payment->amount->value;

			//Update Transaction
			$deuAmount = $invoice->grand_total - $invoice->paid;
			if ($deuAmount <= $amount) {
				DB::beginTransaction();

				$transaction = new Transaction();
				$transaction->trans_date = now();
				$transaction->account_id = $gateway->account;
				$transaction->method = $slug;
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

			session()->forget('payment_id');
			session()->forget('invoice_id');
			session()->forget('slug');

			try {
				$invoice->customer->notify(new InvoicePayment($transaction));
			} catch (\Exception $e) {}

			return redirect()->route('invoices.show_public_invoice', $invoice->short_code)->with('success', _lang('Payment made successfully'));
		} else {
			return redirect()->route('invoices.payment_methods', $invoice->short_code)->with('error', _lang('Sorry, Payment not completed !'));
		}
	}

}