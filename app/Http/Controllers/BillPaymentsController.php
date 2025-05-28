<?php

namespace App\Http\Controllers;

use App\Models\Account;
use App\Models\AuditLog;
use App\Models\BillPayment;
use App\Models\Purchase;
use App\Models\PurchasePayment;
use App\Models\Transaction;
use App\Models\TransactionMethod;
use App\Models\Vendor;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Inertia\Inertia;

class BillPaymentsController extends Controller
{
    public function index(Request $request)
    {
        $per_page = $request->get('per_page', 10);
        $search = $request->get('search', '');
        $sorting = $request->get('sorting', []);
        $sortColumn = $sorting['column'] ?? 'id';
        $sortDirection = $sorting['direction'] ?? 'desc';
        $vendor_id = $request->get('vendor_id');
        $dateRange = $request->get('date_range');

        $query = BillPayment::query();

        // Handle sorting
        if ($sortColumn === 'vendor.name') {
            $query->join('vendors', 'bill_payments.vendor_id', '=', 'vendors.id')
                ->orderBy('vendors.name', $sortDirection)
                ->select('bill_payments.*');
        } else {
            $query->orderBy('bill_payments.' . $sortColumn, $sortDirection);
        }

        $query->with('vendor', 'purchases');

        // Apply search if provided
        if (!empty($search)) {
            $query->where(function ($q) use ($search) {
                $q->whereHas('vendor', function ($q2) use ($search) {
                    $q2->where('name', 'like', "%{$search}%");
                })
                    ->orWhere('amount', 'like', "%{$search}%");
            });
        }

        // Apply vendor filter
        if (!empty($vendor_id)) {
            $query->where('vendor_id', $vendor_id);
        }

        // Apply date range filter
        if (!empty($dateRange)) {
            $query->where('date', '>=', $dateRange[0])
                ->where('date', '<=', $dateRange[1]);
        }

        $payments = $query->paginate($per_page)->withQueryString();
        $vendors = Vendor::all();

        // Return Inertia view
        return Inertia::render('Backend/User/BillPayment/List', [
            'payments' => $payments->items(),
            'meta' => [
                'current_page' => $payments->currentPage(),
                'from' => $payments->firstItem(),
                'last_page' => $payments->lastPage(),
                'links' => $payments->linkCollection(),
                'path' => $payments->path(),
                'per_page' => $payments->perPage(),
                'to' => $payments->lastItem(),
                'total' => $payments->total(),
            ],
            'filters' => [
                'search' => $search,
                'columnFilters' => $request->get('columnFilters', []),
                'sorting' => $sorting,
                'vendor_id' => $vendor_id,
                'date_range' => $dateRange,
            ],
            'vendors' => $vendors,
        ]);
    }

