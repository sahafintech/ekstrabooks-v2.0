<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\EmployeeBenefit;
use App\Models\SalaryBenefit;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class BenefitController extends Controller
{
    public function index()
    {
        $benefits = EmployeeBenefit::with('employee')->get();
        return view('backend.user.benefits.list', compact('benefits'));
    }

    public function create()
    {
        return view('backend.user.benefits.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'employee_id'           => 'required',
            'month'                 => 'required',
            'year'                  => 'required',
            'advance'               => 'nullable|numeric',
        ]);

        DB::beginTransaction();
        $employee_benefit                        = new EmployeeBenefit();
        $employee_benefit->employee_id           = $request->input('employee_id');
        $employee_benefit->month                 = $request->input('month');
        $employee_benefit->year                  = $request->input('year');
        $employee_benefit->advance               = $request->input('advance');
        $employee_benefit->save();

        if ($request->allowances['amount'][0] !== null) {
            for ($i = 0; $i < count($request->allowances['amount']); $i++) {
                $employee_benefit->salary_benefits()->save(new SalaryBenefit([
                    'employee_benefit_id' => $employee_benefit->id,
                    'date'                => Carbon::parse($request->allowances['date'][$i])->format('Y-m-d'),
                    'description'         => $request->allowances['description'][$i],
                    'amount'              => $request->allowances['amount'][$i],
                    'type'                => 'add',
                ]));
            }
        }

        if ($request->deductions['amount'][0] !== null) {
            for ($i = 0; $i < count($request->deductions['amount']); $i++) {
                $employee_benefit->salary_benefits()->save(new SalaryBenefit([
                    'employee_benefit_id' => $employee_benefit->id,
                    'date'                => Carbon::parse($request->deductions['date'][$i])->format('Y-m-d'),
                    'description'         => $request->deductions['description'][$i],
                    'amount'              => $request->deductions['amount'][$i],
                    'type'                => 'deduct',
                ]));
            }
        }

        DB::commit();

        // audit log
        $audit = new AuditLog();
        $audit->date_changed = date('Y-m-d H:i:s');
        $audit->changed_by = auth()->user()->id;
        $audit->event = 'Employee Benefit Created for ' . $employee_benefit->employee->name;
        $audit->save();

        return redirect()->route('benefits.index')->with('success', _lang('Saved Successfully'));
    }

    public function edit($id)
    {
        $benefit = EmployeeBenefit::with('salary_benefits')->find($id);
        return view('backend.user.benefits.edit', compact('benefit', 'id'));
    }

    public function update(Request $request, $id) {
        $request->validate([
            'employee_id'           => 'required',
            'month'                 => 'required',
            'year'                  => 'required',
            'advance'               => 'nullable|numeric',
        ]);

        DB::beginTransaction();
        $employee_benefit                        = EmployeeBenefit::find($id);
        $employee_benefit->employee_id           = $request->input('employee_id');
        $employee_benefit->month                 = $request->input('month');
        $employee_benefit->year                  = $request->input('year');
        $employee_benefit->advance               = $request->input('advance');
        $employee_benefit->save();

        $employee_benefit->salary_benefits()->delete();

        if ($request->allowances['amount'][0] !== null) {
            for ($i = 0; $i < count($request->allowances['amount']); $i++) {
                $employee_benefit->salary_benefits()->save(new SalaryBenefit([
                    'employee_benefit_id' => $employee_benefit->id,
                    'date'                => Carbon::parse($request->allowances['date'][$i])->format('Y-m-d'),
                    'description'         => $request->allowances['description'][$i],
                    'amount'              => $request->allowances['amount'][$i],
                    'type'                => 'add',
                ]));
            }
        }

        if ($request->deductions['amount'][0] !== null) {
            for ($i = 0; $i < count($request->deductions['amount']); $i++) {
                $employee_benefit->salary_benefits()->save(new SalaryBenefit([
                    'employee_benefit_id' => $employee_benefit->id,
                    'date'                => Carbon::parse($request->deductions['date'][$i])->format('Y-m-d'),
                    'description'         => $request->deductions['description'][$i],
                    'amount'              => $request->deductions['amount'][$i],
                    'type'                => 'deduct',
                ]));
            }
        }

        DB::commit();

        // audit log
        $audit = new AuditLog();
        $audit->date_changed = date('Y-m-d H:i:s');
        $audit->changed_by = auth()->user()->id;
        $audit->event = 'Employee Benefit Updated for ' . $employee_benefit->employee->name;
        $audit->save();

        return redirect()->route('benefits.index')->with('success', _lang('Updated Successfully'));
    }

    public function show($id)
    {
        $benefit = EmployeeBenefit::with('salary_benefits', 'employee')->find($id);
        return view('backend.user.benefits.view', compact('benefit'));
    }
}
