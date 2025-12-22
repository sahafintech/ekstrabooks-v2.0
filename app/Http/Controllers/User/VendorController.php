<?php

namespace App\Http\Controllers\User;

use App\Exports\SupplierExport;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;
use App\Models\Vendor;
use App\Models\Purchase;
use App\Models\Transaction;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use App\Http\Controllers\Controller;
use App\Imports\SupplierImport;
use App\Models\AuditLog;
use Maatwebsite\Excel\Facades\Excel;
use Inertia\Inertia;
use Illuminate\Support\Facades\Gate;

class VendorController extends Controller
{

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct() {}

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        Gate::authorize('vendors.view');
        $per_page = $request->get('per_page', 50);
        $search = $request->get('search', '');
        $sorting = $request->get('sorting', []);
        $sortColumn = $sorting['column'] ?? 'id';
        $sortDirection = $sorting['direction'] ?? 'desc';

        $query = Vendor::select('vendors.*')
            ->orderBy($sortColumn, $sortDirection);

        // Apply search if provided
        if (!empty($search)) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('company_name', 'like', "%{$search}%")
                    ->orWhere('mobile', 'like', "%{$search}%");
            });
        }

        // Get vendors with pagination
        $vendors = $query->paginate($per_page)->withQueryString();

        // Return Inertia view
        return Inertia::render('Backend/User/Vendor/List', [
            'vendors' => $vendors->items(),
            'meta' => [
                'current_page' => $vendors->currentPage(),
                'from' => $vendors->firstItem(),
                'last_page' => $vendors->lastPage(),
                'links' => $vendors->linkCollection(),
                'path' => $vendors->path(),
                'per_page' => $vendors->perPage(),
                'to' => $vendors->lastItem(),
                'total' => $vendors->total(),
            ],
            'filters' => [
                'search' => $search,
                'columnFilters' => $request->get('columnFilters', []),
                'sorting' => $request->get('sorting', []),
            ],
            'trashed_vendors' => Vendor::onlyTrashed()->count(),
        ]);
    }

    public function trash(Request $request)
    {
        Gate::authorize('vendors.view');
        $per_page = $request->get('per_page', 50);
        $search = $request->get('search', '');
        $sorting = $request->get('sorting', []);
        $sortColumn = $sorting['column'] ?? 'id';
        $sortDirection = $sorting['direction'] ?? 'desc';

        $query = Vendor::onlyTrashed()->select('vendors.*')
            ->orderBy($sortColumn, $sortDirection);

        // Apply search if provided
        if (!empty($search)) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('company_name', 'like', "%{$search}%")
                    ->orWhere('mobile', 'like', "%{$search}%");
            });
        }

        // Get vendors with pagination
        $vendors = $query->paginate($per_page)->withQueryString();

        // Return Inertia view
        return Inertia::render('Backend/User/Vendor/Trash', [
            'vendors' => $vendors->items(),
            'meta' => [
                'current_page' => $vendors->currentPage(),
                'from' => $vendors->firstItem(),
                'last_page' => $vendors->lastPage(),
                'links' => $vendors->linkCollection(),
                'path' => $vendors->path(),
                'per_page' => $vendors->perPage(),
                'to' => $vendors->lastItem(),
                'total' => $vendors->total(),
            ],
            'filters' => [
                'search' => $search,
                'columnFilters' => $request->get('columnFilters', []),
                'sorting' => $request->get('sorting', []),
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
        if(!Gate::allows('vendors.create')) {
            return back()->with('error', _lang('You are not authorized to access this page'));
        }
        return Inertia::render('Backend/User/Vendor/Create');
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        Gate::authorize('vendors.create');
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
            return redirect()->route('vendors.create')
                ->withErrors($validator)
                ->withInput();
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
        $audit->changed_by = Auth::id();
        $audit->event = 'Supplier Created ' . $vendor->name;
        $audit->save();

        return redirect()->route('vendors.index')->with('success', _lang('Saved Successfully'));
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show(Request $request, $id)
    {
        Gate::authorize('vendors.view');
        $vendor = Vendor::find($id);
        $data = ['vendor' => $vendor];

        if (!isset($request->tab) || $request->tab == 'overview') {
            $data['purchase'] = Purchase::selectRaw('COUNT(id) as total_bill, SUM(grand_total) as total_amount, sum(paid) as total_paid')
                ->where('vendor_id', $id)
                ->first();

            // Add recent purchases for overview
            $data['purchases'] = Purchase::where('vendor_id', $id)
                ->orderBy('purchase_date', 'desc')
                ->take(5)
                ->get();

            // Add recent transactions for overview
            $data['transactions'] = Transaction::where('ref_id', '!=', NULL)
                ->where('vendor_id', $id)
                ->whereHas('account', function ($query) {
                    $query->where('account_type', '=', 'Cash')
                        ->orWhere('account_type', '=', 'Bank');
                })
                ->orderBy('trans_date', 'desc')
                ->take(5)
                ->get();
        }

        if (isset($request->tab) && $request->tab == 'purchases') {
            $data['purchases'] = Purchase::where('vendor_id', $id)
                ->orderBy('purchase_date', 'desc')
                ->get();
        }

        if (isset($request->tab) && $request->tab == 'transactions') {
            $data['transactions'] = Transaction::where('ref_id', '!=', NULL)
                ->where('vendor_id', $id)
                ->whereHas('account', function ($query) {
                    $query->where('account_type', '=', 'Cash')
                        ->orWhere('account_type', '=', 'Bank');
                })
                ->orderBy('trans_date', 'desc')
                ->get();
        }

        $data['activeTab'] = $request->tab ?? 'overview';

        return Inertia::render('Backend/User/Vendor/View', $data);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit(Request $request, $id)
    {
        if(!Gate::allows('vendors.update')) {
            return back()->with('error', _lang('You are not authorized to access this page'));
        }
        $vendor = Vendor::find($id);
        return Inertia::render('Backend/User/Vendor/Edit', [
            'vendor' => $vendor
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
        Gate::authorize('vendors.update');
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
            return redirect()->route('vendors.edit', $id)
                ->withErrors($validator)
                ->withInput();
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
        $audit->changed_by = Auth::id();
        $audit->event = 'Supplier Updated ' . $vendor->name;
        $audit->save();

        return redirect()->route('vendors.index')->with('success', _lang('Updated Successfully'));
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        Gate::authorize('vendors.delete');
        $vendor = Vendor::find($id);
        $vendor->delete();

        // audit log
        $audit = new AuditLog();
        $audit->date_changed = date('Y-m-d H:i:s');
        $audit->changed_by = Auth::id();
        $audit->event = 'Supplier Deleted ' . $vendor->name;
        $audit->save();

        return redirect()->route('vendors.index')->with('success', _lang('Deleted Successfully'));
    }

    public function permanent_destroy($id)
    {
        Gate::authorize('vendors.delete');
        $vendor = Vendor::onlyTrashed()->find($id);
        $vendor->forceDelete();

        // audit log
        $audit = new AuditLog();
        $audit->date_changed = date('Y-m-d H:i:s');
        $audit->changed_by = Auth::id();
        $audit->event = 'Supplier Permanently Deleted ' . $vendor->name;
        $audit->save();

        return redirect()->route('vendors.trash')->with('success', _lang('Permanently Deleted Successfully'));
    }

    public function restore($id)
    {
        Gate::authorize('vendors.restore');
        $vendor = Vendor::onlyTrashed()->find($id);
        $vendor->restore();

        // audit log
        $audit = new AuditLog();
        $audit->date_changed = date('Y-m-d H:i:s');
        $audit->changed_by = Auth::id();
        $audit->event = 'Supplier Restored ' . $vendor->name;
        $audit->save();

        return redirect()->route('vendors.trash')->with('success', _lang('Restored Successfully'));
    }

    /**
     * Store Bulk Actions.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */

    public function import_vendors(Request $request)
    {
        Gate::authorize('vendors.import');
        if ($request->hasFile('vendors_file')) {
            try {
                Excel::import(new SupplierImport, $request->file('vendors_file'));

                // audit log
                $audit = new AuditLog();
                $audit->date_changed = date('Y-m-d H:i:s');
                $audit->changed_by = Auth::id();
                $audit->event = 'Suppliers Imported';
                $audit->save();

                return back()->with('success', _lang('Imported Successfully'));
            } catch (\Exception $e) {
                return back()->with('error', _lang('Error: ' . $e->getMessage()));
            } catch (\Exception $e) {
                return back()->with('error', _lang('Error: ' . $e->getMessage()));
            }
        }else {
            return redirect()->route('vendors.index')->with('error', _lang('Please choose a file'));
        }
    }

    /**
     * Export Customers
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function export_vendors($type)
    {
        Gate::authorize('vendors.export');
        $filename = date('Y-m-d') . '_vendors.' . $type;

        // audit log
        $audit = new AuditLog();
        $audit->date_changed = date('Y-m-d H:i:s');
        $audit->changed_by = auth()->user()->id;
        $audit->event = 'Suppliers Exported';
        $audit->save();

        return Excel::download(new SupplierExport, $filename);
    }

    public function bulk_destroy(Request $request)
    {
        if(!Gate::allows('vendors.delete')) {
            return back()->with('error', _lang('You are not authorized to access this page'));
        }
        $vendors = Vendor::whereIn('id', $request->ids)->get();

        // audit log
        $audit = new AuditLog();
        $audit->date_changed = date('Y-m-d H:i:s');
        $audit->changed_by = Auth::id();
        $audit->event = count($vendors) . 'Suppliers Deleted';
        $audit->save();

        foreach ($vendors as $vendor) {
            $vendor->delete();
        }

        return redirect()->route('vendors.index')->with('success', _lang('Deleted Successfully'));
    }

    public function bulk_permanent_destroy(Request $request)
    {
        Gate::authorize('vendors.delete');
        $vendors = Vendor::onlyTrashed()->whereIn('id', $request->ids)->get();

        // audit log
        $audit = new AuditLog();
        $audit->date_changed = date('Y-m-d H:i:s');
        $audit->changed_by = Auth::id();
        $audit->event = count($vendors) . 'Suppliers Permanently Deleted';
        $audit->save();

        foreach ($vendors as $vendor) {
            $vendor->forceDelete();
        }

        return redirect()->route('vendors.trash')->with('success', _lang('Permanently Deleted Successfully'));
    }

    public function bulk_restore(Request $request)
    {
        Gate::authorize('vendors.restore');
        $vendors = Vendor::onlyTrashed()->whereIn('id', $request->ids)->get();

        // audit log
        $audit = new AuditLog();
        $audit->date_changed = date('Y-m-d H:i:s');
        $audit->changed_by = Auth::id();
        $audit->event = count($vendors) . 'Suppliers Restored';
        $audit->save();

        foreach ($vendors as $vendor) {
            $vendor->restore();
        }

        return redirect()->route('vendors.trash')->with('success', _lang('Restored Successfully'));
    }
}
