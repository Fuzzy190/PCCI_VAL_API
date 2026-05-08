<?php

namespace App\Notifications;

use App\Models\DuesPayment;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class RenewalRequestSubmittedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public DuesPayment $payment;

    public function __construct(DuesPayment $payment)
    {
        $this->payment = $payment;
    }

    public function via($notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail($notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Renewal Request Submitted')
            ->view('emails.renewal_request_submitted', [
                'payment' => $this->payment,
                'member' => $this->payment->member,
                'due' => $this->payment->membershipDue,
            ]);
    }

    public function toDatabase($notifiable): array
    {
        return [
            'title' => 'Renewal Request Submitted',
            'message' => 'A renewal request was submitted by ' . ($this->payment->member->applicant->registered_business_name ?? 'a member') . '.',
            'payment_id' => $this->payment->id,
            'member_id' => $this->payment->member_id,
            'amount' => $this->payment->amount,
        ];
    }
}
