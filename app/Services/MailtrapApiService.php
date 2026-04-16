<?php

namespace App\Services;

use Mailtrap\MailtrapClient;
use Mailtrap\Mime\MailtrapEmail;
use Symfony\Component\Mime\Address;
use Throwable;

class MailtrapApiService
{
    public function sendMail(string $toEmail, string $toName, string $subject, string $text, ?string $html = null): bool
    {
        $token = config('services.mailtrap.token');
        $inboxId = (int) config('services.mailtrap.inbox_id');
        $fromEmail = config('services.mailtrap.from.address');
        $fromName = config('services.mailtrap.from.name');

        if (!$token || !$inboxId) {
            return false;
        }

        $email = (new MailtrapEmail())
            ->from(new Address($fromEmail, $fromName))
            ->to(new Address($toEmail, $toName))
            ->subject($subject)
            ->text($text)
            ->category('Membership Notification');

        // Add HTML content if provided
        if ($html) {
            $email->html($html);
        }

        try {
            MailtrapClient::initSendingEmails(
                apiKey: $token,
                isSandbox: true,
                inboxId: $inboxId,
            )->send($email);

            return true;
        } catch (Throwable $e) {
            report($e);
            return false;
        }
    }
}