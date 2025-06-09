<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Employee;
use App\Models\Leave;
use Carbon\Carbon;
use DataTables;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Inertia\Inertia;

class LeaveController extends Controller
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
        $per_page = $request->get('per_page', 50);
        $search = $request->get('search', '');
        $date = $request->get('date', '');

        $query = Leave::select('leaves.*')->with('staff');

        // Apply search if provided
        if (!empty($search)) {
            $query->where(function ($q) use ($search) {
                $q->whereHas('staff', function ($q2) use ($search) {
                    $q2->where('name', 'like', "%{$search}%");
                })
                ->orWhere('leave_type', 'like', "%{$search}%")
                ->orWhere('description', 'like', "%{$search}%");
            });
        }

        // Apply date filter if provided
        if (!empty($date)) {
            $query->where(function ($q) use ($date) {
                $q->where('start_date', '<=', $date)
                    ->where('end_date', '>=', $date);
            });
        }

        // Handle column filters
        if ($request->has('columnFilters')) {
            $columnFilters = $request->get('columnFilters');
            if (is_string($columnFilters)) {
                $columnFilters = json_decode($columnFilters, true);
            }
            if (is_array($columnFilters)) {
                foreach ($columnFilters as $column => $value) {
                    if ($value !== null && $value !== '') {
                        if ($column === 'staff.name') {
                            $query->whereHas('staff', function ($q) use ($value) {
                                $q->where('name', $value);
                            });
                        } else if ($column === 'leave_type') {
                            $query->where('leave_type', $value);
                        } else if ($column === 'status') {
                            $query->where('status', $value);
                        }
                    }
                }
            }
        }

        // Handle sorting
        $sorting = $request->get('sorting', []);
        $sortColumn = $sorting['column'] ?? 'id';
        $sortDirection = $sorting['direction'] ?? 'desc';

        if ($sortColumn === 'staff.name') {
            $query->join('employees', 'leaves.employee_id', '=', 'employees.id')
                ->orderBy('employees.name', $sortDirection)
                ->select('leaves.*');
        } else {
            $query->orderBy('leaves.' . $sortColumn, $sortDirection);
        }

        // Get leaves with pagination
        $leaves = $query->paginate($per_page)->withQueryString();

        // Get staff for dropdown
        $staff = Employee::select('id', 'name')->get();

        // Return Inertia view
        return Inertia::render('Backend/User/Leave/List', [
            'leaves' => $leaves->items(),
            'meta' => [
                'current_page' => $leaves->currentPage(),
                'from' => $leaves->firstItem(),
                'last_page' => $leaves->lastPage(),
                'links' => $leaves->linkCollection(),
                'path' => $leaves->path(),
                'per_page' => $leaves->perPage(),
                'to' => $leaves->lastItem(),
                'total' => $leaves->total(),
            ],
            'filters' => [
                'search' => $search,
                'date' => $date,
                'columnFilters' => $request->get('columnFilters', []),
                'sorting' => $sorting,
            ],
            'staff' => $staff,
        ]);
    }

    /**
     * Show the form for creating a new leave record.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        // Get staff for dropdown
        $staff = Employee::select('id', 'name')->get();

        return Inertia::render('Backend/User/Leave/Create', [
            'staff' => $staff,
        ]);
    }

    /**
     * Store a newly created leave record in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'employee_id' => 'required',
            'leave_type' => 'required',
            'leave_duration' => 'required',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'total_days' => 'required|numeric',
            'status' => 'required',
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        $leave = new Leave();
        $leave->employee_id = $request->employee_id;
        $leave->leave_type = $request->leave_type;
        $leave->leave_duration = $request->leave_duration;
        $leave->start_date = $request->start_date;
        $leave->end_date = $request->end_date;
        $leave->total_days = $request->total_days;
        $leave->description = $request->description;
        $leave->status = $request->status;
        $leave->save();

        // audit log
        $audit = new AuditLog();
        $audit->date_changed = date('Y-m-d H:i:s');
        $audit->changed_by = auth()->user()->id;
        $audit->event = 'Created Leave ' . $leave->leave_type;
        $audit->save();

        return redirect()->route('leaves.index')->with('success', 'Leave created successfully.');
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show(Request $request, $id)
    {
        $leave = Leave::with('staff')->find($id);

        return Inertia::render('Backend/User/Leave/View', [
            'leave' => $leave
        ]);
    }

    /**
     * Show the form for editing the specified leave record.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        $leave = Leave::findOrFail($id);
        $staff = Employee::select('id', 'name')->get();

        return Inertia::render('Backend/User/Leave/Edit', [
            'leave' => $leave,
            'staff' => $staff,
        ]);
    }

    /**
     * Update the specified leave record in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'employee_id' => 'required',
            'leave_type' => 'required',
            'leave_duration' => 'required',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'total_days' => 'required|numeric',
            'status' => 'required',
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        $leave = Leave::findOrFail($id);

        // Save old values for audit log
        $oldValues = $leave->toArray();

        $leave->employee_id = $request->employee_id;
        $leave->leave_type = $request->leave_type;
        $leave->leave_duration = $request->leave_duration;
        $leave->start_date = $request->start_date;
        $leave->end_date = $request->end_date;
        $leave->total_days = $request->total_days;
        $leave->description = $request->description;
        $leave->status = $request->status;
        $leave->save();

        // audit log
        $audit = new AuditLog();
        $audit->date_changed = date('Y-m-d H:i:s');
        $audit->changed_by = auth()->user()->id;
        $audit->event = 'Updated Leave ' . $leave->leave_type;
        $audit->save();

        return redirect()->route('leaves.index')->with('success', 'Leave updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $leave = Leave::find($id);

        // audit log
        $audit = new AuditLog();
        $audit->date_changed = date('Y-m-d H:i:s');
        $audit->changed_by = Auth::id();
        $audit->event = 'Deleted Leave ' . $leave->leave_type;
        $audit->save();

        $leave->delete();
        return redirect()->route('leaves.index')->with('success', _lang('Deleted Successfully'));
    }

    /**
     * Bulk delete selected leaves
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function bulk_destroy(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'ids' => 'required|array',
            'ids.*' => 'exists:leaves,id',
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator);
        }

        // Get leave types for audit log
        $leaveTypes = Leave::whereIn('id', $request->ids)->pluck('leave_type')->implode(', ');

        // Delete the leaves
        Leave::whereIn('id', $request->ids)->delete();

        // Audit log
        $audit = new AuditLog();
        $audit->date_changed = date('Y-m-d H:i:s');
        $audit->changed_by = Auth::id();
        $audit->event = 'Bulk Deleted Leaves: ' . $leaveTypes;
        $audit->save();

        return redirect()->route('leaves.index')->with('success', _lang('Deleted Successfully'));
    }

    public function bulk_approve(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'ids' => 'required|array',
            'ids.*' => 'exists:leaves,id',
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator);
        }

        // Delete the leaves
        Leave::whereIn('id', $request->ids)->update(['status' => 1]);

        // Audit log
        $audit = new AuditLog();
        $audit->date_changed = date('Y-m-d H:i:s');
        $audit->changed_by = Auth::id();
        $audit->event = 'Bulk Approved Leaves: ' . count($request->ids) . ' leaves';
        $audit->save();

        return redirect()->route('leaves.index')->with('success', _lang('Approved Successfully'));
    }

    public function bulk_reject(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'ids' => 'required|array',
            'ids.*' => 'exists:leaves,id',
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator);
        }

        // Delete the leaves
        Leave::whereIn('id', $request->ids)->update(['status' => 2]);

        // Audit log
        $audit = new AuditLog();
        $audit->date_changed = date('Y-m-d H:i:s');
        $audit->changed_by = Auth::id();
        $audit->event = 'Bulk Rejected Leaves: ' . count($request->ids) . ' leaves';
        $audit->save();

        return redirect()->route('leaves.index')->with('success', _lang('Rejected Successfully'));
    }
}
