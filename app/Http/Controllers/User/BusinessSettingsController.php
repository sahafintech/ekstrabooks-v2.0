<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Mail\GeneralMail;
use App\Models\AuditLog;
use App\Models\Business;
use App\Models\BusinessSetting;
use App\Models\Currency;
use App\Models\Tax;
use App\Models\User;
use App\Utilities\Overrider;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;
use Inertia\Inertia;

class BusinessSettingsController extends Controller
{

    private $ignoreRequests = ['_token', 'businessList', 'activeBusiness', 'isOwner', 'permissionList'];

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct() {}

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @param  string  $tab
     * @return \Illuminate\Http\Response
     */
    public function settings($id, $tab = 'general')
    {
        Gate::authorize('business.settings');
        $business = Business::with('systemSettings')->find($id);

        // Provide common data for all settings pages
        $timezonesList = timezone_list();
        $timezones = [];
        foreach ($timezonesList as $timezone) {
            $timezones[] = ['id' => $timezone['ZONE'], 'value' => $timezone['ZONE'], 'label' => $timezone['GMT'], 'name' => $timezone['GMT'] . ' ' . $timezone['ZONE']];
        }

        $dateFormats = [
            ['format' => 'Y-m-d',   'example' => date('Y-m-d')],
            ['format' => 'd-m-Y',   'example' => date('d-m-Y')],
            ['format' => 'd/m/Y',   'example' => date('d/m/Y')],
            ['format' => 'm-d-Y',   'example' => date('m-d-Y')],
            ['format' => 'm.d.Y',   'example' => date('m.d.Y')],
            ['format' => 'm/d/Y',   'example' => date('m/d/Y')],
            ['format' => 'd.m.Y',   'example' => date('d.m.Y')],
            ['format' => 'd/M/Y',   'example' => date('d/M/Y')],
            ['format' => 'M/d/Y',   'example' => date('M/d/Y')],
            ['format' => 'd M, Y',  'example' => date('d M, Y')],
        ];

        $languages = load_language();

        $financial_years = [
            ['id' => 'January,December', 'value' => 'January - December'],
            ['id' => 'February,January', 'value' => 'February - January'],
            ['id' => 'March,February', 'value' => 'March - February'],
            ['id' => 'April,March', 'value' => 'April - March'],
            ['id' => 'May,April', 'value' => 'May - April'],
            ['id' => 'June,May', 'value' => 'June - May'],
            ['id' => 'July,June', 'value' => 'July - June'],
            ['id' => 'August,July', 'value' => 'August - July'],
            ['id' => 'September,August', 'value' => 'September - August'],
            ['id' => 'October,September', 'value' => 'October - September'],
            ['id' => 'November,October', 'value' => 'November - October'],
            ['id' => 'December,November', 'value' => 'December - November'],
        ];

        $invoiceColumn = json_decode(get_setting($business->systemSettings, 'invoice_column', null, $id));
        $receiptColumn = json_decode(get_setting($business->systemSettings, 'receipt_column', null, $id));
        $quotationColumn = json_decode(get_setting($business->systemSettings, 'quotation_column', null, $id));
        $salesReturnColumn = json_decode(get_setting($business->systemSettings, 'sales_return-column', null, $id));
        $purchaseReturnColumn = json_decode(get_setting($business->systemSettings, 'purchase_return-column', null, $id));
        $purchaseColumn = json_decode(get_setting($business->systemSettings, 'purchase_column', null, $id));

        $taxes = Tax::all();
        $currencies = Currency::all();

        $data = [
            'business' => $business,
            'id' => $id,
            'timezones' => $timezones,
            'dateFormats' => $dateFormats,
            'languages' => $languages,
            'financial_years' => $financial_years,
            'activeTab' => $tab,
            'invoiceColumn' => $invoiceColumn,
            'receiptColumn' => $receiptColumn,
            'quotationColumn' => $quotationColumn,
            'salesReturnColumn' => $salesReturnColumn,
            'purchaseReturnColumn' => $purchaseReturnColumn,
            'purchaseColumn' => $purchaseColumn,
            'currencies' => $currencies,
            'taxes' => $taxes
        ];

        // Render the appropriate component based on the active tab
        switch ($tab) {
            case 'currency':
                return Inertia::render('Backend/User/Business/Settings/Currency', $data);
            case 'invoice':
                return Inertia::render('Backend/User/Business/Settings/Invoice', $data);
            case 'credit_invoice':
                return Inertia::render('Backend/User/Business/Settings/CreditInvoice', $data);
            case 'cash_invoice':
                return Inertia::render('Backend/User/Business/Settings/CashInvoice', $data);
            case 'bill_invoice':
                return Inertia::render('Backend/User/Business/Settings/Bill', $data);
            case 'sales_return':
                return Inertia::render('Backend/User/Business/Settings/SalesReturn', $data);
            case 'purchase_return':
                return Inertia::render('Backend/User/Business/Settings/PurchaseReturn', $data);
            case 'pos_settings':
                return Inertia::render('Backend/User/Business/Settings/PosSettings', $data);
            case 'payroll':
                return Inertia::render('Backend/User/Business/Settings/Payroll', $data);
            case 'approvals':
                // Get users that belong to this business
                $users = User::whereHas('business', function ($query) use ($id) {
                    $query->where('business_id', $id);
                })->select('id', 'name', 'email')->get();
                $data['users'] = $users;
                return Inertia::render('Backend/User/Business/Settings/Approvals', $data);
            default:
                return Inertia::render('Backend/User/Business/Settings/General', $data);
        }
    }

