<?php

namespace App\Notifications;

use App\Models\DuesPayment;
use App\Models\Member;
use App\Models\Transaction;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class RenewalApprovedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public Member $member;
    public ?Transaction $transaction;
    public ?DuesPayment $payment;
    public Carbon $newEndDate;

    public function __construct(Member $member, ?Transaction $transaction, ?DuesPayment $payment, Carbon $newEndDate)
    {
        $this->member = $member;
        $this->transaction = $transaction;
        $this->payment = $payment;
        $this->newEndDate = $newEndDate;
    }

    public function via($notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail($notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Membership Renewal Approved')
            ->view('emails.renewal_approved', [
                'member' => $this->member,
                'payment' => $this->payment,
                'transaction' => $this->transaction,
                'newEndDate' => $this->newEndDate,
            ]);
    }

    public function toDatabase($notifiable): array
    {
        return [
            'title' => 'Renewal Approved',
            'message' => 'Your renewal payment has been approved. Membership extended to ' . $this->newEndDate->format('F j, Y') . '.',
            'member_id' => $this->member->id,
            'payment_id' => $this->payment?->id,
            'transaction_id' => $this->transaction?->id,
            'new_end_date' => $this->newEndDate->toDateString(),
        ];
    }
}
