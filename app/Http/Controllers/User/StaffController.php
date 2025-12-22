<?php

namespace App\Http\Controllers\User;

use App\Exports\StaffsExport;
use App\Http\Controllers\Controller;
use App\Imports\StaffImport;
use App\Models\AuditLog;
use App\Models\Employee;
use App\Models\EmployeeDepartmentHistory;
use App\Models\Department;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Gate;

class StaffController extends Controller
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
        Gate::authorize('staffs.view');
        $per_page = $request->get('per_page', 50);
        $search = $request->get('search', '');
        $sorting = $request->get('sorting', ['column' => 'id', 'direction' => 'desc']);

        $query = Employee::with('department', 'designation')
            ->where('employees.business_id', $request->activeBusiness->id);

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('employees.name', 'like', "%$search%")
                    ->orWhere('employees.employee_id', 'like', "%$search%")
                    ->orWhere('employees.email', 'like', "%$search%")
                    ->orWhere('employees.phone', 'like', "%$search%");
            });
        }

        // Handle sorting
        if (isset($sorting['column']) && isset($sorting['direction'])) {
            $column = $sorting['column'];
            $direction = $sorting['direction'];

            // Handle relationship sorting
            if (str_contains($column, '.')) {
                [$relation, $field] = explode('.', $column);
                $query->join($relation . 's', 'employees.' . $relation . '_id', '=', $relation . 's.id')
                    ->where($relation . 's.business_id', $request->activeBusiness->id)
                    ->orderBy($relation . 's.' . $field, $direction)
                    ->select('employees.*');
            } else {
                $query->orderBy('employees.' . $column, $direction);
            }
        }

        $employees = $query->paginate($per_page);

        return Inertia::render('Backend/User/Staff/List', [
            'employees' => $employees->items(),
            'meta' => [
                'current_page' => $employees->currentPage(),
                'per_page' => $employees->perPage(),
                'from' => $employees->firstItem(),
                'to' => $employees->lastItem(),
                'total' => $employees->total(),
                'last_page' => $employees->lastPage(),
            ],
            'filters' => [
                'search' => $search,
                'sorting' => $sorting,
            ],
            'trashed_staffs' => Employee::onlyTrashed()->count(),
        ]);
    }

    public function trash(Request $request)
    {
        Gate::authorize('staffs.view');
        $per_page = $request->get('per_page', 50);
        $search = $request->get('search', '');
        $sorting = $request->get('sorting', ['column' => 'id', 'direction' => 'desc']);

        $query = Employee::onlyTrashed()->with('department', 'designation')
            ->where('employees.business_id', $request->activeBusiness->id);

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('employees.name', 'like', "%$search%")
                    ->orWhere('employees.employee_id', 'like', "%$search%")
                    ->orWhere('employees.email', 'like', "%$search%")
                    ->orWhere('employees.phone', 'like', "%$search%");
            });
        }

        // Handle sorting
        if (isset($sorting['column']) && isset($sorting['direction'])) {
            $column = $sorting['column'];
            $direction = $sorting['direction'];

            // Handle relationship sorting
            if (str_contains($column, '.')) {
                [$relation, $field] = explode('.', $column);
                $query->join($relation . 's', 'employees.' . $relation . '_id', '=', $relation . 's.id')
                    ->where($relation . 's.business_id', $request->activeBusiness->id)
                    ->orderBy($relation . 's.' . $field, $direction)
                    ->select('employees.*');
            } else {
                $query->orderBy('employees.' . $column, $direction);
            }
        }

        $employees = $query->paginate($per_page);

        return Inertia::render('Backend/User/Staff/Trash', [
            'employees' => $employees->items(),
            'meta' => [
                'current_page' => $employees->currentPage(),
                'per_page' => $employees->perPage(),
                'from' => $employees->firstItem(),
                'to' => $employees->lastItem(),
                'total' => $employees->total(),
                'last_page' => $employees->lastPage(),
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
    public function create()
    {
        Gate::authorize('staffs.create');
        $departments = Department::with('designations')->get();

        return Inertia::render('Backend/User/Staff/Create', [
            'departments' => $departments,
        ]);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        Gate::authorize('staffs.create');
        $validator = Validator::make($request->all(), [
            'employee_id'     => [
                'required',
                Rule::unique('employees')->where(function ($query) use ($request) {
                    return $query->where('business_id', $request->activeBusiness->id);
                }),
            ],
            'name'            => 'required|max:50',
            'date_of_birth'   => 'nullable',
            'email'           => [
                'nullable',
                'email',
                Rule::unique('employees')->where(function ($query) use ($request) {
                    return $query->where('business_id', $request->activeBusiness->id);
                }),
            ],
            'phone'           => 'nullable|max:30',
            'department_id'   => 'required',
            'designation_id'  => 'required',
            'joining_date'    => 'required',
            'basic_salary'    => 'required',
            'working_hours'   => 'required',
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        DB::beginTransaction();

        $employee                  = new Employee();
        $employee->business_id     = $request->activeBusiness->id;
        $employee->employee_id     = $request->input('employee_id');
        $employee->name            = $request->input('name');
        $employee->date_of_birth   = $request->input('date_of_birth') ? Carbon::parse($request->input('date_of_birth'))->format('Y-m-d') : null;
        $employee->email           = $request->input('email');
        $employee->phone           = $request->input('phone');
        $employee->city            = $request->input('city');
        $employee->country         = $request->input('country');
        $employee->department_id   = $request->input('department_id');
        $employee->designation_id  = $request->input('designation_id');
        $employee->basic_salary    = $request->input('basic_salary');
        $employee->working_hours   = $request->input('working_hours');
        $employee->time_sheet_based = $request->input('time_sheet_based');
        $employee->max_overtime_hours = $request->input('max_overtime_hours');
        $employee->joining_date    = Carbon::parse($request->input('joining_date'))->format('Y-m-d');
        $employee->end_date        = $request->input('end_date') ? Carbon::parse($request->input('end_date'))->format('Y-m-d') : null;
        $employee->bank_name       = $request->input('bank_name');
        $employee->branch_name     = $request->input('branch_name');
        $employee->account_name    = $request->input('account_name');
        $employee->account_number  = $request->input('account_number');
        $employee->save();

        //Update Employee History
        $history              = new EmployeeDepartmentHistory();
        $history->employee_id = $employee->id;
        $history->details     = json_encode(array(
            'employee_id'  => $employee->employee_id,
            'department'   => $employee->department,
            'designation'  => $employee->designation,
            'joining_date' => $employee->joining_date,
            'end_date'     => $employee->end_date,
        ));
        $history->save();

        DB::commit();

        // audit log
        $audit = new AuditLog();
        $audit->date_changed = date('Y-m-d H:i:s');
        $audit->changed_by = Auth::id();
        $audit->event = 'Created New Staff ' . $employee->name;
        $audit->save();

        return redirect()->route('staffs.index')->with('success', _lang('Saved Successfully'));
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show(Request $request, $id)
    {
        $employee = Employee::with('department', 'designation', 'department_history')
            ->where('business_id', $request->activeBusiness->id)
            ->findOrFail($id);

        return Inertia::render('Backend/User/Staff/View', [
            'employee' => $employee
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit(Request $request, $id)
    {
        $employee = Employee::findOrFail($id);

        $departments = Department::with('designations')->get();

        return Inertia::render('Backend/User/Staff/Edit', [
            'employee' => $employee,
            'departments' => $departments,
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
        Gate::authorize('staffs.update');
        $validator = Validator::make($request->all(), [
            'name'                   => 'required|max:50',
            'date_of_birth'          => 'nullable',
            'email'                  => [
                'nullable',
                'email',
                Rule::unique('employees')->where(function ($query) use ($request, $id) {
                    return $query->where('business_id', $request->activeBusiness->id)
                        ->where('id', '!=', $id);
                }),
            ],
            'phone'                  => 'nullable|max:30',
            'update_company_details' => 'required',
            'employee_id'            => [
                'required_if:update_company_details,1',
                Rule::unique('employees')->where(function ($query) use ($request, $id) {
                    return $query->where('business_id', $request->activeBusiness->id)
                        ->where('id', '!=', $id);
                }),
            ],
            'department_id'          => 'required_if:update_company_details,1',
            'designation_id'         => 'required_if:update_company_details,1',
            'basic_salary'           => 'required_if:update_company_details,1',
            'joining_date'           => 'required_if:update_company_details,1',
            'working_hours'          => 'required_if:update_company_details,1',
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        DB::beginTransaction();
        $employee = Employee::where('business_id', $request->activeBusiness->id)
            ->findOrFail($id);

        $employee->name          = $request->input('name');
        $employee->date_of_birth = $request->input('date_of_birth') ? Carbon::parse($request->input('date_of_birth'))->format('Y-m-d') : null;
        $employee->email         = $request->input('email');
        $employee->phone         = $request->input('phone');
        $employee->city          = $request->input('city');
        $employee->country       = $request->input('country');
        $employee->working_hours = $request->input('working_hours');
        $employee->time_sheet_based = $request->input('time_sheet_based');
        $employee->max_overtime_hours = $request->input('max_overtime_hours');
        $employee->basic_salary  = $request->input('basic_salary');

        if ($request->update_company_details == 1) {
            $employee->employee_id     = $request->input('employee_id');
            $employee->department_id   = $request->input('department_id');
            $employee->designation_id  = $request->input('designation_id');
            $employee->joining_date    = Carbon::parse($request->input('joining_date'))->format('Y-m-d');
            $employee->end_date        = $request->input('end_date') ? Carbon::parse($request->input('end_date'))->format('Y-m-d') : null;
            //Update Employee History
            $history              = new EmployeeDepartmentHistory();
            $history->employee_id = $employee->id;
            $history->details     = json_encode(array(
                'employee_id'  => $employee->employee_id,
                'department'   => $employee->department,
                'designation'  => $employee->designation,
                'joining_date' => Carbon::parse($request->input('joining_date'))->format('Y-m-d'),
                'end_date'     => $request->input('end_date') ? Carbon::parse($request->input('end_date'))->format('Y-m-d') : null,
            ));
            $history->save();
        }
        $employee->bank_name      = $request->input('bank_name');
        $employee->branch_name    = $request->input('branch_name');
        $employee->account_name   = $request->input('account_name');
        $employee->account_number = $request->input('account_number');
        $employee->save();

        DB::commit();

        // audit log
        $audit = new AuditLog();
        $audit->date_changed = date('Y-m-d H:i:s');
        $audit->changed_by = Auth::id();
        $audit->event = 'Updated Staff ' . $employee->name;
        $audit->save();

        return redirect()->route('staffs.index')->with('success', _lang('Updated Successfully'));
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy(Request $request, $id)
    {
        Gate::authorize('staffs.delete');
        $employee = Employee::where('business_id', $request->activeBusiness->id)
            ->findOrFail($id);

        // audit log
        $audit = new AuditLog();
        $audit->date_changed = date('Y-m-d H:i:s');
        $audit->changed_by = Auth::id();
        $audit->event = 'Deleted Staff ' . $employee->name;
        $audit->save();

        $employee->delete();

        return redirect()->route('staffs.index')->with('success', _lang('Deleted Successfully'));
    }

    public function bulk_destroy(Request $request)
    {
        Gate::authorize('staffs.delete');
        foreach ($request->ids as $id) {
            $employee = Employee::where('business_id', $request->activeBusiness->id)
                ->findOrFail($id);

            // audit log
            $audit = new AuditLog();
            $audit->date_changed = date('Y-m-d H:i:s');
            $audit->changed_by = Auth::id();
            $audit->event = 'Deleted Staff ' . $employee->name;
            $audit->save();

            $employee->delete();
        }

        return redirect()->route('staffs.index')->with('success', _lang('Deleted Successfully'));
    }

    public function permanent_destroy(Request $request, $id)
    {
        Gate::authorize('staffs.delete');
        $employee = Employee::onlyTrashed()->where('business_id', $request->activeBusiness->id)
            ->findOrFail($id);

        // audit log
        $audit = new AuditLog();
        $audit->date_changed = date('Y-m-d H:i:s');
        $audit->changed_by = Auth::id();
        $audit->event = 'Permanently Deleted Staff ' . $employee->name;
        $audit->save();

        $employee->forceDelete();

        return redirect()->route('staffs.trash')->with('success', _lang('Permanently Deleted Successfully'));
    }

    public function bulk_permanent_destroy(Request $request)
    {
        Gate::authorize('staffs.delete');
        foreach ($request->ids as $id) {
            $employee = Employee::onlyTrashed()->where('business_id', $request->activeBusiness->id)
                ->findOrFail($id);

            // audit log
            $audit = new AuditLog();
            $audit->date_changed = date('Y-m-d H:i:s');
            $audit->changed_by = Auth::id();
            $audit->event = 'Permanently Deleted Staff ' . $employee->name;
            $audit->save();

            $employee->forceDelete();
        }

        return redirect()->route('staffs.trash')->with('success', _lang('Permanently Deleted Successfully'));
    }

    public function restore(Request $request, $id)
    {
        Gate::authorize('staffs.restore');
        $employee = Employee::onlyTrashed()->where('business_id', $request->activeBusiness->id)
            ->findOrFail($id);

        // audit log
        $audit = new AuditLog();
        $audit->date_changed = date('Y-m-d H:i:s');
        $audit->changed_by = Auth::id();
        $audit->event = 'Restored Staff ' . $employee->name;
        $audit->save();

        $employee->restore();

        return redirect()->route('staffs.trash')->with('success', _lang('Restored Successfully'));
    }

    public function bulk_restore(Request $request)
    {
        Gate::authorize('staffs.restore');
        foreach ($request->ids as $id) {
            $employee = Employee::onlyTrashed()->where('business_id', $request->activeBusiness->id)
                ->findOrFail($id);

            // audit log
            $audit = new AuditLog();
            $audit->date_changed = date('Y-m-d H:i:s');
            $audit->changed_by = Auth::id();
            $audit->event = 'Restored Staff ' . $employee->name;
            $audit->save();

            $employee->restore();
        }

        return redirect()->route('staffs.trash')->with('success', _lang('Restored Successfully'));
    }

    /**
     * Bulk delete selected staff members
     * 
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function bulkDelete(Request $request)
    {
        Gate::authorize('staffs.delete');
        $validator = Validator::make($request->all(), [
            'ids' => 'required|array',
            'ids.*' => 'required|integer',
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator);
        }

        $employees = Employee::whereIn('id', $request->ids)
            ->where('business_id', $request->activeBusiness->id)
            ->get();

        foreach ($employees as $employee) {
            // audit log
            $audit = new AuditLog();
            $audit->date_changed = date('Y-m-d H:i:s');
            $audit->changed_by = Auth::id();
            $audit->event = 'Deleted Staff ' . $employee->name;
            $audit->save();

            $employee->delete();
        }

        return redirect()->route('staffs.index')->with('success', _lang('Selected staff deleted successfully'));
    }

    public function import_staffs(Request $request)
    {
        Gate::authorize('staffs.import');
        $request->validate([
            'staffs_file' => 'required|mimes:xls,xlsx',
        ]);

        try {
            Excel::import(new StaffImport, $request->file('staffs_file'));
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }

        // audit log
        $audit = new AuditLog();
        $audit->date_changed = date('Y-m-d H:i:s');
        $audit->changed_by = Auth::id();
        $audit->event = 'Imported Staffs';
        $audit->save();

        return redirect()->route('staffs.index')->with('success', _lang('Staffs Imported'));
    }

    public function export_staffs()
    {
        return Excel::download(new StaffsExport, 'staffs ' . now()->format('d m Y') . '.xlsx');
    }

    public function change_status(Request $request, $id)
    {
        $employee = Employee::where('business_id', $request->activeBusiness->id)
            ->findOrFail($id);
        
        $employee->status = $request->status;
        $employee->save();

        // audit log
        $audit = new AuditLog();
        $audit->date_changed = date('Y-m-d H:i:s');
        $audit->changed_by = Auth::id();
        $audit->event = 'Changed Status for Staff ' . $employee->name . ' to ' . ($request->status == 1 ? 'Active' : 'Inactive');
        $audit->save();

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => _lang('Status Changed Successfully'),
                'status' => $employee->status
            ]);
        }

        return redirect()->route('staffs.index')->with('success', _lang('Status Changed Successfully'));
    }
}
