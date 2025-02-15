<?php

namespace App\Http\Controllers\User\Gateway\PremierWaller;

use App\Http\Controllers\Controller;
use App\Models\Invoice;
use App\Models\InvoicePayment as ModelsInvoicePayment;
use App\Models\ReceivePayment;
use App\Models\Transaction;
use App\Notifications\InvoicePayment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Stripe\Charge;
use Stripe\Stripe;

class ProcessController extends Controller
{

	/**
	 * Create a new controller instance.
	 *
	 * @return void
	 */
	public function __construct()
	{
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
	public static function process($invoice, $slug)
	{
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
	public function callback(Request $request)
	{
		@ini_set('max_execution_time', 0);
		@set_time_limit(0);

		$invoice = Invoice::withoutGlobalScopes()
			->with('business')
			->where('id', $request->invoice_id)
			->where('status', '!=', 2)
			->where('status', '!=', 0)
			->first();

		$gateway = json_decode(get_business_option($request->slug, null, $invoice->business_id));

		Stripe::setApiKey($gateway->secret_key);
		$charge = Charge::create([
			"amount" => round($invoice->grand_total - $invoice->paid) * 100,
			"currency" => $invoice->business->currency,
			"source" => $request->stripeToken,
			"description" => _lang('Invoice Payment') . ' #' . $invoice->invoice_number,
		]);

		if ($charge->amount_refunded == 0 && $charge->failure_code == null && $charge->paid == true && $charge->captured == true) {

			$amount = $charge->amount / 100;

			//Update Transaction
			$deuAmount = $invoice->grand_total - $invoice->paid;
			if ($deuAmount <= $amount) {
				DB::beginTransaction();

				$payment = new ReceivePayment();
				$payment->date = now();
				$payment->account_id = $gateway->account;
				$payment->payment_method = $request->slug;
				$payment->amount = convert_currency($invoice->business->currency, $invoice->currency, $amount);
				$payment->customer_id = $invoice->customer_id;
				$payment->type = 'online';
				$payment->save();

				$invoice_payment = new ModelsInvoicePayment();
				$invoice_payment->invoice_id = $invoice->id;
				$invoice_payment->payment_id = $payment->id;
				$invoice_payment->amount = convert_currency($invoice->business->currency, $invoice->currency, $amount);
				$invoice_payment->save();

				$transaction              = new Transaction();
				$transaction->trans_date  = now();
				$transaction->account_id  = $gateway->account;
				$transaction->transaction_method      = $request->slug;
				$transaction->dr_cr       = 'dr';
				$transaction->transaction_amount      = convert_currency($invoice->business->currency, $invoice->currency, $amount);
				$transaction->transaction_currency    = $invoice->currency;
				$transaction->currency_rate           = $invoice->exchange_rate;
				$transaction->base_currency_amount = convert_currency($invoice->currency, $invoice->business->currency, convert_currency($invoice->business->currency, $invoice->currency, $amount));
				$transaction->description = _lang('Credit Invoice Payment') . ' #' . $invoice->invoice_number;
				$transaction->ref_id = $invoice->id;
				$transaction->ref_type = 'invoice';
				$transaction->customer_id = $invoice->customer_id;
				$transaction->user_id = $invoice->user_id;
				$transaction->business_id = $invoice->business_id;
				$transaction->saveQuietly();

				$transaction              = new Transaction();
				$transaction->trans_date  = now();
				$transaction->account_id  = get_account('Accounts Receivable')->id;
				$transaction->dr_cr       = 'cr';
				$transaction->transaction_amount      = convert_currency($invoice->business->currency, $invoice->currency, $amount);
				$transaction->transaction_currency    = $invoice->currency;
				$transaction->currency_rate           = $invoice->exchange_rate;
				$transaction->base_currency_amount = convert_currency($invoice->currency, $invoice->business->currency, convert_currency($invoice->business->currency, $invoice->currency, $amount));
				$transaction->description = _lang('Credit Invoice Payment') . ' #' . $invoice->invoice_number;
				$transaction->ref_id = $invoice->id;
				$transaction->ref_type = 'invoice';
				$transaction->user_id = $invoice->user_id;
				$transaction->business_id = $invoice->business_id;
				$transaction->saveQuietly();

				$invoice->paid = $invoice->paid + $transaction->base_currency_amount;
				$invoice->status = 3; //Partially Paid
				if ($invoice->paid >= $invoice->grand_total) {
					$invoice->status = 2; //Paid
				}
				$invoice->save();

				DB::commit();
			}

			try {
				$invoice->customer->notify(new InvoicePayment($transaction));
			} catch (\Exception $e) {
			}

			return redirect()->route('invoices.show_public_invoice', $invoice->short_code)->with('success', _lang('Payment made successfully'));
		} else {
			return redirect()->route('invoices.payment_methods', $invoice->short_code)->with('error', _lang('Sorry, Payment not completed !'));
		}
	}
}
