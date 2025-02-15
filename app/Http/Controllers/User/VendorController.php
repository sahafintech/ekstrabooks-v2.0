<?php

namespace App\Http\Controllers\User;

use App\Exports\SupplierExport;
use Validator;
use DataTables;
use App\Models\Vendor;
use App\Models\Purchase;
use App\Models\Transaction;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use App\Http\Controllers\Controller;
use App\Imports\SupplierImport;
use App\Models\AuditLog;
use Maatwebsite\Excel\Facades\Excel;

class VendorController extends Controller
{

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $vendors = Vendor::select('vendors.*')
            ->orderBy("vendors.id", "desc")
            ->get();
        return view('backend.user.vendor.list', compact('vendors'));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create(Request $request)
    {
        $alert_col = 'col-lg-10 offset-lg-1';
        if (!$request->ajax()) {
            return view('backend.user.vendor.create', compact('alert_col'));
        } else {
            return view('backend.user.vendor.modal.create');
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
                Rule::unique('vendors')->where(function ($query) use ($request) {
                    return $query->where('user_id', $request->activeBusiness->user_id)
                        ->where('business_id', $request->activeBusiness->id);
                }),
            ],
            'password'        => 'nullable|min:6',
        ]);

        if ($validator->fails()) {
            if ($request->ajax()) {
                return response()->json(['result' => 'error', 'message' => $validator->errors()->all()]);
            } else {
                return redirect()->route('vendors.create')
                    ->withErrors($validator)
                    ->withInput();
            }
        }

        $profile_picture = 'default.png';
        if ($request->hasfile('profile_picture')) {
            $file            = $request->file('profile_picture');
            $profile_picture = time() . $file->getClientOriginalName();
            $file->move(public_path() . "/uploads/profile/", $profile_picture);
        }

        $vendor                  = new Vendor();
        $vendor->name            = $request->input('name');
        $vendor->company_name    = $request->input('company_name');
        $vendor->email           = $request->input('email');
        $vendor->password        = $request->input('password');
        $vendor->registration_no = $request->input('registration_no');
        $vendor->vat_id          = $request->input('vat_id');
        $vendor->mobile          = $request->input('mobile');
        $vendor->country         = $request->input('country');
        $vendor->city            = $request->input('city');
        $vendor->contract_no     = $request->input('contract_no');
        $vendor->zip             = $request->input('zip');
        $vendor->address         = $request->input('address');

        $vendor->save();

        // audit log
        $audit = new AuditLog();
        $audit->date_changed = date('Y-m-d H:i:s');
        $audit->changed_by = auth()->user()->id;
        $audit->event = 'Supplier Created ' . $vendor->name;
        $audit->save();

        if (!$request->ajax()) {
            return redirect()->route('vendors.index')->with('success', _lang('Saved Successfully'));
        } else {
            return response()->json(['result' => 'success', 'action' => 'store', 'message' => _lang('Saved Successfully'), 'data' => $vendor, 'table' => '#vendors_table']);
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
        $data           = array();
        $data['alert_col'] = 'col-lg-10 offset-lg-1';
        $data['vendor'] = Vendor::find($id);

        if (!isset($_GET['tab'])) {
            $data['purchase'] = Purchase::selectRaw('COUNT(id) as total_bill, SUM(grand_total) as total_amount, sum(paid) as total_paid')
                ->where('vendor_id', $id)
                ->first();
        }

        if (isset($_GET['tab']) && $_GET['tab'] == 'purchases') {
            $data['purchases'] = Purchase::where('vendor_id', $id)
                ->orderBy('purchase_date', 'desc')
                ->paginate(15);
            $data['purchases']->withPath('?tab=' . $_GET['tab']);
        }

        if (isset($_GET['tab']) && $_GET['tab'] == 'transactions') {
            $data['transactions'] = Transaction::where('ref_id', '!=', NULL)
                ->where('vendor_id', $id)
                ->whereHas('account', function ($query) {
                    $query->where('account_type', '=', 'Cash')
                        ->orWhere('account_type', '=', 'Bank');
                })
                ->paginate(15);
            $data['transactions']->withPath('?tab=' . $_GET['tab']);
        }

        return view('backend.user.vendor.view', $data);
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
        $vendor    = Vendor::find($id);
        return view('backend.user.vendor.edit', compact('vendor', 'id', 'alert_col'));
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
                Rule::unique('vendors')->where(function ($query) use ($request) {
                    return $query->where('user_id', $request->activeBusiness->user_id)
                        ->where('business_id', $request->activeBusiness->id);
                })->ignore($id),
            ],
            'password'        => 'nullable|min:6',
        ]);

        if ($validator->fails()) {
            if ($request->ajax()) {
                return response()->json(['result' => 'error', 'message' => $validator->errors()->all()]);
            } else {
                return redirect()->route('vendors.edit', $id)
                    ->withErrors($validator)
                    ->withInput();
            }
        }

        if ($request->hasfile('profile_picture')) {
            $file            = $request->file('profile_picture');
            $profile_picture = time() . $file->getClientOriginalName();
            $file->move(public_path() . "/uploads/profile/", $profile_picture);
        }

        $vendor                  = Vendor::find($id);
        $vendor->name            = $request->input('name');
        $vendor->company_name    = $request->input('company_name');
        $vendor->email           = $request->input('email');
        $vendor->password        = $request->input('password');
        $vendor->registration_no = $request->input('registration_no');
        $vendor->vat_id          = $request->input('vat_id');
        $vendor->mobile          = $request->input('mobile');
        $vendor->country         = $request->input('country');
        $vendor->city            = $request->input('city');
        $vendor->zip             = $request->input('zip');
        $vendor->address         = $request->input('address');
        $vendor->save();

        // audit log
        $audit = new AuditLog();
        $audit->date_changed = date('Y-m-d H:i:s');
        $audit->changed_by = auth()->user()->id;
        $audit->event = 'Supplier Updated ' . $vendor->name;
        $audit->save();

        if (!$request->ajax()) {
            return redirect()->route('vendors.index')->with('success', _lang('Updated Successfully'));
        } else {
            return response()->json(['result' => 'success', 'action' => 'update', 'message' => _lang('Updated Successfully'), 'data' => $vendor, 'table' => '#vendors_table']);
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
        $vendor = Vendor::find($id);
        $vendor->delete();
        return redirect()->route('vendors.index')->with('success', _lang('Deleted Successfully'));
    }

    public function import_vendors(Request $request)
    {
        $request->validate([
            'vendors_file' => 'required|mimes:xls,xlsx',
        ]);

        try {
            Excel::import(new SupplierImport, $request->file('vendors_file'));
        } catch (\Exception $e) {
            return redirect()->route('vendors.index')->with('error', $e->getMessage());
        }

        // audit log
        $audit = new AuditLog();
        $audit->date_changed = date('Y-m-d H:i:s');
        $audit->changed_by = auth()->user()->id;
        $audit->event = 'Suppliers Imported';
        $audit->save();

        return redirect()->route('vendors.index')->with('success', _lang('Suppliers Imported'));
    }

    public function export_vendors()
    {
        // audit log
        $audit = new AuditLog();
        $audit->date_changed = date('Y-m-d H:i:s');
        $audit->changed_by = auth()->user()->id;
        $audit->event = 'Suppliers Exported';
        $audit->save();

        return Excel::download(new SupplierExport, 'suppliers export ' . now()->format('d m Y') . '.xlsx');
    }

    public function vendors_all(Request $request)
    {
        if ($request->vendors == null) {
            return redirect()->route('vendors.index')->with('error', _lang('Please Select a Supplier'));
        }

        $vendors = Vendor::whereIn('id', $request->vendors)->get();

        // audit log
        $audit = new AuditLog();
        $audit->date_changed = date('Y-m-d H:i:s');
        $audit->changed_by = auth()->user()->id;
        $audit->event = count($vendors) . 'Suppliers Deleted';
        $audit->save();

        foreach ($vendors as $vendor) {
            $vendor->delete();
        }

        return redirect()->route('vendors.index')->with('success', _lang('Deleted Successfully'));
    }
}
