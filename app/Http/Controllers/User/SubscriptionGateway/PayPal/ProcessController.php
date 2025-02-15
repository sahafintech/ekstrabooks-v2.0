<?php

namespace App\Http\Controllers\User\SubscriptionGateway\PayPal;

use App\Http\Controllers\Controller;
use App\Models\PaymentGateway;
use App\Models\SubscriptionPayment;
use App\Models\User;
use App\Notifications\SubscriptionNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use PayPal\Checkout\Requests\OrderCaptureRequest;
use PayPal\Http\Environment\ProductionEnvironment;
use PayPal\Http\Environment\SandboxEnvironment;
use PayPal\Http\PayPalClient;

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
        $data                 = array();
        $data['callback_url'] = route('subscription_callback.' . $slug);
        $data['user_id']      = $user->id;
        $data['view']         = 'backend.guest.membership.gateway.' . $slug;

        return json_encode($data);
    }

    /**
     * Callback function from Payment Gateway
     *
     * @return \Illuminate\Http\Response
     **/
    public function callback(Request $request) {
        @ini_set('max_execution_time', 0);
        @set_time_limit(0);

        $user    = User::find($request->user_id);
        $package = $user->package;

        $gateway = PaymentGateway::where('slug', $request->slug)->first();

        // Creating an environment
        $clientId     = $gateway->parameters->client_id;
        $clientSecret = $gateway->parameters->client_secret;

        if ($gateway->parameters->environment == 'sandbox') {
            $environment = new SandboxEnvironment($clientId, $clientSecret);
        } else {
            $environment = new ProductionEnvironment($clientId, $clientSecret);
        }

        $client = new PayPalClient($environment);

        $payPalRequest = new OrderCaptureRequest($request->order_id);

        try {
            $response = $client->send($payPalRequest);
            $result   = json_decode((string) $response->getBody());

            if ($result->status == 'COMPLETED') {
                $amount = $result->purchase_units[0]->amount->value;

                //Update Membership
                $packageAmount = $package->cost - ($package->discount / 100) * $package->cost;

                if ($packageAmount <= $amount) {
                    DB::beginTransaction();

                    $subscriptionpayment                  = new SubscriptionPayment();
                    $subscriptionpayment->user_id         = $user->id;
                    $subscriptionpayment->order_id        = $request->order_id;
                    $subscriptionpayment->payment_method  = $gateway->name;
                    $subscriptionpayment->package_id      = $package->id;
                    $subscriptionpayment->amount          = $amount;
                    $subscriptionpayment->status          = 1;
                    $subscriptionpayment->created_user_id = $user->id;

                    $subscriptionpayment->save();

                    $user->membership_type   = 'member';
                    $user->subscription_date = now();
                    $user->valid_to          = update_membership_date($package, $user->subscription_date);
                    $user->s_email_send_at   = null;
                    $user->save();

                    DB::commit();

                    try {
                        $user->notify(new SubscriptionNotification($user));
                    } catch (\Exception$e) {}
                }

                return redirect()->route('dashboard.index')->with('success', _lang('Payment made successfully'));
            }

        } catch (\Exception $ex) {
            return redirect()->route('membership.payment_gateways')->with('error', $ex->getMessage());
        }
    }

}