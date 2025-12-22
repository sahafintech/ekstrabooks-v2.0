<?php

namespace App\Http\Controllers;

use App\Models\Account;
use App\Models\Attachment;
use App\Models\AuditLog;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\InvoicePayment;
use App\Models\ReceivePayment;
use App\Models\Transaction;
use App\Models\TransactionMethod;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Validator;
use Inertia\Inertia;
use function Spatie\LaravelPdf\Support\pdf;

class ReceivePaymentsController extends Controller
{
    public function index(Request $request)
    {
        Gate::authorize('receive_payments.view');

        $per_page = $request->get('per_page', 10);
        $search = $request->get('search', '');
        $sorting = $request->get('sorting', []);
        $sortColumn = $sorting['column'] ?? 'id';
        $sortDirection = $sorting['direction'] ?? 'desc';
        $customer_id = $request->get('customer_id', '');
        $date_range = $request->get('date_range', null);

        $query = ReceivePayment::where('deffered_payment', 0);

        // Handle sorting
        if ($sortColumn === 'customer.name') {
            $query->join('customers', 'receive_payments.customer_id', '=', 'customers.id')
                ->orderBy('customers.name', $sortDirection)
                ->select('receive_payments.*');
        } else {
            $query->orderBy('receive_payments.' . $sortColumn, $sortDirection);
        }

        $query->with('customer', 'invoices');

        // Apply search if provided
        if (!empty($search)) {
            $query->where(function ($q) use ($search) {
                $q->whereHas('customer', function ($q2) use ($search) {
                    $q2->where('name', 'like', "%{$search}%");
                })
                    ->orWhere('amount', 'like', "%{$search}%");
            });
        }

        // Apply customer filter
        if (!empty($customer_id)) {
            $query->where('customer_id', $customer_id);
        }

        // Apply date range filter
        if (!empty($date_range)) {
            $query->whereBetween('date', [$date_range[0], $date_range[1]]);
        }

        $payments = $query->paginate($per_page)->withQueryString();
        $customers = Customer::all();

        // Return Inertia view
        return Inertia::render('Backend/User/ReceivePayment/List', [
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
                'sorting' => $request->get('sorting', []),
                'customer_id' => $customer_id,
                'date_range' => $date_range,
            ],
            'customers' => $customers,
        ]);
    }

    public function create()
    {
        Gate::authorize('receive_payments.create');

        $customers = Customer::all();
        $accounts = Account::where(function ($query) {
            $query->where('account_type', 'Bank')
                ->orWhere('account_type', 'Cash');
        })->get();
        $methods = TransactionMethod::all();

        return Inertia::render('Backend/User/ReceivePayment/Create', [
            'customers' => $customers,
            'accounts' => $accounts,
            'methods' => $methods,
        ]);
    }

    public function store(Request $request)
    {
        Gate::authorize('receive_payments.create');

        $validator = Validator::make($request->all(), [
            'trans_date' => 'required',
            'account_id' => 'required',
            'method'     => 'nullable',
            'customer_id' => 'required',
        ]);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }

        $default_accounts = ['Accounts Receivable', 'Sales Tax Payable', 'Sales Discount Allowed', 'Inventory'];

        // if these accounts are not exists then create it
        foreach ($default_accounts as $account) {
            if (!Account::where('account_name', $account)->where('business_id', $request->activeBusiness->id)->exists()) {
                $account_obj = new Account();
                if ($account == 'Accounts Receivable') {
                    $account_obj->account_code = '1100';
                } elseif ($account == 'Sales Tax Payable') {
                    $account_obj->account_code = '2200';
                } elseif ($account == 'Sales Discount Allowed') {
                    $account_obj->account_code = '4009';
                } elseif ($account == 'Inventory') {
                    $account_obj->account_code = '1000';
                }
                $account_obj->account_name = $account;
                if ($account == 'Accounts Receivable') {
                    $account_obj->account_type = 'Other Current Asset';
                } elseif ($account == 'Sales Tax Payable') {
                    $account_obj->account_type = 'Current Liability';
                } elseif ($account == 'Sales Discount Allowed') {
                    $account_obj->account_type = 'Other Income';
                } elseif ($account == 'Inventory') {
                    $account_obj->account_type = 'Other Current Asset';
                }
                if ($account == 'Accounts Receivable') {
                    $account_obj->dr_cr   = 'dr';
                } elseif ($account == 'Sales Tax Payable') {
                    $account_obj->dr_cr   = 'cr';
                } elseif ($account == 'Sales Discount Allowed') {
                    $account_obj->dr_cr   = 'dr';
                } elseif ($account == 'Inventory') {
                    $account_obj->dr_cr   = 'dr';
                }
                $account_obj->business_id = $request->activeBusiness->id;
                $account_obj->user_id     = $request->activeBusiness->user->id;
                $account_obj->opening_date   = now()->format('Y-m-d');
                $account_obj->save();
            }
        }

        if ($request->invoices == null) {
            return redirect()->back()->with('error', _lang('Please Select At Least One Invoice'));
        }

        for ($i = 0; $i < count($request->invoices); $i++) {
            $invoice = Invoice::find($request->invoices[$i]['invoice_id']);

            if ($request->invoices[$i]['amount'] > ($invoice->grand_total - $invoice->paid)) {
                return redirect()->back()->with('error', _lang('Amount must be equal or less than due amount'));
            }

            if ($request->invoices[$i]['amount'] == 0) {
                return redirect()->back()->with('error', _lang('Amount must be greater than 0'));
            }
        }

        $amount = 0;

        foreach ($request->invoices as $invoice) {
            $amount += $invoice['amount'];
        }

        $payment = new ReceivePayment();
        $payment->date = Carbon::parse($request->trans_date)->format('Y-m-d');
        $payment->account_id = $request->account_id;
        $payment->payment_method = $request->method;
        $payment->amount = $amount;
        $payment->customer_id = $request->customer_id;
        $payment->reference = $request->reference;
        $payment->type = 'offline';
        $payment->save();

        // if attachments then upload
		if (isset($request->attachments)) {
			if ($request->attachments != null) {
				for ($i = 0; $i < count($request->attachments); $i++) {
					$theFile = $request->file("attachments.$i");
					if ($theFile == null) {
						continue;
					}
					$theAttachment = rand() . time() . $theFile->getClientOriginalName();
					$theFile->move(public_path() . "/uploads/media/attachments/", $theAttachment);

					$attachment = new Attachment();
					$attachment->file_name = $request->attachments[$i]->getClientOriginalName();
					$attachment->path = "/uploads/media/attachments/" . $theAttachment;
					$attachment->ref_type = 'receive payment';
					$attachment->ref_id = $payment->id;
					$attachment->save();
				}
			}
		}

        $currentTime = Carbon::now();

        for ($i = 0; $i < count($request->invoices); $i++) {
            DB::beginTransaction();

            $invoice = Invoice::find($request->invoices[$i]['invoice_id']);

            if ($request->invoices[$i]['amount'] > ($invoice->grand_total - $invoice->paid)) {
                return redirect()->back()->with('error', _lang('Amount must be equal or less than due amount'));
            }

            $invoice_payment = new InvoicePayment();
            $invoice_payment->invoice_id = $request->invoices[$i]['invoice_id'];
            $invoice_payment->payment_id = $payment->id;
            $invoice_payment->amount = convert_currency($invoice->currency, $request->activeBusiness->currency, convert_currency($request->activeBusiness->currency, $invoice->currency, $request->invoices[$i]['amount']));
            $invoice_payment->save();

            $transaction              = new Transaction();
            $transaction->trans_date  = Carbon::parse($request->trans_date)->setTime($currentTime->hour, $currentTime->minute, $currentTime->second)->format('Y-m-d H:i:s');
            $transaction->account_id  = $request->account_id;
            $transaction->transaction_method      = $request->method;
            $transaction->dr_cr       = 'dr';
            $transaction->transaction_amount      = convert_currency($request->activeBusiness->currency, $invoice->currency, $request->invoices[$i]['amount']);
            $transaction->transaction_currency    = $invoice->currency;
            $transaction->currency_rate           = $invoice->exchange_rate;
            $transaction->base_currency_amount = convert_currency($invoice->currency, $request->activeBusiness->currency, convert_currency($request->activeBusiness->currency, $invoice->currency, $request->invoices[$i]['amount']));
            $transaction->reference   = $request->reference;
            $transaction->description = _lang('Credit Invoice Payment') . ' #' . $invoice->invoice_number;
            $transaction->ref_id      = $request->invoices[$i]['invoice_id'] . ',' . $payment->id;
            $transaction->ref_type    = 'invoice payment';
            $transaction->customer_id = $request->customer_id;
            $transaction->project_id  = $invoice->project_id;
            $transaction->save();

            $transaction              = new Transaction();
            $transaction->trans_date  = Carbon::parse($request->trans_date)->setTime($currentTime->hour, $currentTime->minute, $currentTime->second)->format('Y-m-d H:i');
            $transaction->account_id  = get_account('Accounts Receivable')->id;
            $transaction->dr_cr       = 'cr';
            $transaction->transaction_amount      = convert_currency($request->activeBusiness->currency, $invoice->currency, $request->invoices[$i]['amount']);
            $transaction->transaction_currency    = $invoice->currency;
            $transaction->currency_rate           = $invoice->exchange_rate;
            $transaction->base_currency_amount = convert_currency($invoice->currency, $request->activeBusiness->currency, convert_currency($request->activeBusiness->currency, $invoice->currency, $request->invoices[$i]['amount']));
            $transaction->reference   = $request->reference;
            $transaction->description = _lang('Credit Invoice Payment') . ' #' . $invoice->invoice_number;
            $transaction->ref_id      = $request->invoices[$i]['invoice_id'] . ',' . $payment->id;
            $transaction->ref_type    = 'invoice payment';
            $transaction->customer_id = $request->customer_id;
            $transaction->project_id  = $invoice->project_id;
            $transaction->save();

            $invoice->paid   = $invoice->paid + convert_currency($invoice->currency, $request->activeBusiness->currency, convert_currency($request->activeBusiness->currency, $invoice->currency, $request->invoices[$i]['amount']));
            $invoice->status = 3; //Partially Paid
            if ($invoice->paid >= $invoice->grand_total) {
                $invoice->status = 2; //Paid
            }
            $invoice->save();

            DB::commit();
        }

        // audit log
        $audit = new AuditLog();
        $audit->date_changed = date('Y-m-d H:i:s');
        $audit->changed_by = auth()->user()->id;
        $audit->event = 'Invoice Payment Received';
        $audit->save();

        return redirect()->route('receive_payments.index')->with('success', _lang('Payment Received Successfully'));
    }

    public function update(Request $request, $id)
    {
        Gate::authorize('receive_payments.update');

        $validator = Validator::make($request->all(), [
            'trans_date' => 'required',
            'account_id' => 'required',
            'method'     => 'nullable',
            'customer_id' => 'required',
        ]);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }

        if ($request->invoices == null) {
            return redirect()->back()->with('error', _lang('Please Select At Least One Invoice'));
        }

        $payment = ReceivePayment::find($id);

        for ($i = 0; $i < count($request->invoices); $i++) {

            $invoice = Invoice::find($request->invoices[$i]['invoice_id']);

            $invoice_payment = InvoicePayment::where('invoice_id', $request->invoices[$i]['invoice_id'])->where('payment_id', $payment->id)->first();

            if ($request->invoices[$i]['amount'] > ($invoice->grand_total - ($invoice->paid - $invoice_payment->amount))) {
                return redirect()->back()->with('error', _lang('Amount must be equal or less than due amount'));
            }

            if ($request->invoices[$i]['amount'] == 0) {
                return redirect()->back()->with('error', _lang('Amount must be greater than 0'));
            }
        }

        $default_accounts = ['Accounts Receivable', 'Sales Tax Payable', 'Sales Discount Allowed', 'Inventory'];

        // if these accounts are not exists then create it
        foreach ($default_accounts as $account) {
            if (!Account::where('account_name', $account)->where('business_id', $request->activeBusiness->id)->exists()) {
                $account_obj = new Account();
                if ($account == 'Accounts Receivable') {
                    $account_obj->account_code = '1100';
                } elseif ($account == 'Sales Tax Payable') {
                    $account_obj->account_code = '2200';
                } elseif ($account == 'Sales Discount Allowed') {
                    $account_obj->account_code = '4009';
                } elseif ($account == 'Inventory') {
                    $account_obj->account_code = '1000';
                }
                $account_obj->account_name = $account;
                if ($account == 'Accounts Receivable') {
                    $account_obj->account_type = 'Other Current Asset';
                } elseif ($account == 'Sales Tax Payable') {
                    $account_obj->account_type = 'Current Liability';
                } elseif ($account == 'Sales Discount Allowed') {
                    $account_obj->account_type = 'Other Income';
                } elseif ($account == 'Inventory') {
                    $account_obj->account_type = 'Other Current Asset';
                }
                if ($account == 'Accounts Receivable') {
                    $account_obj->dr_cr   = 'dr';
                } elseif ($account == 'Sales Tax Payable') {
                    $account_obj->dr_cr   = 'cr';
                } elseif ($account == 'Sales Discount Allowed') {
                    $account_obj->dr_cr   = 'dr';
                } elseif ($account == 'Inventory') {
                    $account_obj->dr_cr   = 'dr';
                }
                $account_obj->business_id = $request->activeBusiness->id;
                $account_obj->user_id     = $request->activeBusiness->user->id;
                $account_obj->opening_date   = now()->format('Y-m-d');
                $account_obj->save();
            }
        }

        $amount = 0;

        foreach ($request->invoices as $invoice) {
            $amount += $invoice['amount'];
        }

        $payment->date = Carbon::parse($request->trans_date)->format('Y-m-d');
        $payment->account_id = $request->account_id;
        $payment->payment_method = $request->method;
        $payment->amount = $amount;
        $payment->customer_id = $request->customer_id;
        $payment->reference = $request->reference;
        $payment->type = 'offline';
        $payment->save();

        // delete old attachments
		$attachments = Attachment::where('ref_id', $payment->id)->where('ref_type', 'receive payment')->get(); // Get attachments from the database

        if (isset($request->attachments)) {
			foreach ($attachments as $attachment) {
				if (!in_array($attachment->path, $request->attachments)) {
					$filePath = public_path($attachment->path);
					if (file_exists($filePath)) {
						unlink($filePath); // Delete the file
					}
					$attachment->delete(); // Delete the database record
				}
			}
		}

		// if attachments then upload
		if (isset($request->attachments)) {
			if ($request->attachments != null) {
				for ($i = 0; $i < count($request->attachments); $i++) {
					$theFile = $request->file("attachments.$i");
					if ($theFile == null) {
						continue;
					}
					$theAttachment = rand() . time() . $theFile->getClientOriginalName();
					$theFile->move(public_path() . "/uploads/media/attachments/", $theAttachment);

					$attachment = new Attachment();
					$attachment->file_name = $request->attachments[$i]->getClientOriginalName();
					$attachment->path = "/uploads/media/attachments/" . $theAttachment;
					$attachment->ref_type = 'receive payment';
					$attachment->ref_id = $payment->id;
					$attachment->save();
				}
			}
		}

        $currentTime = Carbon::now();
        DB::beginTransaction();

        for ($i = 0; $i < count($request->invoices); $i++) {

            $invoice = Invoice::find($request->invoices[$i]['invoice_id']);

            $invoice_payment = InvoicePayment::where('invoice_id', $request->invoices[$i]['invoice_id'])->where('payment_id', $payment->id)->first();

            $invoice->paid   = $invoice->paid - convert_currency($invoice->currency, $request->activeBusiness->currency, convert_currency($request->activeBusiness->currency, $invoice->currency, $invoice_payment->amount));
            if ($invoice->paid >= $invoice->grand_total) {
                $invoice->status = 2; //Paid
            } else if ($invoice->paid == 0) {
                $invoice->status = 1; //Unpaid
            } else if ($invoice->paid < $invoice->grand_total) {
                $invoice->status = 3; //Partially Paid
            }
            $invoice->save();

            $invoice_payment->forceDelete();

            $transactions = Transaction::where('ref_id', $request->invoices[$i]['invoice_id'] . ',' . $payment->id)->where('ref_type', 'invoice payment')->get();

            foreach ($transactions as $transaction) {
                $transaction->forceDelete();
            }

            $invoice_payment = new InvoicePayment();
            $invoice_payment->invoice_id = $request->invoices[$i]['invoice_id'];
            $invoice_payment->payment_id = $payment->id;
            $invoice_payment->amount = convert_currency($invoice->currency, $request->activeBusiness->currency, convert_currency($request->activeBusiness->currency, $invoice->currency, $request->invoices[$i]['amount']));
            $invoice_payment->save();

            $transaction              = new Transaction();
            $transaction->trans_date  = Carbon::parse($request->trans_date)->setTime($currentTime->hour, $currentTime->minute, $currentTime->second)->format('Y-m-d H:i:s');
            $transaction->account_id  = $request->account_id;
            $transaction->transaction_method      = $request->method;
            $transaction->dr_cr       = 'dr';
            $transaction->transaction_amount      = convert_currency($request->activeBusiness->currency, $invoice->currency, $request->invoices[$i]['amount']);
            $transaction->transaction_currency    = $invoice->currency;
            $transaction->currency_rate           = $invoice->exchange_rate;
            $transaction->base_currency_amount = convert_currency($invoice->currency, $request->activeBusiness->currency, convert_currency($request->activeBusiness->currency, $invoice->currency, $request->invoices[$i]['amount']));
            $transaction->reference   = $request->reference;
            $transaction->description = _lang('Credit Invoice Payment') . ' #' . $invoice->invoice_number;
            $transaction->ref_id      = $request->invoices[$i]['invoice_id'] . ',' . $payment->id;
            $transaction->ref_type    = 'invoice payment';
            $transaction->customer_id = $request->customer_id;
            $transaction->project_id  = $invoice->project_id;
            $transaction->save();

            $transaction              = new Transaction();
            $transaction->trans_date  = Carbon::parse($request->trans_date)->setTime($currentTime->hour, $currentTime->minute, $currentTime->second)->format('Y-m-d H:i');
            $transaction->account_id  = get_account('Accounts Receivable')->id;
            $transaction->dr_cr       = 'cr';
            $transaction->transaction_amount      = convert_currency($request->activeBusiness->currency, $invoice->currency, $request->invoices[$i]['amount']);
            $transaction->transaction_currency    = $invoice->currency;
            $transaction->currency_rate           = $invoice->exchange_rate;
            $transaction->base_currency_amount = convert_currency($invoice->currency, $request->activeBusiness->currency, convert_currency($request->activeBusiness->currency, $invoice->currency, $request->invoices[$i]['amount']));
            $transaction->reference   = $request->reference;
            $transaction->description = _lang('Credit Invoice Payment') . ' #' . $invoice->invoice_number;
            $transaction->ref_id      = $request->invoices[$i]['invoice_id'] . ',' . $payment->id;
            $transaction->ref_type    = 'invoice payment';
            $transaction->customer_id = $request->customer_id;
            $transaction->project_id  = $invoice->project_id;
            $transaction->save();

            $invoice->paid   = $invoice->paid + convert_currency($invoice->currency, $request->activeBusiness->currency, convert_currency($request->activeBusiness->currency, $invoice->currency, $request->invoices[$i]['amount']));
            $invoice->status = 3; //Partially Paid
            if ($invoice->paid >= $invoice->grand_total) {
                $invoice->status = 2; //Paid
            }
            $invoice->save();

        }
        
        DB::commit();

        // audit log
        $audit = new AuditLog();
        $audit->date_changed = date('Y-m-d H:i:s');
        $audit->changed_by = auth()->user()->id;
        $audit->event = 'Invoice Payment Updated';
        $audit->save();

        return redirect()->route('receive_payments.index')->with('success', _lang('Payment Updated Successfully'));
    }

    public function edit($id)
    {
        Gate::authorize('receive_payments.update');

        $payment = ReceivePayment::where('id', $id)->with('invoices')->first();
        $customers = Customer::all();
        $accounts = Account::where(function ($query) {
            $query->where('account_type', 'Bank')
                ->orWhere('account_type', 'Cash');
        })->get();
        $methods = TransactionMethod::all();
        $theAttachment = Attachment::where('ref_id', $payment->id)->where('ref_type', 'receive payment')->get();

        return Inertia::render('Backend/User/ReceivePayment/Edit', [
            'customers' => $customers,
            'accounts' => $accounts,
            'methods' => $methods,
            'payment' => $payment,
            'theAttachments' => $theAttachment,
        ]);
    }

    public function destroy(Request $request, $id)
    {
        Gate::authorize('receive_payments.delete');

        $payment = ReceivePayment::find($id);
        $invoices_payments = InvoicePayment::where('payment_id', $id)->get();

        // audit log
        $audit = new AuditLog();
        $audit->date_changed = date('Y-m-d H:i:s');
        $audit->changed_by = auth()->user()->id;
        $audit->event = 'Invoice Payment Deleted';
        $audit->save();

        foreach ($invoices_payments as $invoice_payment) {
            $invoice = Invoice::find($invoice_payment->invoice_id);

            $invoice->paid = $invoice->paid - $invoice_payment->amount;
            $invoice->status = 3; //Partially Paid
            if ($invoice->paid >= $invoice->grand_total) {
                $invoice->status = 2; //Paid
            } else if ($invoice->paid == 0) {
                $invoice->status = 1; //Unpaid
            }
            $invoice->save();

            $transaction = Transaction::where('ref_id', $invoice->id . ',' . $payment->id)->where('ref_type', 'invoice payment')->get();

            foreach ($transaction as $trans) {
                $trans->delete();
            }

            $invoice_payment->delete();
        }

        $transaction = Transaction::where('ref_id', $payment->id)->where('ref_type', 'invoice payment')->get();

        foreach ($transaction as $trans) {
            $trans->delete();
        
        
        // delete attachments
		$attachments = Attachment::where('ref_id', $payment->id)->where('ref_type', 'receive payment')->get();
		foreach ($attachments as $attachment) {
			$filePath = public_path($attachment->path);
			if (file_exists($filePath)) {
				unlink($filePath);
			}
			$attachment->delete();
		}}

        $payment->delete();

        return redirect()->route('receive_payments.index')->with('success', _lang('Payment Deleted Successfully'));
    }

    public function show($id)
    {
        Gate::authorize('receive_payments.view');

        $payment = ReceivePayment::where('id', $id)->with('invoices', 'customer', 'business')->first();
        $decimalPlace = get_business_option('decimal_place', 2);
        $attachment = Attachment::where('ref_id', $payment->id)->where('ref_type', 'receive payment')->get();

        return Inertia::render('Backend/User/ReceivePayment/View', [
            'payment' => $payment,
            'decimalPlace' => $decimalPlace,
            'attachments' => $attachment,
        ]);
    }

    public function pdf($id)
    {
        Gate::authorize('receive_payments.pdf');

        $payment = ReceivePayment::where('id', $id)->with('invoices', 'customer', 'business', 'account')->first();
        return pdf()
            ->view('backend.user.pdf.receive-payment', compact('payment'))
            ->name('receive-payment-' . $payment->id . '.pdf')
            ->download();
    }

    public function show_public_receive_payment($id)
    {
        $payment = ReceivePayment::where('id', $id)->with('invoices', 'customer', 'business')->first();

        $request = request();
        // add activeBusiness object to request
        $request->merge(['activeBusiness' => $payment->business]);

        return Inertia::render('Backend/User/ReceivePayment/PublicView', [
            'payment' => $payment,
        ]);
    }
}
