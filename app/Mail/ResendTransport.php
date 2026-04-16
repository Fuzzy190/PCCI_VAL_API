<?php

namespace App\Mail;

use App\Services\ResendService;
use Symfony\Component\Mailer\Transport\AbstractTransport;
use Symfony\Component\Mailer\SentMessage;
use Symfony\Component\Mime\Message;

class ResendTransport extends AbstractTransport
{
    protected ResendService $resend;

    public function __construct()
    {
        parent::__construct();
        $this->resend = new ResendService();
    }

    protected function doSend(SentMessage $message): void
    {
        $message = $message->getOriginalMessage();
        $toAddresses = $message->getTo();
        $subject = $message->getSubject();
        
        // Get HTML and text parts
        $html = '';
        $text = '';

        if ($message->getHtmlBody()) {
            $html = $message->getHtmlBody();
        }

        if ($message->getTextBody()) {
            $text = $message->getTextBody();
        }

        if (!$html && !$text) {
            // Fallback: extract from message
            $text = $message->getBodyAsString();
        }

        foreach ($toAddresses as $recipient) {
            $this->resend->sendMail(
                toEmail: $recipient->getAddress(),
                toName: $recipient->getName() ?? '',
                subject: $subject ?? '',
                text: $text,
                html: $html ?: null
            );
        }
    }

    public function __toString(): string
    {
        return 'resend';
    }
}
