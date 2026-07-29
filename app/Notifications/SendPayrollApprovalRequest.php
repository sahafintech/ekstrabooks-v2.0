<?php

namespace App\Notifications;

use App\Models\EmailTemplate;
use App\Utilities\Overrider;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use stdClass;

class SendPayrollApprovalRequest extends Notification
{
    use Queueable;

    private $payrolls;
    private $recipient;
    private $template;
    private $origin;

    public function __construct($payrolls, $recipient, array $customMessage)
    {
        $this->payrolls = $payrolls;
        $this->recipient = $recipient;
        $this->origin = $this->resolveOrigin($customMessage['origin'] ?? null);

        $this->template = EmailTemplate::where('slug', 'PAYROLL_APPROVAL_REQUEST')->first() ?? new stdClass();
        $this->template->email_status = 1;
        $this->template->sms_status = 0;
        $this->template->notification_status = 0;
        $this->template->subject = $customMessage['subject']
            ?? $this->template->subject
            ?? 'Payroll Approval Required';
        $this->template->email_body = $customMessage['message']
            ?? $this->template->email_body
            ?? '';

        if ($this->payrolls->isNotEmpty()) {
            Overrider::loadBusinessSettings($this->payrolls->first()->business_id);
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
        $path = '/' . ltrim($path, '/');

        return $this->origin === '' ? $path : $this->origin . $path;
    }

    private function normalizePayrollHref(string $href): string
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

        $suffix = ($query !== '' ? '?' . $query : '') . ($fragment !== '' ? '#' . $fragment : '');

        if ($host === 'payslips' && $path !== '') {
            return $this->makeAbsoluteUrl('/user/payslips/' . ltrim($path, '/')) . $suffix;
        }

        if (in_array($scheme, ['http', 'https'], true) && $host !== '') {
            $originHost = strtolower((string) parse_url($this->origin, PHP_URL_HOST));
            if ($originHost !== '' && $host !== $originHost) {
                return $href;
            }
        }

        if (preg_match('~(?:^|/)(user/)?payslips/([^/?#]+)~i', $href, $matches)) {
            return $this->makeAbsoluteUrl('/user/payslips/' . $matches[2]) . $suffix;
        }

        return str_starts_with($href, '/') ? $this->makeAbsoluteUrl($href) : $href;
    }

    private function renderBody(): string
    {
        $body = $this->template->email_body ?? '';
        $business = $this->payrolls->first()?->business;
        $businessName = $business?->business_name ?? $business?->name ?? '';

        $body = str_replace('{{approverName}}', $this->recipient->name ?? '', $body);
        $body = str_replace('{{companyName}}', $businessName, $body);

        $normalized = preg_replace_callback('/href=(["\'])(.*?)\1/i', function ($matches) {
            $safeHref = htmlspecialchars($this->normalizePayrollHref($matches[2]), ENT_QUOTES, 'UTF-8');

            return 'href=' . $matches[1] . $safeHref . $matches[1];
        }, $body);

        return $normalized ?? $body;
    }

    public function via($notifiable): array
    {
        return ['mail'];
    }

    public function toMail($notifiable): MailMessage
    {
        $business = $this->payrolls->first()?->business;
        $businessName = $business?->business_name ?? $business?->name ?? '';

        return (new MailMessage)
            ->subject($this->template->subject)
            ->markdown('email.notification-business', [
                'message' => $this->renderBody(),
                'businessName' => $businessName,
            ]);
    }

    public function toArray($notifiable): array
    {
        return ['message' => strip_tags($this->renderBody())];
    }
}
