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
    public function index(Request $request)
    {
        $per_page = $request->get('per_page', 50);
        $search = $request->get('search', '');

        $query = SubscriptionPayment::query();

        // Apply search if provided
        if (!empty($search)) {
            $query->where(function ($q) use ($search) {
                $q->whereHas('user', function ($q2) use ($search) {
                    $q2->where('name', 'like', "%{$search}%");
                })
                    ->orWhere('order_id', 'like', "%{$search}%")
                    ->orWhere('payment_method', 'like', "%{$search}%")
                    ->orWhere('amount', 'like', "%{$search}%");
            });
        }

        // Handle sorting
        $sorting = $request->get('sorting', []);
        $sortColumn = $sorting['column'] ?? 'id';
        $sortDirection = $sorting['direction'] ?? 'desc';

        if ($sortColumn === 'user.name') {
            $query->join('users', 'subscription_payments.user_id', '=', 'users.id')
                ->orderBy('users.name', $sortDirection)
                ->select('subscription_payments.*');
        } else if ($sortColumn === 'package.name') {
            $query->join('user_packages', 'subscription_payments.user_package_id', '=', 'user_packages.id')
                ->orderBy('user_packages.name', $sortDirection)
                ->select('subscription_payments.*');
        } else {
            $query->orderBy($sortColumn, $sortDirection);
        }

        // Get payments with pagination
        $subscription_payments = $query->with('user', 'package', 'created_by')
            ->paginate($per_page)
            ->withQueryString();

        // Return Inertia view
        return Inertia::render('Backend/Admin/SubscriptionPayments/List', [
            'subscription_payments' => $subscription_payments->items(),
            'meta' => [
                'current_page' => $subscription_payments->currentPage(),
                'from' => $subscription_payments->firstItem(),
                'last_page' => $subscription_payments->lastPage(),
                'links' => $subscription_payments->linkCollection(),
                'path' => $subscription_payments->path(),
                'per_page' => $subscription_payments->perPage(),
                'to' => $subscription_payments->lastItem(),
                'total' => $subscription_payments->total(),
            ],
            'filters' => [
                'search' => $search,
                'columnFilters' => $request->get('columnFilters', []),
                'sorting' => $sorting,
            ],
        ]);
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
        return Inertia::render('Backend/Admin/SubscriptionPayments/Create', [
            'user_packages' => $user_packages,
            'users' => $users,
            'currency' => get_option('currency'),
        ]);
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
        $subscriptionpayment = SubscriptionPayment::find($id);
        $user_packages = UserPackage::all();
        $users = User::where('user_type', 'user')->get();

        return Inertia::render('Backend/Admin/SubscriptionPayments/Edit', [
            'subscriptionpayment' => $subscriptionpayment,
            'user_packages' => $user_packages,
            'users' => $users,
            'currency' => get_option('currency'),
        ]);
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
            'user_id'        => 'required',
            'order_id'       => 'required|unique:subscription_payments,order_id,' . $id,
            'payment_method' => 'required',
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
        $subscriptionpayment->user_id        = $request->input('user_id');
        $subscriptionpayment->order_id       = $request->input('order_id');
        $subscriptionpayment->payment_method = $request->input('payment_method');
        $subscriptionpayment->user_package_id = $request->input('user_package_id');
        $subscriptionpayment->amount         = $request->input('amount');
        $subscriptionpayment->status         = $request->input('status');

        $subscriptionpayment->save();

        return redirect()->route('subscription_payments.index')->with('success', _lang('Updated Successfully'));
    }
}
