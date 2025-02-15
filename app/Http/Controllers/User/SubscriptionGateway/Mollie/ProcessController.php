<?php

namespace App\Http\Controllers\User\SubscriptionGateway\Mollie;

use App\Models\User;
use Illuminate\Http\Request;
use App\Models\PaymentGateway;
use Mollie\Api\MollieApiClient;
use Illuminate\Support\Facades\DB;
use App\Models\SubscriptionPayment;
use App\Http\Controllers\Controller;
use Mollie\Api\Exceptions\ApiException;
use App\Notifications\SubscriptionNotification;

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

        $gateway = PaymentGateway::where('slug', $slug)->first();
        $package = $user->package;

        $data = array();

        $mollie = new MollieApiClient();
        $mollie->setApiKey($gateway->parameters->api_key);

        try {
            $payment = $mollie->payments->create([
                'amount'      => [
                    'currency' => currency(),
                    'value'    => '' . sprintf('%0.2f', round($package->cost - ($package->discount / 100) * $package->cost, 2)) . '',
                ],
                'description' => get_option('company_name') . ' ' . _lang('Subscription'),
                'redirectUrl' => route('subscription_callback.' . $slug),
                'metadata'    => [
                    "invoice_id" => uniqid(),
                ],
            ]);
        } catch (ApiException $e) {
            $data['error']         = true;
            $data['error_message'] = $e->getPlainMessage();
            return json_encode($data);
        }

        session()->put('payment_id', $payment->id);
        session()->put('user_id', $user->id);
        session()->put('slug', $slug);

        $data['redirect']     = true;
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
        $user_id    = session()->get('user_id');
        $slug       = session()->get('slug');

        $user    = User::find($user_id);
        $package = $user->package;

        $gateway = PaymentGateway::where('slug', $slug)->first();

        $mollie = new MollieApiClient();
        $mollie->setApiKey($gateway->parameters->api_key);
        $payment = $mollie->payments->get($payment_id);

        if ($payment->isPaid()) {
            $amount = $payment->amount->value;

            //Update Membership
            $packageAmount = $package->cost - ($package->discount / 100) * $package->cost;

            if ($packageAmount <= $amount) {
                DB::beginTransaction();

                $subscriptionpayment                  = new SubscriptionPayment();
                $subscriptionpayment->user_id         = $user->id;
                $subscriptionpayment->order_id        = $payment->id;
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
                } catch (\Exception $e) {}
            }

            return redirect()->route('dashboard.index')->with('success', _lang('Payment made successfully'));
        } else {
            return redirect()->route('membership.payment_gateways')->with('error', _lang('Sorry, Payment not completed !'));
        }
    }

}