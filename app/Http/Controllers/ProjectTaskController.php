<?php

namespace App\Http\Controllers;

use App\Exports\CostCodesExport;
use App\Exports\ProjectTasksExport;
use App\Http\Controllers\Controller;
use App\Imports\CostCodeImport;
use App\Imports\ProjectTaskImport;
use App\Models\AuditLog;
use App\Models\ProjectTask;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Maatwebsite\Excel\Facades\Excel;

class ProjectTaskController extends Controller
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

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'task_code'            => 'required|max:50',
            'description'     => 'nullable',
            'status'            => 'required',
            'completed_percent' => 'required',
            'start_date'        => 'required|date',
            'end_date'          => 'required|date',
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        $project_task                  = new ProjectTask();
        $project_task->task_code           = $request->input('task_code');
        $project_task->description    = $request->input('description');
        $project_task->status            = $request->input('status');
        $project_task->completed_percent = $request->input('completed_percent');
        $project_task->project_id        = $request->input('project_id');
        $project_task->start_date        = $request->input('start_date');
        $project_task->end_date          = $request->input('end_date');
        $project_task->created_by     = Auth::id();
        $project_task->save();

        // audit log
        $audit = new AuditLog();
        $audit->date_changed = date('Y-m-d H:i:s');
        $audit->changed_by = Auth::id();
        $audit->event = 'Created New Project Task ' . $project_task->task_code;
        $audit->save();

        return redirect()->back()->with('success', _lang('Task Saved Successfully'));
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
            'task_code'            => 'required|max:50',
            'description'     => 'nullable',
            'status'            => 'required',
            'completed_percent' => 'required',
            'start_date'        => 'required|date',
            'end_date'          => 'required|date',
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        $project_task = ProjectTask::findOrFail($id);

        $project_task->task_code          = $request->input('task_code');
        $project_task->description   = $request->input('description');
        $project_task->status            = $request->input('status');
        $project_task->completed_percent = $request->input('completed_percent');
        $project_task->start_date        = $request->input('start_date');
        $project_task->end_date          = $request->input('end_date');
        $project_task->updated_by    = Auth::id();
        $project_task->save();

        // audit log
        $audit = new AuditLog();
        $audit->date_changed = date('Y-m-d H:i:s');
        $audit->changed_by = Auth::id();
        $audit->event = 'Updated Project Task ' . $project_task->task_code;
        $audit->save();

        return redirect()->back()->with('success', _lang('Task Updated Successfully'));
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $project_task = ProjectTask::findOrFail($id);
        $project_task->deleted_by = Auth::id();
        $project_task->save();

        // audit log
        $audit = new AuditLog();
        $audit->date_changed = date('Y-m-d H:i:s');
        $audit->changed_by = Auth::id();
        $audit->event = 'Deleted Project Task ' . $project_task->task_code;
        $audit->save();

        $project_task->delete();

        return redirect()->back()->with('success', _lang('Task Deleted Successfully'));
    }

    public function bulk_destroy(Request $request)
    {
        foreach ($request->ids as $id) {
            $project_task = ProjectTask::findOrFail($id);
            $project_task->deleted_by = Auth::id();
            $project_task->save();

            // audit log
            $audit = new AuditLog();
            $audit->date_changed = date('Y-m-d H:i:s');
            $audit->changed_by = Auth::id();
            $audit->event = 'Deleted Project Task ' . $project_task->task_code;
            $audit->save();

            $project_task->delete();
        }

        return redirect()->back()->with('success', _lang('Tasks Deleted Successfully'));
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

        $project_tasks = ProjectTask::whereIn('id', $request->ids)
            ->get();

        foreach ($project_tasks as $project_task) {
            // audit log
            $audit = new AuditLog();
            $audit->date_changed = date('Y-m-d H:i:s');
            $audit->changed_by = Auth::id();
            $audit->event = 'Deleted Project Task ' . $project_task->task_code;
            $audit->save();

            $project_task->delete();
        }

        return redirect()->back()->with('success', _lang('Tasks Deleted Successfully'));
    }

    public function import_project_tasks(Request $request)
    {
        $request->validate([
            'project_tasks_file' => 'required|mimes:xls,xlsx',
        ]);

        try {
            Excel::import(new ProjectTaskImport($request->project_id), $request->file('project_tasks_file'));
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }

        // audit log
        $audit = new AuditLog();
        $audit->date_changed = date('Y-m-d H:i:s');
        $audit->changed_by = Auth::id();
        $audit->event = 'Imported Project Tasks';
        $audit->save();

        return redirect()->back()->with('success', _lang('Project Tasks Imported'));
    }

    public function export_project_tasks()
    {
        return Excel::download(new ProjectTasksExport, 'project_tasks ' . now()->format('d m Y') . '.xlsx');
    }
}
