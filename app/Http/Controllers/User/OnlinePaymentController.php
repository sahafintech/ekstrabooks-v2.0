<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\Invoice;
use Illuminate\Http\Request;

class OnlinePaymentController extends Controller {

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct() {}

    /**
     * Display available payment gateways
     *
     * @return \Illuminate\Http\Response
     */
    public function payment_methods($short_code) {
        $invoice = Invoice::withoutGlobalScopes()->with(['customer', 'business', 'items', 'taxes'])
            ->where('short_code', $short_code)
            ->where('status', '!=', 2)
            ->where('status', '!=', 0)
            ->first();

        if (package($invoice->user_id)->online_invoice_payment != 1) {
            if (!request()->ajax()) {
                return back()->with('error', _lang('Sorry, This module is not available in your current package !'));
            } else {
                return response()->json(['result' => 'error', 'message' => _lang('Sorry, This module is not available in your current package !')]);
            }
        }

        if (!$invoice) {
            return redirect('login');
        }
        return view('backend.guest.invoice.gateway.list', compact('invoice'));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function make_payment(Request $request, $short_code, $slug) {
        $invoice = Invoice::withoutGlobalScopes()
            ->with('business')
            ->where('short_code', $short_code)
            ->where('status', '!=', 2)
            ->where('status', '!=', 0)
            ->first();

        if (package($invoice->user_id)->online_invoice_payment != 1) {
            if (!request()->ajax()) {
                return back()->with('error', _lang('Sorry, This module is not available in your current package !'));
            } else {
                return response()->json(['result' => 'error', 'message' => _lang('Sorry, This module is not available in your current package !')]);
            }
        }

        $gateway = json_decode(get_business_option($slug, null, $invoice->business_id));

        //Process Via Payment Gateway
        $paymentGateway = '\App\Http\Controllers\User\Gateway\\' . $slug . '\\ProcessController';

        $data = $paymentGateway::process($invoice, $slug);
        $data = json_decode($data);

        if (isset($data->redirect)) {
            return redirect($data->redirect_url);
        }

        if (isset($data->error)) {
            return back()
                ->with('error', $data->error_message);
        }

        $alert_col = 'col-lg-6 offset-lg-3';
        return view($data->view, compact('data', 'invoice', 'gateway', 'slug', 'alert_col'));
    }

}