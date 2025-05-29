<?php

namespace App\Http\Controllers;

use App\Exports\ProjectExport;
use App\Http\Controllers\Controller;
use App\Imports\ProjectImport;
use App\Models\Account;
use App\Models\AuditLog;
use App\Models\ChangeOrder;
use App\Models\CostCode;
use App\Models\Currency;
use App\Models\Customer;
use App\Models\Employee;
use App\Models\ProductUnit;
use App\Models\Project;
use App\Models\ProjectGroup;
use App\Models\ProjectTask;
use App\Models\Transaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Maatwebsite\Excel\Facades\Excel;

class ProjectController extends Controller
{

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware(function ($request, $next) {

            if (package()->construction_module != 1) {
                return back()->with('error', _lang('Sorry, This module is not available in your current package !'));
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
        $per_page = $request->get('per_page', 50);
        $search = $request->get('search', '');

        $query = Project::with('project_group', 'customer', 'manager');

        // Apply search if provided
        if (!empty($search)) {
            $query->where(function ($q) use ($search) {
                $q->where('project_code', 'like', "%{$search}%")
                    ->orWhere('project_name', 'like', "%{$search}%")
                    ->orWhere('start_date', 'like', "%{$search}%")
                    ->orWhere('end_date', 'like', "%{$search}%")
                    ->orWhere('status', 'like', "%{$search}%")
                    ->orWhere('priority', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%")

                    ->orWhereHas('project_group', function ($q) use ($search) {
                        $q->where('group_name', 'like', "%{$search}%");
                    })
                    ->orWhereHas('customer', function ($q) use ($search) {
                        $q->where('name', 'like', "%{$search}%")
                            ->orWhere('email', 'like', "%{$search}%")
                            ->orWhere('mobile', 'like', "%{$search}%");
                    })
                    ->orWhereHas('manager', function ($q) use ($search) {
                        $q->where('name', 'like', "%{$search}%")
                            ->orWhere('email', 'like', "%{$search}%")
                            ->orWhere('mobile', 'like', "%{$search}%");
                    });
            });
        }

        // Handle sorting
        $sorting = $request->get('sorting', []);
        $sortColumn = $sorting['column'] ?? 'id';
        $sortDirection = $sorting['direction'] ?? 'desc';

        $query->orderBy($sortColumn, $sortDirection);

        // Get projects with pagination
        $projects = $query->paginate($per_page)->withQueryString();

        // Return Inertia view
        return Inertia::render('Backend/User/Construction/Project/List', [
            'projects' => $projects->items(),
            'meta' => [
                'current_page' => $projects->currentPage(),
                'from' => $projects->firstItem(),
                'last_page' => $projects->lastPage(),
                'links' => $projects->linkCollection(),
                'path' => $projects->path(),
                'per_page' => $projects->perPage(),
                'to' => $projects->lastItem(),
                'total' => $projects->total(),
            ],
            'filters' => [
                'search' => $search,
                'columnFilters' => $request->get('columnFilters', []),
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
        $project_groups = ProjectGroup::all();
        $customers = Customer::all();
        $employees = Employee::all();
        $currencies = Currency::all();

        return Inertia::render('Backend/User/Construction/Project/Create', [
            'project_groups' => $project_groups,
            'customers' => $customers,
            'employees' => $employees,
            'base_currency' => request()->activeBusiness->currency,
            'currencies' => $currencies,
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
            'project_code'            => 'required|max:50|unique:projects',
            'project_name'     => 'required|max:50',
            'project_group_id'     => 'nullable|max:50',
            'customer_id'     => 'nullable|max:50',
            'project_manager_id'     => 'nullable|max:50',
            'start_date'     => 'nullable|max:50',
            'end_date'     => 'nullable|max:50',
            'status'     => 'nullable|max:50',
            'priority'     => 'nullable|max:50',
            'project_currency'     => 'required|max:50',
            'description'     => 'nullable|max:50',
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        $project                  = new Project();
        $project->project_code           = $request->input('project_code');
        $project->project_name    = $request->input('project_name');
        $project->project_group_id     = $request->input('project_group_id');
        $project->customer_id     = $request->input('customer_id');
        $project->project_manager_id     = $request->input('project_manager_id');
        $project->start_date     = $request->input('start_date');
        $project->end_date     = $request->input('end_date');
        $project->status     = $request->input('status');
        $project->priority     = $request->input('priority');
        $project->project_currency     = $request->input('project_currency');
        $project->description     = $request->input('description');
        $project->created_by     = Auth::id();
        $project->save();

        // audit log
        $audit = new AuditLog();
        $audit->date_changed = date('Y-m-d H:i:s');
        $audit->changed_by = Auth::id();
        $audit->event = 'Created New Project ' . $project->project_name;
        $audit->save();

        return redirect()->route('projects.index')->with('success', _lang('Project created successfully'));
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        $project = Project::findOrFail($id);
        $project_groups = ProjectGroup::all();
        $customers = Customer::all();
        $employees = Employee::all();
        $currencies = Currency::all();

        return Inertia::render('Backend/User/Construction/Project/Edit', [
            'project' => $project,
            'project_groups' => $project_groups,
            'customers' => $customers,
            'employees' => $employees,
            'currencies' => $currencies,
        ]);
    }

    public function show($id, Request $request)
    {
        $project = Project::with('tasks', 'budgets', 'budgets.cost_codes', 'budgets.tasks')->findOrFail($id);
        $tab = $request->get('tab', 'tasks');
        $cost_codes = CostCode::all();
        $unit_of_measures = ProductUnit::all();
        $transactions = Transaction::where('project_id', $id)->with('account')->get();
        $change_orders = ChangeOrder::where('project_id', $id)->with('project', 'project_task', 'cost_code')->get();
        $accounts = Account::all();
        
        return Inertia::render('Backend/User/Construction/Project/View', [
            'project' => $project,
            'activeTab' => $tab,
            'cost_codes' => $cost_codes,
            'unit_of_measures' => $unit_of_measures,
            'transactions' => $transactions,
            'change_orders' => $change_orders,
            'accounts' => $accounts,
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
        $validator = Validator::make($request->all(), [
            'project_code'            => 'required|max:50|unique:projects,project_code,' . $id,
            'project_name'     => 'required|max:50',
            'project_group_id'     => 'nullable|max:50',
            'customer_id'     => 'nullable|max:50',
            'project_manager_id'     => 'nullable|max:50',
            'start_date'     => 'nullable|max:50',
            'end_date'     => 'nullable|max:50',
            'status'     => 'nullable|max:50',
            'priority'     => 'nullable|max:50',
            'project_currency'     => 'required|max:50',
            'description'     => 'nullable',
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        $project = Project::findOrFail($id);

        $project->project_code    = $request->input('project_code');
        $project->project_name    = $request->input('project_name');
        $project->project_group_id    = $request->input('project_group_id');
        $project->customer_id    = $request->input('customer_id');
        $project->project_manager_id    = $request->input('project_manager_id');
        $project->start_date    = $request->input('start_date');
        $project->end_date    = $request->input('end_date');
        $project->status    = $request->input('status');
        $project->priority    = $request->input('priority');
        $project->project_currency    = $request->input('project_currency');
        $project->description    = $request->input('description');
        $project->updated_by    = Auth::id();
        $project->save();

        // audit log
        $audit = new AuditLog();
        $audit->date_changed = date('Y-m-d H:i:s');
        $audit->changed_by = Auth::id();
        $audit->event = 'Updated Project ' . $project->project_name;
        $audit->save();

        return redirect()->route('projects.index')->with('success', _lang('Project updated successfully'));
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $project = Project::findOrFail($id);
        $project->deleted_by = Auth::id();
        $project->save();

        // audit log
        $audit = new AuditLog();
        $audit->date_changed = date('Y-m-d H:i:s');
        $audit->changed_by = Auth::id();
        $audit->event = 'Deleted Project ' . $project->project_name;
        $audit->save();

        $project->delete();

        return redirect()->route('projects.index')->with('success', _lang('Selected project deleted successfully'));
    }

    public function bulk_destroy(Request $request)
    {
        foreach ($request->ids as $id) {
            $project = Project::findOrFail($id);
            $project->deleted_by = Auth::id();
            $project->save();

            // audit log
            $audit = new AuditLog();
            $audit->date_changed = date('Y-m-d H:i:s');
            $audit->changed_by = Auth::id();
            $audit->event = 'Deleted Project ' . $project->project_name;
            $audit->save();

            $project->delete();
        }

        return redirect()->route('projects.index')->with('success', _lang('Selected projects deleted successfully'));
    }

    /**
     * Bulk delete selected staff members
     * 
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function bulkDelete(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'ids' => 'required|array',
            'ids.*' => 'required|integer',
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator);
        }

        $projects = Project::whereIn('id', $request->ids)
            ->get();

        foreach ($projects as $project) {
            $project->deleted_by = Auth::id();
            $project->save();

            // audit log
            $audit = new AuditLog();
            $audit->date_changed = date('Y-m-d H:i:s');
            $audit->changed_by = Auth::id();
            $audit->event = 'Deleted Project ' . $project->project_name;
            $audit->save();

            $project->delete();
        }

        return redirect()->route('projects.index')->with('success', _lang('Selected projects deleted successfully'));
    }

    public function import_projects(Request $request)
    {
        $request->validate([
            'projects_file' => 'required|mimes:xls,xlsx',
        ]);

        try {
            Excel::import(new ProjectImport, $request->file('projects_file'));
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }

        // audit log
        $audit = new AuditLog();
        $audit->date_changed = date('Y-m-d H:i:s');
        $audit->changed_by = Auth::id();
        $audit->event = 'Imported Projects';
        $audit->save();

        return redirect()->route('projects.index')->with('success', _lang('Projects Imported'));
    }

    public function export_projects()
    {
        return Excel::download(new ProjectExport, 'projects ' . now()->format('d m Y') . '.xlsx');
    }
}
