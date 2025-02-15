<?php

namespace App\Http\Controllers\User\SubscriptionGateway\Stripe;

use Stripe\Charge;
use Stripe\Stripe;
use App\Models\User;
use Illuminate\Http\Request;
use App\Models\PaymentGateway;
use Illuminate\Support\Facades\DB;
use App\Models\SubscriptionPayment;
use App\Http\Controllers\Controller;
use App\Notifications\SubscriptionNotification;
use Carbon\Carbon;
use Database\Seeders\ChartOfAccountsSeeder;
use Illuminate\Support\Facades\Artisan;

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
     */
    public function callback(Request $request) {
        @ini_set('max_execution_time', 0);
        @set_time_limit(0);

        $user    = User::find($request->user_id);
        $package = $user->package;

        $gateway = PaymentGateway::where('slug', $request->slug)->first();

        Stripe::setApiKey($gateway->parameters->secret_key);

        $charge = Charge::create([
            "amount"      => round($package->cost - ($package->discount / 100) * $package->cost) * 100,
            "currency"    => currency(),
            "source"      => $request->stripeToken,
            "description" => get_option('company_name').' '._lang('Subscription'),
        ]);

        if ($charge->amount_refunded == 0 && $charge->failure_code == null && $charge->paid == true && $charge->captured == true) {

            $amount = $charge->amount / 100;

            //Update Membership
            $packageAmount = $package->cost - ($package->discount / 100) * $package->cost;

            if ($packageAmount <= $amount) {
                DB::beginTransaction();

                $subscriptionpayment                  = new SubscriptionPayment();
                $subscriptionpayment->user_id         = $user->id;
                $subscriptionpayment->order_id        = $charge->id;
                $subscriptionpayment->payment_method  = $gateway->name;
                $subscriptionpayment->package_id      = $package->id;
                $subscriptionpayment->amount          = $amount;
                $subscriptionpayment->status          = 1;
                $subscriptionpayment->created_user_id = $user->id;

                $subscriptionpayment->save();

                $user->membership_type   = 'member';
                $user->subscription_date = now();
                $user->valid_to          = update_membership_date($package, now());
                $user->s_email_send_at   = null;
                $user->save();

                DB::commit();
                
                try {
                    $user->notify(new SubscriptionNotification($user));
                } catch (\Exception$e) {}
            }

            return redirect()->route('dashboard.index')->with('success', _lang('Payment made successfully'));
        } else {
            return redirect()->route('membership.payment_gateways')->with('error', _lang('Sorry, Payment not completed !'));
        }
    }

}