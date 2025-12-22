<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use App\Models\Project;
use App\Models\Timesheet;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\Gate;

class TimesheetController extends Controller
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
		Gate::authorize('timesheets.view');
		$query = Timesheet::with('employee', 'project');

		// Apply search if provided
		if ($request->has('search')) {
			$search = $request->search;
			$query->where(function ($q) use ($search) {
				$q->whereHas('employee', function ($q2) use ($search) {
					$q2->where('name', 'like', "%{$search}%");
				})
					->orWhereHas('project', function ($q2) use ($search) {
						$q2->where('project_name', 'like', "%{$search}%");
					});
			})
				->orWhere('date', $search)
				->orWhere('working_hours', $search)
				->orWhere('working_day_type', $search);
		}

		// Filter by employee
		if ($request->employee_id) {
			$query->where('employee_id', $request->employee_id);
		}

		// Filter by project
		if ($request->project_id) {
			$query->where('project_id', $request->project_id);
		}

		// Filter by date range
		if ($request->date_range) {
			$dateRange = $request->date_range;
			if (!empty($dateRange[0])) {
				$query->whereDate('date', '>=', $dateRange[0]);
			}
			if (!empty($dateRange[1])) {
				$query->whereDate('date', '<=', $dateRange[1]);
			}
		}

		// Handle sorting
		$sorting = $request->get('sorting', []);
		$sortColumn = $sorting['column'] ?? 'id';
		$sortDirection = $sorting['direction'] ?? 'desc';
		if ($sortColumn === 'employee.name') {
			$query->join('employees', 'timesheets.employee_id', '=', 'employees.id')
				->orderBy('employees.name', $sortDirection)
				->select('timesheets.*');
		} else if ($sortColumn === 'project.name') {
			$query->join('projects', 'timesheets.project_id', '=', 'projects.id')
				->orderBy('projects.project_name', $sortDirection)
				->select('timesheets.*');
		} else {
			$query->orderBy('timesheets.' . $sortColumn, $sortDirection);
		}

		// Get summary statistics for all invoices matching filters
		$allTimesheets = Timesheet::query();

		if ($request->has('search')) {
			$search = $request->search;
			$allTimesheets->where(function ($q) use ($search) {
				$q->whereHas('employee', function ($q2) use ($search) {
					$q2->where('name', 'like', "%{$search}%");
				})
					->orWhereHas('project', function ($q2) use ($search) {
						$q2->where('project_name', 'like', "%{$search}%");
					});
			});
		}

		if ($request->employee_id) {
			$allTimesheets->where('employee_id', $request->employee_id);
		}

		if ($request->project_id) {
			$allTimesheets->where('project_id', $request->project_id);
		}

		if ($request->date_range) {
			$dateRange = $request->date_range;
			if (!empty($dateRange[0])) {
				$allTimesheets->whereDate('date', '>=', $dateRange[0]);
			}
			if (!empty($dateRange[1])) {
				$allTimesheets->whereDate('date', '<=', $dateRange[1]);
			}
		}

		$allTimesheets = $allTimesheets->get();

		// Handle pagination
		$perPage = $request->get('per_page', 50);
		$timesheets = $query->paginate($perPage);

		return Inertia::render('Backend/User/Timesheet/List', [
			'timesheets' => $timesheets->items(),
			'meta' => [
				'total' => $timesheets->total(),
				'per_page' => $timesheets->perPage(),
				'current_page' => $timesheets->currentPage(),
				'last_page' => $timesheets->lastPage(),
				'from' => $timesheets->firstItem(),
				'to' => $timesheets->lastItem(),
			],
			'filters' => array_merge($request->only(['search', 'employee_id', 'project_id', 'date_range']), [
				'sorting' => $sorting,
			]),
			'employees' => Employee::select('id', 'name')->orderBy('id')->get(),
			'projects' => Project::select('id', 'project_name')->orderBy('id')->get(),
		]);
	}

	public function store(Request $request)
	{
		Gate::authorize('timesheets.create');
		$request->validate([
			'employee_id' => 'required',
			'project_id' => 'required',
			'date' => 'required',
			'check_in' => 'required',
			'check_out' => 'required',
			'working_day_type' => 'required',
			'working_hours' => 'required',
		]);

		$timesheet = new Timesheet();
		$timesheet->employee_id = $request->employee_id;
		$timesheet->project_id = $request->project_id;
		$timesheet->date = $request->date;
		$timesheet->check_in = $request->check_in;
		$timesheet->check_out = $request->check_out;
		$timesheet->working_day_type = $request->working_day_type;
		$timesheet->working_hours = $request->working_hours;
		$timesheet->save();

		return redirect()->back()->with('success', _lang('Timesheet created successfully'));
	}

	public function update(Request $request, $id)
	{
		Gate::authorize('timesheets.update');
		$request->validate([
			'employee_id' => 'required',
			'project_id' => 'required',
			'date' => 'required',
			'check_in' => 'required',
			'check_out' => 'required',
			'working_day_type' => 'required',
			'working_hours' => 'required',
		]);

		$timesheet = Timesheet::find($id);
		$timesheet->employee_id = $request->employee_id;
		$timesheet->project_id = $request->project_id;
		$timesheet->date = $request->date;
		$timesheet->check_in = $request->check_in;
		$timesheet->check_out = $request->check_out;
		$timesheet->working_day_type = $request->working_day_type;
		$timesheet->working_hours = $request->working_hours;
		$timesheet->save();

		return redirect()->back()->with('success', _lang('Timesheet updated successfully'));
	}
}
