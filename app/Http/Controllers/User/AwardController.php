<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Award;
use App\Models\Employee;
use Carbon\Carbon;
use DataTables;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class AwardController extends Controller
{

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware(function ($request, $next) {

            if (package()->payroll_module != 1) {
                if (!$request->ajax()) {
                    return back()->with('error', _lang('Sorry, This module is not available in your current package !'));
                } else {
                    return response()->json(['result' => 'error', 'message' => _lang('Sorry, This module is not available in your current package !')]);
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
    public function index(Request $request)
    {
        $per_page = $request->get('per_page', 10);
        $search = $request->get('search', '');

        $query = Award::with('staff');

        // Apply search if provided
        if (!empty($search)) {
            $query->where(function ($q) use ($search) {
                $q->where('award_name', 'like', "%{$search}%")
                    ->orWhere('award', 'like', "%{$search}%")
                    ->orWhere('award_date', 'like', "%{$search}%")
                    ->orWhereHas('staff', function ($q) use ($search) {
                        $q->where('name', 'like', "%{$search}%");
                    });
            });
        }

        // Handle sorting
        $sorting = $request->get('sorting', []);
        $sortColumn = $sorting['column'] ?? 'id';
        $sortDirection = $sorting['direction'] ?? 'desc';

        if ($sortColumn === 'staff.name') {
            $query->join('employees', 'awards.employee_id', '=', 'employees.id')
                ->orderBy('employees.name', $sortDirection)
                ->select('awards.*');
        } else {
            $query->orderBy($sortColumn, $sortDirection);
        }

        // Get awards with pagination
        $awards = $query->paginate($per_page);
        $employees = Employee::select('id', 'name')->get();

        // Return Inertia view
        return Inertia::render('Backend/User/Award/List', [
            'awards' => $awards->items(),
            'employees' => $employees,
            'meta' => [
                'current_page' => $awards->currentPage(),
                'per_page' => $awards->perPage(),
                'from' => $awards->firstItem(),
                'to' => $awards->lastItem(),
                'total' => $awards->total(),
                'last_page' => $awards->lastPage(),
            ],
            'filters' => [
                'search' => $search,
                'columnFilters' => $request->get('columnFilters', []),
                'sorting' => $sorting,
            ],
            'trashed_awards' => Award::onlyTrashed()->count(),
        ]);
    }

    /**
     * Display a listing of trashed awards.
     *
     * @return \Illuminate\Http\Response
     */
    public function trash(Request $request)
    {
        $per_page = $request->get('per_page', 10);
        $search = $request->get('search', '');
        $sorting = $request->get('sorting', ['column' => 'id', 'direction' => 'desc']);

        $query = Award::onlyTrashed()->with('staff');

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('award_name', 'like', "%$search%")
                    ->orWhere('award', 'like', "%$search%")
                    ->orWhere('award_date', 'like', "%$search%")
                    ->orWhereHas('staff', function ($q) use ($search) {
                        $q->where('name', 'like', "%$search%");
                    });
            });
        }

        // Handle sorting
        if (isset($sorting['column']) && isset($sorting['direction'])) {
            $column = $sorting['column'];
            $direction = $sorting['direction'];
            
            if ($column === 'staff.name') {
                $query->join('employees', 'awards.employee_id', '=', 'employees.id')
                    ->orderBy('employees.name', $direction)
                    ->select('awards.*');
            } else {
                $query->orderBy($column, $direction);
            }
        }

        $awards = $query->paginate($per_page);

        return Inertia::render('Backend/User/Award/Trash', [
            'awards' => $awards->items(),
            'meta' => [
                'current_page' => $awards->currentPage(),
                'per_page' => $awards->perPage(),
                'from' => $awards->firstItem(),
                'to' => $awards->lastItem(),
                'total' => $awards->total(),
                'last_page' => $awards->lastPage(),
            ],
            'filters' => [
                'search' => $search,
                'sorting' => $sorting,
            ],
        ]);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'employee_id' => 'required',
            'award_date'  => 'required',
            'award_name'  => 'required',
            'award'       => 'required',
        ]);

        $award              = new Award();
        $award->employee_id = $request->input('employee_id');
        $award->award_date  = Carbon::parse($request->input('award_date'))->format('Y-m-d');
        $award->award_name  = $request->input('award_name');
        $award->award       = $request->input('award');
        $award->details     = $request->input('details');
        $award->user_id     = Auth::id();
        $award->business_id = $request->activeBusiness->id;

        $award->save();

        // Get employee name for audit log
        $employeeName = Employee::find($award->employee_id)->name ?? 'Unknown Employee';

        // audit log
        $audit = new AuditLog();
        $audit->date_changed = date('Y-m-d H:i:s');
        $audit->changed_by = Auth::id();
        $audit->event = 'Created Award for ' . $employeeName;
        $audit->save();

        return redirect()->route('awards.index')->with('success', _lang('Saved Successfully'));
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show(Request $request, $id)
    {
        $award = Award::with('staff')->find($id);

        if ($request->wantsJson() || $request->header('X-Inertia')) {
            return Inertia::render('Backend/User/Award/View', [
                'award' => $award
            ]);
        }

        if (!$request->ajax()) {
            return back();
        } else {
            return view('backend.user.award.modal.view', compact('award', 'id'));
        }
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */

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
            'employee_id' => 'required',
            'award_date'  => 'required',
            'award_name'  => 'required',
            'award'       => 'required',
        ]);

        if ($validator->fails()) {
            if ($request->ajax()) {
                return response()->json(['result' => 'error', 'message' => $validator->errors()->all()]);
            } else {
                return redirect()->route('awards.edit', $id)
                    ->withErrors($validator)
                    ->withInput();
            }
        }

        $award              = Award::find($id);
        $award->employee_id = $request->input('employee_id');
        $award->award_date  = Carbon::parse($request->input('award_date'))->format('Y-m-d');
        $award->award_name  = $request->input('award_name');
        $award->award       = $request->input('award');
        $award->details     = $request->input('details');

        $award->save();

        // Get employee name for audit log
        $employeeName = Employee::find($award->employee_id)->name ?? 'Unknown Employee';

        // audit log
        $audit = new AuditLog();
        $audit->date_changed = date('Y-m-d H:i:s');
        $audit->changed_by = Auth::id();
        $audit->event = 'Updated Award for ' . $employeeName;
        $audit->save();

        return redirect()->route('awards.index')->with('success', _lang('Updated Successfully'));
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $award = Award::find($id);

        // Get employee name for audit log
        $employeeName = Employee::find($award->employee_id)->name ?? 'Unknown Employee';

        // audit log
        $audit = new AuditLog();
        $audit->date_changed = date('Y-m-d H:i:s');
        $audit->changed_by = Auth::id();
        $audit->event = 'Deleted Award for ' . $employeeName;
        $audit->save();

        try {
            $award->delete();
            return redirect()->route('awards.index')->with('success', _lang('Deleted Successfully'));
        } catch (\Exception $e) {
            return redirect()->route('awards.index')->with('error', _lang('This item is already exists in other entity'));
        }
    }

    /**
     * Bulk delete selected awards
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function bulk_destroy(Request $request)
    {
        $ids = $request->ids;
        $awards = Award::whereIn('id', $ids)->get();
        
        foreach($awards as $award) {
            // Get employee name for audit log
            $employeeName = Employee::find($award->employee_id)->name ?? 'Unknown Employee';
            
            // audit log
            $audit = new AuditLog();
            $audit->date_changed = date('Y-m-d H:i:s');
            $audit->changed_by = Auth::id();
            $audit->event = 'Deleted Award for ' . $employeeName;
            $audit->save();

            try {
                $award->delete();
            } catch (\Exception $e) {
                // Continue with the next award
            }
        }
        
        return back()->with('success', 'Selected awards deleted successfully');
    }

    /**
     * Restore the specified award from trash.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function restore(Request $request, $id)
    {
        $award = Award::onlyTrashed()->findOrFail($id);

        // Get employee name for audit log
        $employeeName = Employee::find($award->employee_id)->name ?? 'Unknown Employee';

        // audit log
        $audit = new AuditLog();
        $audit->date_changed = date('Y-m-d H:i:s');
        $audit->changed_by = Auth::id();
        $audit->event = 'Restored Award for ' . $employeeName;
        $audit->save();

        $award->restore();

        return redirect()->route('awards.trash')->with('success', _lang('Restored Successfully'));
    }

    /**
     * Bulk restore selected awards from trash.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function bulk_restore(Request $request)
    {
        foreach ($request->ids as $id) {
            $award = Award::onlyTrashed()->findOrFail($id);

            // Get employee name for audit log
            $employeeName = Employee::find($award->employee_id)->name ?? 'Unknown Employee';

            // audit log
            $audit = new AuditLog();
            $audit->date_changed = date('Y-m-d H:i:s');
            $audit->changed_by = Auth::id();
            $audit->event = 'Restored Award for ' . $employeeName;
            $audit->save();

            $award->restore();
        }

        return redirect()->route('awards.trash')->with('success', _lang('Restored Successfully'));
    }

    /**
     * Permanently delete the specified award from trash.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function permanent_destroy(Request $request, $id)
    {
        $award = Award::onlyTrashed()->findOrFail($id);

        // Get employee name for audit log
        $employeeName = Employee::find($award->employee_id)->name ?? 'Unknown Employee';

        // audit log
        $audit = new AuditLog();
        $audit->date_changed = date('Y-m-d H:i:s');
        $audit->changed_by = Auth::id();
        $audit->event = 'Permanently Deleted Award for ' . $employeeName;
        $audit->save();

        $award->forceDelete();

        return redirect()->route('awards.trash')->with('success', _lang('Permanently Deleted Successfully'));
    }

    /**
     * Bulk permanently delete selected awards from trash.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function bulk_permanent_destroy(Request $request)
    {
        foreach ($request->ids as $id) {
            $award = Award::onlyTrashed()->findOrFail($id);

            // Get employee name for audit log
            $employeeName = Employee::find($award->employee_id)->name ?? 'Unknown Employee';

            // audit log
            $audit = new AuditLog();
            $audit->date_changed = date('Y-m-d H:i:s');
            $audit->changed_by = Auth::id();
            $audit->event = 'Permanently Deleted Award for ' . $employeeName;
            $audit->save();

            $award->forceDelete();
        }

        return redirect()->route('awards.trash')->with('success', _lang('Permanently Deleted Successfully'));
    }
}
