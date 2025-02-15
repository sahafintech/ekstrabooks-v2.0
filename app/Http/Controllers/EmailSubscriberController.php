<?php

namespace App\Http\Controllers;

use Validator;
use DataTables;
use App\Mail\GeneralMail;
use App\Utilities\Overrider;
use Illuminate\Http\Request;
use App\Models\EmailSubscriber;
use Illuminate\Support\Facades\Mail;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\EmailSubscribersExport;

class EmailSubscriberController extends Controller {

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct() {
        date_default_timezone_set(get_option('timezone', 'Asia/Dhaka'));
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index() {
        $assets = ['datatable'];
        $emailsubscribers = EmailSubscriber::select('email_subscribers.*')
            ->orderBy("email_subscribers.id", "desc")
            ->get();
        return view('backend.admin.email_subscriber.list', compact('assets', 'emailsubscribers'));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function send_email(Request $request) {
        if ($request->isMethod('get')) {
            $assets    = ['tinymce'];
            $alert_col = 'col-lg-8 offset-lg-2';
            return view('backend.admin.email_subscriber.send-email', compact('assets', 'alert_col'));
        } else {
            $validator = Validator::make($request->all(), [
                'subject' => 'required',
                'message' => 'required',
            ]);

            if ($validator->fails()) {
                return back()
                    ->withErrors($validator)
                    ->withInput();
            }

            $mail          = new \stdClass ();
            $mail->subject = $request->subject;
            $mail->body    = $request->message;

            $emailAddresses = EmailSubscriber::all()->pluck('email_address');

            try {
                Overrider::load('Settings');
                Mail::to($emailAddresses)->send(new GeneralMail($mail));
                return redirect()->route('email_subscribers.index')->with('success', _lang('Email Send Successfully'));
            } catch (\Exception $e) {
                return back()->with('error', $e->getMessage())->withInput();
            }
        }
    }

    public function export(){
        return Excel::download(new EmailSubscribersExport, 'EmailSubscribers.xlsx');
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id) {
        $emailsubscriber = EmailSubscriber::find($id);
        $emailsubscriber->delete();
        return redirect()->route('email_subscribers.index')->with('success', _lang('Deleted Successfully'));
    }
}
