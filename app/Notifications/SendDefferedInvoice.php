<?php

namespace App\Notifications;

use App\Channels\SmsMessage;
use App\Models\EmailTemplate;
use App\Utilities\Overrider;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use stdClass;

class SendDefferedInvoice extends Notification {
    use Queueable;

    private $defferedInvoice;
    private $template;
    private $customMessage;
    private $replace = [];

    /**
     * Create a new notification instance.
     *
     * @return void
     */
    public function __construct($defferedInvoice, $customMessage, $slug = null) {
        $this->defferedInvoice       = $defferedInvoice;
        $this->customMessage = $customMessage;

        $this->template = $slug != null ? EmailTemplate::where('slug', $slug)->first() : new stdClass();

        $this->template->subject    = $this->customMessage['subject'];
        $this->template->email_body = $this->customMessage['message'];
        if ($slug == null) {
            $this->template->email_status        = 1;
            $this->template->sms_status          = 0;
            $this->template->notification_status = 0;
        }

        Overrider::loadBusinessSettings($this->defferedInvoice->business_id);

        $this->replace['customerName']  = $this->defferedInvoice->customer->name;
        $this->replace['dueAmount']     = formatAmount($this->defferedInvoice->grand_total - $this->defferedInvoice->paid, currency_symbol($this->defferedInvoice->business->currency), $this->defferedInvoice->business_id);
        $this->replace['invoiceDate']   = $this->defferedInvoice->invoice_date;
        $this->replace['dueDate']       = $this->defferedInvoice->due_date;
        $this->replace['invoiceNumber'] = $this->defferedInvoice->invoice_number;
        $this->replace['invoiceLink']   = route('deffered_invoices.show_public_deffered_invoice', $this->defferedInvoice->short_code);

    }

    /**
     * Get the notification's delivery channels.
     *
     * @param  mixed  $notifiable
     * @return array
     */
    public function via($notifiable) {
        $channels = [];
        if ($this->template != null && $this->template->email_status == 1) {
            array_push($channels, 'mail');
        }
        if ($this->template != null && $this->template->sms_status == 1) {
            array_push($channels, \App\Channels\SMS::class);
        }
        if ($this->template != null && $this->template->notification_status == 1) {
            array_push($channels, 'database');
        }
        return $channels;
    }

    /**
     * Get the mail representation of the notification.
     *
     * @param  mixed  $notifiable
     * @return \Illuminate\Notifications\Messages\MailMessage
     */
    public function toMail($notifiable) {
        $message = processShortCode($this->template->email_body, $this->replace);

        return (new MailMessage)
            ->subject($this->template->subject)
            ->markdown('email.notification-business', ['message' => $message, 'businessName' => $this->defferedInvoice->business->name]);
    }

    /**
     * Get the sms representation of the notification.
     *
     * @param  mixed  $notifiable
     * @return \Illuminate\Notifications\Messages\MailMessage
     */
    public function toSMS($notifiable) {
        $message = processShortCode($this->template->sms_body, $this->replace);

        return (new SmsMessage())
            ->setContent($message)
            ->setRecipient($notifiable->country_code . $notifiable->phone);
    }

    /**
     * Get the array representation of the notification.
     *
     * @param  mixed  $notifiable
     * @return array
     */
    public function toArray($notifiable) {
        $message = processShortCode($this->template->notification_body, $this->replace);
        return ['message' => $message];
    }
}
