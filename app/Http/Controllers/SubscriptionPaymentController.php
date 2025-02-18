<?php

namespace App\Http\Controllers;

use App\Models\SubscriptionPayment;
use App\Models\User;
use App\Models\UserPackage;
use DataTables;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Validator;

class SubscriptionPaymentController extends Controller
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

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $subscription_payments = SubscriptionPayment::select('subscription_payments.*')
            ->with('user', 'package', 'created_by')
            ->orderBy("subscription_payments.id", "desc")
            ->get()->map(function ($payment) {
                $payment->amount = decimalPlace($payment->amount, currency_symbol());
                return $payment;
            });

        return Inertia::render('Backend/Admin/SubscriptionPayments/List', compact('subscription_payments'));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create(Request $request)
    {
        $user_packages = UserPackage::all();
        $users = User::where('user_type', 'user')->get();
        $currency_symbol = currency_symbol();
        return Inertia::render('Backend/Admin/SubscriptionPayments/Create', compact('user_packages', 'users', 'currency_symbol'));
    }

    public function membership_date($package_type, $subscription_date)
    {
        if ($package_type == 'monthly') {
            $newDate = date('Y-m-d', strtotime($subscription_date . ' + 1 months'));
        } else if ($package_type == 'yearly') {
            $newDate = date('Y-m-d', strtotime($subscription_date . ' + 1 years'));
        } else if ($package_type == 'lifetime') {
            $newDate = date('Y-m-d', strtotime($subscription_date . ' + 25 years'));
        }

        return $newDate;
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'user_id'        => 'required',
            'order_id'       => 'required|unique:subscription_payments',
            'payment_method' => 'required',
            'amount'         => 'required|numeric',
            'status'         => 'required',
        ]);

        if ($validator->fails()) {
            return redirect()->route('subscription_payments.create')
                ->withErrors($validator)
                ->withInput();
        }

        DB::beginTransaction();

        $subscriptionpayment                    = new SubscriptionPayment();
        $subscriptionpayment->user_id           = $request->input('user_id');
        $subscriptionpayment->order_id          = $request->input('order_id');
        $subscriptionpayment->payment_method    = $request->input('payment_method');
        $subscriptionpayment->user_package_id   = $request->input('user_package_id');
        $subscriptionpayment->amount            = $request->input('amount');
        $subscriptionpayment->status            = $request->input('status');
        $subscriptionpayment->created_user_id   = auth()->id();
        $subscriptionpayment->save();

        $user_package                           = UserPackage::find($request->input('user_package_id'));
        $user_package->membership_type          = 'member';
        $user_package->subscription_date        = now();
        $user_package->valid_to                 = $this->membership_date($user_package->package_type, now());
        $user_package->save();

        DB::commit();

        if ($subscriptionpayment->id > 0) {
            return redirect()->route('subscription_payments.create')->with('success', _lang('Saved Successfully'));
        }
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit(Request $request, $id)
    {
        $alert_col           = 'col-lg-8 offset-lg-2';
        $subscriptionpayment = SubscriptionPayment::find($id);
        return view('backend.admin.subscription_payment.edit', compact('subscriptionpayment', 'id', 'alert_col'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'package_id'     => 'required',
            'amount'         => 'required|numeric',
            'status'         => 'required',
        ]);

        if ($validator->fails()) {
            if ($request->ajax()) {
                return response()->json(['result' => 'error', 'message' => $validator->errors()->all()]);
            } else {
                return redirect()->route('subscription_payments.edit', $id)
                    ->withErrors($validator)
                    ->withInput();
            }
        }

        $subscriptionpayment                 = SubscriptionPayment::find($id);
        $subscriptionpayment->package_id     = $request->input('package_id');
        $subscriptionpayment->amount         = $request->input('amount');
        $subscriptionpayment->status         = $request->input('status');

        $subscriptionpayment->save();

        if (!$request->ajax()) {
            return redirect()->route('subscription_payments.index')->with('success', _lang('Updated Successfully'));
        } else {
            return response()->json(['result' => 'success', 'action' => 'update', 'message' => _lang('Updated Successfully'), 'data' => $subscriptionpayment, 'table' => '#subscription_payments_table']);
        }
    }
}
