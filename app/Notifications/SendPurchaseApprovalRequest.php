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
    private $origin;

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
        $this->origin        = $this->resolveOrigin($customMessage['origin'] ?? null);

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

    private function resolveOrigin(?string $origin): string
    {
        $origin = rtrim((string) $origin, '/');

        if ($origin !== '' && filter_var($origin, FILTER_VALIDATE_URL)) {
            $scheme = strtolower((string) parse_url($origin, PHP_URL_SCHEME));
            if (in_array($scheme, ['http', 'https'], true)) {
                return $origin;
            }
        }

        return rtrim((string) config('app.url'), '/');
    }

    private function makeAbsoluteUrl(string $path): string
    {
        $normalizedPath = '/' . ltrim($path, '/');

        if ($this->origin === '') {
            return $normalizedPath;
        }

        return $this->origin . $normalizedPath;
    }

    private function normalizePurchaseHref(string $href): string
    {
        $href = trim(html_entity_decode($href, ENT_QUOTES, 'UTF-8'));

        if ($href === '' || str_starts_with($href, '#') || str_starts_with($href, 'mailto:') || str_starts_with($href, 'tel:')) {
            return $href;
        }

        $scheme = strtolower((string) parse_url($href, PHP_URL_SCHEME));
        $host = strtolower((string) parse_url($href, PHP_URL_HOST));
        $path = (string) parse_url($href, PHP_URL_PATH);
        $query = (string) parse_url($href, PHP_URL_QUERY);
        $fragment = (string) parse_url($href, PHP_URL_FRAGMENT);

        $suffix = '';
        if ($query !== '') {
            $suffix .= '?' . $query;
        }
        if ($fragment !== '') {
            $suffix .= '#' . $fragment;
        }

        if (in_array($host, ['bill_invoices', 'cash_purchases'], true) && $path !== '') {
            return $this->makeAbsoluteUrl('/user/' . $host . '/' . ltrim($path, '/')) . $suffix;
        }

        // Keep unrelated external links as-is.
        if (in_array($scheme, ['http', 'https'], true) && !in_array($host, ['', 'bill_invoices', 'cash_purchases'], true)) {
            $originHost = strtolower((string) parse_url($this->origin, PHP_URL_HOST));
            if ($originHost !== '' && $host !== $originHost) {
                return $href;
            }
        }

        if (preg_match('~(?:^|/)(user/)?(bill_invoices|cash_purchases)/([^/?#]+)~i', $href, $matches)) {
            $entity = strtolower($matches[2]);
            $id = $matches[3];
            return $this->makeAbsoluteUrl("/user/{$entity}/{$id}") . $suffix;
        }

        if (str_starts_with($href, '/')) {
            return $this->makeAbsoluteUrl($href);
        }

        return $href;
    }

    private function normalizePurchaseLinks(string $body): string
    {
        if ($body === '') {
            return $body;
        }

        $normalized = preg_replace_callback('/href=(["\'])(.*?)\1/i', function ($matches) {
            $quote = $matches[1];
            $href = $matches[2];
            $safeHref = htmlspecialchars($this->normalizePurchaseHref($href), ENT_QUOTES, 'UTF-8');
            return 'href=' . $quote . $safeHref . $quote;
        }, $body);

        return $normalized ?? $body;
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
        return $this->normalizePurchaseLinks($body);
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
