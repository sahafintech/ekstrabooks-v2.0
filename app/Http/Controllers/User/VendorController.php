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
        Gate::authorize('vendors.create');
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
        Gate::authorize('vendors.update');
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

    public function import()
    {
        Gate::authorize('vendors.csv.import');
        
        return Inertia::render('Backend/User/Vendor/Import');
    }

    public function uploadImportFile(Request $request)
    {
        Gate::authorize('vendors.csv.import');
        
        // If this is a GET request (page refresh), redirect to step 1
        if ($request->isMethod('get')) {
            return redirect()->route('vendors.import.page');
        }
        
        $request->validate([
            'file' => 'required|file|mimes:xlsx,xls,csv|max:10240',
        ]);

        try {
            // Create a unique temporary directory
            $sessionId = session()->getId();
            $tempDir = storage_path("app/imports/temp/{$sessionId}");

            // Ensure directory exists with proper permissions
            if (!is_dir($tempDir)) {
                mkdir($tempDir, 0755, true);
            }

            // Generate unique filename
            $fileName = uniqid() . '_' . $request->file('file')->getClientOriginalName();
            $fullPath = $tempDir . '/' . $fileName;

            // Move uploaded file
            $request->file('file')->move($tempDir, $fileName);

            // Verify file exists
            if (!file_exists($fullPath)) {
                throw new \Exception('Failed to store uploaded file');
            }

            // Store relative path for later use
            $relativePath = "imports/temp/{$sessionId}/{$fileName}";

            $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($fullPath);
            $worksheet = $spreadsheet->getActiveSheet();
            $headers = [];

            foreach ($worksheet->getRowIterator(1, 1) as $row) {
                $cellIterator = $row->getCellIterator();
                $cellIterator->setIterateOnlyExistingCells(false);

                foreach ($cellIterator as $cell) {
                    $value = $cell->getValue();
                    if ($value) {
                        $headers[] = (string) $value;
                    }
                }
            }

            // Store in session with explicit save
            session()->put('vendor_import_file_path', $relativePath);
            session()->put('vendor_import_full_path', $fullPath);
            session()->put('vendor_import_file_name', $request->file('file')->getClientOriginalName());
            session()->put('vendor_import_headers', $headers);
            session()->save();

            return Inertia::render('Backend/User/Vendor/Import', [
                'previewData' => [
                    'headers' => $headers,
                ],
            ]);
        } catch (\Exception $e) {
            return back()->with('error', 'Failed to process file: ' . $e->getMessage());
        }
    }

    public function previewImport(Request $request)
    {
        Gate::authorize('vendors.csv.import');
        
        // If this is a GET request (page refresh), redirect to step 1
        if ($request->isMethod('get')) {
            return redirect()->route('vendors.import.page');
        }
        
        $mappings = $request->input('mappings', []);
        $fullPath = session('vendor_import_full_path');
        $headers = session('vendor_import_headers', []);

        if (!$fullPath || !file_exists($fullPath)) {
            return back()->with('error', 'Import session expired or file not found. Please upload your file again.');
        }

        try {
            $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($fullPath);
            $worksheet = $spreadsheet->getActiveSheet();

            $headers = session('vendor_import_headers', []);
            $previewRecords = [];
            $validCount = 0;
            $errorCount = 0;
            $warningCount = 0;
            $totalRows = 0;

            // Process rows (skip header row)
            foreach ($worksheet->getRowIterator(2) as $row) {
                $rowIndex = $row->getRowIndex();
                $totalRows++;

                $cellIterator = $row->getCellIterator();
                $cellIterator->setIterateOnlyExistingCells(false);

                $rowData = [];
                $cellIndex = 0;

                foreach ($cellIterator as $cell) {
                    if ($cellIndex < count($headers)) {
                        $header = $headers[$cellIndex];
                        $systemField = $mappings[$header] ?? null;

                        if ($systemField && $systemField !== 'skip') {
                            $rowData[$systemField] = $cell->getValue();
                        }
                    }
                    $cellIndex++;
                }

                // Validate row
                $errors = [];

                // Name is required
                if (empty($rowData['name'])) {
                    $errors[] = 'Name is required';
                }

                // Check for duplicate email if provided
                if (!empty($rowData['email'])) {
                    $existingVendor = Vendor::where('email', $rowData['email'])->first();
                    if ($existingVendor && empty($rowData['id'])) {
                        // This is not an error, just a warning that it will update existing
                        $warningCount++;
                    }
                }

                // Check for ID-based updates
                if (!empty($rowData['id'])) {
                    $vendor = Vendor::find($rowData['id']);
                    if (!$vendor) {
                        $errors[] = 'Supplier with ID "' . $rowData['id'] . '" not found';
                    }
                }

                $status = count($errors) > 0 ? 'error' : 'valid';
                if ($status === 'error') {
                    $errorCount++;
                } else {
                    $validCount++;
                }

                // Only add rows with errors to preview_records
                if ($status === 'error') {
                    $previewRecords[] = [
                        'row' => $rowIndex,
                        'data' => $rowData,
                        'status' => $status,
                        'errors' => $errors,
                    ];
                }
            }

            session()->put('vendor_import_mappings', $mappings);
            session()->save();

            return Inertia::render('Backend/User/Vendor/Import', [
                'previewData' => [
                    'headers' => $headers,
                    'total_rows' => $totalRows,
                    'preview_records' => $previewRecords,
                    'valid_count' => $validCount,
                    'error_count' => $errorCount,
                    'warning_count' => $warningCount,
                ],
            ]);
        } catch (\Exception $e) {
            return back()->with('error', 'Failed to preview import: ' . $e->getMessage());
        }
    }

    public function executeImport(Request $request)
    {
        Gate::authorize('vendors.csv.import');
        
        // If this is a GET request (page refresh), redirect to step 1
        if ($request->isMethod('get')) {
            return redirect()->route('vendors.import.page');
        }
        
        $mappings = session('vendor_import_mappings', []);
        $fullPath = session('vendor_import_full_path');
        $headers = session('vendor_import_headers', []);

        if (!$fullPath || !file_exists($fullPath)) {
            return redirect()
                ->route('vendors.index')
                ->with('error', 'Import session expired or file not found. Please start over.');
        }

        try {
            Excel::import(new SupplierImport($mappings), $fullPath);

            // audit log
            $audit = new AuditLog();
            $audit->date_changed = date('Y-m-d H:i:s');
            $audit->changed_by = Auth::id();
            $audit->event = 'Suppliers Imported - ' . session('vendor_import_file_name');
            $audit->save();

            // Clean up
            if (file_exists($fullPath)) {
                unlink($fullPath);
            }
            session()->forget(['vendor_import_file_path', 'vendor_import_full_path', 'vendor_import_file_name', 'vendor_import_headers', 'vendor_import_mappings']);

            return redirect()
                ->route('vendors.index')
                ->with('success', 'Suppliers imported successfully.');
        } catch (\Exception $e) {
            return redirect()
                ->route('vendors.index')
                ->with('error', 'Import failed: ' . $e->getMessage());
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
        Gate::authorize('vendors.csv.export');
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
        Gate::authorize('vendors.delete');
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
