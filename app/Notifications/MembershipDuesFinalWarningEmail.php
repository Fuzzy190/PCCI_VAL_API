<?php

namespace App\Notifications;

use App\Models\MembershipDue;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Email notification for membership dues expiration - Final Warning (1 month before)
 * 
 * SET ASIDE: Enable by uncommenting "implements ShouldQueue" to queue emails
 */
class MembershipDuesFinalWarningEmail extends Notification
{
    use Queueable;

    protected $membershipDue;

    public function __construct(MembershipDue $membershipDue)
    {
        $this->membershipDue = $membershipDue;
    }

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $member = $this->membershipDue->member;
        $expirationDate = $member->membership_end_date->format('F d, Y');
        $remainingDays = now()->diffInDays($member->membership_end_date);

        return (new MailMessage)
            ->subject('❗ URGENT: Membership Expiring - Final Notice')
            ->greeting("Hello {$member->applicant->contact_person_name},")
            ->line("Your PCCI membership will expire very soon!")
            ->line("**URGENT:** Only {$remainingDays} days left ({$expirationDate})")
            ->line("**Amount Due:** ₱" . number_format($this->membershipDue->amount, 2))
            ->line("")
            ->line("**This is your final notice.** Please renew immediately to avoid membership suspension.")
            ->action('Renew Immediately', config('app.frontend_url') . '/renew-membership')
            ->line("After expiration date, your membership may be suspended.")
            ->salutation('Best regards, PCCI');
    }
}