    public function store_general_settings(Request $request, $businessId)
    {
        Gate::authorize('business.settings');
        $settingsData = $request->except($this->ignoreRequests);

        foreach ($settingsData as $key => $value) {
            $value = is_array($value) ? json_encode($value) : $value;
            update_business_option($key, $value, $businessId);
        }

        // audit log
        $audit = new AuditLog();
        $audit->date_changed = date('Y-m-d H:i:s');
        $audit->changed_by = auth()->user()->id;
        $audit->event = 'Updated Business Settings for ' . $request->activeBusiness->name;
        $audit->save();

        return back()->with('success', _lang('Saved Successfully'));
    }

    public function store_payroll_settings(Request $request, $businessId)
    {
        Gate::authorize('business.settings');
        $settingsData = $request->except($this->ignoreRequests);

        foreach ($settingsData as $key => $value) {
            $value = is_array($value) ? json_encode($value) : $value;
            update_business_option($key, $value, $businessId);
        }

        // audit log
        $audit = new AuditLog();
        $audit->date_changed = date('Y-m-d H:i:s');
        $audit->changed_by = auth()->user()->id;
        $audit->event = 'Updated Payroll Settings for ' . $request->activeBusiness->name;
        $audit->save();

        return back()->with('success', _lang('Saved Successfully'));
    }

