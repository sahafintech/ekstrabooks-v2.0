<?php

namespace App\Http\Controllers;

use App\Mail\GeneralMail;
use App\Utilities\Overrider;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;

class ServerErrorController extends Controller
{
    public function index()
    {
        return back()->with('error', 'Something went wrong');

        Overrider::load("BusinessSettings");

        //Send Email
        $email   = 'mohamedjagne110@gmail.com';
        $message = 'Dear Mohamed Jagne,<br><br>';
        $message .= 'We are sorry to inform you that your app is facing some technical issues. Please check the website and resolve the issue as soon as possible.<br><br>
        <a href="' . url()->current() . '">' . url()->current() . '</a><br><br>
        Regards,<br>Team ' . config('app.name');

        $mail          = new \stdClass();
        $mail->subject = "500 Server error";
        $mail->body    = $message;

        try {
            Mail::to($email)->send(new GeneralMail($mail));
        } catch (\Exception $e) {
            return back()->with('error', 'Something went wrong');
        }
    }
}
