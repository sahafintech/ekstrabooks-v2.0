<?php

namespace App\Http\Controllers;

use App\Exports\CostCodesExport;
use App\Http\Controllers\Controller;
use App\Imports\CostCodeImport;
use App\Models\AuditLog;
use App\Models\CostCode;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Maatwebsite\Excel\Facades\Excel;

class CostCodeController extends Controller
{

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware(function ($request, $next) {

            if (package()->construction_module != 1) {
                return back()->with('error', _lang('Sorry, This module is not available in your current package !'));
            }

            return $next($request);
        });
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $per_page = $request->get('per_page', 50);
        $search = $request->get('search', '');

        $query = CostCode::query();

        // Apply search if provided
        if (!empty($search)) {
            $query->where(function ($q) use ($search) {
                $q->where('code', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
            });
        }

        // Handle sorting
        $sorting = $request->get('sorting', []);
        $sortColumn = $sorting['column'] ?? 'id';
        $sortDirection = $sorting['direction'] ?? 'desc';

        $query->orderBy($sortColumn, $sortDirection);

        // Get costcodes with pagination
        $cost_codes = $query->paginate($per_page)->withQueryString();

        // Return Inertia view
        return Inertia::render('Backend/User/Construction/CostCode/List', [
            'cost_codes' => $cost_codes->items(),
            'meta' => [
                'current_page' => $cost_codes->currentPage(),
                'from' => $cost_codes->firstItem(),
                'last_page' => $cost_codes->lastPage(),
                'links' => $cost_codes->linkCollection(),
                'path' => $cost_codes->path(),
                'per_page' => $cost_codes->perPage(),
                'to' => $cost_codes->lastItem(),
                'total' => $cost_codes->total(),
            ],
            'filters' => [
                'search' => $search,
                'columnFilters' => $request->get('columnFilters', []),
                'sorting' => $sorting,
            ],
        ]);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        return Inertia::render('Backend/User/Construction/CostCode/Create');
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
            'code'            => 'required|max:50',
            'description'     => 'nullable',
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        $cost_code                  = new CostCode();
        $cost_code->code           = $request->input('code');
        $cost_code->description    = $request->input('description');
        $cost_code->created_by     = Auth::id();
        $cost_code->save();

        // audit log
        $audit = new AuditLog();
        $audit->date_changed = date('Y-m-d H:i:s');
        $audit->changed_by = Auth::id();
        $audit->event = 'Created New Cost Code ' . $cost_code->code;
        $audit->save();

        return redirect()->route('cost_codes.index')->with('success', _lang('Saved Successfully'));
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        $cost_code = CostCode::findOrFail($id);

        return Inertia::render('Backend/User/Construction/CostCode/Edit', [
            'cost_code' => $cost_code,
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
        $validator = Validator::make($request->all(), [
            'code'            => 'required|max:50',
            'description'     => 'nullable',
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        $cost_code = CostCode::findOrFail($id);

        $cost_code->code          = $request->input('code');
        $cost_code->description   = $request->input('description');
        $cost_code->updated_by    = Auth::id();
        $cost_code->save();

        // audit log
        $audit = new AuditLog();
        $audit->date_changed = date('Y-m-d H:i:s');
        $audit->changed_by = Auth::id();
        $audit->event = 'Updated Cost Code ' . $cost_code->code;
        $audit->save();

        return redirect()->route('cost_codes.index')->with('success', _lang('Updated Successfully'));
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $cost_code = CostCode::findOrFail($id);
        $cost_code->deleted_by = Auth::id();
        $cost_code->save();

        // audit log
        $audit = new AuditLog();
        $audit->date_changed = date('Y-m-d H:i:s');
        $audit->changed_by = Auth::id();
        $audit->event = 'Deleted Cost Code ' . $cost_code->code;
        $audit->save();

        $cost_code->delete();

        return redirect()->route('cost_codes.index')->with('success', _lang('Deleted Successfully'));
    }

    public function bulk_destroy(Request $request)
    {
        foreach ($request->ids as $id) {
            $cost_code = CostCode::findOrFail($id);
            $cost_code->deleted_by = Auth::id();
            $cost_code->save();

            // audit log
            $audit = new AuditLog();
            $audit->date_changed = date('Y-m-d H:i:s');
            $audit->changed_by = Auth::id();
            $audit->event = 'Deleted Cost Code ' . $cost_code->code;
            $audit->save();

            $cost_code->delete();
        }

        return redirect()->route('cost_codes.index')->with('success', _lang('Deleted Successfully'));
    }

    /**
     * Bulk delete selected staff members
     * 
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function bulkDelete(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'ids' => 'required|array',
            'ids.*' => 'required|integer',
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator);
        }

        $cost_codes = CostCode::whereIn('id', $request->ids)
            ->get();

        foreach ($cost_codes as $cost_code) {
            // audit log
            $audit = new AuditLog();
            $audit->date_changed = date('Y-m-d H:i:s');
            $audit->changed_by = Auth::id();
            $audit->event = 'Deleted Cost Code ' . $cost_code->code;
            $audit->save();

            $cost_code->delete();
        }

        return redirect()->route('cost_codes.index')->with('success', _lang('Selected cost codes deleted successfully'));
    }

    public function import_cost_codes(Request $request)
    {
        $request->validate([
            'cost_codes_file' => 'required|mimes:xls,xlsx',
        ]);

        try {
            Excel::import(new CostCodeImport, $request->file('cost_codes_file'));
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }

        // audit log
        $audit = new AuditLog();
        $audit->date_changed = date('Y-m-d H:i:s');
        $audit->changed_by = Auth::id();
        $audit->event = 'Imported Cost Codes';
        $audit->save();

        return redirect()->route('cost_codes.index')->with('success', _lang('Cost Codes Imported'));
    }

    public function export_cost_codes()
    {
        return Excel::download(new CostCodesExport, 'cost_codes ' . now()->format('d m Y') . '.xlsx');
    }
}
