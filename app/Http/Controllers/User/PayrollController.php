<?php

namespace App\Http\Controllers\User;

use App\Exports\PayslipsExport;
use App\Http\Controllers\Controller;
use App\Mail\GeneralMail;
use App\Models\AbsentFine;
use App\Models\Account;
use App\Models\Attendance;
use App\Models\AuditLog;
use App\Models\Currency;
use App\Models\Employee;
use App\Models\EmployeeBenefit;
use App\Models\Holiday;
use App\Models\Payroll;
use App\Models\SalaryBenefit;
use App\Models\Tax;
use App\Models\Timesheet;
use App\Models\Transaction;
use App\Models\TransactionMethod;
use App\Utilities\Overrider;
use Carbon\Carbon;
use DataTables;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Inertia\Inertia;
use Maatwebsite\Excel\Facades\Excel;
use PhpOffice\PhpSpreadsheet\Calculation\DateTimeExcel\Current;
use Validator;

class PayrollController extends Controller
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
        $month = $request->month ?? date('m');
        $year = $request->year ?? date('Y');
        $search = $request->search;
        $per_page = $request->per_page ?? 50;

        // Store month and year in session
        session(['month' => $month, 'year' => $year]);

        $query = Payroll::query()
            ->where('payslips.business_id', $request->activeBusiness->id)
            ->where('month', $month)
            ->where('year', $year)
            ->with('staff');

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('employee_id', 'like', "%$search%")
                    ->orWhereHas('employee', function ($q) use ($search) {
                        $q->where('name', 'like', "%$search%");
                    })
                    ->orWhere('current_salary', 'like', "%$search%");
            });
        }

        // Handle sorting
        $sorting = $request->get('sorting', []);
        $sortColumn = $sorting['column'] ?? 'id';
        $sortDirection = $sorting['direction'] ?? 'desc';

        // Handle relationship sorting
        if (str_contains($sortColumn, '.')) {
            $parts = explode('.', $sortColumn);
            $column = $parts[1];
            $query->join('employees', 'payslips.employee_id', '=', 'employees.id')
                ->where('employees.business_id', $request->activeBusiness->id)
                ->orderBy('employees.' . $column, $sortDirection)
                ->select('payslips.*');
        } else {
            $query->orderBy('payslips.' . $sortColumn, $sortDirection);
        }

        $payrolls = $query->paginate($per_page);

        // Calculate totals
        $totals = [
            'net_salary' => $payrolls->sum('net_salary'),
            'current_salary' => $payrolls->sum('current_salary'),
            'total_allowance' => $payrolls->sum('total_allowance'),
            'total_deduction' => $payrolls->sum('total_deduction'),
            'tax_amount' => $payrolls->sum('tax_amount'),
            'advance' => $payrolls->sum('advance'),
        ];

        return Inertia::render('Backend/User/Payroll/List', [
            'payrolls' => $payrolls->items(),
            'meta' => [
                'current_page' => $payrolls->currentPage(),
                'per_page' => $payrolls->perPage(),
                'from' => $payrolls->firstItem(),
                'to' => $payrolls->lastItem(),
                'total' => $payrolls->total(),
                'last_page' => $payrolls->lastPage(),
            ],
            'filters' => [
                'search' => $search,
                'sorting' => $sorting,
            ],
            'years' => range(date('Y') - 5, date('Y') + 5),
            'year' => $year,
            'month' => $month,
            'totals' => $totals,
            'accounts' => Account::where('business_id', $request->activeBusiness->id)->get(),
            'methods' => TransactionMethod::where('business_id', $request->activeBusiness->id)->get(),
        ]);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create(Request $request)
    {
        $years = [];
        for ($y = 2020; $y <= date('Y'); $y++) {
            $years[] = $y;
        }
        return Inertia::render('Backend/User/Payroll/Create', [
            'years' => $years,
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
        $validator = Validator::make($request->all(), [
            'month' => 'required',
            'year'  => 'required',
        ]);

        if ($validator->fails()) {
            return redirect()->route('payslips.create')
                ->withErrors($validator)
                ->withInput();
        }

        $month = $request->month;
        $year  = $request->year;

        // previous month and year
        $previous_month = Carbon::parse($year . '-' . $month . '-01')->subMonth()->format('m');
        $previous_year  = Carbon::parse($year . '-' . $month . '-01')->subMonth()->format('Y');

        // duplicate employee benefits from previous month
        $employee_benefits = EmployeeBenefit::where('month', $previous_month)
            ->where('year', $previous_year)
            ->get();

        foreach ($employee_benefits as $employee_benefit) {
            $new_employee_benefit = $employee_benefit->replicate();
            $new_employee_benefit->month = $month;
            $new_employee_benefit->year = $year;
            $new_employee_benefit->advance = $employee_benefit->advance;
            $new_employee_benefit->save();

            $salary_benefits = SalaryBenefit::where('employee_benefit_id', $employee_benefit->id)->get();
            foreach ($salary_benefits as $salary_benefit) {
                $new_salary_benefit = $salary_benefit->replicate();
                $new_salary_benefit->employee_benefit_id = $new_employee_benefit->id;
                $new_salary_benefit->date = $salary_benefit->date;
                $new_salary_benefit->amount = $salary_benefit->amount;
                $new_salary_benefit->type = $salary_benefit->type;
                $new_salary_benefit->save();
            }
        }

        DB::beginTransaction();

        $employees = Employee::with('employee_benefits.salary_benefits')
            ->whereDoesntHave('payslips', function ($query) use ($month, $year) {
                $query->where('month', $month)->where('year', $year);
            })
            ->where(function (Builder $query) {
                $query->where("end_date", NULL)->orWhere("end_date", ">", now());
            })
            ->get();

        if ($employees->count() == 0) {
            return back()->with('error', _lang('Payslip is already generated for the selected period !'));
        }

        foreach ($employees as $employee) {
            // Initialize variables with default values
            $required_working_days = cal_days_in_month(CAL_GREGORIAN, $request->month, $request->year);
            $public_holidays = 0;
            $weekend = 0;
            $required_working_hours = $employee->working_hours * $required_working_days;
            $actual_working_hours = $required_working_hours;
            $overtime_hours = 0;

            // Only calculate from timesheet if employee is timesheet-based and package has timesheet module
            if ($employee->timesheet_based == 1 && package()->timesheet_module == 1) {
                $weekends = json_decode(get_business_option('weekends'));
                $holidays = Holiday::where('business_id', request()->activeBusiness->id)
                    ->whereMonth('date', $request->month)
                    ->whereYear('date', $request->year)
                    ->get();
                $required_working_days = cal_days_in_month(CAL_GREGORIAN, $request->month, $request->year) - $holidays->count() - $this->countSpecificDaysInMonth($request->year, $request->month, $weekends);
                $public_holidays = Timesheet::where('employee_id', $employee->id)->whereIn('date', $holidays->pluck('date'))->sum('working_hours');
                $weekend = Timesheet::where('employee_id', $employee->id)
                    ->whereMonth('date', $request->month)
                    ->whereYear('date', $request->year)
                    ->get()
                    ->filter(function ($timesheet) use ($weekends) {
                        return in_array(strtolower(\Carbon\Carbon::createFromFormat(get_date_format(), $timesheet->date)->format('l')), $weekends);
                    })
                    ->sum('working_hours');
                $required_working_hours = $required_working_days * $employee->working_hours;
                $actual_working_hours = Timesheet::where('employee_id', $employee->id)
                    ->whereMonth('date', $request->month)
                    ->whereYear('date', $request->year)
                    ->sum('working_hours');
                $overtime_hours = min(max($actual_working_hours - $required_working_hours, 0), $employee->max_overtime_hours * $required_working_days);
            }

            $benefits        = $employee->employee_benefits->where('month', $month)->where('year', $year)->first()?->salary_benefits()->where('type', 'add')->get();
            $deductions      = $employee->employee_benefits->where('month', $month)->where('year', $year)->first()?->salary_benefits()->where('type', 'deduct')->get();
            $total_benefits  = $employee->basic_salary + $benefits?->sum('amount');
            $total_deduction = $deductions?->sum('amount');

            $payroll                            = new Payroll();
            $payroll->employee_id               = $employee->id;
            $payroll->month                     = $month;
            $payroll->year                      = $year;
            $payroll->required_working_days     = $required_working_days;
            $payroll->public_holiday            = $public_holidays;
            $payroll->weekend                   = $weekend;
            $payroll->required_working_hours    = $required_working_hours;
            $payroll->overtime_hours            = $overtime_hours;
            $payroll->cost_normal_hours         = $employee->basic_salary / $required_working_hours;
            $payroll->cost_overtime_hours       = $employee->basic_salary / $required_working_hours * get_business_option('overtime_rate_multiplier');
            $payroll->cost_public_holiday       = $employee->basic_salary / $required_working_hours * get_business_option('public_holiday_rate_multiplier');
            $payroll->cost_weekend              = $employee->basic_salary / $required_working_hours * get_business_option('weekend_holiday_rate_multiplier');
            $payroll->total_cost_normal_hours   = $payroll->cost_normal_hours * $actual_working_hours;
            $payroll->total_cost_overtime_hours = $payroll->cost_overtime_hours * $overtime_hours;
            $payroll->total_cost_public_holiday = $payroll->cost_public_holiday * $public_holidays;
            $payroll->total_cost_weekend        = $payroll->cost_weekend * $weekend;
            $payroll->current_salary            = $employee->basic_salary;
            $payroll->net_salary                = $payroll->total_cost_normal_hours + $payroll->total_cost_overtime_hours + $payroll->total_cost_public_holiday + $payroll->total_cost_weekend;
            $payroll->tax_amount                = 0;
            $payroll->total_allowance           = $benefits?->sum('amount');
            $payroll->total_deduction           = $total_deduction;
            $payroll->absence_fine              = 0;
            $payroll->advance                   = $employee->employee_benefits->where('month', $month)->where('year', $year)->first()?->advance;
            $payroll->status                    = 0;
            $payroll->save();
        }

        DB::commit();

        // audit log
        $audit = new AuditLog();
        $audit->date_changed = date('Y-m-d H:i:s');
        $audit->changed_by = auth()->user()->id;
        $audit->event = 'Generated Payslip for ' . count($employees) . ' employees';
        $audit->save();

        return redirect()->route('payslips.index')->with('success', _lang('Payslip Generated Successfully'));
    }

    function countSpecificDaysInMonth($year, $month, $weekends)
    {
        $totalDays = cal_days_in_month(CAL_GREGORIAN, $month, $year);
        $count = 0;

        // Map day names to numbers for easy comparison
        $dayMap = [
            'sunday' => 0,
            'monday' => 1,
            'tuesday' => 2,
            'wednesday' => 3,
            'thursday' => 4,
            'friday' => 5,
            'saturday' => 6,
        ];

        // Convert weekend names to day numbers
        $weekendNumbers = array_map(function ($day) use ($dayMap) {
            return $dayMap[$day];
        }, $weekends);

        // Loop through each day of the month
        for ($day = 1; $day <= $totalDays; $day++) {
            $timestamp = mktime(0, 0, 0, $month, $day, $year);
            $dayOfWeek = date('w', $timestamp); // 0 (Sun) to 6 (Sat)

            if (in_array($dayOfWeek, $weekendNumbers)) {
                $count++;
            }
        }

        return $count;
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        $payroll      = Payroll::with('staff', 'staff.department', 'staff.designation')->find($id);
        $working_days = Attendance::whereMonth('date', $payroll->month)
            ->whereYear('date', $payroll->year)
            ->groupBy('date')->get()->count();

        $absence = Attendance::where('employee_id', $payroll->employee_id)
            ->selectRaw("SUM(CASE WHEN attendance.leave_duration = 'half_day' THEN 0.5 ELSE 1 END) as absence")
            ->whereMonth('date', $payroll->month)
            ->whereYear('date', $payroll->year)
            ->where('attendance.status', 0)
            ->first()
            ->absence;

        $allowances = array();
        $deductions = array();

        $benefits = $payroll->employee->employee_benefits()->where('month', $payroll->month)
            ->where('year', $payroll->year)->get();

        foreach ($benefits as $benefit) {
            $salary_allowance = $benefit->salary_benefits()->where('type', 'add')->get();
            foreach ($salary_allowance as $key => $value) {
                $allowances[$key]['date']           = $value->date;
                $allowances[$key]['description']    = $value->description;
                $allowances[$key]['amount']         = $value->amount;
            }

            $salary_deduction = $benefit->salary_benefits()->where('type', 'deduct')->get();
            foreach ($salary_deduction as $key => $value) {
                $deductions[$key]['date']           = $value->date;
                $deductions[$key]['description']    = $value->description;
                $deductions[$key]['amount']         = $value->amount;
            }
        }

        $advance = $payroll->employee->employee_benefits()->where('month', $payroll->month)
            ->where('year', $payroll->year)->first()?->advance;

        $actual_normal_working_hours = Timesheet::where('employee_id', $payroll->employee_id)
            ->whereMonth('date', $payroll->month)
            ->whereYear('date', $payroll->year)
            ->sum('working_hours');
        $overtime_hours = min(max($actual_normal_working_hours - $payroll->required_working_hours, 0), $payroll->employee->max_overtime_hours * $payroll->required_working_days);

        $actual_working_hours = $actual_normal_working_hours - $overtime_hours;

        return Inertia::render('Backend/User/Payroll/View', [
            'payroll' => $payroll,
            'working_days' => $working_days,
            'absence' => $absence,
            'allowances' => $allowances,
            'deductions' => $deductions,
            'advance' => $advance,
            'actual_working_hours' => $actual_working_hours,
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        $payroll = Payroll::with('staff')
            ->with(['employee.employee_benefits' => function ($query) use ($id) {
                $query->where('month', Payroll::find($id)->month)
                    ->where('year', Payroll::find($id)->year)
                    ->with('salary_benefits');
            }])
            ->find($id);

        $employee_benefits = EmployeeBenefit::where('month', $payroll->month)
            ->where('year', $payroll->year)
            ->where('employee_id', $payroll->employee_id)
            ->with('salary_benefits')
            ->first();
        $decimalPlace = get_business_option('decimal_place', 2);
        $accounts = Account::all();

        return Inertia::render('Backend/User/Payroll/Edit', [
            'payroll' => $payroll,
            'employee_benefits' => $employee_benefits,
            'accounts' => $accounts,
            'decimalPlace' => $decimalPlace,
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
        DB::beginTransaction();

        $payroll = Payroll::find($id);
        if ($payroll->status != 0) {
            return redirect()->back()->with('error', _lang('Sorry, Only unpaid payslip can be modified !'));
        }

        // delete employee benefit where year and month is same as payroll
        $payroll->employee->employee_benefits()->where('month', $payroll->month)->where('year', $payroll->year)->delete();

        // create employee benefit
        $employee_benefit = $payroll->employee->employee_benefits()->create([
            'month' => $payroll->month,
            'year'  => $payroll->year,
            'advance' => $request->advance,
        ]);

        $benefits = 0;
        if (isset($request->allowances)) {
            for ($i = 0; $i < count($request->allowances); $i++) {
                if (!empty($request->allowances[$i]['date']) && !empty($request->allowances[$i]['amount'])) {
                    $employee_benefit->salary_benefits()->save(new SalaryBenefit([
                        'employee_benefit_id' => $employee_benefit->id,
                        'date'                => Carbon::parse($request->allowances[$i]['date'])->format('Y-m-d'),
                        'description'         => $request->allowances[$i]['description'],
                        'amount'              => $request->allowances[$i]['amount'],
                        'type'                => 'add',
                    ]));
                    $benefits += $request->allowances[$i]['amount'];
                }
            }
        }

        $deductions = 0;
        if (isset($request->deductions)) {
            for ($i = 0; $i < count($request->deductions); $i++) {
                if (!empty($request->deductions[$i]['date']) && !empty($request->deductions[$i]['amount'])) {
                    $employee_benefit->salary_benefits()->save(new SalaryBenefit([
                        'employee_benefit_id' => $employee_benefit->id,
                        'date'                => Carbon::parse($request->deductions[$i]['date'])->format('Y-m-d'),
                        'description'         => $request->deductions[$i]['description'],
                        'amount'              => $request->deductions[$i]['amount'],
                        'account_id'          => $request->deductions[$i]['account_id'],
                        'type'                => 'deduct',
                    ]));
                    $deductions += $request->deductions[$i]['amount'];
                }
            }
        }

        $total_benefits  = $payroll->current_salary + $benefits;
        $total_deduction = $deductions + $request->advance;

        $payroll->net_salary = ($total_benefits - $total_deduction - $payroll->tax_amount);
        $payroll->total_allowance = $benefits;
        $payroll->total_deduction = $deductions;
        $payroll->advance = $request->advance;
        $payroll->advance_description = $request->advance_description;
        $payroll->save();

        DB::commit();

        // audit log
        $audit = new AuditLog();
        $audit->date_changed = date('Y-m-d H:i:s');
        $audit->changed_by = auth()->user()->id;
        $audit->event = 'Updated Payslip for ' . $payroll->employee->name;
        $audit->save();

        return redirect()->route('payslips.index')->with('success', _lang('Updated Successfully'));
    }

    public function bulk_payment(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'payments'      => 'required',
            'method'          => 'nullable',
            'credit_account_id'      => 'required',
            'debit_account_id' => 'required',
            'advance_account_id' => 'required',
            'payment_date' => 'required'
        ]);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }

        $currentTime = now();

        DB::beginTransaction();
        try {
            $payslips = Payroll::whereIn('id', array_column($request->payments, 'id'))->get();

            foreach ($request->payments as $payment) {
                $payslip = Payroll::find($payment['id']);
                if ($payslip->status == 4) {
                    continue;
                }
                $payslip->paid = $payslip->paid + $payment['amount'];
                $payslip->save();

                // Set payment status
                if ($payslip->paid >= $payslip->net_salary) {
                    $payslip->status = 4;
                    $payslip->save();
                } else {
                    $payslip->status = 3;
                    $payslip->save();
                }

                $transaction                         = new Transaction();
                $transaction->trans_date             = Carbon::parse($request->input('payment_date'))->setTime($currentTime->hour, $currentTime->minute, $currentTime->second)->format('Y-m-d H:i');
                $transaction->account_id             = $request->credit_account_id;
                $transaction->dr_cr                  = 'cr';
                $transaction->transaction_amount     = $payment['amount'];
                $transaction->currency_rate          = Currency::where('name', request()->activeBusiness->currency)->first()->exchange_rate;
                $transaction->base_currency_amount   = $payment['amount'];
                $transaction->description            = _lang('Staff Salary Payment ' . $payslip->month . '/' . $payslip->year) . ' - ' . $payslip->employee->name;
                $transaction->ref_id                 = $payslip->employee_id;
                $transaction->ref_type               = 'payslip';
                $transaction->employee_id            = $payslip->employee_id;
                $transaction->save();

                $payslip->transaction_id = $transaction->id;
                $payslip->save();

                if ($payslip->status == 4) {
                    $transaction                          = new Transaction();
                    $transaction->trans_date              = Carbon::parse($request->input('payment_date'))->setTime($currentTime->hour, $currentTime->minute, $currentTime->second)->format('Y-m-d H:i');
                    $transaction->account_id              = $request->debit_account_id;
                    $transaction->dr_cr                   = 'dr';
                    $transaction->transaction_amount      = $payslip->current_salary;
                    $transaction->currency_rate           = Currency::where('name', request()->activeBusiness->currency)->first()->exchange_rate;
                    $transaction->base_currency_amount    = $payslip->current_salary;
                    $transaction->description             = _lang('Staff Salary ' . $payslip->month . '/' . $payslip->year) . ' - ' . $payslip->employee->name;
                    $transaction->ref_id                  = $payslip->employee_id;
                    $transaction->ref_type                = 'payslip';
                    $transaction->employee_id             = $payslip->employee_id;
                    $transaction->save();
                }

                // Process deductions with specific accounts
                $deductions = SalaryBenefit::whereHas('employee_benefit', function ($query) use ($payslip) {
                    $query->where('employee_id', $payslip->employee_id)
                        ->where('month', $payslip->month)
                        ->where('year', $payslip->year);
                })->where('type', 'deduct')
                    ->whereNotNull('account_id')
                    ->get();

                if ($payslip->status == 4) {
                    foreach ($deductions as $deduction) {
                        $transaction = new Transaction();
                        $transaction->trans_date = Carbon::parse($request->input('payment_date'))->setTime($currentTime->hour, $currentTime->minute, $currentTime->second)->format('Y-m-d H:i');
                        $transaction->account_id = $deduction->account_id;
                        $transaction->dr_cr = 'cr';
                        $transaction->transaction_amount = $deduction->amount;
                        $transaction->currency_rate = Currency::where('name', request()->activeBusiness->currency)->first()->exchange_rate;
                        $transaction->base_currency_amount = $deduction->amount;
                        $transaction->description = _lang('Payroll Deduction: ' . $deduction->description . ' - ' . $payslip->month . '/' . $payslip->year) . ' - ' . $payslip->employee->name;
                        $transaction->ref_id = $payslip->employee_id;
                        $transaction->ref_type = 'payslip_deduction';
                        $transaction->employee_id = $payslip->employee_id;
                        $transaction->save();
                    }
                }

                if ($payslip->status == 4 && $payslip->advance > 0) {
                    $transaction                          = new Transaction();
                    $transaction->trans_date              = Carbon::parse($request->input('payment_date'))->setTime($currentTime->hour, $currentTime->minute, $currentTime->second)->format('Y-m-d H:i');
                    $transaction->account_id              = $request->advance_account_id;
                    $transaction->dr_cr                   = 'cr';
                    $transaction->transaction_amount      = $payslip->advance;
                    $transaction->currency_rate           = Currency::where('name', request()->activeBusiness->currency)->first()->exchange_rate;
                    $transaction->base_currency_amount    = $payslip->advance;
                    $transaction->description             = _lang('Staff Salary Advance ' . $payslip->month . '/' . $payslip->year) . ' - ' . $payslip->employee->name;
                    $transaction->ref_id                  = $payslip->employee_id;
                    $transaction->ref_type                = 'payslip';
                    $transaction->employee_id             = $payslip->employee_id;
                    $transaction->save();
                }
            }

            DB::commit();

            // audit log
            $audit = new AuditLog();
            $audit->date_changed = date('Y-m-d H:i:s');
            $audit->changed_by = auth()->user()->id;
            $audit->event = 'Processed Payslip Payment for ' . count($payslips) . ' employees';
            $audit->save();

            return redirect()->back()->with('success', _lang('Payment Processed Successfully'));
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->with('error', $e->getMessage());
        }
    }

    public function bulk_accrue(Request $request)
    {
        if (empty($request->ids)) {
            return back()->with('error', _lang('You must select at least one employee'))->withInput();
        }

        $validator = Validator::make($request->all(), [
            'liability_account_id'    => 'required',
            'expense_account_id'      => 'required',
        ], [
            'liability_account_id.required'    => 'You must select debit account',
            'expense_account_id.required'      => 'Expense account is required',
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        DB::beginTransaction();

        $payslips = Payroll::whereIn('id', $request->ids)
            ->where('status', 1)
            ->get();

        if ($payslips->isEmpty()) {
            return back()->with('error', _lang('No active payslips found'));
        }

        foreach ($payslips as $payslip) {
            $transaction                          = new Transaction();
            $transaction->trans_date              = now();
            $transaction->account_id              = $request->liability_account_id;
            $transaction->transaction_method      = $request->method;
            $transaction->dr_cr                   = 'cr';
            $transaction->transaction_amount      = $payslip->net_salary;
            $transaction->currency_rate           = Currency::where('name', request()->activeBusiness->currency)->first()->exchange_rate;
            $transaction->base_currency_amount    = $payslip->net_salary;
            $transaction->description             = _lang('Staff Salary ' . $payslip->month . '/' . $payslips->first()->year) . ' - ' . $payslip->employee->name;
            $transaction->ref_id                  = $payslip->employee_id;
            $transaction->ref_type                = 'payslip';
            $transaction->employee_id             = $payslip->employee_id;
            $transaction->save();

            $transaction                          = new Transaction();
            $transaction->trans_date              = now();
            $transaction->account_id              = $request->expense_account_id;
            $transaction->dr_cr                   = 'dr';
            $transaction->transaction_amount      = $payslip->employee->basic_salary + $payslip->total_allowance - $payslip->total_deduction;
            $transaction->currency_rate           = Currency::where('name', request()->activeBusiness->currency)->first()->exchange_rate;
            $transaction->base_currency_amount    = $payslip->employee->basic_salary + $payslip->total_allowance - $payslip->total_deduction;
            $transaction->description             = _lang('Staff Salary Expense ' . $payslip->month . ' ' . $payslips->first()->year) . ' - ' . $payslip->employee->name;
            $transaction->ref_id                  = $payslip->employee_id;
            $transaction->ref_type                = 'payslip';
            $transaction->employee_id             = $payslip->employee_id;
            $transaction->save();

            $payslip->status         = 2;
            $payslip->transaction_id = $transaction->id;
            $payslip->save();
        }

        DB::commit();

        // audit log
        $audit = new AuditLog();
        $audit->date_changed = date('Y-m-d H:i:s');
        $audit->changed_by = auth()->user()->id;
        $audit->event = 'Accruement made for ' . count($payslips) . ' employees';
        $audit->save();

        return redirect()->route('payslips.index')->with('success', _lang('Accruement made successfully'));
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $payroll = Payroll::find($id);

        // audit log
        $audit = new AuditLog();
        $audit->date_changed = date('Y-m-d H:i:s');
        $audit->changed_by = auth()->user()->id;
        $audit->event = 'Deleted Payslip for ' . $payroll->employee->name;
        $audit->save();

        // delete transactions
        $transactions = Transaction::where('ref_id', $payroll->employee_id)->where('ref_type', 'payslip')
            ->where('description', 'like', '%' . $payroll->month . '/' . $payroll->year . '%')
            ->get();
        foreach ($transactions as $transaction) {
            $transaction->delete();
        }

        $payroll->delete();
        return redirect()->back()->with('success', _lang('Deleted Successfully'));
    }

    public function export_payslips()
    {
        // audit log
        $audit = new AuditLog();
        $audit->date_changed = date('Y-m-d H:i:s');
        $audit->changed_by = auth()->user()->id;
        $audit->event = 'Exported Payslips for ' . date('F', mktime(0, 0, 0, session('month'), 10)) . ' ' . session('year');
        $audit->save();

        return Excel::download(new PayslipsExport(), 'Payroll ' . date('F', mktime(0, 0, 0, session('month'), 10)) . ' ' . session('year') . '.xlsx');
    }

    public function bulk_approve(Request $request)
    {
        $payrolls = Payroll::where('status', 0)
            ->whereIn('id', $request->ids)
            ->get();

        if ($payrolls->count() == 0) {
            return back()->with('error', _lang('No payslip found to approve'));
        }

        foreach ($payrolls as $payroll) {
            $payroll->status = 1;
            $payroll->save();
        }

        @ini_set('max_execution_time', 0);
        @set_time_limit(0);

        Overrider::load("BusinessSettings");

        $payroll = Payroll::where('status', 1)->where('month', session('month'))->where('year', session('year'))->get();

        //Send Email
        $email   = $request->activeBusiness->email;
        $message = 'Dear ' . $request->activeBusiness->name . ',<br><br>';
        $message .= 'We are pleased to inform you that the payroll for the period ' . date('F', mktime(0, 0, 0, session('month'), 10)) . ' ' . session('year') . ' has been successfully approved.<br><br>
        Details:<br>
        Approval Date:' . now()->format(get_date_format()) . '<br>
        Total Amount:' . currency_symbol($request->activeBusiness->currency) . $payroll->sum('net_salary') . '<br>
        Number of Employees Paid:' . count($payroll) . '<br><br>';

        $mail          = new \stdClass();
        $mail->subject = "Payroll Approved";
        $mail->body    = $message;

        // audit log
        $audit = new AuditLog();
        $audit->date_changed = date('Y-m-d H:i:s');
        $audit->changed_by = auth()->user()->id;
        $audit->event = 'Approved Payslip for ' . count($payroll) . ' employees';
        $audit->save();

        try {
            Mail::to($email)->send(new GeneralMail($mail));
            return back()->with('success', _lang('Payroll approved and email notification sent sucessfully'));
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    public function bulk_reject(Request $request)
    {
        $payrolls = Payroll::whereIn('id', $request->ids)
            ->where('status', 1)
            ->get();

        if ($payrolls->count() == 0) {
            return back()->with('error', _lang('No payslip found to reject'));
        }

        foreach ($payrolls as $payroll) {
            $payroll->status = 0;
            $payroll->save();
        }

        @ini_set('max_execution_time', 0);
        @set_time_limit(0);

        Overrider::load("BusinessSettings");

        $payroll = Payroll::where('status', 0)->where('month', session('month'))->where('year', session('year'))->get();

        //Send Email
        $email   = $request->activeBusiness->email;
        $message = 'Dear ' . $request->activeBusiness->name . ',<br><br>';
        $message .= 'We are sorry to inform you that the payroll for the period ' . date('F', mktime(0, 0, 0, session('month'), 10)) . ' ' . session('year') . ' has been rejected.<br><br>
        Details:<br>
        Reject Date:' . now()->format(get_date_format()) . '<br>
        Total Amount:' . currency_symbol($request->activeBusiness->currency) . $payroll->sum('net_salary') . '<br>
        Number of Employees Paid:' . count($payroll) . '<br><br>';

        $mail          = new \stdClass();
        $mail->subject = "Payroll Rejected";
        $mail->body    = $message;

        // audit log
        $audit = new AuditLog();
        $audit->date_changed = date('Y-m-d H:i:s');
        $audit->changed_by = auth()->user()->id;
        $audit->event = 'Rejected Payslip for ' . count($payroll) . ' employees';
        $audit->save();

        try {
            Mail::to($email)->send(new GeneralMail($mail));
            return back()->with('success', _lang('Payroll rejected email notification sent sucessfully'));
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    public function bulk_delete(Request $request)
    {
        if ($request->ids == null) {
            return redirect()->back()->with('error', _lang('Please Select Payslip'));
        }

        $payslips = Payroll::whereIn('id', $request->ids)->get();

        // audit log
        $audit = new AuditLog();
        $audit->date_changed = date('Y-m-d H:i:s');
        $audit->changed_by = auth()->user()->id;
        $audit->event = 'Deleted Payslip for ' . count($payslips) . ' employees';
        $audit->save();

        foreach ($payslips as $payslip) {
            // delete transactions
            $transactions = Transaction::where('ref_id', $payslip->employee_id)->where('ref_type', 'payslip')->where('description', 'like', '%' . $payslip->month . '/' . $payslip->year . '%')->get();
            foreach ($transactions as $transaction) {
                $transaction->delete();
            }
            $payslip->delete();
        }

        return redirect()->back()->with('success', _lang('Deleted Successfully'));
    }
}
