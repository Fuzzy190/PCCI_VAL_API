<?php

namespace App\Services;

use Resend;
use Throwable;

class ResendService
{
    protected $resend;

    public function __construct()
    {
        $apiKey = config('services.resend.key');
        $this->resend = Resend::client($apiKey);
    }

    /**
     * Send an email using Resend
     */
    public function sendMail(
        string $toEmail,
        string $toName,
        string $subject,
        string $text,
        ?string $html = null
    ): bool {
        $fromEmail = config('mail.from.address');
        $fromName = config('mail.from.name');

        try {
            $this->resend->emails->send([
                'from' => "{$fromName} <{$fromEmail}>",
                'to' => $toEmail,
                'subject' => $subject,
                'text' => $text,
                'html' => $html ?? $text,
            ]);

            return true;
        } catch (Throwable $e) {
            report($e);
            return false;
        }
    }

    /**
     * Send an HTML email using Resend
     */
    public function sendHtmlMail(
        string $toEmail,
        string $subject,
        string $html
    ): bool {
        $fromEmail = config('mail.from.address');
        $fromName = config('mail.from.name');

        try {
            $this->resend->emails->send([
                'from' => "{$fromName} <{$fromEmail}>",
                'to' => $toEmail,
                'subject' => $subject,
                'html' => $html,
            ]);

            return true;
        } catch (Throwable $e) {
            report($e);
            return false;
        }
    }
}
