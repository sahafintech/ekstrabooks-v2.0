<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\UserPackage;
use Illuminate\Http\Request;
use Inertia\Inertia;

class UserPackageController extends Controller
{
    public function index()
    {
        $packages = UserPackage::all();
        return Inertia::render('Backend/Admin/UserPackages/List', compact('packages'));
    }

    public function create(Request $request)
    {
        $users = User::where('user_type', 'user')->get();
        $currency_symbol = currency_symbol();
        return Inertia::render('Backend/Admin/UserPackages/Create', compact('users', 'currency_symbol'));
    }

    public function show($id)
    {
        $packageData = UserPackage::find($id);
        return Inertia::render('Backend/Admin/UserPackages/View', compact('packageData'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'user_id'        => 'required|unique:user_packages',
        ]);

        $user_package                           = new UserPackage();
        $user_package->user_id                  = $request->input('user_id');
        $user_package->name                     = $request->input('name');
        $user_package->package_type             = $request->input('package_type');
        $user_package->cost                     = $request->input('cost');
        $user_package->status                   = $request->input('status');
        $user_package->is_popular               = $request->input('is_popular');
        $user_package->discount                 = $request->input('discount');
        $user_package->trial_days               = $request->input('trial_days');
        $user_package->user_limit               = $request->input('user_limit');
        $user_package->invoice_limit            = $request->input('invoice_limit');
        $user_package->quotation_limit          = $request->input('quotation_limit');
        $user_package->recurring_invoice        = $request->input('recurring_invoice');
        $user_package->customer_limit           = $request->input('customer_limit');
        $user_package->business_limit           = $request->input('business_limit');
        $user_package->invoice_builder          = $request->input('invoice_builder');
        $user_package->online_invoice_payment   = $request->input('online_invoice_payment');
        $user_package->payroll_module           = $request->input('payroll_module');
        $user_package->pos                      = $request->input('pos');
        $user_package->deffered_invoice         = $request->input('deffered_invoice');
        $user_package->storage_limit            = $request->input('storage_limit');
        $user_package->medical_record           = $request->input('medical_record');
        $user_package->prescription             = $request->input('prescription');
        $user_package->membership_type          = null;
        $user_package->subscription_date        = null;
        $user_package->valid_to                 = null;
        $user_package->save();

        if ($user_package->id > 0) {
            return redirect()->route('user_packages.index')->with('success', 'Package created successfully');
        } else {
            return redirect()->route('user_packages.index')->with('error', 'Failed to create package');
        }
    }

    public function edit($id)
    {
        $packageData = UserPackage::find($id);
        $users = User::where('user_type', 'user')->get();
        return Inertia::render('Backend/Admin/UserPackages/Edit', compact('packageData', 'users', 'id'));
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'user_id'        => 'required|unique:user_packages,user_id,' . $id,
        ]);

        $user_package                           = UserPackage::find($id);
        $user_package->user_id                  = $request->input('user_id');
        $user_package->name                     = $request->input('name');
        $user_package->package_type             = $request->input('package_type');
        $user_package->cost                     = $request->input('cost');
        $user_package->status                   = $request->input('status');
        $user_package->is_popular               = $request->input('is_popular');
        $user_package->discount                 = $request->input('discount');
        $user_package->trial_days               = $request->input('trial_days');
        $user_package->user_limit               = $request->input('user_limit');
        $user_package->invoice_limit            = $request->input('invoice_limit');
        $user_package->quotation_limit          = $request->input('quotation_limit');
        $user_package->recurring_invoice        = $request->input('recurring_invoice');
        $user_package->customer_limit           = $request->input('customer_limit');
        $user_package->business_limit           = $request->input('business_limit');
        $user_package->invoice_builder          = $request->input('invoice_builder');
        $user_package->online_invoice_payment   = $request->input('online_invoice_payment');
        $user_package->payroll_module           = $request->input('payroll_module');
        $user_package->pos                      = $request->input('pos');
        $user_package->deffered_invoice         = $request->input('deffered_invoice');
        $user_package->storage_limit            = $request->input('storage_limit');
        $user_package->medical_record           = $request->input('medical_record');
        $user_package->prescription             = $request->input('prescription');
        $user_package->save();

        return redirect()->route('user_packages.index')->with('success', 'Package updated successfully');
    }

    public function destroy($id)
    {
        $user_package = UserPackage::find($id);
        $user_package->delete();
        return redirect()->route('user_packages.index')->with('success', 'Package deleted successfully');
    }
}
