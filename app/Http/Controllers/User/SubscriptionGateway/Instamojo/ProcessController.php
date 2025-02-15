<?php

namespace App\Http\Controllers\User\SubscriptionGateway\Instamojo;

use App\Http\Controllers\Controller;
use App\Models\PaymentGateway;
use App\Models\SubscriptionPayment;
use App\Models\User;
use App\Notifications\SubscriptionNotification;
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
	public static function process($user, $slug) {
		$data = array();

		$package = $user->package;
		$gateway = PaymentGateway::where('slug', $slug)->first();

		$ch = curl_init();
		if ($gateway->parameters->environment == 'sandbox') {
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
				"X-Api-Key:" . $gateway->parameters->api_key,
				"X-Auth-Token:" . $gateway->parameters->auth_token,
			)
		);
		$payload = array(
			'purpose' => get_option('company_name') . ' ' . _lang('Subscription'),
			'amount' => round($package->cost - ($package->discount / 100) * $package->cost, 2),
			'currency' => currency(),
			'buyer_name' => $user->name,
			'email' => $user->email,
			'redirect_url' => route('subscription_callback.' . $slug) . '?user_id=' . $user->id, // GET method
			'send_email' => 'True',
			'webhook' => route('subscription_callback.' . $slug) . '?user_id=' . $user->id . '&slug=' . $slug, // POST Method
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
			$user = User::find($request->user_id);
			return redirect()->route('membership.index');
		} else {
			$user = User::find($request->user_id);
			$package = $user->package;
			$gateway = PaymentGateway::where('slug', $request->slug)->first();

			$data = $_POST;
			$mac_provided = $data['mac'];
			unset($data['mac']);
			ksort($data, SORT_STRING | SORT_FLAG_CASE);

			$mac_calculated = hash_hmac("sha1", implode("|", $data), $gateway->parameters->salt);
			if ($mac_provided == $mac_calculated) {
				if ($data['status'] == "Credit") {
					$amount = $data['amount'];

					//Update Membership
					$packageAmount = $package->cost - ($package->discount / 100) * $package->cost;

					if ($packageAmount <= $amount) {
						DB::beginTransaction();

						$subscriptionpayment = new SubscriptionPayment();
						$subscriptionpayment->user_id = $user->id;
						$subscriptionpayment->order_id = $data['payment_id'];
						$subscriptionpayment->payment_method = $gateway->name;
						$subscriptionpayment->package_id = $package->id;
						$subscriptionpayment->amount = $amount;
						$subscriptionpayment->status = 1;
						$subscriptionpayment->created_user_id = $user->id;

						$subscriptionpayment->save();

						$user->membership_type = 'member';
						$user->subscription_date = now();
						$user->valid_to = update_membership_date($package, $user->subscription_date);
						$user->s_email_send_at = null;
						$user->save();

						DB::commit();

						try {
							$user->notify(new SubscriptionNotification($user));
						} catch (\Exception $e) {}
					}

				}
			}

		} //End else condition

	}

}