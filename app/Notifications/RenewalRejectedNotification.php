<?php

namespace App\Notifications;

use App\Models\DuesPayment;
use App\Models\Member;
use App\Models\Transaction;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class RenewalRejectedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public Member $member;
    public ?Transaction $transaction;
    public ?DuesPayment $payment;
    public string $reason;

    public function __construct(Member $member, ?DuesPayment $payment, ?Transaction $transaction, string $reason)
    {
        $this->member = $member;
        $this->transaction = $transaction;
        $this->payment = $payment;
        $this->reason = $reason;
    }

    public function via($notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail($notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Renewal Request Rejected')
            ->view('emails.renewal_rejected', [
                'member' => $this->member,
                'payment' => $this->payment,
                'transaction' => $this->transaction,
                'reason' => $this->reason,
            ]);
    }

    public function toDatabase($notifiable): array
    {
        return [
            'title' => 'Renewal Rejected',
            'message' => 'Your renewal request was rejected. Reason: ' . $this->reason,
            'member_id' => $this->member->id,
            'payment_id' => $this->payment?->id,
            'transaction_id' => $this->transaction?->id,
            'reason' => $this->reason,
        ];
    }
}