    public function create()
    {
        $vendors = Vendor::all();
        $accounts = Account::where(function ($query) {
            $query->where('account_type', 'Bank')
                ->orWhere('account_type', 'Cash');
        })->get();
        $methods = TransactionMethod::all();

        return Inertia::render('Backend/User/BillPayment/Create', [
            'vendors' => $vendors,
            'accounts' => $accounts,
            'methods' => $methods,
        ]);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'trans_date' => 'required',
            'account_id' => 'required',
            'method'     => 'nullable',
            'vendor_id' => 'required',
            'attachment' => 'nullable|mimes:jpeg,JPEG,png,PNG,jpg,doc,pdf,docx,zip',
        ]);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator);
        }

        if ($request->invoices == null) {
            return redirect()->back()->with('error', _lang('Please Select At Least One Invoice'));
        }

        for ($i = 0; $i < count($request->invoices); $i++) {

            $purchase = Purchase::find($request->invoices[$i]['invoice_id']);

            if ($request->invoices[$i]['amount'] > ($purchase->grand_total - $purchase->paid)) {
                return redirect()->back()->with('error', _lang('Amount must be equal or less than due amount'));
            }

            if ($request->invoices[$i]['amount'] <= 0) {
                return redirect()->back()->with('error', _lang('Amount must be greater than 0'));
            }
        }

        $amount = 0;

        foreach ($request->invoices as $invoice) {
            $amount += $invoice['amount'];
        }

        $payment = new BillPayment();
        $payment->date = Carbon::parse($request->trans_date)->format('Y-m-d');
        $payment->account_id = $request->account_id;
        $payment->payment_method = $request->method;
        $payment->amount = $amount;
        if ($request->vendor_id != 'all') {
            $payment->vendor_id = $request->vendor_id;
        } else {
            $payment->vendor_id = null;
        }
        $payment->reference = $request->reference;
        $payment->save();

        $attachment = '';
        if ($request->hasfile('attachment')) {
            $file       = $request->file('attachment');
            $attachment = rand() . time() . $file->getClientOriginalName();
            $file->move(public_path() . "/uploads/media/", $attachment);
        }

        $currentTime = Carbon::now();

        for ($i = 0; $i < count($request->invoices); $i++) {
            DB::beginTransaction();

            $purchase = Purchase::find($request->invoices[$i]['invoice_id']);

            $purchase_payment = new PurchasePayment();
            $purchase_payment->purchase_id = $request->invoices[$i]['invoice_id'];
            $purchase_payment->payment_id = $payment->id;
            $purchase_payment->amount = convert_currency($purchase->currency, $request->activeBusiness->currency, convert_currency($request->activeBusiness->currency, $purchase->currency, $request->invoices[$i]['amount']));
            $purchase_payment->save();

            $transaction              = new Transaction();
            $transaction->trans_date  = Carbon::parse($request->trans_date)->setTime($currentTime->hour, $currentTime->minute, $currentTime->second)->format('Y-m-d H:i:s');
            $transaction->account_id  = $request->account_id;
            $transaction->transaction_method      = $request->method;
            $transaction->dr_cr       = 'cr';
            $transaction->transaction_amount      = convert_currency($request->activeBusiness->currency, $purchase->currency, $request->invoices[$i]['amount']);
            $transaction->transaction_currency    = $purchase->currency;
            $transaction->currency_rate           = $purchase->exchange_rate;
            $transaction->base_currency_amount = convert_currency($purchase->currency, $request->activeBusiness->currency, convert_currency($request->activeBusiness->currency, $purchase->currency, $request->invoices[$i]['amount']));
            $transaction->reference   = $request->reference;
            $transaction->description = _lang('Bill Invoice Payment') . ' #' . $purchase->bill_no;
            $transaction->attachment  = $attachment;
            $transaction->ref_id      = $request->invoices[$i]['invoice_id'] . ',' . $payment->id;
            $transaction->ref_type    = 'bill payment';
            $transaction->vendor_id = $request->vendor_id;
            $transaction->save();

            $transaction              = new Transaction();
            $transaction->trans_date  = Carbon::parse($request->trans_date)->setTime($currentTime->hour, $currentTime->minute, $currentTime->second)->format('Y-m-d H:i');
            $transaction->account_id  = get_account('Accounts Payable')->id;
            $transaction->dr_cr       = 'dr';
            $transaction->transaction_amount      = convert_currency($request->activeBusiness->currency, $purchase->currency, $request->invoices[$i]['amount']);
            $transaction->transaction_currency    = $purchase->currency;
            $transaction->currency_rate           = $purchase->exchange_rate;
            $transaction->base_currency_amount = convert_currency($purchase->currency, $request->activeBusiness->currency, convert_currency($request->activeBusiness->currency, $purchase->currency, $request->invoices[$i]['amount']));
            $transaction->reference   = $request->reference;
            $transaction->description = _lang('Bill Invoice Payment') . ' #' . $purchase->bill_no;
            $transaction->attachment  = $attachment;
            $transaction->ref_id      = $request->invoices[$i]['invoice_id'] . ',' . $payment->id;
            $transaction->ref_type    = 'bill payment';
            $transaction->vendor_id = $request->vendor_id;
            $transaction->save();

            $purchase->paid   = $purchase->paid + convert_currency($purchase->currency, $request->activeBusiness->currency, convert_currency($request->activeBusiness->currency, $purchase->currency, $request->invoices[$i]['amount']));
            $purchase->status = 1; //Partially Paid
            if ($purchase->paid >= $purchase->grand_total) {
                $purchase->status = 2; //Paid
            }
            $purchase->save();

            DB::commit();
        }

        // audit log
        $audit = new AuditLog();
        $audit->date_changed = date('Y-m-d H:i:s');
        $audit->changed_by = auth()->user()->id;
        $audit->event = 'Bill Payment Created for ' . $purchase->bill_no;
        $audit->save();

        return redirect()->route('bill_payments.index')->with('success', _lang('Payment Made Successfully'));
    }

    public function edit($id)
    {
        $payment = BillPayment::where('id', $id)->with('purchases')->first();
        $vendors = Vendor::all();
        $accounts = Account::where(function ($query) {
            $query->where('account_type', 'Bank')
                ->orWhere('account_type', 'Cash');
        })->get();
        $methods = TransactionMethod::all();

        return Inertia::render('Backend/User/BillPayment/Edit', [
            'vendors' => $vendors,
            'accounts' => $accounts,
            'methods' => $methods,
            'payment' => $payment,
        ]);
    }

    public function update(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'trans_date' => 'required',
            'account_id' => 'required',
            'method'     => 'nullable',
            'vendor_id'  => 'required',
            'attachment' => 'nullable|mimes:jpeg,JPEG,png,PNG,jpg,doc,pdf,docx,zip',
        ]);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }

        if ($request->invoices == null) {
            return redirect()->back()->with('error', _lang('Please Select At Least One Invoice'));
        }

        DB::beginTransaction();

        $payment = BillPayment::findOrFail($id);

        // Validate amounts before processing
        for ($i = 0; $i < count($request->invoices); $i++) {
            $purchase = Purchase::findOrFail($request->invoices[$i]['invoice_id']);
            $old_purchase_payment = PurchasePayment::where('purchase_id', $request->invoices[$i]['invoice_id'])
                ->where('payment_id', $payment->id)
                ->firstOrFail();

            if ($request->invoices[$i]['amount'] > ($purchase->grand_total - ($purchase->paid - $old_purchase_payment->amount))) {
                throw new \Exception(_lang('Amount must be equal or less than due amount'));
            }

            if ($request->invoices[$i]['amount'] <= 0) {
                throw new \Exception(_lang('Amount must be greater than 0'));
            }
        }

        $amount = array_sum(array_map('floatval', array_column($request->invoices, 'amount')));

        // Update payment details
        $payment->date = Carbon::parse($request->trans_date)->format('Y-m-d');
        $payment->account_id = $request->account_id;
        $payment->payment_method = $request->method;
        $payment->amount = $amount;
        $payment->vendor_id = $request->vendor_id;
        $payment->reference = $request->reference;
        $payment->save();

        $attachment = '';
        if ($request->hasfile('attachment')) {
            $file = $request->file('attachment');
            $attachment = rand() . time() . $file->getClientOriginalName();
            $file->move(public_path() . "/uploads/media/", $attachment);
        }

        $currentTime = Carbon::now();

        // Process each invoice
        foreach ($request->invoices as $i => $invoice) {
            $purchase = Purchase::findOrFail($invoice['invoice_id']);
            $old_purchase_payment = PurchasePayment::where('purchase_id', $invoice['invoice_id'])
                ->where('payment_id', $payment->id)
                ->firstOrFail();

            // Reset purchase paid amount
            $purchase->paid = $purchase->paid - $old_purchase_payment->amount;
            $purchase->status = $this->calculatePurchaseStatus($purchase->paid, $purchase->grand_total);
            $purchase->save();

            // Delete old payment and transactions
            $old_purchase_payment->delete();
            Transaction::where('ref_id', $invoice['invoice_id'] . ',' . $payment->id)
                ->where('ref_type', 'bill payment')
                ->delete();

            // Create new purchase payment
            $purchase_payment = new PurchasePayment();
            $purchase_payment->purchase_id = $invoice['invoice_id'];
            $purchase_payment->payment_id = $payment->id;
            $purchase_payment->amount = convert_currency(
                $purchase->currency,
                $request->activeBusiness->currency,
                convert_currency(
                    $request->activeBusiness->currency,
                    $purchase->currency,
                    $invoice['amount']
                )
            );
            $purchase_payment->save();

            // Create credit transaction
            $this->createTransaction(
                $request,
                $purchase,
                $payment,
                $currentTime,
                'cr',
                $request->account_id,
                $attachment,
                $invoice
            );

            // Create debit transaction
            $this->createTransaction(
                $request,
                $purchase,
                $payment,
                $currentTime,
                'dr',
                get_account('Accounts Payable')->id,
                $attachment,
                $invoice
            );

            // Update purchase paid amount
            $purchase->paid = $purchase->paid + convert_currency(
                $purchase->currency,
                $request->activeBusiness->currency,
                convert_currency(
                    $request->activeBusiness->currency,
                    $purchase->currency,
                    $invoice['amount']
                )
            );
            $purchase->status = $this->calculatePurchaseStatus($purchase->paid, $purchase->grand_total);
            $purchase->save();
        }

        // Create audit log
        $audit = new AuditLog();
        $audit->date_changed = date('Y-m-d H:i:s');
        $audit->changed_by = auth()->user()->id;
        $audit->event = 'Bill Payment Updated for ' . $purchase->bill_no;
        $audit->save();

        DB::commit();
        return redirect()->route('bill_payments.index')->with('success', _lang('Payment Updated Successfully'));
    }

    private function calculatePurchaseStatus($paid, $grand_total)
    {
        if ($paid >= $grand_total) {
            return 2; // Paid
        } elseif ($paid == 0) {
            return 0; // Unpaid
        } else {
            return 1; // Partially Paid
        }
    }

    private function createTransaction($request, $purchase, $payment, $currentTime, $dr_cr, $account_id, $attachment, $invoice)
    {
        $transaction = new Transaction();
        $transaction->trans_date = Carbon::parse($request->trans_date)
            ->setTime($currentTime->hour, $currentTime->minute, $currentTime->second)
            ->format('Y-m-d H:i:s');
        $transaction->account_id = $account_id;
        $transaction->transaction_method = $request->method;
        $transaction->dr_cr = $dr_cr;
        $transaction->transaction_amount = convert_currency(
            $request->activeBusiness->currency,
            $purchase->currency,
            $invoice['amount']
        );
        $transaction->transaction_currency = $purchase->currency;
        $transaction->currency_rate = $purchase->exchange_rate;
        $transaction->base_currency_amount = convert_currency(
            $purchase->currency,
            $request->activeBusiness->currency,
            convert_currency(
                $request->activeBusiness->currency,
                $purchase->currency,
                $invoice['amount']
            )
        );
        $transaction->reference = $request->reference;
        $transaction->description = _lang('Bill Invoice Payment') . ' #' . $purchase->bill_no;
        $transaction->attachment = $attachment;
        $transaction->ref_id = $invoice['invoice_id'] . ',' . $payment->id;
        $transaction->ref_type = 'bill payment';
        $transaction->vendor_id = $request->vendor_id;
        $transaction->save();
    }

    public function destroy($id)
    {
        $payment = BillPayment::find($id);
        $purchase_payments = PurchasePayment::where('payment_id', $id)->get();

        // audit log
        $audit = new AuditLog();
        $audit->date_changed = date('Y-m-d H:i:s');
        $audit->changed_by = auth()->user()->id;
        $audit->event = 'Bill Payment Deleted for ' . $payment->id;
        $audit->save();

        foreach ($purchase_payments as $purchase_payment) {
            $purchase = Purchase::find($purchase_payment->purchase_id);

            $purchase->paid = $purchase->paid - $purchase_payment->amount;
            $purchase->status = 1; //Partially Paid
            if ($purchase->paid >= $purchase->grand_total) {
                $purchase->status = 2; //Paid
            } else if ($purchase->paid == 0) {
                $purchase->status = 0; //Unpaid
            }
            $purchase->save();


            $transaction = Transaction::where('ref_id', $purchase->id . ',' . $payment->id)->where('ref_type', 'bill payment')->get();

            foreach ($transaction as $trans) {
                $trans->delete();
            }

            $purchase_payment->delete();
        }

        $payment->delete();

        return redirect()->route('bill_payments.index')->with('success', _lang('Payment Deleted Successfully'));
    }

    public function bulk_estroy(Request $request)
    {
        foreach ($request->ids as $id) {
            $payment = BillPayment::find($id);
            $purchase_payments = PurchasePayment::where('payment_id', $id)->get();

            // audit log
            $audit = new AuditLog();
            $audit->date_changed = date('Y-m-d H:i:s');
            $audit->changed_by = auth()->user()->id;
            $audit->event = 'Bill Payment Deleted for ' . $payment->id;
            $audit->save();

            foreach ($purchase_payments as $purchase_payment) {
                $purchase = Purchase::find($purchase_payment->purchase_id);

                $purchase->paid = $purchase->paid - $purchase_payment->amount;
                $purchase->status = 1; //Partially Paid
                if ($purchase->paid >= $purchase->grand_total) {
                    $purchase->status = 2; //Paid
                } else if ($purchase->paid == 0) {
                    $purchase->status = 0; //Unpaid
                }
                $purchase->save();


                $transaction = Transaction::where('ref_id', $purchase->id . ',' . $payment->id)->where('ref_type', 'bill payment')->get();

                foreach ($transaction as $trans) {
                    $trans->delete();
                }

                $purchase_payment->delete();
            }

            $payment->delete();
        }

        return redirect()->route('bill_payments.index')->with('success', _lang('Payment Deleted Successfully'));
    }

    public function show($id)
    {
        $payment = BillPayment::where('id', $id)->with('purchases', 'vendor', 'business')->first();
        $decimalPlace = get_business_option('decimal_place', 2);

        return Inertia::render('Backend/User/BillPayment/View', [
            'payment' => $payment,
            'decimalPlace' => $decimalPlace
        ]);
    }

    public function show_public_bill_payment($id)
	{
		$payment = BillPayment::where('id', $id)->with('purchases', 'vendor', 'business')->first();

		$request = request();
		// add activeBusiness object to request
		$request->merge(['activeBusiness' => $payment->business]);

		return Inertia::render('Backend/User/BillPayment/PublicView', [
			'payment' => $payment,
		]);
	}
}
