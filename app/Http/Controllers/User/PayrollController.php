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
use App\Models\Holiday;
use App\Models\Payroll;
use App\Models\SalaryAdvance;
use App\Models\SalaryBenefit;
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
            ->with('staff', 'staff.salary_benefits');

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

        DB::beginTransaction();
        // Get employees for payroll generation
        $employees = $this->getEmployeesForPayroll($month, $year);

        if ($employees->isEmpty()) {
            DB::rollBack();
            return back()->with('error', _lang('Payslip is already generated for the selected period !'));
        }

        // Get business settings
        $businessSettings = $this->getBusinessSettings();

        foreach ($employees as $employee) {
            $this->validateEmployeeRequirements($employee);
            $this->generateEmployeePayroll($employee, $month, $year, $businessSettings);
        }

        // Create audit log
        $this->createAuditLog(count($employees));

        DB::commit();
        return redirect()->back()->with('success', _lang('Payslip Generated Successfully'));
    }

    /**
     * Duplicate employee benefits from previous month
     */

    /**
     * Get employees for payroll generation
     */
    private function getEmployeesForPayroll($month, $year)
    {
        return Employee::with([
            'salary_benefits' => function ($q) use ($month, $year) {
                $q->where('month', $month)->where('year', $year);
            },
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
            'weekendOption' => (function_exists('package') && package()->timesheet_module == 1)
                ? (json_decode(get_business_option('weekends'), true) ?: [])
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
        $benefitsData = $this->getSalaryBenefits($employee, $month, $year);

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
    private function getSalaryBenefits($employee, $month, $year)
    {
        $benefits = $employee->salary_benefits()
            ->where('month', $month)
            ->where('year', $year)
            ->where('type', 'add')->get();
        $deductions = $employee->salary_benefits()
            ->where('month', $month)
            ->where('year', $year)
            ->where('type', 'deduct')->get();

        if (SalaryAdvance::where('employee_id', $employee->id)->where('payroll_month', $month)->where('payroll_year', $year)->sum('amount') > 0) {
            $salary_benefit = new SalaryBenefit;
            $salary_benefit->employee_id = $employee->id;
            $salary_benefit->month = $month;
            $salary_benefit->year = $year;
            $salary_benefit->description = 'Salary Advance for ' . $month . '/' . $year;
            $salary_benefit->amount = SalaryAdvance::where('employee_id', $employee->id)->where('payroll_month', $month)->where('payroll_year', $year)->sum('amount');
            $salary_benefit->type = 'advance';
            $salary_benefit->save();
        }

        $advance = $employee->salary_benefits()
            ->where('month', $month)
            ->where('year', $year)
            ->where('type', 'advance')->sum('amount') ?? 0;

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

        $benefits = $payroll->employee->salary_benefits()
            ->where('month', $payroll->month)
            ->where('year', $payroll->year)
            ->get();

        foreach ($benefits as $benefit) {
            if ($benefit->type == 'add') {
                $allowances[] = [
                    'description' => $benefit->description,
                    'amount' => $benefit->amount
                ];
            } elseif ($benefit->type == 'deduct') {
                $deductions[] = [
                    'description' => $benefit->description,
                    'amount' => $benefit->amount
                ];
            }
        }

        if (SalaryAdvance::where('employee_id', $payroll->employee_id)->where('payroll_month', $payroll->month)->where('payroll_year', $payroll->year)->sum('amount') > 0) {
            $salary_benefit = new SalaryBenefit;
            $salary_benefit->employee_id = $payroll->employee_id;
            $salary_benefit->month = $payroll->month;
            $salary_benefit->year = $payroll->year;
            $salary_benefit->description = 'Salary Advance for ' . $payroll->month . '/' . $payroll->year;
            $salary_benefit->amount = SalaryAdvance::where('employee_id', $payroll->employee_id)->where('payroll_month', $payroll->month)->where('payroll_year', $payroll->year)->sum('amount');
            $salary_benefit->type = 'advance';
            $salary_benefit->save();
        }

        $advance = $payroll->employee->salary_benefits()
            ->where('month', $payroll->month)
            ->where('year', $payroll->year)
            ->where('type', 'advance')
            ->sum('amount');

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

        // Delete the employee benefit for this employee/month/year and its salary benefits
        $salaryBenefit = SalaryBenefit::where('employee_id', $payroll->employee_id)
            ->where('month', $payroll->month)
            ->where('year', $payroll->year)
            ->get();

        if (!empty($salaryBenefit)) {
            // Delete salary benefits first if not using cascade
            foreach ($salaryBenefit as $eb) {
                $eb->forceDelete();
            }
        }

        if (isset($request->advance) && $request->advance > 0) {
            $salaryBenefit = new SalaryBenefit;
            $salaryBenefit->employee_id = $payroll->employee_id;
            $salaryBenefit->month = $payroll->month;
            $salaryBenefit->year = $payroll->year;
            $salaryBenefit->description = $request->advance_description;
            $salaryBenefit->amount = $request->advance;
            $salaryBenefit->type = 'advance';
            $salaryBenefit->save();
        }

        $benefits = 0;
        if (isset($request->allowances)) {
            for ($i = 0; $i < count($request->allowances); $i++) {
                if (!empty($request->allowances[$i]['amount'])) {
                    $salary_benefit = new SalaryBenefit;
                    $salary_benefit->employee_id = $payroll->employee_id;
                    $salary_benefit->month = $payroll->month;
                    $salary_benefit->year = $payroll->year;
                    $salary_benefit->description = $request->allowances[$i]['description'];
                    $salary_benefit->amount = $request->allowances[$i]['amount'];
                    $salary_benefit->type = 'add';
                    $salary_benefit->save();

                    $benefits += $request->allowances[$i]['amount'];
                }
            }
        }

        $deductions = 0;
        if (isset($request->deductions)) {
            for ($i = 0; $i < count($request->deductions); $i++) {
                if (!empty($request->deductions[$i]['amount'])) {
                    $salary_benefit = new SalaryBenefit;
                    $salary_benefit->employee_id = $payroll->employee_id;
                    $salary_benefit->month = $payroll->month;
                    $salary_benefit->year = $payroll->year;
                    $salary_benefit->description = $request->deductions[$i]['description'];
                    $salary_benefit->amount = $request->deductions[$i]['amount'];
                    $salary_benefit->type = 'deduct';
                    $salary_benefit->account_id = $request->deductions[$i]['account_id'];
                    $salary_benefit->save();

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

        return redirect()->back()->with('success', _lang('Updated Successfully'));
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

                // Only process payslips with status 1 (approved), 2 (accrued), or 3 (partially paid)
                if (!in_array($payslip->status, [1, 2, 3])) {
                    continue;
                }

                // Store original status to check if it was accrued
                // Check if this payslip was accrued by looking for existing liability transactions
                $wasAccrued = ($payslip->status == 2) || 
                    Transaction::where('ref_id', $payslip->employee_id)
                        ->where('ref_type', 'payslip')
                        ->where('description', 'like', '%' . $payslip->month . '/' . $payslip->year . '%')
                        ->where('dr_cr', 'cr')
                        ->whereHas('account', function($query) {
                            // Check if this is a liability account transaction (typical for accruals)
                            $query->where('account_type', 'like', '%Liability%');
                        })
                        ->exists();

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

                // If fully paid, create debit transactions
                if ($payslip->status == 4) {
                    if ($wasAccrued) {
                        // If payslip was already accrued (status = 2), deductions and advances were already posted
                        // Only post the net salary debit
                        $debitTx = new Transaction();
                        $debitTx->trans_date = $paymentDate->format('Y-m-d H:i');
                        $debitTx->account_id = $request->debit_account_id;
                        $debitTx->dr_cr = 'dr';
                        $debitTx->transaction_amount = $payslip->net_salary;
                        $debitTx->currency_rate = $exchange_rate;
                        $debitTx->base_currency_amount = $payslip->net_salary;
                        $debitTx->description = _lang('Staff Salary ' . $payslip->month . '/' . $payslip->year) . ' - ' . $payslip->employee->name;
                        $debitTx->ref_id = $payslip->employee_id;
                        $debitTx->ref_type = 'payslip';
                        $debitTx->employee_id = $payslip->employee_id;
                        $debitTx->save();
                    } else {
                        // Payslip was not accrued, post full accounting entries
                        // Get deductions with specific accounts
                        $deductions = SalaryBenefit::where('employee_id', $payslip->employee_id)
                            ->where('month', $payslip->month)
                            ->where('year', $payslip->year)
                            ->where('type', 'deduct')
                            ->whereNotNull('account_id')
                            ->get();

                        $totalDeductions = $deductions->sum('amount');
                        $hasAdvance = $payslip->advance > 0;
                        $hasDeductionsOrAdvance = $totalDeductions > 0 || $hasAdvance;

                        if ($hasDeductionsOrAdvance) {
                            // If deductions or advance exist: Dr Current Salary, Cr Net Salary, Cr Deduction/Advance Accounts

                            // Debit current salary
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

                            // Credit deduction accounts
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
                                $deductTx->ref_type = 'payslip deduction';
                                $deductTx->employee_id = $payslip->employee_id;
                                $deductTx->save();
                            }

                            // Credit advance account if any
                            if ($hasAdvance) {
                                $advanceTx = new Transaction();
                                $advanceTx->trans_date = $paymentDate->format('Y-m-d H:i');
                                $advanceTx->account_id = $request->advance_account_id;
                                $advanceTx->dr_cr = 'cr';
                                $advanceTx->transaction_amount = $payslip->advance;
                                $advanceTx->currency_rate = $exchange_rate;
                                $advanceTx->base_currency_amount = $payslip->advance;
                                $advanceTx->description = _lang('Staff Salary Advance ' . $payslip->month . '/' . $payslip->year) . ' - ' . $payslip->employee->name;
                                $advanceTx->ref_id = $payslip->employee_id;
                                $advanceTx->ref_type = 'payslip advance';
                                $advanceTx->employee_id = $payslip->employee_id;
                                $advanceTx->save();
                            }
                        } else {
                            // Debit net salary 
                            $debitTx = new Transaction();
                            $debitTx->trans_date = $paymentDate->format('Y-m-d H:i');
                            $debitTx->account_id = $request->debit_account_id;
                            $debitTx->dr_cr = 'dr';
                            $debitTx->transaction_amount = $payslip->net_salary;
                            $debitTx->currency_rate = $exchange_rate;
                            $debitTx->base_currency_amount = $payslip->net_salary;
                            $debitTx->description = _lang('Staff Salary ' . $payslip->month . '/' . $payslip->year) . ' - ' . $payslip->employee->name;
                            $debitTx->ref_id = $payslip->employee_id;
                            $debitTx->ref_type = 'payslip';
                            $debitTx->employee_id = $payslip->employee_id;
                            $debitTx->save();

                            // Note: Net salary credit is already handled above in the main credit transaction
                        }
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
            'advance_account_id'   => 'required',
            'accrue_date'          => 'required|date',
        ], [
            'liability_account_id.required' => 'You must select debit account',
            'expense_account_id.required'   => 'Expense account is required',
            'advance_account_id.required'   => 'Advance account is required',
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
                // Get deductions with specific accounts
                $deductions = SalaryBenefit::where('employee_id', $payslip->employee_id)
                    ->where('month', $payslip->month)
                    ->where('year', $payslip->year)
                    ->where('type', 'deduct')
                    ->whereNotNull('account_id')
                    ->get();

                $totalDeductions = $deductions->sum('amount');
                $hasAdvance = $payslip->advance > 0;
                $hasDeductionsOrAdvance = $totalDeductions > 0 || $hasAdvance;

                // Credit liability (net salary)
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

                if ($hasDeductionsOrAdvance) {
                    // Debit expense (current salary)
                    $expenseTx = new Transaction();
                    $expenseTx->trans_date = Carbon::parse($request->accrue_date)->format('Y-m-d H:i:s');
                    $expenseTx->account_id = $request->expense_account_id;
                    $expenseTx->dr_cr = 'dr';
                    $expenseTx->transaction_amount = $payslip->current_salary;
                    $expenseTx->currency_rate = $exchange_rate;
                    $expenseTx->base_currency_amount = $payslip->current_salary;
                    $expenseTx->description = _lang('Staff Salary Expense ' . $payslip->month . ' ' . $payslips->first()->year) . ' - ' . $payslip->employee->name;
                    $expenseTx->ref_id = $payslip->employee_id;
                    $expenseTx->ref_type = 'payslip';
                    $expenseTx->employee_id = $payslip->employee_id;
                    $expenseTx->save();

                    // Credit deduction accounts
                    foreach ($deductions as $deduction) {
                        $deductTx = new Transaction();
                        $deductTx->trans_date = Carbon::parse($request->accrue_date)->format('Y-m-d H:i:s');
                        $deductTx->account_id = $deduction->account_id;
                        $deductTx->dr_cr = 'cr';
                        $deductTx->transaction_amount = $deduction->amount;
                        $deductTx->currency_rate = $exchange_rate;
                        $deductTx->base_currency_amount = $deduction->amount;
                        $deductTx->description = _lang('Payroll Deduction: ' . $deduction->description . ' - ' . $payslip->month . '/' . $payslip->year) . ' - ' . $payslip->employee->name;
                        $deductTx->ref_id = $payslip->employee_id;
                        $deductTx->ref_type = 'payslip deduction';
                        $deductTx->employee_id = $payslip->employee_id;
                        $deductTx->save();
                    }

                    // Credit advance account if any
                    if ($hasAdvance) {
                        $advanceTx = new Transaction();
                        $advanceTx->trans_date = Carbon::parse($request->accrue_date)->format('Y-m-d H:i:s');
                        $advanceTx->account_id = $request->advance_account_id;
                        $advanceTx->dr_cr = 'cr';
                        $advanceTx->transaction_amount = $payslip->advance;
                        $advanceTx->currency_rate = $exchange_rate;
                        $advanceTx->base_currency_amount = $payslip->advance;
                        $advanceTx->description = _lang('Staff Salary Advance ' . $payslip->month . '/' . $payslip->year) . ' - ' . $payslip->employee->name;
                        $advanceTx->ref_id = $payslip->employee_id;
                        $advanceTx->ref_type = 'payslip advance';
                        $advanceTx->employee_id = $payslip->employee_id;
                        $advanceTx->save();
                    }
                } else {
                    // Debit expense (net salary)
                    $expenseTx = new Transaction();
                    $expenseTx->trans_date = Carbon::parse($request->accrue_date)->format('Y-m-d H:i:s');
                    $expenseTx->account_id = $request->expense_account_id;
                    $expenseTx->dr_cr = 'dr';
                    $expenseTx->transaction_amount = $payslip->net_salary;
                    $expenseTx->currency_rate = $exchange_rate;
                    $expenseTx->base_currency_amount = $payslip->net_salary;
                    $expenseTx->description = _lang('Staff Salary Expense ' . $payslip->month . ' ' . $payslips->first()->year) . ' - ' . $payslip->employee->name;
                    $expenseTx->ref_id = $payslip->employee_id;
                    $expenseTx->ref_type = 'payslip';
                    $expenseTx->employee_id = $payslip->employee_id;
                    $expenseTx->save();
                }

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
                ->where(function($query) {
                    $query->where('ref_type', 'payslip')
                        ->orWhere('ref_type', 'payslip advance')
                        ->orWhere('ref_type', 'payslip deduction');
                })
                ->where(function($query) use ($payroll) {
                    $query->where('description', 'like', '%' . $payroll->month . '/' . $payroll->year . '%')
                        ->orWhere('description', 'like', '%' . $payroll->month . ' ' . $payroll->year . '%');
                })
                ->delete();

            $salaryBenefit = SalaryBenefit::where('employee_id', $payroll->employee_id)
                ->where('month', $payroll->month)
                ->where('year', $payroll->year)
                ->get();

            foreach ($salaryBenefit as $sb) {
                $sb->delete();
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
            $transactions = Transaction::where('ref_id', $payslip->employee_id)
            ->where(function($query) {
                $query->where('ref_type', 'payslip')
                    ->orWhere('ref_type', 'payslip advance')
                    ->orWhere('ref_type', 'payslip deduction');
            })
            ->where(function($query) use ($payslip) {
                $query->where('description', 'like', '%' . $payslip->month . '/' . $payslip->year . '%')
                    ->orWhere('description', 'like', '%' . $payslip->month . ' ' . $payslip->year . '%');
            })
            ->get();
            foreach ($transactions as $transaction) {
                $transaction->delete();
            }

            $salaryBenefit = SalaryBenefit::where('employee_id', $payslip->employee_id)
                ->where('month', $payslip->month)
                ->where('year', $payslip->year)
                ->get();

            foreach ($salaryBenefit as $sb) {
                $sb->delete();
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
            1 => 'January',
            2 => 'February',
            3 => 'March',
            4 => 'April',
            5 => 'May',
            6 => 'June',
            7 => 'July',
            8 => 'August',
            9 => 'September',
            10 => 'October',
            11 => 'November',
            12 => 'December'
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

            $salaryBenefit = SalaryBenefit::onlyTrashed()
                ->where('employee_id', $payroll->employee_id)
                ->where('month', $payroll->month)
                ->where('year', $payroll->year)
                ->first();

            if (!empty($salaryBenefit)) {
                foreach ($salaryBenefit as $sb) {
                    $sb->restore();
                }
                $salaryBenefit->restore();
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
                ->where(function($query) {
                    $query->where('ref_type', 'payslip')
                        ->orWhere('ref_type', 'payslip advance')
                        ->orWhere('ref_type', 'payslip deduction');
                })
                ->where(function($query) use ($payroll) {
                    $query->where('description', 'like', '%' . $payroll->month . '/' . $payroll->year . '%')
                        ->orWhere('description', 'like', '%' . $payroll->month . ' ' . $payroll->year . '%');
                })
                ->forceDelete();

            // Permanently delete the employee benefit for this employee/month/year and its salary benefits
            $salaryBenefit = SalaryBenefit::onlyTrashed()
                ->where('employee_id', $payroll->employee_id)
                ->where('month', $payroll->month)
                ->where('year', $payroll->year)
                ->get();

            foreach ($salaryBenefit as $sb) {
                $sb->forceDelete();
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
                ->where(function($query) {
                    $query->where('ref_type', 'payslip')
                        ->orWhere('ref_type', 'payslip advance')
                        ->orWhere('ref_type', 'payslip deduction');
                })
                ->where(function($query) use ($payslip) {
                    $query->where('description', 'like', '%' . $payslip->month . '/' . $payslip->year . '%')
                        ->orWhere('description', 'like', '%' . $payslip->month . ' ' . $payslip->year . '%');
                })
                ->get();
            foreach ($transactions as $transaction) {
                $transaction->forceDelete();
            }

            $salaryBenefit = SalaryBenefit::onlyTrashed()
                ->where('employee_id', $payslip->employee_id)
                ->where('month', $payslip->month)
                ->where('year', $payslip->year)
                ->get();

            foreach ($salaryBenefit as $sb) {
                $sb->forceDelete();
            }

            $payslip->forceDelete();
        }

        return redirect()->back()->with('success', _lang('Permanently Deleted Successfully'));
    }
}
