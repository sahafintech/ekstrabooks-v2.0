<?php

namespace App\Http\Controllers;

use App\Models\Package;
use App\Models\PaymentGateway;
use App\Models\UserPackage;
use Database\Seeders\BusinessSettingSeeder;
use Database\Seeders\CurrencySeeder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Inertia\Inertia;

class MembershipController extends Controller
{

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        date_default_timezone_set(get_option('timezone', 'Asia/Dhaka'));
    }

    /** Show Active Subscription */
    public function index()
    {
        $lastPayments = auth()->user()->subscriptionPayments()->orderBy('id', 'desc')->first();
        $pkg     = UserPackage::where('user_id', auth()->id())->first();
        return Inertia::render('Backend/User/Profile/ActiveSubscription', [
            'lastPayments' => $lastPayments,
            'pkg'     => $pkg
        ]);
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function packages()
    {
        $packages = Package::active()->get();
        return view('backend.guest.membership.packages', compact('packages'));
    }

    public function choose_package(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'package_id' => 'required',
        ]);

        if ($validator->fails()) {
            if ($request->ajax()) {
                return response()->json(['result' => 'error', 'message' => $validator->errors()->all()]);
            } else {
                return back()->withErrors($validator)->withInput();
            }
        }

        $user                       = Auth::user();
        $user_package               = UserPackage::where('user_id', $user->id)->first();
        if($user_package !== null && $user_package->valid_to > date('Y-m-d')){
            return redirect()->back()->with('error', _lang('You already have an active subscription'));
        }
        $user_package->valid_to     = null;
        $user->s_email_send_at      = null;
        $user->save();
        $user_package->save();

        return redirect()->route('dashboard.index');
    }

    public function payment_gateways(Request $request)
    {
        $businessList     = auth()->user()->business;
        $activeBusiness   = auth()->user()->business()->withoutGlobalScopes()->wherePivot('is_active', 1)->first();
        $payment_gateways = PaymentGateway::active()->get();
        return view('backend.guest.membership.payment_gateways', compact('payment_gateways', 'businessList', 'activeBusiness'));
    }

    public function make_payment(Request $request, $slug)
    {
        $user = auth()->user();

        $gateway = PaymentGateway::where('slug', $slug)->first();
        $package = UserPackage::where('user_id', $user->id)->first();

        if ($user->package_id == null) {
            return redirect()->route('membership.packages')->with('error', _lang("Please choose your package first"));
        }

        //Process Via Payment Gateway
        $paymentGateway = '\App\Http\Controllers\User\SubscriptionGateway\\' . $slug . '\\ProcessController';

        $data = $paymentGateway::process($user, $slug);
        $data = json_decode($data);

        if (isset($data->redirect)) {
            return redirect($data->redirect_url);
        }

        if (isset($data->error)) {
            return back()->with('error', $data->error_message);
        }

        $alert_col = 'col-lg-6 offset-lg-3';
        return view($data->view, compact('data', 'package', 'gateway', 'slug', 'alert_col'));
    }
}
