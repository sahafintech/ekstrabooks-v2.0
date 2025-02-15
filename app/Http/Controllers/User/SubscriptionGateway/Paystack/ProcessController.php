<?php

namespace App\Http\Controllers\User\SubscriptionGateway\Paystack;

use App\Models\User;
use Illuminate\Http\Request;
use App\Models\PaymentGateway;
use Illuminate\Support\Facades\DB;
use App\Models\SubscriptionPayment;
use App\Http\Controllers\Controller;
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
        $data                 = array();
        $data['callback_url'] = route('subscription_callback.' . $slug);
        $data['user']         = $user;
        $data['view']         = 'backend.guest.membership.gateway.' . $slug;

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

        $user    = User::find($request->user_id);
        $package = $user->package;

        $gateway = PaymentGateway::where('slug', $request->slug)->first();

        $curl = curl_init();

        curl_setopt_array($curl, array(
            CURLOPT_URL            => "https://api.paystack.co/transaction/verify/" . $request->reference,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING       => "",
            CURLOPT_MAXREDIRS      => 10,
            CURLOPT_TIMEOUT        => 30,
            CURLOPT_HTTP_VERSION   => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST  => "GET",
            CURLOPT_HTTPHEADER     => array(
                "Authorization: Bearer " . $gateway->parameters->paystack_secret_key,
                "Cache-Control: no-cache",
            ),
        ));

        $response = curl_exec($curl);
        $err      = curl_error($curl);
        curl_close($curl);

        if ($err) {
            return redirect()->route('membership.payment_gateways')->with('error', $err);
        }

        $charge = json_decode($response);

        if ($charge->status == true) {

            $amount = $charge->data->amount / 100;
            
            //Update Membership
            $packageAmount = $package->cost - ($package->discount / 100) * $package->cost;

            if ($packageAmount <= $amount) {
                DB::beginTransaction();

                $subscriptionpayment                  = new SubscriptionPayment();
                $subscriptionpayment->user_id         = $user->id;
                $subscriptionpayment->order_id        = $charge->data->id;
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