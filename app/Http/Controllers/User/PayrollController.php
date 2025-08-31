<?php

namespace App\Http\Controllers\User;

use App\Exports\PayslipsExport;
use App\Http\Controllers\Controller;
use App\Mail\GeneralMail;
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
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Inertia\Inertia;
use Maatwebsite\Excel\Facades\Excel;
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
            'trashed_payrolls' => Payroll::onlyTrashed()->where('business_id', $request->activeBusiness->id)->count(),
        ]);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
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
            'year'  => 'required|integer',
        ]);

        if ($validator->fails()) {
            return redirect()->route('payslips.create')
                ->withErrors($validator)
                ->withInput();
        }

        $month = str_pad($request->month, 2, '0', STR_PAD_LEFT);
        $year  = $request->year;
        $periodDate = Carbon::createFromFormat('Y-m-d', "{$year}-{$month}-01");

        DB::beginTransaction();
        try {
            // Duplicate employee benefits from previous month
            $this->duplicateEmployeeBenefits($periodDate);

            // Get employees for payroll generation
            $employees = $this->getEmployeesForPayroll($month, $year);

            if ($employees->isEmpty()) {
                DB::rollBack();
                return back()->with('error', _lang('Payslip is already generated for the selected period !'));
            }

            // Get business settings
            $businessSettings = $this->getBusinessSettings();

            $errors = [];
            foreach ($employees as $employee) {
                try {
                    $this->validateEmployeeRequirements($employee);
                    $this->generateEmployeePayroll($employee, $month, $year, $businessSettings);
                } catch (\Exception $e) {
                    $errors[] = "Error processing {$employee->name}: " . $e->getMessage();
                }
            }

            if (!empty($errors)) {
                DB::commit();
                $msg = implode('; ', $errors);
                return redirect()->route('payslips.index')
                    ->with('warning', _lang('Payslip generated with some skipped employees: ') . $msg)
                    ->with('success', _lang('Payslip Generated Successfully'));
            }

            // Create audit log
            $this->createAuditLog(count($employees));

            DB::commit();
            return redirect()->route('payslips.index')->with('success', _lang('Payslip Generated Successfully'));
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', $e->getMessage());
        }
    }

    /**
     * Duplicate employee benefits from previous month
     */
    private function duplicateEmployeeBenefits(Carbon $periodDate)
    {
        $previousPeriod = $periodDate->copy()->subMonth();
        $previous_month = $previousPeriod->format('m');
        $previous_year  = $previousPeriod->format('Y');
        $month = $periodDate->format('m');
        $year = $periodDate->format('Y');

        $employee_benefits = EmployeeBenefit::with('salary_benefits')
            ->where('month', $previous_month)
            ->where('year', $previous_year)
            ->get();

        foreach ($employee_benefits as $employee_benefit) {
            $new_employee_benefit = $employee_benefit->replicate();
            $new_employee_benefit->month = $month;
            $new_employee_benefit->year = $year;
            $new_employee_benefit->save();

            foreach ($employee_benefit->salary_benefits as $salary_benefit) {
                $new_salary_benefit = $salary_benefit->replicate();
                $new_salary_benefit->employee_benefit_id = $new_employee_benefit->id;
                $new_salary_benefit->save();
            }
        }
    }

    /**
     * Get employees for payroll generation
     */
    private function getEmployeesForPayroll($month, $year)
    {
        return Employee::with([
            'employee_benefits' => function ($q) use ($month, $year) {
                $q->where('month', $month)->where('year', $year);
            },
            'employee_benefits.salary_benefits',
        ])
            ->whereDoesntHave('payslips', function ($query) use ($month, $year) {
                $query->where('month', $month)->where('year', $year);
            })
            ->where(function (Builder $query) {
                $query->whereNull('end_date')->orWhere('end_date', '>', now());
            })
            ->get();
    }

    /**
     * Get business settings for payroll calculations
     */
    private function getBusinessSettings()
    {
        $business = request()->activeBusiness;
        
        return [
            'business' => $business,
            'weekendOption' => function_exists('package') && package()->timesheet_module == 1 
                ? json_decode(get_business_option('weekends'), true) ?: [] 
                : null,
            'overtimeMultiplier' => floatval(get_business_option('overtime_rate_multiplier') ?: 1),
            'publicHolidayMultiplier' => floatval(get_business_option('public_holiday_rate_multiplier') ?: 1),
            'weekendMultiplier' => floatval(get_business_option('weekend_holiday_rate_multiplier') ?: 1),
        ];
    }

    /**
     * Validate employee requirements based on timesheet setting
     */
    private function validateEmployeeRequirements($employee)
    {
        if ($employee->timesheet_based == 1) {
            if (empty($employee->working_hours) || $employee->working_hours == 0) {
                throw new \Exception("Working hours is not set for {$employee->name}");
            }
        }
        // If timesheet_based == 0, working_hours and max_overtime are not required
    }

    /**
     * Generate payroll for a single employee
     */
    private function generateEmployeePayroll($employee, $month, $year, $businessSettings)
    {
        $calculationData = $this->calculateWorkingData($employee, $month, $year, $businessSettings);
        $benefitsData = $this->getEmployeeBenefits($employee);
        
        $payroll = $this->createPayrollRecord($employee, $month, $year, $calculationData, $benefitsData, $businessSettings);
        $payroll->save();
    }

    /**
     * Calculate working data for employee
     */
    private function calculateWorkingData($employee, $month, $year, $businessSettings)
    {
        $required_working_days = cal_days_in_month(CAL_GREGORIAN, $month, $year);
        $public_holidays = 0;
        $weekend = 0;
        $required_working_hours = $employee->working_hours * $required_working_days;
        $actual_working_hours = $required_working_hours;
        $overtime_hours = 0;

        if ($employee->timesheet_based == 1 && package()->timesheet_module == 1) {
            return $this->calculateTimesheetBasedData($employee, $month, $year, $businessSettings);
        }

        return [
            'required_working_days' => $required_working_days,
            'public_holidays' => $public_holidays,
            'weekend' => $weekend,
            'required_working_hours' => $required_working_hours,
            'actual_working_hours' => $actual_working_hours,
            'overtime_hours' => $overtime_hours,
        ];
    }

    /**
     * Calculate timesheet-based working data
     */
    private function calculateTimesheetBasedData($employee, $month, $year, $businessSettings)
    {
        $weekends = array_map('strtolower', $businessSettings['weekendOption'] ?: []);
        $business = $businessSettings['business'];

        $holidays = Holiday::where('business_id', $business->id)
            ->whereMonth('date', $month)
            ->whereYear('date', $year)
            ->get();

        $specificWeekendDays = $this->countSpecificDaysInMonth($year, $month, $businessSettings['weekendOption']);
        $required_working_days = cal_days_in_month(CAL_GREGORIAN, $month, $year) - $holidays->count() - $specificWeekendDays;

        $public_holidays = Timesheet::where('employee_id', $employee->id)
            ->whereIn('date', $holidays->pluck('date'))
            ->sum('working_hours');

        $timesheets = Timesheet::where('employee_id', $employee->id)
            ->whereMonth('date', $month)
            ->whereYear('date', $year)
            ->get();

        $weekend = $timesheets
            ->filter(function ($ts) use ($weekends) {
                $dayName = strtolower(Carbon::createFromFormat(get_date_format(), $ts->date)->format('l'));
                return in_array($dayName, $weekends);
            })
            ->sum('working_hours');

        $required_working_hours = $required_working_days * $employee->working_hours;
        $actual_working_hours = $timesheets->sum('working_hours');

        $maxOvertimePerDay = $employee->max_overtime_hours ?? 0;
        $overtime_hours = min(max($actual_working_hours - $required_working_hours, 0), $maxOvertimePerDay * max($required_working_days, 0));

        return [
            'required_working_days' => $required_working_days,
            'public_holidays' => $public_holidays,
            'weekend' => $weekend,
            'required_working_hours' => $required_working_hours,
            'actual_working_hours' => $actual_working_hours,
            'overtime_hours' => $overtime_hours,
        ];
    }

    /**
     * Get employee benefits and deductions
     */
    private function getEmployeeBenefits($employee)
    {
        $empBenefit = $employee->employee_benefits->first();
        $benefits = collect();
        $deductions = collect();
        $advance = 0;

        if ($empBenefit) {
            $benefits = $empBenefit->salary_benefits()->where('type', 'add')->get();
            $deductions = $empBenefit->salary_benefits()->where('type', 'deduct')->get();
            $advance = $empBenefit->advance ?? 0;
        }

        return [
            'benefits' => $benefits,
            'deductions' => $deductions,
            'advance' => $advance,
            'total_benefits' => $benefits->sum('amount'),
            'total_deduction' => $deductions->sum('amount') + $advance,
        ];
    }

    /**
     * Create payroll record
     */
    private function createPayrollRecord($employee, $month, $year, $calculationData, $benefitsData, $businessSettings)
    {
        // Guard against division by zero
        $cost_base = $calculationData['required_working_hours'] > 0 
            ? ($employee->basic_salary / $calculationData['required_working_hours']) 
            : 0;

        $payroll = new Payroll();
        $payroll->employee_id = $employee->id;
        $payroll->month = $month;
        $payroll->year = $year;
        $payroll->required_working_days = $calculationData['required_working_days'];
        $payroll->public_holiday = $calculationData['public_holidays'];
        $payroll->weekend = $calculationData['weekend'];
        $payroll->required_working_hours = $calculationData['required_working_hours'];
        $payroll->overtime_hours = $calculationData['overtime_hours'];
        $payroll->cost_normal_hours = $cost_base;
        $payroll->cost_overtime_hours = $cost_base * $businessSettings['overtimeMultiplier'];
        $payroll->cost_public_holiday = $cost_base * $businessSettings['publicHolidayMultiplier'];
        $payroll->cost_weekend = $cost_base * $businessSettings['weekendMultiplier'];
        $payroll->total_cost_normal_hours = $payroll->cost_normal_hours * $calculationData['actual_working_hours'];
        $payroll->total_cost_overtime_hours = $payroll->cost_overtime_hours * $calculationData['overtime_hours'];
        $payroll->total_cost_public_holiday = $payroll->cost_public_holiday * $calculationData['public_holidays'];
        $payroll->total_cost_weekend = $payroll->cost_weekend * $calculationData['weekend'];
        $payroll->current_salary = $employee->basic_salary;
        $payroll->net_salary = $payroll->total_cost_normal_hours
            + $payroll->total_cost_overtime_hours
            + $payroll->total_cost_public_holiday
            + $payroll->total_cost_weekend
            + $benefitsData['total_benefits']
            - $benefitsData['total_deduction'];
        $payroll->tax_amount = 0;
        $payroll->total_allowance = $benefitsData['total_benefits'];
        $payroll->total_deduction = $benefitsData['total_deduction'];
        $payroll->absence_fine = 0;
        $payroll->advance = $benefitsData['advance'];
        $payroll->status = 0;

        return $payroll;
    }

    /**
     * Create audit log entry
     */
    private function createAuditLog($employeeCount)
    {
        $audit = new AuditLog();
        $audit->date_changed = now()->format('Y-m-d H:i:s');
        $audit->changed_by = auth()->user()->id;
        $audit->event = 'Generated Payslip for ' . $employeeCount . ' employees';
        $audit->save();
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
        $payroll = Payroll::with('staff', 'staff.department', 'staff.designation')->find($id);
        
        if (!$payroll) {
            abort(404, 'Payroll not found');
        }
        
        $working_days = Attendance::whereMonth('date', $payroll->month)
            ->whereYear('date', $payroll->year)
            ->groupBy('date')->get()->count();

        $absence = Attendance::where('employee_id', $payroll->employee_id)
            ->selectRaw("SUM(CASE WHEN attendance.leave_duration = 'half_day' THEN 0.5 ELSE 1 END) as absence")
            ->whereMonth('date', $payroll->month)
            ->whereYear('date', $payroll->year)
            ->where('attendance.status', 0)
            ->first();
            
        $absence = $absence ? $absence->absence : 0;

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

        if (!$payroll) {
            abort(404, 'Payroll not found');
        }

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
            'payments'             => 'required|array',
            'payments.*.id'        => 'required|integer',
            'payments.*.amount'    => 'required|numeric|min:0',
            'credit_account_id'    => 'required',
            'debit_account_id'     => 'required',
            'advance_account_id'   => 'required',
            'payment_date'         => 'required|date',
        ]);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }

        $currentTime = now();
        $paymentDate = Carbon::parse($request->input('payment_date'))
            ->setTime($currentTime->hour, $currentTime->minute, $currentTime->second);
        $currency = Currency::where('name', request()->activeBusiness->currency)->first();
        $exchange_rate = $currency ? $currency->exchange_rate : 1;

        DB::beginTransaction();
        try {
            $payslipIds = array_column($request->payments, 'id');
            $payslips = Payroll::with('employee')->whereIn('id', $payslipIds)->get();
            $payslipMap = $payslips->keyBy('id');

            foreach ($request->payments as $payment) {
                if (!isset($payslipMap[$payment['id']])) {
                    continue;
                }
                $payslip = $payslipMap[$payment['id']];

                if ($payslip->status == 4) {
                    continue;
                }

                $amount = floatval($payment['amount']);
                $payslip->paid = ($payslip->paid ?? 0) + $amount;

                // Determine status
                if ($payslip->paid >= $payslip->net_salary) {
                    $payslip->status = 4;
                } else {
                    $payslip->status = 3;
                }

                $payslip->save();

                // Credit transaction
                $creditTx = new Transaction();
                $creditTx->trans_date = $paymentDate->format('Y-m-d H:i');
                $creditTx->account_id = $request->credit_account_id;
                $creditTx->dr_cr = 'cr';
                $creditTx->transaction_amount = $amount;
                $creditTx->currency_rate = $exchange_rate;
                $creditTx->base_currency_amount = $amount;
                $creditTx->description = _lang('Staff Salary Payment ' . $payslip->month . '/' . $payslip->year) . ' - ' . $payslip->employee->name;
                $creditTx->ref_id = $payslip->employee_id;
                $creditTx->ref_type = 'payslip';
                $creditTx->employee_id = $payslip->employee_id;
                $creditTx->save();

                $payslip->transaction_id = $creditTx->id;
                $payslip->save();

                // If fully paid, create debit and deduction/advance transactions
                if ($payslip->status == 4) {
                    // Debit main salary
                    $debitTx = new Transaction();
                    $debitTx->trans_date = $paymentDate->format('Y-m-d H:i');
                    $debitTx->account_id = $request->debit_account_id;
                    $debitTx->dr_cr = 'dr';
                    $debitTx->transaction_amount = $payslip->current_salary;
                    $debitTx->currency_rate = $exchange_rate;
                    $debitTx->base_currency_amount = $payslip->current_salary;
                    $debitTx->description = _lang('Staff Salary ' . $payslip->month . '/' . $payslip->year) . ' - ' . $payslip->employee->name;
                    $debitTx->ref_id = $payslip->employee_id;
                    $debitTx->ref_type = 'payslip';
                    $debitTx->employee_id = $payslip->employee_id;
                    $debitTx->save();

                    // deductions with specific accounts
                    $deductions = SalaryBenefit::whereHas('employee_benefit', function ($query) use ($payslip) {
                        $query->where('employee_id', $payslip->employee_id)
                            ->where('month', $payslip->month)
                            ->where('year', $payslip->year);
                    })->where('type', 'deduct')
                        ->whereNotNull('account_id')
                        ->get();

                    foreach ($deductions as $deduction) {
                        $deductTx = new Transaction();
                        $deductTx->trans_date = $paymentDate->format('Y-m-d H:i');
                        $deductTx->account_id = $deduction->account_id;
                        $deductTx->dr_cr = 'cr';
                        $deductTx->transaction_amount = $deduction->amount;
                        $deductTx->currency_rate = $exchange_rate;
                        $deductTx->base_currency_amount = $deduction->amount;
                        $deductTx->description = _lang('Payroll Deduction: ' . $deduction->description . ' - ' . $payslip->month . '/' . $payslip->year) . ' - ' . $payslip->employee->name;
                        $deductTx->ref_id = $payslip->employee_id;
                        $deductTx->ref_type = 'payslip_deduction';
                        $deductTx->employee_id = $payslip->employee_id;
                        $deductTx->save();
                    }

                    // advance if any
                    if ($payslip->advance > 0) {
                        $advanceTx = new Transaction();
                        $advanceTx->trans_date = $paymentDate->format('Y-m-d H:i');
                        $advanceTx->account_id = $request->advance_account_id;
                        $advanceTx->dr_cr = 'cr';
                        $advanceTx->transaction_amount = $payslip->advance;
                        $advanceTx->currency_rate = $exchange_rate;
                        $advanceTx->base_currency_amount = $payslip->advance;
                        $advanceTx->description = _lang('Staff Salary Advance ' . $payslip->month . '/' . $payslip->year) . ' - ' . $payslip->employee->name;
                        $advanceTx->ref_id = $payslip->employee_id;
                        $advanceTx->ref_type = 'payslip';
                        $advanceTx->employee_id = $payslip->employee_id;
                        $advanceTx->save();
                    }
                }
            }

            // audit log
            $audit = new AuditLog();
            $audit->date_changed = now()->format('Y-m-d H:i:s');
            $audit->changed_by = auth()->user()->id;
            $audit->event = 'Processed Payslip Payment for ' . count($payslips ?? []) . ' employees';
            $audit->save();

            DB::commit();

            return redirect()->back()->with('success', _lang('Payment Processed Successfully'));
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->with('error', $e->getMessage());
        }
    }

    public function bulk_accrue(Request $request)
    {
        if (empty($request->ids) || !is_array($request->ids)) {
            return back()->with('error', _lang('You must select at least one employee'))->withInput();
        }

        $validator = Validator::make($request->all(), [
            'liability_account_id' => 'required',
            'expense_account_id'   => 'required',
            'accrue_date'          => 'required|date',
        ], [
            'liability_account_id.required' => 'You must select debit account',
            'expense_account_id.required'   => 'Expense account is required',
            'accrue_date.required'          => 'Accrue date is required',
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        DB::beginTransaction();
        try {
            $currency = Currency::where('name', request()->activeBusiness->currency)->first();
            $exchange_rate = $currency ? $currency->exchange_rate : 1;

            $payslips = Payroll::with('employee')
                ->whereIn('id', $request->ids)
                ->where('status', 1)
                ->get();

            if ($payslips->isEmpty()) {
                DB::rollBack();
                return back()->with('error', _lang('No active payslips found'));
            }

            foreach ($payslips as $payslip) {
                // Credit liability
                $liabilityTx = new Transaction();
                $liabilityTx->trans_date = Carbon::parse($request->accrue_date)->format('Y-m-d H:i:s');
                $liabilityTx->account_id = $request->liability_account_id;
                $liabilityTx->transaction_method = $request->method ?? null;
                $liabilityTx->dr_cr = 'cr';
                $liabilityTx->transaction_amount = $payslip->net_salary;
                $liabilityTx->currency_rate = $exchange_rate;
                $liabilityTx->base_currency_amount = $payslip->net_salary;
                $liabilityTx->description = _lang('Staff Salary ' . $payslip->month . '/' . $payslips->first()->year) . ' - ' . $payslip->employee->name;
                $liabilityTx->ref_id = $payslip->employee_id;
                $liabilityTx->ref_type = 'payslip';
                $liabilityTx->employee_id = $payslip->employee_id;
                $liabilityTx->save();

                // Debit expense
                $expenseAmount = $payslip->employee->basic_salary + $payslip->total_allowance - $payslip->total_deduction;
                $expenseTx = new Transaction();
                $expenseTx->trans_date = Carbon::parse($request->accrue_date)->format('Y-m-d H:i:s');
                $expenseTx->account_id = $request->expense_account_id;
                $expenseTx->dr_cr = 'dr';
                $expenseTx->transaction_amount = $expenseAmount;
                $expenseTx->currency_rate = $exchange_rate;
                $expenseTx->base_currency_amount = $expenseAmount;
                $expenseTx->description = _lang('Staff Salary Expense ' . $payslip->month . ' ' . $payslips->first()->year) . ' - ' . $payslip->employee->name;
                $expenseTx->ref_id = $payslip->employee_id;
                $expenseTx->ref_type = 'payslip';
                $expenseTx->employee_id = $payslip->employee_id;
                $expenseTx->save();

                $payslip->status = 2;
                $payslip->transaction_id = $expenseTx->id;
                $payslip->save();
            }

            // audit log
            $audit = new AuditLog();
            $audit->date_changed = now()->format('Y-m-d H:i:s');
            $audit->changed_by = auth()->user()->id;
            $audit->event = 'Accruement made for ' . count($payslips) . ' employees';
            $audit->save();

            DB::commit();

            return redirect()->route('payslips.index')->with('success', _lang('Accruement made successfully'));
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', $e->getMessage());
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
        $payroll = Payroll::with('employee')->find($id);

        if (!$payroll) {
            return redirect()->back()->with('error', _lang('Payslip not found'));
        }

        DB::beginTransaction();
        try {
            // Delete related transactions
            Transaction::where('ref_id', $payroll->employee_id)
                ->whereIn('ref_type', ['payslip', 'payslip_deduction'])
                ->where('description', 'like', '%' . $payroll->month . '/' . $payroll->year . '%')
                ->delete();

            // Delete the employee benefit for this employee/month/year and its salary benefits
            $employeeBenefit = EmployeeBenefit::with('salary_benefits')
                ->where('employee_id', $payroll->employee_id)
                ->where('month', $payroll->month)
                ->where('year', $payroll->year)
                ->first();

            if ($employeeBenefit) {
                // Delete salary benefits first if not using cascade
                foreach ($employeeBenefit->salary_benefits as $sb) {
                    $sb->delete();
                }
                $employeeBenefit->delete();
            }

            // Audit log
            $audit = new AuditLog();
            $audit->date_changed = now()->format('Y-m-d H:i:s');
            $audit->changed_by = auth()->user()->id;
            $audit->event = 'Deleted Payslip and related benefits for ' . $payroll->employee->name . " ({$payroll->month}/{$payroll->year})";
            $audit->save();

            // Delete payroll
            $payroll->delete();

            DB::commit();
            return redirect()->back()->with('success', _lang('Deleted Successfully'));
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->with('error', $e->getMessage());
        }
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

    /**
     * Display a listing of trashed payrolls.
     *
     * @return \Illuminate\Http\Response
     */
    public function trash(Request $request)
    {
        $month = $request->month ?? date('m');
        $year = $request->year ?? date('Y');
        $search = $request->search;
        $per_page = $request->per_page ?? 50;

        // Store month and year in session
        session(['month' => $month, 'year' => $year]);

        $query = Payroll::onlyTrashed()
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
            'total_payrolls' => $payrolls->total(),
            'total_salary' => $payrolls->sum('current_salary'),
            'total_net_salary' => $payrolls->sum('net_salary'),
            'total_tax' => $payrolls->sum('tax_amount'),
            'total_allowance' => $payrolls->sum('total_allowance'),
            'total_deduction' => $payrolls->sum('total_deduction'),
        ];

        $years = range(date('Y') - 5, date('Y') + 1);
        $months = [
            1 => 'January', 2 => 'February', 3 => 'March', 4 => 'April',
            5 => 'May', 6 => 'June', 7 => 'July', 8 => 'August',
            9 => 'September', 10 => 'October', 11 => 'November', 12 => 'December'
        ];

        return Inertia::render('Backend/User/Payroll/Trash', [
            'payrolls' => $payrolls->items(),
            'meta' => [
                'current_page' => $payrolls->currentPage(),
                'per_page' => $payrolls->perPage(),
                'last_page' => $payrolls->lastPage(),
                'total' => $payrolls->total(),
            ],
            'filters' => [
                'search' => $search,
                'month' => $month,
                'year' => $year,
                'sorting' => $sorting,
            ],
            'totals' => $totals,
            'years' => $years,
            'months' => $months,
            'year' => $year,
            'month' => $month,
        ]);
    }

    /**
     * Restore a soft deleted payroll.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function restore($id)
    {
        $payroll = Payroll::onlyTrashed()->find($id);

        if (!$payroll) {
            return redirect()->back()->with('error', _lang('Payslip not found'));
        }

        DB::beginTransaction();
        try {
            // Restore related transactions
            Transaction::onlyTrashed()
                ->where('ref_id', $payroll->employee_id)
                ->whereIn('ref_type', ['payslip', 'payslip_deduction'])
                ->where('description', 'like', '%' . $payroll->month . '/' . $payroll->year . '%')
                ->restore();

            // Restore the employee benefit for this employee/month/year and its salary benefits
            $employeeBenefit = EmployeeBenefit::onlyTrashed()
                ->with('salary_benefits')
                ->where('employee_id', $payroll->employee_id)
                ->where('month', $payroll->month)
                ->where('year', $payroll->year)
                ->first();

            if ($employeeBenefit) {
                // Restore salary benefits first
                foreach ($employeeBenefit->salary_benefits as $sb) {
                    $sb->restore();
                }
                $employeeBenefit->restore();
            }

            // Audit log
            $audit = new AuditLog();
            $audit->date_changed = now()->format('Y-m-d H:i:s');
            $audit->changed_by = auth()->user()->id;
            $audit->event = 'Restored Payslip and related benefits for ' . $payroll->employee->name . " ({$payroll->month}/{$payroll->year})";
            $audit->save();

            // Restore payroll
            $payroll->restore();

            DB::commit();
            return redirect()->back()->with('success', _lang('Restored Successfully'));
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->with('error', $e->getMessage());
        }
    }

    /**
     * Bulk restore soft deleted payrolls.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function bulk_restore(Request $request)
    {
        if ($request->ids == null) {
            return redirect()->back()->with('error', _lang('Please Select Payslip'));
        }

        $payslips = Payroll::onlyTrashed()->whereIn('id', $request->ids)->get();

        // audit log
        $audit = new AuditLog();
        $audit->date_changed = date('Y-m-d H:i:s');
        $audit->changed_by = auth()->user()->id;
        $audit->event = 'Restored Payslip for ' . count($payslips) . ' employees';
        $audit->save();

        foreach ($payslips as $payslip) {
            // restore transactions
            $transactions = Transaction::onlyTrashed()
                ->where('ref_id', $payslip->employee_id)
                ->where('ref_type', 'payslip')
                ->where('description', 'like', '%' . $payslip->month . '/' . $payslip->year . '%')
                ->get();
            foreach ($transactions as $transaction) {
                $transaction->restore();
            }
            $payslip->restore();
        }

        return redirect()->back()->with('success', _lang('Restored Successfully'));
    }

    /**
     * Permanently delete a soft deleted payroll.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function permanent_destroy($id)
    {
        $payroll = Payroll::onlyTrashed()->find($id);

        if (!$payroll) {
            return redirect()->back()->with('error', _lang('Payslip not found'));
        }

        DB::beginTransaction();
        try {
            // Permanently delete related transactions
            Transaction::onlyTrashed()
                ->where('ref_id', $payroll->employee_id)
                ->whereIn('ref_type', ['payslip', 'payslip_deduction'])
                ->where('description', 'like', '%' . $payroll->month . '/' . $payroll->year . '%')
                ->forceDelete();

            // Permanently delete the employee benefit for this employee/month/year and its salary benefits
            $employeeBenefit = EmployeeBenefit::onlyTrashed()
                ->with('salary_benefits')
                ->where('employee_id', $payroll->employee_id)
                ->where('month', $payroll->month)
                ->where('year', $payroll->year)
                ->first();

            if ($employeeBenefit) {
                // Permanently delete salary benefits first
                foreach ($employeeBenefit->salary_benefits as $sb) {
                    $sb->forceDelete();
                }
                $employeeBenefit->forceDelete();
            }

            // Audit log
            $audit = new AuditLog();
            $audit->date_changed = now()->format('Y-m-d H:i:s');
            $audit->changed_by = auth()->user()->id;
            $audit->event = 'Permanently Deleted Payslip and related benefits for ' . $payroll->employee->name . " ({$payroll->month}/{$payroll->year})";
            $audit->save();

            // Permanently delete payroll
            $payroll->forceDelete();

            DB::commit();
            return redirect()->back()->with('success', _lang('Permanently Deleted Successfully'));
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->with('error', $e->getMessage());
        }
    }

    /**
     * Bulk permanently delete soft deleted payrolls.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function bulk_permanent_destroy(Request $request)
    {
        if ($request->ids == null) {
            return redirect()->back()->with('error', _lang('Please Select Payslip'));
        }

        $payslips = Payroll::onlyTrashed()->whereIn('id', $request->ids)->get();

        // audit log
        $audit = new AuditLog();
        $audit->date_changed = date('Y-m-d H:i:s');
        $audit->changed_by = auth()->user()->id;
        $audit->event = 'Permanently Deleted Payslip for ' . count($payslips) . ' employees';
        $audit->save();

        foreach ($payslips as $payslip) {
            // permanently delete transactions
            $transactions = Transaction::onlyTrashed()
                ->where('ref_id', $payslip->employee_id)
                ->where('ref_type', 'payslip')
                ->where('description', 'like', '%' . $payslip->month . '/' . $payslip->year . '%')
                ->get();
            foreach ($transactions as $transaction) {
                $transaction->forceDelete();
            }
            $payslip->forceDelete();
        }

        return redirect()->back()->with('success', _lang('Permanently Deleted Successfully'));
    }
}
