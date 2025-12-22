<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Holiday;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Illuminate\Support\Facades\Gate;

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
    public function index(Request $request)
    {
        Gate::authorize('holidays.view');
        $query = Holiday::select('holidays.*')
            ->whereRaw('YEAR(date)=?', [date('Y')]);

        // Search functionality
        if ($request->has('search') && !empty($request->search)) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%$search%")
                    ->orWhere('date', 'like', "%$search%");
            });
        }

        // Date filter
        if ($request->has('date') && !empty($request->date)) {
            $date = $request->date;
            $query->whereDate('date', $date);
        }

        // Define pagination
        $per_page = $request->input('per_page', 50);

        // Get holidays with pagination
        $holidays = $query->orderBy('date', 'desc')->paginate($per_page)->withQueryString();

        $weekends = json_decode(get_business_option('weekends', '[]', business_id()));

        // Return Inertia view
        return Inertia::render('Backend/User/Holiday/List', [
            'holidays' => $holidays->items(),
            'meta' => [
                'current_page' => $holidays->currentPage(),
                'per_page' => $holidays->perPage(),
                'from' => $holidays->firstItem(),
                'to' => $holidays->lastItem(),
                'total' => $holidays->total(),
                'last_page' => $holidays->lastPage(),
            ],
            'filters' => [
                'search' => $request->search ?? '',
                'date' => $request->date ?? '',
            ],
            'weekends' => $weekends,
            'trashed_holidays' => Holiday::onlyTrashed()->count(),
        ]);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create(Request $request)
    {
        Gate::authorize('holidays.create');
        return Inertia::render('Backend/User/Holiday/Create');
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        Gate::authorize('holidays.create');
        $validator = Validator::make($request->all(), [
            'title' => 'required|string|max:191',
            'date'  => 'required|unique:holidays,date|date',
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        $holiday = new Holiday();
        $holiday->date = Carbon::parse($request->date)->format('Y-m-d');
        $holiday->title = $request->title;
        $holiday->save();

        // audit log
        $audit = new AuditLog();
        $audit->date_changed = date('Y-m-d H:i:s');
        $audit->changed_by = Auth::id();
        $audit->event = 'Created Holiday: ' . $holiday->title;
        $audit->save();

        return redirect()->route('holidays.index')->with('success', _lang('Saved Successfully'));
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        Gate::authorize('holidays.view');
        $holiday = Holiday::find($id);

        return Inertia::render('Backend/User/Holiday/Edit', [
            'holiday' => $holiday
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
        Gate::authorize('holidays.update');
        $validator = Validator::make($request->all(), [
            'title' => 'required|max:191',
            'date'  => [
                'required',
                'date',
                Rule::unique('holidays')->ignore($id)->where(function ($query) {
                    return $query->where('business_id', business_id());
                }),
            ],
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        $holiday        = Holiday::find($id);
        $holiday->title = $request->input('title');
        $holiday->date  = Carbon::parse($request->input('date'))->format('Y-m-d');

        $holiday->save();

        // audit log
        $audit = new AuditLog();
        $audit->date_changed = date('Y-m-d H:i:s');
        $audit->changed_by = Auth::id();
        $audit->event = 'Updated Holiday ' . $holiday->title;
        $audit->save();

        return redirect()->route('holidays.index')->with('success', _lang('Updated Successfully'));
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

            return Inertia::render('Backend/User/Holiday/Weekends', [
                'weekends' => $weekends
            ]);
        } else {
            update_business_option('weekends', json_encode($request->weekends), business_id());

            // audit log
            $audit = new AuditLog();
            $audit->date_changed = date('Y-m-d H:i:s');
            $audit->changed_by = Auth::id();
            $audit->event = 'Updated Weekend days';
            $audit->save();

            return redirect()->back()->with('success', _lang('Saved Successfully'));
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
        Gate::authorize('holidays.delete');
        $holiday = Holiday::find($id);
        $holiday->delete();
        return redirect()->route('holidays.index')->with('success', _lang('Deleted Successfully'));
    }

    /**
     * Bulk delete selected holidays
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function bulk_delete(Request $request)
    {
        Gate::authorize('holidays.delete');
        // Validate the request
        $validator = Validator::make($request->all(), [
            'ids' => 'required|array',
            'ids.*' => 'required|integer|exists:holidays,id'
        ]);

        if ($validator->fails()) {
            return redirect()->route('holidays.index')->with('error', $validator->errors()->first());
        }

        // Get holiday names for audit log
        $holidayNames = Holiday::whereIn('id', $request->ids)->pluck('title')->implode(', ');

        // Delete the holidays
        Holiday::whereIn('id', $request->ids)->delete();

        // Audit log
        $audit = new AuditLog();
        $audit->date_changed = date('Y-m-d H:i:s');
        $audit->changed_by = Auth::id();
        $audit->event = 'Bulk Deleted Holidays: ' . $holidayNames;
        $audit->save();

        return redirect()->route('holidays.index')->with('success', _lang('Deleted Successfully'));
    }

    /**
     * Display a listing of trashed holidays.
     *
     * @return \Illuminate\Http\Response
     */
    public function trash(Request $request)
    {
        Gate::authorize('holidays.view');
        $per_page = $request->get('per_page', 10);
        $search = $request->get('search', '');
        $sorting = $request->get('sorting', ['column' => 'id', 'direction' => 'desc']);

        $query = Holiday::onlyTrashed();

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%$search%")
                    ->orWhere('date', 'like', "%$search%");
            });
        }

        // Handle sorting
        if (isset($sorting['column']) && isset($sorting['direction'])) {
            $column = $sorting['column'];
            $direction = $sorting['direction'];
            $query->orderBy($column, $direction);
        }

        $holidays = $query->paginate($per_page);

        return Inertia::render('Backend/User/Holiday/Trash', [
            'holidays' => $holidays->items(),
            'meta' => [
                'current_page' => $holidays->currentPage(),
                'per_page' => $holidays->perPage(),
                'from' => $holidays->firstItem(),
                'to' => $holidays->lastItem(),
                'total' => $holidays->total(),
                'last_page' => $holidays->lastPage(),
            ],
            'filters' => [
                'search' => $search,
                'sorting' => $sorting,
            ],
        ]);
    }

    /**
     * Restore the specified holiday from trash.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function restore(Request $request, $id)
    {
        Gate::authorize('holidays.restore');
        $holiday = Holiday::onlyTrashed()->findOrFail($id);

        // audit log
        $audit = new AuditLog();
        $audit->date_changed = date('Y-m-d H:i:s');
        $audit->changed_by = Auth::id();
        $audit->event = 'Restored Holiday ' . $holiday->title;
        $audit->save();

        $holiday->restore();

        return redirect()->route('holidays.trash')->with('success', _lang('Restored Successfully'));
    }

    /**
     * Bulk restore selected holidays from trash.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function bulk_restore(Request $request)
    {
        Gate::authorize('holidays.restore');
        foreach ($request->ids as $id) {
            $holiday = Holiday::onlyTrashed()->findOrFail($id);

            // audit log
            $audit = new AuditLog();
            $audit->date_changed = date('Y-m-d H:i:s');
            $audit->changed_by = Auth::id();
            $audit->event = 'Restored Holiday ' . $holiday->title;
            $audit->save();

            $holiday->restore();
        }

        return redirect()->route('holidays.trash')->with('success', _lang('Restored Successfully'));
    }

    /**
     * Permanently delete the specified holiday from trash.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function permanent_destroy(Request $request, $id)
    {
        Gate::authorize('holidays.delete');
        $holiday = Holiday::onlyTrashed()->findOrFail($id);

        // audit log
        $audit = new AuditLog();
        $audit->date_changed = date('Y-m-d H:i:s');
        $audit->changed_by = Auth::id();
        $audit->event = 'Permanently Deleted Holiday ' . $holiday->title;
        $audit->save();

        $holiday->forceDelete();

        return redirect()->route('holidays.trash')->with('success', _lang('Permanently Deleted Successfully'));
    }

    /**
     * Bulk permanently delete selected holidays from trash.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function bulk_permanent_destroy(Request $request)
    {
        Gate::authorize('holidays.delete');
        foreach ($request->ids as $id) {
            $holiday = Holiday::onlyTrashed()->findOrFail($id);

            // audit log
            $audit = new AuditLog();
            $audit->date_changed = date('Y-m-d H:i:s');
            $audit->changed_by = Auth::id();
            $audit->event = 'Permanently Deleted Holiday ' . $holiday->title;
            $audit->save();

            $holiday->forceDelete();
        }

        return redirect()->route('holidays.trash')->with('success', _lang('Permanently Deleted Successfully'));
    }
}
