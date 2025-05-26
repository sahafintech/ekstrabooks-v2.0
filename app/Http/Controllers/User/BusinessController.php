<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Business;
use App\Models\BusinessBankAccount;
use App\Models\BusinessSetting;
use App\Models\BusinessType;
use App\Models\Currency;
use App\Models\Role;
use Database\Seeders\BusinessSettingSeeder;
use Database\Seeders\CurrencySeeder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Validator;
use Inertia\Inertia;

class BusinessController extends Controller
{

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware(function ($request, $next) {

            $route_name = request()->route()->getName();
            if ($route_name == 'business.store') {
                if (has_limit('business', 'business_limit') <= 0) {
                    if (!$request->ajax()) {
                        return back()->with('error', _lang('Sorry, Your have already reached your package quota !'));
                    } else {
                        return response()->json(['result' => 'error', 'message' => _lang('Sorry, Your have already reached your package quota !')]);
                    }
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
        $per_page = $request->get('per_page', 50);
        $search = $request->get('search', '');

        $query = Business::select('business.*')
            ->with('business_type', 'user');

        // Apply search if provided
        if (!empty($search)) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%");
            });
        }

        // Handle sorting
        $sorting = $request->get('sorting', []);
        $sortColumn = $sorting['column'] ?? 'id';
        $sortDirection = $sorting['direction'] ?? 'desc';

        if ($sortColumn === 'business_type.name') {
            $query->join('business_types', 'business.business_type_id', '=', 'business_types.id')
                ->orderBy('business_types.name', $sortDirection)
                ->select('business.*');
        } else {
            $query->orderBy('business.' . $sortColumn, $sortDirection);
        }

        $businesses = $query->paginate($per_page)->withQueryString();
        return Inertia::render('Backend/User/Business/List', [
            'businesses' => $businesses->items(),
            'meta' => [
                'current_page' => $businesses->currentPage(),
                'from' => $businesses->firstItem(),
                'last_page' => $businesses->lastPage(),
                'per_page' => $per_page,
                'to' => $businesses->lastItem(),
                'total' => $businesses->total(),
                'links' => $businesses->linkCollection(),
                'path' => $businesses->path(),
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
        $business_types = BusinessType::all();
        $currencies = Currency::all();
        $countriesJson = file_get_contents(app_path() . '/Helpers/country.json');
        $countriesData = json_decode($countriesJson, true);

        // Convert to array format suitable for frontend mapping
        $countries = [];
        foreach ($countriesData as $code => $data) {
            $countries[] = [
                'id' => $code,
                'code' => $code,
                'name' => $data['country'],
                'dial_code' => $data['dial_code']
            ];
        }
        return Inertia::render('Backend/User/Business/Create', [
            'business_types' => $business_types,
            'currencies' => $currencies,
            'countries' => $countries
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
            'name'             => 'required',
            'business_type_id' => 'required',
            'country'          => 'required',
            'currency'         => 'required',
            'logo'             => 'nullable|image|max:2048',
            'status'           => 'required',
            'default'          => 'required',
            'email'            => 'required'
        ]);

        if ($validator->fails()) {
            return redirect()->route('business.create')
                ->withErrors($validator)
                ->withInput();
        }

        $logo = 'default/default-company-logo.png';
        if ($request->hasfile('logo')) {
            $file = $request->file('logo');
            $logo = time() . $file->getClientOriginalName();
            $file->move(public_path() . "/uploads/media/", $logo);
        }

        DB::beginTransaction();

        $business                   = new Business();
        $business->name             = $request->input('name');
        $business->reg_no           = $request->input('reg_no');
        $business->vat_id           = $request->input('vat_id');
        $business->user_id          = auth()->id();
        $business->business_type_id = $request->input('business_type_id');
        $business->email            = $request->input('email');
        $business->phone            = $request->input('phone');
        $business->country          = $request->input('country');
        $business->currency         = $request->input('currency');
        $business->address          = $request->input('address');
        $business->zip              = $request->input('zip');
        $business->logo             = $logo;
        $business->status           = $request->input('status');
        if ($request->default == 1) {
            Business::where('default', 1)->update(['default' => 0]);
            $business->default = $request->input('default');
        }

        $business->save();

        $business->users()->attach($business->user_id, ['owner_id' => $business->user_id, 'is_active' => count($request->businessList) == 0 ? 1 : 0]);

        if (isset($request->bank_accounts)) {
            for ($i = 0; $i < count($request->bank_accounts['bank_name']); $i++) {
                $bank_account                     = new BusinessBankAccount();
                $bank_account->bank_name          = $request->bank_accounts['bank_name'][$i];
                $bank_account->account_name       = $request->bank_accounts['account_name'][$i];
                $bank_account->account_number     = $request->bank_accounts['account_number'][$i];
                $bank_account->account_currency   = $request->bank_accounts['account_currency'][$i];
                $bank_account->branch             = $request->bank_accounts['branch'][$i];
                $bank_account->swift_code         = $request->bank_accounts['swift_code'][$i];
                $bank_account->display_on_invoice = $request->bank_accounts['display_on_invoice'][$i];
                $bank_account->business_id        = $business->id;
                $bank_account->save();
            }
        }

        DB::commit();

        // audit log
        $audit = new AuditLog();
        $audit->date_changed = date('Y-m-d H:i:s');
        $audit->changed_by = auth()->user()->id;
        $audit->event = 'Created Business ' . $business->name;
        $audit->save();

        if ($business->id > 0) {
            return redirect()->route('business.index')->with('success', _lang('Saved Successfully'));
        }
    }

    /**
     * Display System User list
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function users(Request $request, $id)
    {
        // Find the business first
        $business = Business::findOrFail($id);

        $per_page = $request->get('per_page', 50);
        $search = $request->get('search', '');

        // Get users related to this business with roles and pagination
        $usersQuery = $business->users()->with('roles');

        // Apply search if provided
        if (!empty($search)) {
            $usersQuery->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        // Get paginated results
        $users = $usersQuery->paginate($per_page)->withQueryString();

        return Inertia::render('Backend/User/Business/SystemUser/List', [
            'business' => $business->only(['id', 'name']),
            'users' => $users->items(),
            'roles' => Role::all(),
            'meta' => [
                'current_page' => $users->currentPage(),
                'from' => $users->firstItem(),
                'last_page' => $users->lastPage(),
                'per_page' => $per_page,
                'to' => $users->lastItem(),
                'total' => $users->total(),
                'links' => $users->linkCollection(),
                'path' => $users->path(),
            ],
            'filters' => [
                'search' => $search
            ]
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        $business  = Business::with('bank_accounts')->find($id);

        $currencies = Currency::all();
        $countriesJson = file_get_contents(app_path() . '/Helpers/country.json');
        $countriesData = json_decode($countriesJson, true);

        // Convert to array format suitable for frontend mapping
        $countries = [];
        foreach ($countriesData as $code => $data) {
            $countries[] = [
                'id' => $code,
                'code' => $code,
                'name' => $data['country'],
                'dial_code' => $data['dial_code']
            ];
        }

        $business_types = BusinessType::all();
        $businessSettings = BusinessSetting::where('business_id', request()->activeBusiness->id)->get();

        if ($currencies->count() <= 0 && $businessSettings->count() <= 0) {
            // Instantiate the seeder with arguments
            $cseeder = new CurrencySeeder(auth()->user()->id, request()->activeBusiness->id);

            // Run the seeder
            $cseeder->run();

            // Instantiate the seeder with arguments
            $bseeder = new BusinessSettingSeeder(request()->activeBusiness->id);

            // Run the seeder
            $bseeder->run();
        }

        return Inertia::render('Backend/User/Business/Edit', [
            'business' => $business,
            'id' => $id,
            'currencies' => $currencies,
            'countries' => $countries,
            'business_types' => $business_types,
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
            'name'             => 'required',
            'business_type_id' => 'required',
            'country'          => 'required',
            'status'           => 'required',
            'default'          => 'required',
        ]);

        if ($validator->fails()) {
            return redirect()->route('business.edit', $id)
                ->withErrors($validator)
                ->withInput();
        }

        DB::beginTransaction();

        $business = Business::find($id);

        if ($request->hasfile('logo')) {
            $file = $request->file('logo');
            $logo = time() . $file->getClientOriginalName();
            $file->move(public_path() . "/uploads/media/", $logo);

            // delete old image
            if ($business->logo != 'default/default-company-logo.png') {
                $image_path = public_path() . "/uploads/media/" . $business->logo;
                if (file_exists($image_path)) {
                    unlink($image_path);
                }
            }
        }

        $business->name             = $request->input('name');
        $business->reg_no           = $request->input('reg_no');
        $business->vat_id           = $request->input('vat_id');
        $business->business_type_id = $request->input('business_type_id');
        $business->email            = $request->input('email');
        $business->phone            = $request->input('phone');
        $business->country          = $request->input('country');
        $business->zip              = $request->input('zip');
        if ($business->transactions->count() == 0 && $business->quotations->count() == 0) {
            $business->currency = $request->input('currency');
        }
        $business->address = $request->input('address');
        if ($request->hasfile('logo')) {
            $business->logo = $logo;
        }

        $business->status = $request->input('status');

        if (isset($request->bank_accounts)) {
            BusinessBankAccount::where('business_id', $business->id)->delete();
            for ($i = 0; $i < count($request->bank_accounts['bank_name']); $i++) {
                $bank_account                     = new BusinessBankAccount();
                $bank_account->bank_name          = $request->bank_accounts['bank_name'][$i];
                $bank_account->account_name       = $request->bank_accounts['account_name'][$i];
                $bank_account->account_number     = $request->bank_accounts['account_number'][$i];
                $bank_account->account_currency   = $request->bank_accounts['account_currency'][$i];
                $bank_account->branch             = $request->bank_accounts['branch'][$i];
                $bank_account->swift_code         = $request->bank_accounts['swift_code'][$i];
                $bank_account->display_on_invoice = $request->bank_accounts['display_on_invoice'][$i];
                $bank_account->business_id        = $business->id;
                $bank_account->save();
            }
        }

        if ($request->default == 1) {
            Business::where('default', 1)->update(['default' => 0]);
            $business->default = $request->input('default');
        }

        $business->save();

        DB::commit();

        // audit log
        $audit = new AuditLog();
        $audit->date_changed = date('Y-m-d H:i:s');
        $audit->changed_by = auth()->user()->id;
        $audit->event = 'Updated Business ' . $business->name;
        $audit->save();

        return redirect()->route('business.index')->with('success', _lang('Updated Successfully'));
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $business = Business::find($id);
        if ($business->default == 1 || Business::count() == 1) {
            return redirect()->route('business.index')->with('error', _lang('Sorry, You will not be able to delete default business!'));
        }

        // audit log
        $audit = new AuditLog();
        $audit->date_changed = date('Y-m-d H:i:s');
        $audit->changed_by = auth()->user()->id;
        $audit->event = 'Deleted Business ' . $business->name;
        $audit->save();

        $business->delete();
        return redirect()->route('business.index')->with('success', _lang('Deleted Successfully'));
    }

    public function bulk_destroy(Request $request)
    {
        foreach ($request->ids as $id) {
            $business = Business::find($id);
            if ($business->default == 1 || Business::count() == 1) {
                return redirect()->route('business.index')->with('error', _lang('Sorry, You will not be able to delete default business!'));
            }

            // audit log
            $audit = new AuditLog();
            $audit->date_changed = date('Y-m-d H:i:s');
            $audit->changed_by = auth()->user()->id;
            $audit->event = 'Deleted Business ' . $business->name;
            $audit->save();

            $business->delete();
        }
        return redirect()->route('business.index')->with('success', _lang('Deleted Successfully'));
    }

    /** Switch Business Account **/
    public function switch_business(Request $request, $id)
    {
        $user = auth()->user();
        if ($user->user_type != 'user') {
            return back()->with('error', _lang('Permission denied !'));
        }
        $business = $user->business()->where('business.id', $id)->first();

        if (!$business) {
            return back()->with('error', _lang('Permission denied !'));
        }

        $user->business()->updateExistingPivot($request->activeBusiness->id, ['is_active' => 0]);

        $user->business()->updateExistingPivot($id, ['is_active' => 1]);

        return redirect()->route('dashboard.index')->with('success', _lang('Business switched to') . ' ' . $request->activeBusiness->name);
    }
}
