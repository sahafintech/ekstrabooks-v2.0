<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Award;
use Carbon\Carbon;
use DataTables;
use Illuminate\Http\Request;
use Validator;

class AwardController extends Controller {

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct() {
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
    public function index() {
        $awards = Award::select('awards.*')->with('staff')->get();

        return view('backend.user.award.list', compact('awards'));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create(Request $request) {
        if (!$request->ajax()) {
            return back();
        } else {
            return view('backend.user.award.modal.create');
        }
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request) {
        $validator = Validator::make($request->all(), [
            'employee_id' => 'required',
            'award_date'  => 'required',
            'award_name'  => 'required',
            'award'       => 'required',
        ]);

        if ($validator->fails()) {
            if ($request->ajax()) {
                return response()->json(['result' => 'error', 'message' => $validator->errors()->all()]);
            } else {
                return redirect()->route('awards.create')
                    ->withErrors($validator)
                    ->withInput();
            }
        }

        $award              = new Award();
        $award->employee_id = $request->input('employee_id');
        $award->award_date  = Carbon::parse($request->input('award_date'))->format('Y-m-d');
        $award->award_name  = $request->input('award_name');
        $award->award       = $request->input('award');
        $award->details     = $request->input('details');

        $award->save();

        // audit log
        $audit = new AuditLog();
        $audit->date_changed = date('Y-m-d H:i:s');
        $audit->changed_by = auth()->user()->id;
        $audit->event = 'Created Award for ' . $award->staff->name;
        $audit->save();

        if (!$request->ajax()) {
            return redirect()->route('awards.create')->with('success', _lang('Saved Successfully'));
        } else {
            return response()->json(['result' => 'success', 'action' => 'store', 'message' => _lang('Saved Successfully'), 'data' => $award, 'table' => '#awards_table']);
        }

    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show(Request $request, $id) {
        $award = Award::find($id);
        if (!$request->ajax()) {
            return back();
        } else {
            return view('backend.user.award.modal.view', compact('award', 'id'));
        }

    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit(Request $request, $id) {
        $award = Award::find($id);
        if (!$request->ajax()) {
            return back();
        } else {
            return view('backend.user.award.modal.edit', compact('award', 'id'));
        }
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id) {
        $validator = Validator::make($request->all(), [
            'employee_id' => 'required',
            'award_date'  => 'required',
            'award_name'  => 'required',
            'award'       => 'required',
        ]);

        if ($validator->fails()) {
            if ($request->ajax()) {
                return response()->json(['result' => 'error', 'message' => $validator->errors()->all()]);
            } else {
                return redirect()->route('awards.edit', $id)
                    ->withErrors($validator)
                    ->withInput();
            }
        }

        $award              = Award::find($id);
        $award->employee_id = $request->input('employee_id');
        $award->award_date  = Carbon::parse($request->input('award_date'))->format('Y-m-d');
        $award->award_name  = $request->input('award_name');
        $award->award       = $request->input('award');
        $award->details     = $request->input('details');

        $award->save();

        // audit log
        $audit = new AuditLog();
        $audit->date_changed = date('Y-m-d H:i:s');
        $audit->changed_by = auth()->user()->id;
        $audit->event = 'Updated Award for ' . $award->staff->name;
        $audit->save();

        if (!$request->ajax()) {
            return redirect()->route('awards.index')->with('success', _lang('Updated Successfully'));
        } else {
            return response()->json(['result' => 'success', 'action' => 'update', 'message' => _lang('Updated Successfully'), 'data' => $award, 'table' => '#awards_table']);
        }

    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id) {
        $award = Award::find($id);
        $award->delete();

        // audit log
        $audit = new AuditLog();
        $audit->date_changed = date('Y-m-d H:i:s');
        $audit->changed_by = auth()->user()->id;
        $audit->event = 'Deleted Award for ' . $award->staff->name;
        $audit->save();

        return redirect()->route('awards.index')->with('success', _lang('Deleted Successfully'));
    }
}