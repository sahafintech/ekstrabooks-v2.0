<?php

namespace App\Notifications;

use App\Channels\SmsMessage;
use App\Models\EmailTemplate;
use App\Utilities\Overrider;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use stdClass;

class SendPurchaseOrder extends Notification {
    use Queueable;

    private $purchase_order;
    private $template;
    private $customMessage;
    private $replace = [];

    /**
     * Create a new notification instance.
     *
     * @return void
     */
    public function __construct($purchase_order, $customMessage, $slug = null) {
        $this->purchase_order       = $purchase_order;
        $this->customMessage = $customMessage;

        $this->template = $slug != null ? EmailTemplate::where('slug', $slug)->first() : new stdClass();

        $this->template->subject    = $this->customMessage['subject'];
        $this->template->email_body = $this->customMessage['message'];
        if ($slug == null) {
            $this->template->email_status        = 1;
            $this->template->sms_status          = 0;
            $this->template->notification_status = 0;
        }

        Overrider::loadBusinessSettings($this->purchase_order->business_id);

        $this->replace['vendorName'] = $this->purchase_order->vendor->name;
        $this->replace['orderDate'] = $this->purchase_order->order_date;
        $this->replace['orderNumber'] = $this->purchase_order->order_number;
        $this->replace['orderLink'] = route('purchase_orders.show_public_purchase_order', $this->purchase_order->short_code);
        $this->replace['grandTotal'] = formatAmount($this->purchase_order->grand_total, currency_symbol($this->purchase_order->business->currency), $this->purchase_order->business_id);

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
            ->markdown('email.notification-business', ['message' => $message, 'businessName' => $this->purchase_order->business->name]);
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
