<?php

namespace App\Http\Controllers\User;

use App\Exports\StaffsExport;
use App\Http\Controllers\Controller;
use App\Imports\StaffImport;
use App\Models\AuditLog;
use App\Models\Employee;
use App\Models\EmployeeDepartmentHistory;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Maatwebsite\Excel\Facades\Excel;
use Validator;

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
    public function index()
    {
        $employees = Employee::with('department', 'designation')
            ->select('employees.*')
            ->get();

        return view('backend.user.staff.list', compact('employees'));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create(Request $request)
    {
        return view('backend.user.staff.create');
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
            'employee_id'     =>
            'required',
            Rule::unique('employees')->where(function ($query) use($request) {
                return $query->where('business_id', $request->activeBusiness->id);
            }),
            'name'            => 'required|max:50',
            'date_of_birth'   => 'nullable',
            'email'           =>
            'nullable|email|',
            Rule::unique('employees')->where(function ($query) use($request) {
                return $query->where('business_id', $request->activeBusiness->id);
            }),
            'phone'           => 'nullable|max:30',
            'department_id'   => 'required',
            'designation_id'  => 'required',
            'joining_date'    => 'required',
            'basic_salary'    => 'required',
        ]);

        if ($validator->fails()) {
            if ($request->ajax()) {
                return response()->json(['result' => 'error', 'message' => $validator->errors()->all()]);
            } else {
                return redirect()->route('staffs.create')
                    ->withErrors($validator)
                    ->withInput();
            }
        }

        DB::beginTransaction();

        $employee                  = new Employee();
        $employee->employee_id     = $request->input('employee_id');
        $employee->name            = $request->input('name');
        $employee->date_of_birth   = $request->input('date_of_birth')? Carbon::parse($request->input('date_of_birth'))->format('Y-m-d') : null;
        $employee->email           = $request->input('email');
        $employee->phone           = $request->input('phone');
        $employee->city            = $request->input('city');
        $employee->country         = $request->input('country');
        $employee->department_id   = $request->input('department_id');
        $employee->designation_id  = $request->input('designation_id');
        $employee->basic_salary    = $request->input('basic_salary');
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
        $audit->changed_by = auth()->user()->id;
        $audit->event = 'Created New Staff '.$employee->name;
        $audit->save();

        if (!$request->ajax()) {
            return redirect()->route('staffs.index')->with('success', _lang('Saved Successfully'));
        } else {
            return response()->json(['result' => 'success', 'action' => 'store', 'message' => _lang('Saved Successfully'), 'data' => $employee, 'table' => '#employees_table']);
        }
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show(Request $request, $id)
    {
        $employee  = Employee::with('department', 'designation')->find($id);
        return view('backend.user.staff.view', compact('employee', 'id'));
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit(Request $request, $id)
    {
        $employee  = Employee::find($id);
        return view('backend.user.staff.edit', compact('employee', 'id'));
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
        ]);

        if ($validator->fails()) {
            if ($request->ajax()) {
                return response()->json(['result' => 'error', 'message' => $validator->errors()->all()]);
            } else {
                return redirect()->route('staffs.edit', $id)
                    ->withErrors($validator)
                    ->withInput();
            }
        }

        DB::beginTransaction();
        $employee                = Employee::find($id);
        $employee->name          = $request->input('name');
        $employee->date_of_birth = $request->input('date_of_birth')? Carbon::parse($request->input('date_of_birth'))->format('Y-m-d') : null;
        $employee->email         = $request->input('email');
        $employee->phone         = $request->input('phone');
        $employee->city          = $request->input('city');
        $employee->country       = $request->input('country');
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
        $audit->changed_by = auth()->user()->id;
        $audit->event = 'Updated Staff '.$employee->name;
        $audit->save();

        if (!$request->ajax()) {
            return redirect()->route('staffs.index')->with('success', _lang('Updated Successfully'));
        } else {
            return response()->json(['result' => 'success', 'action' => 'update', 'message' => _lang('Updated Successfully'), 'data' => $employee, 'table' => '#employees_table']);
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
        $employee = Employee::find($id);

        // audit log
        $audit = new AuditLog();
        $audit->date_changed = date('Y-m-d H:i:s');
        $audit->changed_by = auth()->user()->id;
        $audit->event = 'Deleted Staff '.$employee->name;
        $audit->save();

        $employee->delete();
        return redirect()->route('staffs.index')->with('success', _lang('Deleted Successfully'));
    }

    public function import_staffs(Request $request)
    {
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
        $audit->changed_by = auth()->user()->id;
        $audit->event = 'Imported Staffs';
        $audit->save();

        return redirect()->route('staffs.index')->with('success', _lang('Staffs Imported'));
    }

    public function export_staffs()
    {
        return Excel::download(new StaffsExport, 'staffs ' . now()->format('d m Y') . '.xlsx');
    }
}
