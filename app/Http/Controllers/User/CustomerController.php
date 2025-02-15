<?php

namespace App\Http\Controllers\User;

use App\Exports\CustomerExport;
use App\Http\Controllers\Controller;
use App\Imports\CustomerImport;
use App\Models\AuditLog;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\Quotation;
use App\Models\Receipt;
use App\Models\Transaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Maatwebsite\Excel\Facades\Excel;

class CustomerController extends Controller
{

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware(function ($request, $next) {
            $route_name = request()->route()->getName();
            if ($route_name == 'customers.store') {
                if (has_limit('customers', 'customer_limit') <= 0) {
                    if (!$request->ajax()) {
                        return back()->with('error', _lang('Sorry, Your have already reached your package quota !'));
                    } else {
                        return response()->json(['result' => 'error', 'message' => _lang('Sorry, Your have already reached your package quota !')]);
                    }
                }
            }

            return $next($request);
        });
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $customers = Customer::select('customers.*')
            ->orderBy("customers.id", "desc")
            ->get();
        return view('backend.user.customer.list', compact('customers'));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create(Request $request)
    {
        $alert_col = 'col-lg-8 offset-lg-2';
        if (!$request->ajax()) {
            return view('backend.user.customer.create', compact('alert_col'));
        } else {
            return view('backend.user.customer.modal.create', compact('alert_col'));
        }
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
            'name'            => 'required|max:50',
            'email'           => [
                'nullable',
                'email',
                Rule::unique('customers')->where(function ($query) use ($request) {
                    return $query->where('user_id', $request->activeBusiness->user_id)
                        ->where('business_id', $request->activeBusiness->id);
                }),
            ],
        ]);

        if ($validator->fails()) {
            return redirect()->route('customers.create')
                ->withErrors($validator)
                ->withInput();
        }

        $profile_picture = 'default.png';
        if ($request->hasfile('profile_picture')) {
            $file            = $request->file('profile_picture');
            $profile_picture = time() . $file->getClientOriginalName();
            $file->move(public_path() . "/uploads/profile/", $profile_picture);
        }

        $customer                  = new Customer();
        $customer->name            = $request->input('name');
        $customer->company_name    = $request->input('company_name');
        $customer->email           = $request->input('email');
        $customer->mobile          = $request->input('mobile');
        $customer->country         = $request->input('country');
        $customer->vat_id          = $request->input('vat_id');
        $customer->reg_no          = $request->input('reg_no');
        $customer->city            = $request->input('city');
        $customer->contract_no     = $request->input('contract_no');
        $customer->zip             = $request->input('zip');
        $customer->address         = $request->input('address');
        $customer->age             = $request->input('age');
        $customer->gender          = $request->input('gender');
        $customer->save();

        // audit log
        $audit = new AuditLog();
        $audit->date_changed = date('Y-m-d H:i:s');
        $audit->changed_by = auth()->user()->id;
        $audit->event = 'Created Customer ' . $customer->name;
        $audit->save();

        if (!$request->ajax()) {
            return redirect()->route('customers.index')->with('success', _lang('Saved Successfully'));
        } else {
            return response()->json(['result' => 'success', 'action' => 'store', 'message' => _lang('Saved Successfully'), 'data' => $customer, 'table' => '#customers_table']);
        }
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        $data             = array();
        $data['alert_col'] = 'col-lg-8 offset-lg-2';
        $data['customer'] = Customer::find($id);

        if (!isset($_GET['tab'])) {
            $data['invoice'] = Invoice::selectRaw('COUNT(id) as total_invoice, SUM(grand_total) as total_amount, sum(paid) as total_paid')
                ->where('customer_id', $id)
                ->where('is_recurring', 0)
                ->where('status', '!=', 0)
                ->first();
        }

        if (isset($_GET['tab']) && $_GET['tab'] == 'invoices') {
            $data['invoices'] = Invoice::where('customer_id', $id)
                ->where('is_recurring', 0)
                ->orderBy('invoice_date', 'desc')
                ->get();
        }

        if (isset($_GET['tab']) && $_GET['tab'] == 'receipts') {
            $data['receipts'] = Receipt::where('customer_id', $id)
                ->orderBy('receipt_date', 'desc')
                ->get();
        }

        if (isset($_GET['tab']) && $_GET['tab'] == 'quotations') {
            $data['quotations'] = Quotation::where('customer_id', $id)
                ->orderBy('quotation_date', 'desc')
                ->paginate(15);
            $data['quotations']->withPath('?tab=' . $_GET['tab']);
        }

        if (isset($_GET['tab']) && $_GET['tab'] == 'transactions') {
            $data['transactions'] = Transaction::where('ref_id', '!=', NULL)
                ->where('customer_id', $id)
                ->whereHas('account', function ($query) {
                    $query->where('account_type', '=', 'Cash')
                        ->orWhere('account_type', '=', 'Bank');
                })
                ->paginate(15);
            $data['transactions']->withPath('?tab=' . $_GET['tab']);
        }

        return view('backend.user.customer.view', $data);
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
        $customer  = Customer::find($id);
        return view('backend.user.customer.edit', compact('customer', 'id', 'alert_col'));
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
            'name'            => 'required|max:50',
            'email'           => [
                'nullable',
                'email',
                Rule::unique('customers')->where(function ($query) use ($request) {
                    return $query->where('user_id', $request->activeBusiness->user_id)
                        ->where('business_id', $request->activeBusiness->id);
                })->ignore($id),
            ],
        ]);

        if ($validator->fails()) {
            return redirect()->route('customers.edit', $id)
                ->withErrors($validator)
                ->withInput();
        }

        if ($request->hasfile('profile_picture')) {
            $file            = $request->file('profile_picture');
            $profile_picture = time() . $file->getClientOriginalName();
            $file->move(public_path() . "/uploads/profile/", $profile_picture);
        }

        $customer                   = Customer::find($id);
        $customer->name             = $request->input('name');
        $customer->company_name     = $request->input('company_name');
        $customer->email            = $request->input('email');
        $customer->mobile           = $request->input('mobile');
        $customer->country          = $request->input('country');
        $customer->vat_id           = $request->input('vat_id');
        $customer->reg_no           = $request->input('reg_no');
        $customer->city             = $request->input('city');
        $customer->contract_no      = $request->input('contract_no');
        $customer->zip              = $request->input('zip');
        $customer->address          = $request->input('address');
        $customer->age              = $request->input('age');
        $customer->gender           = $request->input('gender');
        $customer->save();

        // audit log
        $audit = new AuditLog();
        $audit->date_changed = date('Y-m-d H:i:s');
        $audit->changed_by = auth()->user()->id;
        $audit->event = 'Updated Customer ' . $customer->name;
        $audit->save();

        if (!$request->ajax()) {
            return redirect()->route('customers.index')->with('success', _lang('Updated Successfully'));
        } else {
            return response()->json(['result' => 'success', 'action' => 'update', 'message' => _lang('Updated Successfully'), 'data' => $customer, 'table' => '#customers_table']);
        }
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $customer = Customer::find($id);

        // audit log
        $audit = new AuditLog();
        $audit->date_changed = date('Y-m-d H:i:s');
        $audit->changed_by = auth()->user()->id;
        $audit->event = 'Deleted Customer ' . $customer->name;
        $audit->save();

        $customer->delete();
        return redirect()->route('customers.index')->with('success', _lang('Deleted Successfully'));
    }

    public function import_customers(Request $request)
    {
        $request->validate([
            'customers_file' => 'required|mimes:xls,xlsx',
        ]);

        try {
            Excel::import(new CustomerImport, $request->file('customers_file'));
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }

        // audit log
        $audit = new AuditLog();
        $audit->date_changed = date('Y-m-d H:i:s');
        $audit->changed_by = auth()->user()->id;
        $audit->event = 'Imported Customers';
        $audit->save();

        return redirect()->route('customers.index')->with('success', _lang('Customers Imported'));
    }

    public function export_customers()
    {
        return Excel::download(new CustomerExport, 'customers export ' . now()->format('d m Y') . '.xlsx');

        // audit log
        $audit = new AuditLog();
        $audit->date_changed = date('Y-m-d H:i:s');
        $audit->changed_by = auth()->user()->id;
        $audit->event = 'Exported Customers';
        $audit->save();
    }
}
