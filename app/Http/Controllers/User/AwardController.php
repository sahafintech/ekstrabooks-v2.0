<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Award;
use App\Models\Employee;
use Carbon\Carbon;
use DataTables;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Inertia\Inertia;

class AwardController extends Controller
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
        $query = Award::select('awards.*')->with('staff');

        // Search functionality
        if ($request->has('search') && !empty($request->search)) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('award_name', 'like', "%$search%")
                    ->orWhere('award', 'like', "%$search%")
                    ->orWhere('award_date', 'like', "%$search%")
                    ->orWhereHas('staff', function ($q) use ($search) {
                        $q->where('name', 'like', "%$search%");
                    });
            });
        }

        // Date filter
        if ($request->has('date') && !empty($request->date)) {
            $date = $request->date;
            $query->whereDate('award_date', $date);
        }

        // Define pagination
        $per_page = $request->input('per_page', 10);

        // Get awards with pagination
        $awards = $query->orderBy('award_date', 'desc')->paginate($per_page)->withQueryString();
        $employees = Employee::select('id', 'name')->get();

        // Return Inertia view
        return Inertia::render('Backend/User/Award/List', [
            'awards' => $awards->items(),
            'employees' => $employees,
            'meta' => [
                'current_page' => $awards->currentPage(),
                'from' => $awards->firstItem(),
                'last_page' => $awards->lastPage(),
                'path' => $awards->path(),
                'per_page' => $awards->perPage(),
                'to' => $awards->lastItem(),
                'total' => $awards->total(),
            ],
            'filters' => [
                'search' => $request->search ?? '',
                'date' => $request->date ?? '',
                'per_page' => $per_page,
            ],
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
            'employee_id' => 'required',
            'award_date'  => 'required',
            'award_name'  => 'required',
            'award'       => 'required',
        ]);

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

        return redirect()->route('awards.index')->with('success', _lang('Saved Successfully'));
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show(Request $request, $id)
    {
        $award = Award::with('staff')->find($id);

        if ($request->wantsJson() || $request->header('X-Inertia')) {
            return Inertia::render('Backend/User/Award/View', [
                'award' => $award
            ]);
        }

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

        return redirect()->route('awards.index')->with('success', _lang('Updated Successfully'));
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $award = Award::find($id);

        // Store the name for the audit log
        $staffName = $award->staff->name;

        $award->delete();

        // audit log
        $audit = new AuditLog();
        $audit->date_changed = date('Y-m-d H:i:s');
        $audit->changed_by = auth()->user()->id;
        $audit->event = 'Deleted Award for ' . $staffName;
        $audit->save();

        return redirect()->route('awards.index')->with('success', _lang('Deleted Successfully'));
    }

    /**
     * Bulk delete selected awards
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function bulk_destroy(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'ids' => 'required|array',
            'ids.*' => 'exists:awards,id',
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator);
        }

        // Get award names for audit log
        $awardNames = Award::whereIn('id', $request->ids)->with('staff')->get()
            ->map(function ($award) {
                return $award->award_name . ' (' . $award->staff->name . ')';
            })->implode(', ');

        // Delete the awards
        Award::whereIn('id', $request->ids)->delete();

        // Audit log
        $audit = new AuditLog();
        $audit->date_changed = date('Y-m-d H:i:s');
        $audit->changed_by = auth()->user()->id;
        $audit->event = 'Bulk Deleted Awards: ' . $awardNames;
        $audit->save();

        return redirect()->route('awards.index')->with('success', _lang('Deleted Successfully'));
    }
}
