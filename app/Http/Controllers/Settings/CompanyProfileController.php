<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Models\CompanyProfile;
use Illuminate\Http\Request;

class CompanyProfileController extends Controller
{
    public function index()
    {
        return view('Settings/company-profile', [
            'user' => auth()->user()
        ]);
    }
    public function store(Request $request)
    {
        $validatedData = $request->validate(([
            'businessname' => 'required',
            'businesstype' => 'required',
            'email' => 'required|email',
            'phonenumber' => 'required',
            'gst/vat' => 'somtimes',
            'dateformat' => 'required',
            'city' => 'required',
            'address' => 'somtimes',
            'currency' => 'required'
        ]));

        $validatedData['image'] = $request->image->store('profile', 'public');

        $company = CompanyProfile::first();

        if ($company == null) {
            CompanyProfile::create($validatedData);
        } else {
            $company->update($validatedData);
        }
    }
}
