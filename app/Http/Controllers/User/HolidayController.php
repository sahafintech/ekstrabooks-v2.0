<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Holiday;
use Carbon\Carbon;
use DataTables;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Validator;

class HolidayController extends Controller
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
    public function index()
    {
        $holidays = Holiday::select('holidays.*')
            ->whereRaw('YEAR(date)=?', [date('Y')])
            ->get();

        return view('backend.user.holiday.list', compact('holidays'));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create(Request $request)
    {
        if (!$request->ajax()) {
            return back();
        } else {
            return view('backend.user.holiday.modal.create');
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
        $customMessages = [];
        $data           = [];
        foreach ($request->date as $key => $value) {
            $customMessages["date.$key.distinct"] = 'This date (:input) should be unique';
            $customMessages["date.$key.unique"]   = 'This date (:input) should be unique';
            array_push($data, [
                'title'       => $request->title[$key],
                'date'        => Carbon::parse($value)->format('Y-m-d'),
                'business_id' => business_id(),
                'user_id'     => $request->activeBusiness->user_id,
                'created_at'  => now(),
                'updated_at'  => now(),
            ]);
        }

        $request->validate([
            'title'   => 'required|array|min:1',
            'title.*' => 'required|distinct',
            'date'    => 'required|array|min:1',
            'date.*'  => 'required|distinct|unique:holidays,date|date',
        ], $customMessages);

        Holiday::insert($data);

        // audit log
        $audit = new AuditLog();
        $audit->date_changed = date('Y-m-d H:i:s');
        $audit->changed_by = auth()->user()->id;
        $audit->event = 'Created ' . count($data) . ' Holidays';
        $audit->save();

        return redirect()->route('holidays.index')->with('success', _lang('Saved Successfully'));
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit(Request $request, $id)
    {
        $holiday = Holiday::find($id);
        if (!$request->ajax()) {
            return back();
        } else {
            return view('backend.user.holiday.modal.edit', compact('holiday', 'id'));
        }
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
            'title' => 'required',
            'date'  => [
                'required',
                'date',
                Rule::unique('holidays')->ignore($id),
            ],
        ]);

        if ($validator->fails()) {
            if ($request->ajax()) {
                return response()->json(['result' => 'error', 'message' => $validator->errors()->all()]);
            } else {
                return redirect()->route('holidays.edit', $id)
                    ->withErrors($validator)
                    ->withInput();
            }
        }

        $holiday        = Holiday::find($id);
        $holiday->title = $request->input('title');
        $holiday->date  = Carbon::parse($request->input('date'))->format('Y-m-d');

        $holiday->save();

        // audit log
        $audit = new AuditLog();
        $audit->date_changed = date('Y-m-d H:i:s');
        $audit->changed_by = auth()->user()->id;
        $audit->event = 'Updated Holiday ' . $holiday->title;
        $audit->save();

        if (!$request->ajax()) {
            return redirect()->route('holidays.index')->with('success', _lang('Updated Successfully'));
        } else {
            return response()->json(['result' => 'success', 'action' => 'update', 'message' => _lang('Updated Successfully'), 'data' => $holiday, 'table' => '#holidays_table']);
        }
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function weekends(Request $request)
    {
        if ($request->isMethod('get')) {
            $weekends = json_decode(get_business_option('weekends', '[]', business_id()));
            return view('backend.user.holiday.modal.weekends', compact('weekends'));
        } else {
            update_business_option('weekends', json_encode($request->weekends), business_id());
            return redirect()->route('holidays.index')->with('success', _lang('Updated Successfully'));
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
        $holiday = Holiday::find($id);

        // audit log
        $audit = new AuditLog();
        $audit->date_changed = date('Y-m-d H:i:s');
        $audit->changed_by = auth()->user()->id;
        $audit->event = 'Deleted Holiday ' . $holiday->title;
        $audit->save();

        $holiday->delete();
        return redirect()->route('holidays.index')->with('success', _lang('Deleted Successfully'));
    }
}
