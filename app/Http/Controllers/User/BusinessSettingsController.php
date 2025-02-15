<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Mail\GeneralMail;
use App\Models\AuditLog;
use App\Models\Business;
use App\Models\BusinessSetting;
use App\Utilities\Overrider;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;

class BusinessSettingsController extends Controller
{

    private $ignoreRequests = ['_token', 'businessList', 'activeBusiness', 'isOwner', 'permissionList'];

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function settings(Request $request, $id)
    {
        $business = Business::with('systemSettings')->find($id);
        return view('backend.user.business.settings', compact('business', 'id'));
    }

    public function store_general_settings(Request $request, $businessId)
    {
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

        if (!$request->ajax()) {
            return back()->with('success', _lang('Saved Successfully'));
        } else {
            return response()->json(['result' => 'success', 'action' => 'update', 'message' => _lang('Saved Successfully')]);
        }
    }

    public function store_currency_settings(Request $request, $businessId)
    {
        $validator = Validator::make($request->all(), [
            'currency_position' => 'required',
            //'thousand_sep'      => 'required',
            //'decimal_sep'       => 'required',
            'decimal_places'    => 'required|integer',
        ]);

        if ($validator->fails()) {
            if ($request->ajax()) {
                return response()->json(['result' => 'error', 'message' => $validator->errors()->all()]);
            } else {
                return back()->withErrors($validator)->withInput();
            }
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

        if (!$request->ajax()) {
            return back()->with('success', _lang('Saved Successfully'));
        } else {
            return response()->json(['result' => 'success', 'action' => 'update', 'message' => _lang('Saved Successfully')]);
        }
    }

    public function store_invoice_settings(Request $request, $businessId)
    {
        $validator = Validator::make($request->all(), [
            'invoice_title'    => 'required',
            'quotation_number' => 'required',
            'invoice_number'   => 'required|integer',
            'quotation_number' => 'required|integer',
        ]);

        if ($validator->fails()) {
            if ($request->ajax()) {
                return response()->json(['result' => 'error', 'message' => $validator->errors()->all()]);
            } else {
                return back()->withErrors($validator)->withInput();
            }
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
        $audit->event = 'Updated Invoice Settings for ' . get_option('invoice_title');
        $audit->save();

        if (!$request->ajax()) {
            return back()->with('success', _lang('Saved Successfully'));
        } else {
            return response()->json(['result' => 'success', 'action' => 'update', 'message' => _lang('Saved Successfully')]);
        }
    }

    public function store_receipt_settings(Request $request, $businessId)
    {
        $validator = Validator::make($request->all(), [
            'receipt_title'    => 'required',
            'receipt_number'   => 'required|integer',
        ]);

        if ($validator->fails()) {
            if ($request->ajax()) {
                return response()->json(['result' => 'error', 'message' => $validator->errors()->all()]);
            } else {
                return back()->withErrors($validator)->withInput();
            }
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
        $audit->event = 'Updated Cash Invoice Settings for ' . get_option('receipt_title');
        $audit->save();

        if (!$request->ajax()) {
            return back()->with('success', _lang('Saved Successfully'));
        } else {
            return response()->json(['result' => 'success', 'action' => 'update', 'message' => _lang('Saved Successfully')]);
        }
    }

    public function store_purchase_settings(Request $request, $businessId)
    {
        $validator = Validator::make($request->all(), [
            'purchase_title'    => 'required',
            'purchase_number'   => 'required|integer',
        ]);

        if ($validator->fails()) {
            if ($request->ajax()) {
                return response()->json(['result' => 'error', 'message' => $validator->errors()->all()]);
            } else {
                return back()->withErrors($validator)->withInput();
            }
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
        $audit->event = 'Updated Purchase Settings for ' . get_option('purchase_title');
        $audit->save();

        if (!$request->ajax()) {
            return back()->with('success', _lang('Saved Successfully'));
        } else {
            return response()->json(['result' => 'success', 'action' => 'update', 'message' => _lang('Saved Successfully')]);
        }
    }

    public function store_sales_return_settings(Request $request, $businessId)
    {
        $validator = Validator::make($request->all(), [
            'sales_return_title'    => 'required',
            'sales_return_number'   => 'required|integer',
        ]);

        if ($validator->fails()) {
            if ($request->ajax()) {
                return response()->json(['result' => 'error', 'message' => $validator->errors()->all()]);
            } else {
                return back()->withErrors($validator)->withInput();
            }
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
        $audit->event = 'Updated Sales Return Settings for ' . get_option('sales_return_title');
        $audit->save();

        if (!$request->ajax()) {
            return back()->with('success', _lang('Saved Successfully'));
        } else {
            return response()->json(['result' => 'success', 'action' => 'update', 'message' => _lang('Saved Successfully')]);
        }
    }

    public function store_purchase_return_settings(Request $request, $businessId)
    {
        $validator = Validator::make($request->all(), [
            'purchase_return_title'    => 'required',
            'purchase_return_number'   => 'required|integer',
        ]);

        if ($validator->fails()) {
            if ($request->ajax()) {
                return response()->json(['result' => 'error', 'message' => $validator->errors()->all()]);
            } else {
                return back()->withErrors($validator)->withInput();
            }
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
        $audit->event = 'Updated Purchase Return Settings for ' . get_option('purchase_return_title');
        $audit->save();

        if (!$request->ajax()) {
            return back()->with('success', _lang('Saved Successfully'));
        } else {
            return response()->json(['result' => 'success', 'action' => 'update', 'message' => _lang('Saved Successfully')]);
        }
    }

    public function store_payment_gateway_settings(Request $request, $businessId)
    {
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
            if ($request->ajax()) {
                return response()->json(['result' => 'error', 'message' => $validator->errors()->all()]);
            } else {
                return back()->withErrors($validator)->withInput();
            }
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

        if (!$request->ajax()) {
            return back()->with('success', _lang('Saved Successfully'));
        } else {
            return response()->json(['result' => 'success', 'action' => 'update', 'message' => _lang('Saved Successfully')]);
        }
    }

    public function store_email_settings(Request $request, $businessId)
    {
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
            if ($request->ajax()) {
                return response()->json(['result' => 'error', 'message' => $validator->errors()->all()]);
            } else {
                return back()->withErrors($validator)->withInput();
            }
        }

        $settingsData = $request->except($this->ignoreRequests);

        foreach ($settingsData as $key => $value) {
            update_business_option($key, $value, $businessId);
        }

        // audit log
        $audit = new AuditLog();
        $audit->date_changed = date('Y-m-d H:i:s');
        $audit->changed_by = auth()->user()->id;
        $audit->event = 'Updated Email Settings for ' . $request->activeBusiness->name;
        $audit->save();

        if (!$request->ajax()) {
            return back()->with('success', _lang('Saved Successfully'));
        } else {
            return response()->json(['result' => 'success', 'action' => 'update', 'message' => _lang('Saved Successfully')]);
        }
    }

    public function send_test_email(Request $request, $businessId)
    {
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
            if (!$request->ajax()) {
                return back()->with('success', _lang('Test Message send sucessfully'));
            } else {
                return response()->json(['result' => 'success', 'action' => 'update', 'message' => _lang('Test Message send sucessfully')]);
            }
        } catch (\Exception $e) {
            if (!$request->ajax()) {
                return back()->with('error', $e->getMessage());
            } else {
                return response()->json(['result' => 'error', 'action' => 'update', 'message' => $e->getMessage()]);
            }
        }
    }

    public function pos_settings($id)
    {
        $business = Business::find($id);
        return view('backend.user.business.pos_settings', compact('business', 'id'));
    }

    public function store_pos_settings(Request $request)
    {
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
            }else{
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
}
