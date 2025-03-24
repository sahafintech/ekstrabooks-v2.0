<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Imports\AttendanceImport;
use App\Models\AbsentFine;
use App\Models\Attendance;
use App\Models\AuditLog;
use App\Models\Employee;
use App\Models\Holiday;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Maatwebsite\Excel\Facades\Excel;

class AttendanceController extends Controller
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
		$per_page = $request->input('per_page', 10);
		$search = $request->input('search', '');

		$query = Attendance::select([
			'attendance.date',
			DB::raw("COUNT(attendance.status) AS total_attendance"),
			DB::raw("SUM(CASE WHEN attendance.status = '0' THEN 1 ELSE 0 END) as absent"),
			DB::raw("SUM(CASE WHEN attendance.status = '1' THEN 1 ELSE 0 END) as present"),
			DB::raw("SUM(CASE WHEN attendance.status = '2' THEN 1 ELSE 0 END) as leaves"),
		])
			->when($search, function ($q) use ($search) {
				return $q->whereDate('attendance.date', 'like', "%$search%");
			})
			->groupBy('attendance.date');

		$attendances = $query->paginate($per_page);

		$filters = [
			'search' => $search,
		];

		return Inertia::render('Backend/User/Attendance/List', [
			'attendances' => $attendances->items(),
			'meta' => [
				'current_page' => $attendances->currentPage(),
				'last_page' => $attendances->lastPage(),
				'per_page' => $attendances->perPage(),
				'total' => $attendances->total(),
				'from' => $attendances->firstItem(),
				'to' => $attendances->lastItem(),
			],
			'filters' => $filters,
		]);
	}

	/**
	 * Show the form for creating a new resource.
	 *
	 * @return \Illuminate\Http\Response
	 */
	public function create(Request $request)
	{
		if ($request->isMethod('get')) {
			$date = $request->date;

			if ($date) {
				$weekends = json_decode(get_business_option('weekends', '[]', business_id()));
				$message = null;
				
				if (in_array(date('l', strtotime($date)), $weekends)) {
					$message = _lang('The date you selected which is a weekend !');
				}

				$holiday = Holiday::whereDate('date', $date)->first();
				if ($holiday) {
					$message = _lang('The date you selected which is a holiday !');
				}

				$employees = Employee::select('employees.*', DB::raw('IFNULL(attendance.status, 1) as attendance_status'), 'attendance.remarks as attendance_remarks', 'attendance.leave_duration as attendance_leave_duration', 'leaves.leave_type', 'leaves.leave_duration', 'leaves.description as leave_description')
					->leftJoin('attendance', function ($join) use ($date) {
						$join->on('attendance.employee_id', 'employees.id')
							->where('attendance.date', $date);
					})
					->leftJoin('leaves', function ($join) use ($date) {
						$join->on('leaves.employee_id', 'employees.id')
							->where('leaves.status', 1)
							->whereRaw("date(leaves.start_date) <= '$date' AND date(leaves.end_date) >= '$date'");
					})
					->orderBy('employees.id', 'ASC')
					->where(function ($query) {
						$query->where("employees.end_date", NULL)->orWhere("employees.end_date", ">", now());
					})
					->get();

				return Inertia::render('Backend/User/Attendance/Create', [
					'employees' => $employees,
					'message' => $message,
					'date' => $date,
				]);
			}

			return Inertia::render('Backend/User/Attendance/Create');
		}
		else if ($request->isMethod('post')) {
			$validator = Validator::make($request->all(), [
				'date' => 'required',
			]);

			if ($validator->fails()) {
				return redirect()->route('attendance.create')->withErrors($validator)->withInput();
			}

			$date = Carbon::parse($request->date)->format('Y-m-d');
			return redirect()->route('attendance.create', ['date' => $date]);
		}
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
			'employee_id.*' => 'required',
			'date' => 'required',
			'status.*' => 'required',
		]);

		if ($validator->fails()) {
			return back()->withErrors($validator)->withInput();
		}

		if (empty($request->employee_id)) {
			return back()->with('error', _lang('You must select at least one employee'))->withInput();
		}

		$data = [];
		foreach ($request->employee_id as $key => $employee_id) {
			$leave_duration = $request->leave_duration[$key];
			if ($request->status[$key] != 1 && $leave_duration == '') {
				$leave_duration = 'full_day';
			}
			array_push($data, [
				'employee_id' => $employee_id,
				'date' => date('Y-m-d', strtotime($request->date)),
				'status' => $request->status[$key],
				'leave_type' => $request->leave_type[$key],
				'leave_duration' => $leave_duration,
				'remarks' => $request->remarks[$key],
				'business_id' => business_id(),
				'user_id' => Auth::id(),
			]);
		}

		Attendance::upsert($data, ['employee_id', 'date', 'business_id', 'user_id']);

		// audit log
		$audit = new AuditLog();
		$audit->date_changed = date('Y-m-d H:i:s');
		$audit->changed_by = Auth::id();
		$audit->event = 'Created Attendance for ' . count($data) . ' employees';
		$audit->save();

		return redirect()->route('attendance.index')->with('success', _lang('Saved Successfully'));
	}

	/**
	 * Display the specified resource.
	 *
	 * @param  int  $id
	 * @return \Illuminate\Http\Response
	 */
	public function show($date)
	{
		$attendanceRecords = Attendance::with('staff')
			->where('date', $date)
			->orderBy('id', 'asc')
			->get();

		$stats = [
			'total_attendance' => $attendanceRecords->count(),
			'present' => $attendanceRecords->where('status', 1)->count(),
			'absent' => $attendanceRecords->where('status', 0)->count(),
			'leaves' => $attendanceRecords->where('status', 2)->count(),
		];

		$fine = AbsentFine::first();
		if ($fine) {
			$stats['full_day_fine'] = $fine->full_day_fine;
			$stats['half_day_fine'] = $fine->half_day_fine;
		}

		return Inertia::render('Backend/User/Attendance/View', [
			'date' => $date,
			'attendanceRecords' => $attendanceRecords,
			'stats' => $stats,
		]);
	}

	/**
	 * Show the form for editing the specified resource.
	 *
	 * @param  int  $id
	 * @return \Illuminate\Http\Response
	 */
	public function edit($date)
	{
		$attendanceData = Attendance::where('date', $date)->first();
		$weekends = json_decode(get_business_option('weekends', '[]', business_id()));
		$message = null;
		
		if (in_array(date('l', strtotime($date)), $weekends)) {
			$message = _lang('The date you selected which is a weekend !');
		}

		$holiday = Holiday::whereDate('date', $date)->first();
		if ($holiday) {
			$message = _lang('The date you selected which is a holiday !');
		}

		$employees = Employee::select('employees.*', DB::raw('IFNULL(attendance.status, 1) as attendance_status'), 'attendance.remarks as attendance_remarks', 'attendance.leave_duration as attendance_leave_duration', 'leaves.leave_type', 'leaves.leave_duration', 'leaves.description as leave_description')
			->leftJoin('attendance', function ($join) use ($date) {
				$join->on('attendance.employee_id', 'employees.id')
					->where('attendance.date', $date);
			})
			->leftJoin('leaves', function ($join) use ($date) {
				$join->on('leaves.employee_id', 'employees.id')
					->where('leaves.status', 1)
					->whereRaw("date(leaves.start_date) <= '$date' AND date(leaves.end_date) >= '$date'");
			})
			->orderBy('employees.id', 'ASC')
			->where(function ($query) {
				$query->where("employees.end_date", NULL)->orWhere("employees.end_date", ">", now());
			})
			->get();

		return Inertia::render('Backend/User/Attendance/Edit', [
			'attendanceData' => $attendanceData,
			'employees' => $employees,
			'message' => $message,
		]);
	}

	/**
	 * Update the specified resource in storage.
	 *
	 * @param  \Illuminate\Http\Request  $request
	 * @param  int  $id
	 * @return \Illuminate\Http\Response
	 */
	public function update(Request $request)
	{
		$validator = Validator::make($request->all(), [
			'employee_id.*' => 'required',
			'date' => 'required',
			'status.*' => 'required',
		]);

		if ($validator->fails()) {
			return back()->withErrors($validator)->withInput();
		}

		if (empty($request->employee_id)) {
			return back()->with('error', _lang('You must select at least one employee'))->withInput();
		}

		$data = [];
		foreach ($request->employee_id as $key => $employee_id) {
			$leave_duration = $request->leave_duration[$key];
			if ($request->status[$key] != 1 && $leave_duration == '') {
				$leave_duration = 'full_day';
			}
			array_push($data, [
				'employee_id' => $employee_id,
				'date' => date('Y-m-d', strtotime($request->date)),
				'status' => $request->status[$key],
				'leave_type' => $request->leave_type[$key],
				'leave_duration' => $leave_duration,
				'remarks' => $request->remarks[$key],
				'business_id' => business_id(),
				'user_id' => Auth::id(),
			]);
		}

		// First delete existing records for this date
		Attendance::where('date', date('Y-m-d', strtotime($request->date)))->delete();
		
		// Then insert the updated records
		Attendance::insert($data);

		// audit log
		$audit = new AuditLog();
		$audit->date_changed = date('Y-m-d H:i:s');
		$audit->changed_by = Auth::id();
		$audit->event = 'Updated Attendance for ' . count($data) . ' employees';
		$audit->save();

		return redirect()->route('attendance.index')->with('success', _lang('Updated Successfully'));
	}

	/**
	 * Remove the specified resource from storage.
	 *
	 * @param  int  $id
	 * @return \Illuminate\Http\Response
	 */
	public function destroy($date)
	{
		$attendance = Attendance::where('date', $date);
		$count = $attendance->count();
		$attendance->delete();

		// audit log
		$audit = new AuditLog();
		$audit->date_changed = date('Y-m-d H:i:s');
		$audit->changed_by = Auth::id();
		$audit->event = 'Deleted Attendance records for date: ' . $date . ' (' . $count . ' records)';
		$audit->save();

		return redirect()->route('attendance.index')->with('success', _lang('Deleted Successfully'));
	}

	/**
	 * Remove multiple resources from storage.
	 *
	 * @param  \Illuminate\Http\Request  $request
	 * @return \Illuminate\Http\Response
	 */
	public function bulk_delete(Request $request)
	{
		$validator = Validator::make($request->all(), [
			'dates' => 'required|array',
		]);

		if ($validator->fails()) {
			return response()->json(['result' => 'error', 'message' => $validator->errors()->first()]);
		}

		$total = 0;
		foreach($request->dates as $date) {
			$count = Attendance::where('date', $date)->count();
			$total += $count;
			Attendance::where('date', $date)->delete();
		}

		// audit log
		$audit = new AuditLog();
		$audit->date_changed = date('Y-m-d H:i:s');
		$audit->changed_by = Auth::id();
		$audit->event = 'Bulk deleted ' . $total . ' attendance records for ' . count($request->dates) . ' dates';
		$audit->save();

		return response()->json(['result' => 'success', 'message' => _lang('Deleted Successfully')]);
	}

	public function import_attendance(Request $request)
	{
		$request->validate([
			'attendances_file' => 'required|mimes:xls,xlsx',
		]);

		try {
			Excel::import(new AttendanceImport, $request->file('attendances_file'));
		} catch (\Exception $e) {
			return back()->with('error', $e->getMessage());
		}

		// audit log
        $audit = new AuditLog();
        $audit->date_changed = date('Y-m-d H:i:s');
        $audit->changed_by = Auth::id();
		$audit->event = 'Imported Attendance';
        $audit->save();

		return redirect()->route('attendance.index')->with('success', _lang('Attendance Imported'));
	}

	public function absent_fine()
	{
		$fine = AbsentFine::first();
		return Inertia::render('Backend/User/Attendance/Fine', [
			'fine' => $fine,
		]);
	}

	public function store_absent_fine(Request $request)
	{
		$request->validate([
			'full_day_fine' => 'required|numeric',
			'half_day_fine' => 'required|numeric',
		]);

		$fine = AbsentFine::where('business_id', business_id())->first();

		if ($fine !== null) {
			$fine->full_day_fine = $request->full_day_fine;
			$fine->half_day_fine = $request->half_day_fine;
			$fine->save();
		} else {
			$fine = new AbsentFine;
			$fine->full_day_fine = $request->full_day_fine;
			$fine->half_day_fine = $request->half_day_fine;
			$fine->business_id = business_id();
			$fine->save();
		}

		// audit log
        $audit = new AuditLog();
        $audit->date_changed = date('Y-m-d H:i:s');
        $audit->changed_by = Auth::id();
		$audit->event = 'Updated Absent Fine';
        $audit->save();

		return redirect()->route('attendance.index')->with('success', _lang('Fine Saved Successfully'));
	}
}
