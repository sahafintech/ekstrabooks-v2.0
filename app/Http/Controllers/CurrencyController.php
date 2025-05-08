<?php

namespace App\Http\Controllers;

use App\Http\Middleware\Business;
use App\Models\AuditLog;
use App\Models\Currency;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Validator;
use Inertia\Inertia;

class CurrencyController extends Controller
{

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        date_default_timezone_set(get_option('timezone', 'Asia/Dhaka'));
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $per_page = $request->get('per_page', 10);
        $search = $request->get('search', '');

        $query = Currency::query();

        // Apply search if provided
        if (!empty($search)) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%")
                    ->orWhere('exchange_rate', 'like', "%{$search}%")
                    ->orWhere('base_currency', 'like', "%{$search}%");
            });
        }

        // Handle sorting
        $sorting = $request->get('sorting', []);
        $sortColumn = $sorting['column'] ?? 'id';
        $sortDirection = $sorting['direction'] ?? 'desc';

        $query->orderBy($sortColumn, $sortDirection);

        $currencies = $query->paginate($per_page)->withQueryString();
        return Inertia::render('Backend/User/Currency/List', [
            'currencies' => $currencies->items(),
            'meta' => [
                'current_page' => $currencies->currentPage(),
                'from' => $currencies->firstItem(),
                'last_page' => $currencies->lastPage(),
                'per_page' => $per_page,
                'to' => $currencies->lastItem(),
                'total' => $currencies->total(),
                'links' => $currencies->linkCollection(),
                'path' => $currencies->path(),
            ],
            'filters' => [
                'search' => $search,
                'sorting' => $sorting,
            ],
        ]);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create(Request $request)
    {
        return Inertia::render('Backend/User/Currency/Create');
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
            'name'          => 'required|min:3|max:4',
            'description'   => 'required',
            'exchange_rate' => 'required|numeric',
            'base_currency' => 'required',
            'status'        => 'required',
        ]);

        if ($validator->fails()) {
            return redirect()->route('currency.create')
                ->withErrors($validator)
                ->withInput();
        }

        if ($request->input('base_currency') == '1') {
            $currency = Currency::where('base_currency', '1')->first();
            if ($currency) {
                $currency->base_currency = 0;
                $currency->save();
            }
        }

        $currency                = new Currency();
        $currency->name          = $request->input('name');
        $currency->description   = $request->input('description');
        $currency->exchange_rate = $request->input('exchange_rate');
        $currency->base_currency = $request->input('base_currency');
        $currency->status        = $request->input('status');

        $currency->save();

        \Cache::forget('base_currency');
        \Cache::forget('base_currency_id');

        //Prefix Output
        $currency->base_currency = $currency->base_currency == '1' ? show_status(_lang('Yes'), 'success') : show_status(_lang('No'), 'danger');
        $currency->status        = status($currency->status);

        // audit log
        $audit = new AuditLog();
        $audit->date_changed = date('Y-m-d H:i:s');
        $audit->changed_by = auth()->user()->id;
        $audit->event = 'Currency Created: ' . $currency->name;
        $audit->save();

        return redirect()->route('currency.index')->with('success', _lang('Saved Successfully'));
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        $currency = Currency::find($id);
        return Inertia::render('Backend/User/Currency/Edit', [
            'currency' => $currency,
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
            'name'          => 'required|min:3|max:4',
            'description'   => 'required',
            'exchange_rate' => 'required|numeric',
            'base_currency' => 'required',
            'status'        => 'required',
        ]);

        if ($validator->fails()) {
            return redirect()->route('currency.edit', $id)
                ->withErrors($validator)
                ->withInput();
        }

        if ($request->input('base_currency') == '1') {
            $base_currency = Currency::where('base_currency', '1')->first();
            if ($base_currency) {
                $base_currency->base_currency = 0;
                $base_currency->save();
            }
        }

        $currency                = Currency::find($id);
        $currency->name          = $request->input('name');
        $currency->description   = $request->input('description');
        $currency->exchange_rate = $request->input('exchange_rate');
        $currency->base_currency = $request->input('base_currency');
        $currency->status        = $request->input('status');

        $currency->save();

        \Cache::forget('base_currency');
        \Cache::forget('base_currency_id');

        //Prefix Output
        $currency->base_currency = $currency->base_currency == '1' ? show_status(_lang('Yes'), 'success') : show_status(_lang('No'), 'danger');
        $currency->status        = status($currency->status);

        // audit log
        $audit = new AuditLog();
        $audit->date_changed = date('Y-m-d H:i:s');
        $audit->changed_by = auth()->user()->id;
        $audit->event = 'Currency Updated: ' . $currency->name;
        $audit->save();

        return redirect()->route('currency.index')->with('success', _lang('Updated Successfully'));
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $currency = Currency::find($id);
        if ($currency->base_currency == 1) {
            return redirect()->route('currency.index')->with('error', _lang('You can not remove base currency !'));
        }

        // audit log
        $audit = new AuditLog();
        $audit->date_changed = date('Y-m-d H:i:s');
        $audit->changed_by = auth()->user()->id;
        $audit->event = 'Currency Deleted: ' . $currency->name;
        $audit->save();

        $currency->delete();
        return redirect()->route('currency.index')->with('success', _lang('Deleted Successfully'));
    }

    public function bulk_destroy(Request $request)
    {
        foreach ($request->ids as $id) {
            $currency = Currency::find($id);
            if ($currency->base_currency == 1) {
                return redirect()->route('currency.index')->with('error', _lang('You can not remove base currency !'));
            }

            // audit log
            $audit = new AuditLog();
            $audit->date_changed = date('Y-m-d H:i:s');
            $audit->changed_by = auth()->user()->id;
            $audit->event = 'Currency Deleted: ' . $currency->name;
            $audit->save();

            $currency->delete();
        }
        return redirect()->route('currency.index')->with('success', _lang('Deleted Successfully'));
    }
}
