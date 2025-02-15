<?php

namespace App\Notifications;

use App\Channels\SmsMessage;
use App\Models\EmailTemplate;
use App\Utilities\Overrider;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class InvoicePayment extends Notification {
    use Queueable;

    private $transaction;
    private $template;
    private $replace = [];

    /**
     * Create a new notification instance.
     *
     * @return void
     */
    public function __construct($transaction) {
        $this->transaction = $transaction;
        $this->template    = EmailTemplate::where('slug', 'INVOICE_PAYMENT_RECEIVED')->first();
        Overrider::loadBusinessSettings($this->transaction->business_id);

        $this->replace['customerName']  = $this->transaction->invoice->customer->name;
        $this->replace['amount']        = formatAmount($this->transaction->amount, currency_symbol($this->transaction->invoice->business->currency), $this->transaction->business_id);
        $this->replace['paymentMethod'] = $this->transaction->method;
        $this->replace['invoiceDate']   = $this->transaction->invoice->invoice_date;
        $this->replace['invoiceNumber'] = $this->transaction->invoice->invoice_number;
        $this->replace['invoiceLink']   = route('invoices.show_public_invoice', $this->transaction->invoice->short_code);
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
            ->markdown('email.notification-business', ['message' => $message, 'businessName' => $this->transaction->invoice->business->name]);
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