    public function store_currency_settings(Request $request, $businessId)
    {
        Gate::authorize('business.settings');
        $validator = Validator::make($request->all(), [
            'currency_position' => 'required',
            //'thousand_sep'      => 'required',
            //'decimal_sep'       => 'required',
            'decimal_places'    => 'required|integer',
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        $settingsData = $request->except($this->ignoreRequests);

        foreach ($settingsData as $key => $value) {
            update_business_option($key, $value, $businessId);
        }

        // audit log
        $audit = new AuditLog();
        $audit->date_changed = date('Y-m-d H:i:s');
        $audit->changed_by = auth()->user()->id;
        $audit->event = 'Updated Currency Settings for ' . get_option('currency');
        $audit->save();

        return back()->with('success', _lang('Saved Successfully'));
    }

    public function store_invoice_settings(Request $request, $businessId)
    {
        Gate::authorize('business.settings');
        $validator = Validator::make($request->all(), [
            'invoice_title'    => 'required',
            'invoice_number'   => 'required|integer',
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        $settingsData = $request->except($this->ignoreRequests);

        foreach ($settingsData as $key => $value) {
            $value = is_array($value) ? json_encode($value) : $value;
            update_business_option($key, $value, $businessId);
        }

        // audit log
        $audit = new AuditLog();
        $audit->date_changed = date('Y-m-d H:i:s');
        $audit->changed_by = auth()->user()->id;
        $audit->event = 'Updated Invoice Settings for ' . $request->invoice_title;
        $audit->save();

        return back()->with('success', _lang('Saved Successfully'));
    }

    public function store_receipt_settings(Request $request, $businessId)
    {
        Gate::authorize('business.settings');
        $validator = Validator::make($request->all(), [
            'receipt_title'    => 'required',
            'receipt_number'   => 'required|integer',
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        $settingsData = $request->except($this->ignoreRequests);

        foreach ($settingsData as $key => $value) {
            $value = is_array($value) ? json_encode($value) : $value;
            update_business_option($key, $value, $businessId);
        }

        // audit log
        $audit = new AuditLog();
        $audit->date_changed = date('Y-m-d H:i:s');
        $audit->changed_by = auth()->user()->id;
        $audit->event = 'Updated Cash Invoice Settings for ' . $request->receipt_title;
        $audit->save();

        return back()->with('success', _lang('Saved Successfully'));
    }

    public function store_purchase_settings(Request $request, $businessId)
    {
        Gate::authorize('business.settings');
        $validator = Validator::make($request->all(), [
            'purchase_title'    => 'required',
            'purchase_number'   => 'required|integer',
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        $settingsData = $request->except($this->ignoreRequests);

        foreach ($settingsData as $key => $value) {
            $value = is_array($value) ? json_encode($value) : $value;
            update_business_option($key, $value, $businessId);
        }

        // audit log
        $audit = new AuditLog();
        $audit->date_changed = date('Y-m-d H:i:s');
        $audit->changed_by = auth()->user()->id;
        $audit->event = 'Updated Purchase Settings for ' . $request->purchase_title;
        $audit->save();

        return back()->with('success', _lang('Saved Successfully'));
    }

    public function store_sales_return_settings(Request $request, $businessId)
    {
        Gate::authorize('business.settings');
        $validator = Validator::make($request->all(), [
            'sales_return_title'    => 'required',
            'sales_return_number'   => 'required|integer',
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        $settingsData = $request->except($this->ignoreRequests);

        foreach ($settingsData as $key => $value) {
            $value = is_array($value) ? json_encode($value) : $value;
            update_business_option($key, $value, $businessId);
        }

        // audit log
        $audit = new AuditLog();
        $audit->date_changed = date('Y-m-d H:i:s');
        $audit->changed_by = auth()->user()->id;
        $audit->event = 'Updated Sales Return Settings for ' . $request->sales_return_title;
        $audit->save();

        return back()->with('success', _lang('Saved Successfully'));
    }

    public function store_purchase_return_settings(Request $request, $businessId)
    {
        Gate::authorize('business.settings');
        $validator = Validator::make($request->all(), [
            'purchase_return_title'    => 'required',
            'purchase_return_number'   => 'required|integer',
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        $settingsData = $request->except($this->ignoreRequests);

        foreach ($settingsData as $key => $value) {
            $value = is_array($value) ? json_encode($value) : $value;
            update_business_option($key, $value, $businessId);
        }

        // audit log
        $audit = new AuditLog();
        $audit->date_changed = date('Y-m-d H:i:s');
        $audit->changed_by = auth()->user()->id;
        $audit->event = 'Updated Purchase Return Settings for ' . $request->purchase_return_title;
        $audit->save();

        return back()->with('success', _lang('Saved Successfully'));
    }

    public function store_payment_gateway_settings(Request $request, $businessId)
    {
        Gate::authorize('business.settings');
        $slug     = $request->slug;
        $rules    = [$slug . '.status' => 'required'];
        $messages = [];
        foreach ($request->$slug as $key => $val) {
            if ($key == 'status') {
                continue;
            }
            $rules[$slug . '.' . $key]                     = "required_if:$slug.status,1";
            $messages[$slug . '.' . $key . '.required_if'] = ucwords(str_replace("_", " ", $key)) . ' ' . _lang("is required");
        }

        $validator = Validator::make($request->all(), $rules, $messages);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        $settingsData = $request->except($this->ignoreRequests);

        foreach ($settingsData as $key => $value) {
            $value = is_array($value) ? json_encode($value) : $value;
            update_business_option($key, $value, $businessId);
        }

        // audit log
        $audit = new AuditLog();
        $audit->date_changed = date('Y-m-d H:i:s');
        $audit->changed_by = auth()->user()->id;
        $audit->event = 'Updated Payment Gateway Settings for ' . $request->slug;
        $audit->save();

        return back()->with('success', _lang('Saved Successfully'));
    }

    public function store_email_settings(Request $request, $businessId)
    {
        Gate::authorize('business.settings');
        $validator = Validator::make($request->all(), [
            'from_email'      => 'required_if:mail_type,smtp,sendmail',
            'from_name'       => 'required_if:mail_type,smtp,sendmail',
            'smtp_host'       => 'required_if:mail_type,smtp,sendmail',
            'smtp_port'       => 'required_if:mail_type,smtp,sendmail',
            'smtp_username'   => 'required_if:mail_type,smtp,sendmail',
            'smtp_password'   => 'required_if:mail_type,smtp,sendmail',
            'smtp_encryption' => 'required_if:mail_type,smtp,sendmail',
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        $settingsData = $request->except($this->ignoreRequests);

        foreach ($settingsData as $key => $value) {
            $value = is_array($value) ? json_encode($value) : $value;
            update_business_option($key, $value, $businessId);
        }

        // audit log
        $audit = new AuditLog();
        $audit->date_changed = date('Y-m-d H:i:s');
        $audit->changed_by = auth()->user()->id;
        $audit->event = 'Updated Email Settings for ' . $request->activeBusiness->name;
        $audit->save();

        return back()->with('success', _lang('Saved Successfully'));
    }

    public function send_test_email(Request $request, $businessId)
    {
        Gate::authorize('business.settings');
        @ini_set('max_execution_time', 0);
        @set_time_limit(0);

        Overrider::load("BusinessSettings");

        $this->validate($request, [
            'recipient_email' => 'required|email',
            'message'         => 'required',
        ]);

        //Send Email
        $email   = $request->input("recipient_email");
        $message = $request->input("message");

        $mail          = new \stdClass();
        $mail->subject = "Email Configuration Testing";
        $mail->body    = $message;

        // audit log
        $audit = new AuditLog();
        $audit->date_changed = date('Y-m-d H:i:s');
        $audit->changed_by = auth()->user()->id;
        $audit->event = 'Sent Test Email to ' . $email;
        $audit->save();

        try {
            Mail::to($email)->send(new GeneralMail($mail));
            return back()->with('success', _lang('Test Message send sucessfully'));
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    /**
     * Display currency settings.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function currencySettings($id)
    {
        Gate::authorize('business.settings');
        $business = Business::with('systemSettings')->find($id);

        return Inertia::render('Backend/User/Business/Settings/Currency', [
            'business' => $business,
            'id' => $id,
            'activeTab' => 'currency'
        ]);
    }

    /**
     * Display invoice settings.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function invoiceSettings($id)
    {
        Gate::authorize('business.settings');
        $business = Business::with('systemSettings')->find($id);

        return Inertia::render('Backend/User/Business/Settings/Invoice', [
            'business' => $business,
            'id' => $id,
            'activeTab' => 'invoice'
        ]);
    }

    /**
     * Display credit invoice settings.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function creditInvoiceSettings($id)
    {
        Gate::authorize('business.settings');
        $business = Business::with('systemSettings')->find($id);

        return Inertia::render('Backend/User/Business/Settings/CreditInvoice', [
            'business' => $business,
            'id' => $id,
            'activeTab' => 'credit-invoice'
        ]);
    }

    /**
     * Display cash invoice settings.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function cashInvoiceSettings($id)
    {
        Gate::authorize('business.settings');
        $business = Business::with('systemSettings')->find($id);

        return Inertia::render('Backend/User/Business/Settings/CashInvoice', [
            'business' => $business,
            'id' => $id,
            'activeTab' => 'cash-invoice'
        ]);
    }

    /**
     * Display bill invoice settings.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function billSettings($id)
    {
        Gate::authorize('business.settings');
        $business = Business::with('systemSettings')->find($id);

        return Inertia::render('Backend/User/Business/Settings/Bill', [
            'business' => $business,
            'id' => $id,
            'activeTab' => 'bill'
        ]);
    }

    /**
     * Display sales return settings.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function salesReturnSettings($id)
    {
        Gate::authorize('business.settings');
        $business = Business::with('systemSettings')->find($id);

        return Inertia::render('Backend/User/Business/Settings/SalesReturn', [
            'business' => $business,
            'id' => $id,
            'activeTab' => 'sales-return'
        ]);
    }

    /**
     * Display purchase return settings.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function purchaseReturnSettings($id)
    {
        Gate::authorize('business.settings');
        $business = Business::with('systemSettings')->find($id);

        return Inertia::render('Backend/User/Business/Settings/PurchaseReturn', [
            'business' => $business,
            'id' => $id,
            'activeTab' => 'purchase-return'
        ]);
    }

    /**
     * Display email settings.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function emailSettings($id)
    {
        Gate::authorize('business.settings');
        $business = Business::with('systemSettings')->find($id);

        return Inertia::render('Backend/User/Business/Settings/Email', [
            'business' => $business,
            'id' => $id,
            'activeTab' => 'email'
        ]);
    }

    public function pos_settings($id)
    {
        Gate::authorize('business.settings');
        $business = Business::find($id);
        return view('backend.user.business.pos_settings', compact('business', 'id'));
    }

    public function store_pos_settings(Request $request)
    {
        Gate::authorize('business.settings');
        $settingsData = $request->except($this->ignoreRequests);

        foreach ($settingsData as $key => $value) {
            $business_setting = BusinessSetting::where('business_id', $request->activeBusiness->id)->where('name', $key)->first();

            $value = is_array($value) ? json_encode($value) : $value;

            if (!$business_setting) {
                $business_setting = new BusinessSetting();
                $business_setting->business_id = $request->activeBusiness->id;
                $business_setting->name        = $key;
                $business_setting->value       = $value;
                $business_setting->save();
            } else {
                $business_setting->value = $value;
                $business_setting->save();
            }
        }

        // audit log
        $audit = new AuditLog();
        $audit->date_changed = date('Y-m-d H:i:s');
        $audit->changed_by = auth()->user()->id;
        $audit->event = 'Updated POS Settings for ' . $request->activeBusiness->name;
        $audit->save();

        return back()->with('success', _lang('Saved Successfully'));
    }

    public function store_approval_settings(Request $request, $businessId)
    {
        Gate::authorize('business.settings');
        $validator = Validator::make($request->all(), [
            'purchase_approval_required_count' => 'required|integer|min:0|max:10',
            'payroll_approval_required_count' => 'required|integer|min:0|max:10',
            'journal_approval_required_count' => 'required|integer|min:0|max:10',
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        $settingsData = $request->except($this->ignoreRequests);

        foreach ($settingsData as $key => $value) {
            $value = is_array($value) ? json_encode($value) : $value;
            update_business_option($key, $value, $businessId);
        }

        // Sync approval records for pending items with new approvers
        $this->syncPurchaseApprovalRecords($request->purchase_approval_users ?? [], $businessId);
        $this->syncPayrollApprovalRecords($request->payroll_approval_users ?? [], $businessId);
        $this->syncJournalApprovalRecords($request->journal_approval_users ?? [], $businessId);

        // audit log
        $audit = new AuditLog();
        $audit->date_changed = date('Y-m-d H:i:s');
        $audit->changed_by = auth()->user()->id;
        $audit->event = 'Updated Approval Settings for ' . $request->activeBusiness->name;
        $audit->save();

        return back()->with('success', _lang('Saved Successfully'));
    }

    /**
     * Sync approval records for all pending purchases (both cash and bill invoices) with the configured approvers
     * This ensures new approvers get added to existing pending purchases
     */
    private function syncPurchaseApprovalRecords(array $approverUserIds, $businessId): void
    {
        if (empty($approverUserIds)) {
            return;
        }

        // Filter to only include user IDs that actually exist in the database
        $validUserIds = \App\Models\User::whereIn('id', $approverUserIds)->pluck('id')->toArray();
        
        if (empty($validUserIds)) {
            return;
        }

        // Get all pending purchases (approval_status = 0) for this business - both cash and bill invoices
        $pendingPurchases = \App\Models\Purchase::where('business_id', $businessId)
            ->where('approval_status', 0)
            ->get();

        foreach ($pendingPurchases as $purchase) {
            foreach ($validUserIds as $userId) {
                // Check if approval record already exists for this user
                $existingApproval = \App\Models\Approvals::where('ref_id', $purchase->id)
                    ->where('ref_name', 'purchase')
                    ->where('action_user', $userId)
                    ->first();

                if (!$existingApproval) {
                    \App\Models\Approvals::create([
                        'ref_id' => $purchase->id,
                        'ref_name' => 'purchase',
                        'action_user' => $userId,
                        'status' => 0, // pending
                    ]);
                }
            }

            // Remove approval records for users no longer in the approvers list
            // Only remove if they haven't taken action yet (status = 0)
            \App\Models\Approvals::where('ref_id', $purchase->id)
                ->where('ref_name', 'purchase')
                ->where('status', 0) // Only remove pending approvals
                ->whereNotIn('action_user', $validUserIds)
                ->delete();
        }
    }

    /**
     * Sync approval records for all draft payrolls with the configured approvers
     * This ensures new approvers get added to existing draft payrolls
     */
    private function syncPayrollApprovalRecords(array $approverUserIds, $businessId): void
    {
        if (empty($approverUserIds)) {
            return;
        }

        // Filter to only include user IDs that actually exist in the database
        $validUserIds = \App\Models\User::whereIn('id', $approverUserIds)->pluck('id')->toArray();
        
        if (empty($validUserIds)) {
            return;
        }

        // Get all draft payrolls (status = 0) for this business
        $draftPayrolls = \App\Models\Payroll::where('business_id', $businessId)
            ->where('status', 0)
            ->get();

        foreach ($draftPayrolls as $payroll) {
            foreach ($validUserIds as $userId) {
                // Check if approval record already exists for this user
                $existingApproval = \App\Models\Approvals::where('ref_id', $payroll->id)
                    ->where('ref_name', 'payroll')
                    ->where('action_user', $userId)
                    ->first();

                if (!$existingApproval) {
                    \App\Models\Approvals::create([
                        'ref_id' => $payroll->id,
                        'ref_name' => 'payroll',
                        'action_user' => $userId,
                        'status' => 0, // pending
                    ]);
                }
            }

            // Remove approval records for users no longer in the approvers list
            // Only remove if they haven't taken action yet (status = 0)
            \App\Models\Approvals::where('ref_id', $payroll->id)
                ->where('ref_name', 'payroll')
                ->where('status', 0) // Only remove pending approvals
                ->whereNotIn('action_user', $validUserIds)
                ->delete();
        }
    }

    /**
     * Sync approval records for all pending journals with the configured approvers
     * This ensures new approvers get added to existing pending journals
     */
    private function syncJournalApprovalRecords(array $approverUserIds, $businessId): void
    {
        if (empty($approverUserIds)) {
            return;
        }

        // Filter to only include user IDs that actually exist in the database
        $validUserIds = \App\Models\User::whereIn('id', $approverUserIds)->pluck('id')->toArray();
        
        if (empty($validUserIds)) {
            return;
        }

        // Get all pending journals (status = 0) for this business
        $pendingJournals = \App\Models\Journal::where('business_id', $businessId)
            ->where('status', 0)
            ->get();

        foreach ($pendingJournals as $journal) {
            foreach ($validUserIds as $userId) {
                // Check if approval record already exists for this user
                $existingApproval = \App\Models\Approvals::where('ref_id', $journal->id)
                    ->where('ref_name', 'journal')
                    ->where('action_user', $userId)
                    ->first();

                if (!$existingApproval) {
                    \App\Models\Approvals::create([
                        'ref_id' => $journal->id,
                        'ref_name' => 'journal',
                        'action_user' => $userId,
                        'status' => 0, // pending
                    ]);
                }
            }

            // Remove approval records for users no longer in the approvers list
            // Only remove if they haven't taken action yet (status = 0)
            \App\Models\Approvals::where('ref_id', $journal->id)
                ->where('ref_name', 'journal')
                ->where('status', 0) // Only remove pending approvals
                ->whereNotIn('action_user', $validUserIds)
                ->delete();
        }
    }
}
