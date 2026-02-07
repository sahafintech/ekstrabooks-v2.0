<?php

namespace App\Notifications;

use App\Models\EmailTemplate;
use App\Utilities\Overrider;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use stdClass;

class SendPurchaseApprovalRequest extends Notification
{
    use Queueable;

    private $purchases;
    private $template;
    private $customMessage;
    private $recipient;

    /**
     * @param \Illuminate\Support\Collection $purchases  Cash purchases collection with vendor & business loaded
     * @param \App\Models\User               $recipient  Approver/checker user
     * @param array                          $customMessage ['subject' => string, 'message' => string]
     */
    public function __construct($purchases, $recipient, array $customMessage)
    {
        $this->purchases     = $purchases;
        $this->recipient     = $recipient;
        $this->customMessage = $customMessage;

        $this->template = EmailTemplate::where('slug', 'PURCHASE_APPROVAL_REQUEST')->first() ?? new stdClass();

        // Force-enable email channel for this bulk action, disable others to avoid silent no-op
        $this->template->email_status        = 1;
        $this->template->sms_status          = 0;
        $this->template->notification_status = 0;

        // Fill subject/body with request overrides or sensible defaults
        $this->template->subject    = $customMessage['subject'] ?? $this->template->subject ?? 'Purchase Approval Required';
        $this->template->email_body = $customMessage['message'] ?? $this->template->email_body ?? '';

        if ($this->purchases->count()) {
            Overrider::loadBusinessSettings($this->purchases->first()->business_id);
        }
    }

    public function via($notifiable)
    {
        $channels = [];
        if ($this->template->email_status == 1) {
            $channels[] = 'mail';
        }
        if ($this->template->sms_status == 1) {
            $channels[] = \App\Channels\SMS::class;
        }
        if ($this->template->notification_status == 1) {
            $channels[] = 'database';
        }
        return $channels;
    }

    private function statusLabel(int $status): string
    {
        return match ($status) {
            1 => 'Approved',
            4 => 'Verified',
            default => 'Pending',
        };
    }

    /**
     * Render the email body; rows are already composed on the frontend.
     */
    private function renderBody(): string
    {
        $body = $this->template->email_body ?? '';
        $body = str_replace('{{approverName}}', $this->recipient->name ?? '', $body);
        $body = str_replace('{{companyName}}', optional($this->purchases->first()->business)->business_name ?? optional($this->purchases->first()->business)->name ?? '', $body);
        return $body;
    }

    public function toMail($notifiable)
    {
        $message      = $this->renderBody();
        $businessName = optional($this->purchases->first()->business)->business_name ?? optional($this->purchases->first()->business)->name ?? '';

        return (new MailMessage)
            ->subject($this->template->subject)
            ->markdown('email.notification-business', ['message' => $message, 'businessName' => $businessName]);
    }

    public function toArray($notifiable)
    {
        return ['message' => strip_tags($this->renderBody())];
    }
}
