<?php

namespace App\Notifications;

use App\Models\MembershipDue;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Email notification for membership dues expiration - Second Warning (3 months before)
 * 
 * SET ASIDE: Enable by uncommenting "implements ShouldQueue" to queue emails
 */
class MembershipDuesSecondWarningEmail extends Notification
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
            ->subject('⏰ Membership Renewal Reminder - Second Notice')
            ->greeting("Hello {$member->applicant->contact_person_name},")
            ->line("This is your second reminder about your upcoming membership expiration.")
            ->line("**Time Left:** {$remainingDays} days until expiration ({$expirationDate})")
            ->line("**Amount Due:** ₱" . number_format($this->membershipDue->amount, 2))
            ->line("")
            ->line("Don't miss out! Renew now to maintain uninterrupted membership.")
            ->action('Renew Now', config('app.frontend_url') . '/renew-membership')
            ->line("Need help? Contact us anytime.")
            ->salutation('Best regards, PCCI');
    }
}
