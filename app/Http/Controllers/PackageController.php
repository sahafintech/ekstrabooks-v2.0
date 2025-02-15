<?php

namespace App\Http\Controllers;

use App\Models\Package;
use Illuminate\Http\Request;
use Validator;

class PackageController extends Controller
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
        $assets   = ['datatable'];
        $packages = Package::all();
        return view('backend.admin.package.list', compact('packages', 'assets'));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create(Request $request)
    {
        $alert_col = 'col-lg-10 offset-lg-1';
        return view('backend.admin.package.create', compact('alert_col'));
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
            'name'                   => 'required',
            'package_type'           => 'required',
            'cost'                   => 'required|numeric',
            'status'                 => 'required',
            'is_popular'             => 'required',
            'discount'               => 'required|numeric',
            'trial_days'             => 'required|integer',
            'user_limit'             => 'required|integer',
            'invoice_limit'          => 'required|integer',
            'quotation_limit'        => 'required|integer',
            'recurring_invoice'      => 'required|integer',
            'customer_limit'         => 'required|integer',
            'business_limit'         => 'required|integer',
            'invoice_builder'        => 'required',
            'online_invoice_payment' => 'required',
            'payroll_module'             => 'required',
        ]);

        if ($validator->fails()) {
            return redirect()->route('packages.create')
                ->withErrors($validator)
                ->withInput();
        }

        $package                         = new Package();
        $package->name                   = $request->input('name');
        $package->package_type           = $request->input('package_type');
        $package->cost                   = $request->input('cost');
        $package->status                 = $request->input('status');
        $package->is_popular             = $request->input('is_popular');
        $package->discount               = $request->input('discount');
        $package->trial_days             = $request->input('trial_days');
        $package->user_limit             = $request->input('user_limit');
        $package->invoice_limit          = $request->input('invoice_limit');
        $package->quotation_limit        = $request->input('quotation_limit');
        $package->recurring_invoice      = $request->input('recurring_invoice');
        $package->customer_limit         = $request->input('customer_limit');
        $package->business_limit         = $request->input('business_limit');
        $package->invoice_builder        = $request->input('invoice_builder');
        $package->online_invoice_payment = $request->input('online_invoice_payment');
        $package->payroll_module         = $request->input('payroll_module');
        $package->pos                    = $request->input('pos');
        $package->deffered_invoice       = $request->input('deffered_invoice');
        $package->medical_records        = $request->input('medical_records ');
        $package->prescription           = $request->input('prescription');
        $package->save();

        if ($package->id > 0) {
            return redirect()->route('packages.index')->with('success', _lang('Saved Successfully'));
        }
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show(Request $request, $id)
    {
        $package = Package::find($id);
        if (!$request->ajax()) {
            return view('backend.admin.package.view', compact('package', 'id'));
        } else {
            return view('backend.admin.package.modal.view', compact('package', 'id'));
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
        $alert_col = 'col-lg-10 offset-lg-1';
        $package   = Package::find($id);
        return view('backend.admin.package.edit', compact('package', 'id', 'alert_col'));
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
            'name'                   => 'required',
            'package_type'           => 'required',
            'cost'                   => 'required|numeric',
            'status'                 => 'required',
            'is_popular'             => 'required',
            'discount'               => 'required|numeric',
            'trial_days'             => 'required|integer',
            'user_limit'             => 'required|integer',
            'invoice_limit'          => 'required|integer',
            'quotation_limit'        => 'required|integer',
            'recurring_invoice'      => 'required|integer',
            'customer_limit'         => 'required|integer',
            'business_limit'         => 'required|integer',
            'invoice_builder'        => 'required',
            'online_invoice_payment' => 'required',
            'payroll_module'             => 'required',
        ]);

        if ($validator->fails()) {
            return redirect()->route('packages.edit', $id)
                ->withErrors($validator)
                ->withInput();
        }

        $package                         = Package::find($id);
        $package->name                   = $request->input('name');
        $package->package_type           = $request->input('package_type');
        $package->cost                   = $request->input('cost');
        $package->status                 = $request->input('status');
        $package->is_popular             = $request->input('is_popular');
        $package->discount               = $request->input('discount');
        $package->trial_days             = $request->input('trial_days');
        $package->user_limit             = $request->input('user_limit');
        $package->invoice_limit          = $request->input('invoice_limit');
        $package->quotation_limit        = $request->input('quotation_limit');
        $package->recurring_invoice      = $request->input('recurring_invoice');
        $package->customer_limit         = $request->input('customer_limit');
        $package->business_limit         = $request->input('business_limit');
        $package->invoice_builder        = $request->input('invoice_builder');
        $package->online_invoice_payment = $request->input('online_invoice_payment');
        $package->payroll_module         = $request->input('payroll_module');
        $package->pos                    = $request->input('pos');
        $package->deffered_invoice       = $request->input('deffered_invoice');
        $package->medical_records        = $request->input('medical_records ');
        $package->prescription           = $request->input('prescription');
        $package->save();

        return redirect()->route('packages.index')->with('success', _lang('Updated Successfully'));
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $package = Package::find($id);
        $package->delete();
        return redirect()->route('packages.index')->with('success', _lang('Deleted Successfully'));
    }
}
