<?php

namespace App\Http\Controllers;

use App\Exports\ProjectGroupsExport;
use App\Http\Controllers\Controller;
use App\Imports\ProjectGroupImport;
use App\Models\AuditLog;
use App\Models\ProjectGroup;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Maatwebsite\Excel\Facades\Excel;

class ProjectGroupController extends Controller
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

        $query = ProjectGroup::query();

        // Apply search if provided
        if (!empty($search)) {
            $query->where(function ($q) use ($search) {
                $q->where('group_name', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
            });
        }

        // Handle sorting
        $sorting = $request->get('sorting', []);
        $sortColumn = $sorting['column'] ?? 'id';
        $sortDirection = $sorting['direction'] ?? 'desc';

        $query->orderBy($sortColumn, $sortDirection);

        // Get project groups with pagination
        $project_groups = $query->paginate($per_page)->withQueryString();

        // Return Inertia view
        return Inertia::render('Backend/User/Construction/ProjectGroup/List', [
            'project_groups' => $project_groups->items(),
            'meta' => [
                'current_page' => $project_groups->currentPage(),
                'from' => $project_groups->firstItem(),
                'last_page' => $project_groups->lastPage(),
                'links' => $project_groups->linkCollection(),
                'path' => $project_groups->path(),
                'per_page' => $project_groups->perPage(),
                'to' => $project_groups->lastItem(),
                'total' => $project_groups->total(),
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
        return Inertia::render('Backend/User/Construction/ProjectGroup/Create');
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
            'group_name'            => 'required|max:50',
            'description'     => 'nullable',
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        $project_group                  = new ProjectGroup();
        $project_group->group_name           = $request->input('group_name');
        $project_group->description    = $request->input('description');
        $project_group->created_by     = Auth::id();
        $project_group->save();

        // audit log
        $audit = new AuditLog();
        $audit->date_changed = date('Y-m-d H:i:s');
        $audit->changed_by = Auth::id();
        $audit->event = 'Created New Project Group ' . $project_group->group_name;
        $audit->save();

        return redirect()->route('project_groups.index')->with('success', _lang('Project group created successfully'));
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        $project_group = ProjectGroup::findOrFail($id);

        return Inertia::render('Backend/User/Construction/ProjectGroup/Edit', [
            'project_group' => $project_group,
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
            'group_name'            => 'required|max:50',
            'description'     => 'nullable',
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        $project_group = ProjectGroup::findOrFail($id);

        $project_group->group_name    = $request->input('group_name');
        $project_group->description   = $request->input('description');
        $project_group->updated_by    = Auth::id();
        $project_group->save();

        // audit log
        $audit = new AuditLog();
        $audit->date_changed = date('Y-m-d H:i:s');
        $audit->changed_by = Auth::id();
        $audit->event = 'Updated Project Group ' . $project_group->group_name;
        $audit->save();

        return redirect()->route('project_groups.index')->with('success', _lang('Project group updated successfully'));
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $project_group = ProjectGroup::findOrFail($id);
        $project_group->deleted_by = Auth::id();
        $project_group->save();

        // audit log
        $audit = new AuditLog();
        $audit->date_changed = date('Y-m-d H:i:s');
        $audit->changed_by = Auth::id();
        $audit->event = 'Deleted Project Group ' . $project_group->group_name;
        $audit->save();

        $project_group->delete();

        return redirect()->route('project_groups.index')->with('success', _lang('Selected project group deleted successfully'));
    }

    public function bulk_destroy(Request $request)
    {
        foreach ($request->ids as $id) {
            $project_group = ProjectGroup::findOrFail($id);
            $project_group->deleted_by = Auth::id();
            $project_group->save();

            // audit log
            $audit = new AuditLog();
            $audit->date_changed = date('Y-m-d H:i:s');
            $audit->changed_by = Auth::id();
            $audit->event = 'Deleted Project Group ' . $project_group->group_name;
            $audit->save();

            $project_group->delete();
        }

        return redirect()->route('project_groups.index')->with('success', _lang('Selected project groups deleted successfully'));
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

        $project_groups = ProjectGroup::whereIn('id', $request->ids)
            ->get();

        foreach ($project_groups as $project_group) {
            $project_group->deleted_by = Auth::id();
            $project_group->save();

            // audit log
            $audit = new AuditLog();
            $audit->date_changed = date('Y-m-d H:i:s');
            $audit->changed_by = Auth::id();
            $audit->event = 'Deleted Project Group ' . $project_group->group_name;
            $audit->save();

            $project_group->delete();
        }

        return redirect()->route('project_groups.index')->with('success', _lang('Selected project groups deleted successfully'));
    }

    public function import_project_groups(Request $request)
    {
        $request->validate([
            'project_groups_file' => 'required|mimes:xls,xlsx',
        ]);

        try {
            Excel::import(new ProjectGroupImport, $request->file('project_groups_file'));
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }

        // audit log
        $audit = new AuditLog();
        $audit->date_changed = date('Y-m-d H:i:s');
        $audit->changed_by = Auth::id();
        $audit->event = 'Imported Project Groups';
        $audit->save();

        return redirect()->route('project_groups.index')->with('success', _lang('Project Groups Imported'));
    }

    public function export_project_groups()
    {
        return Excel::download(new ProjectGroupsExport, 'project_groups ' . now()->format('d m Y') . '.xlsx');
    }
}
