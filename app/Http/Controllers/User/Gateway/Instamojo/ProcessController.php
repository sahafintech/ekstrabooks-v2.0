<?php

namespace App\Http\Controllers\User\Gateway\Instamojo;

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

		$gateway = json_decode(get_business_option($slug, null, $invoice->business_id));

		$ch = curl_init();
		if ($gateway->environment == 'sandbox') {
			curl_setopt($ch, CURLOPT_URL, 'https://test.instamojo.com/api/1.1/payment-requests/');
		} else {
			curl_setopt($ch, CURLOPT_URL, 'https://instamojo.com/api/1.1/payment-requests/');
		}

		curl_setopt($ch, CURLOPT_HEADER, FALSE);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
		curl_setopt($ch, CURLOPT_FOLLOWLOCATION, TRUE);
		curl_setopt(
			$ch,
			CURLOPT_HTTPHEADER,
			array(
				"X-Api-Key:" . $gateway->api_key,
				"X-Auth-Token:" . $gateway->auth_token,
			)
		);
		$payload = array(
			'purpose' => _lang('Invoice Payment') . ' #' . $invoice->invoice_number,
			'amount' => round($invoice->grand_total - $invoice->paid, 2),
			'currency' => $invoice->business->currency,
			'buyer_name' => $invoice->customer->name,
			'email' => $invoice->customer->email,
			'redirect_url' => route('callback.' . $slug) . '?invoice_id=' . $invoice->id, // GET method
			'send_email' => 'True',
			'webhook' => route('callback.' . $slug) . '?invoice_id=' . $invoice->id . '&slug=' . $slug, // POST Method
			'allow_repeated_payments' => 'False',
		);

		curl_setopt($ch, CURLOPT_POST, true);
		curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($payload));
		$response = curl_exec($ch);
		curl_close($ch);
		$result = json_decode($response);

		if ($result->success) {
			$data['redirect'] = true;
			$data['redirect_url'] = $result->payment_request->longurl;
		} else {
			$data['error'] = true;
			$data['error_message'] = json_encode($result->message);
		}

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

		if ($request->isMethod('GET')) {

			$invoice = Invoice::withoutGlobalScopes()
				->with('business')
				->where('id', $request->invoice_id)
				->first();

			if ($invoice->status == 2) {
				return redirect()->route('invoices.show_public_invoice', $invoice->short_code)->with('success', _lang('Payment made successfully'));
			} else {
				return redirect()->route('invoices.payment_methods', $invoice->short_code)->with('error', _lang('Sorry, Payment not completed !'));
			}

		} else {
			$invoice = Invoice::withoutGlobalScopes()
				->with('business')
				->where('id', $request->invoice_id)
				->first();

			$gateway = json_decode(get_business_option($request->slug, null, $invoice->business_id));

			$data = $_POST;
			$mac_provided = $data['mac'];
			unset($data['mac']);
			ksort($data, SORT_STRING | SORT_FLAG_CASE);

			$mac_calculated = hash_hmac("sha1", implode("|", $data), $gateway->salt);
			if ($mac_provided == $mac_calculated) {
				if ($data['status'] == "Credit") {
					$amount = $data['amount'];

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

				}
			}

		} //End else condition

	}

}