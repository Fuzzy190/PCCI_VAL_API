<?php

namespace App\Notifications;

use App\Models\MembershipDue;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Email notification for membership dues expiration - First Warning (5 months before)
 * 
 * SET ASIDE: Enable by uncommenting "implements ShouldQueue" to queue emails
 * This will send via configured mail driver (SMTP, Mailgun, etc)
 */
class MembershipDuesFirstWarningEmail extends Notification
{
    use Queueable;

    protected $membershipDue;

    public function __construct(MembershipDue $membershipDue)
    {
        $this->membershipDue = $membershipDue;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail']; // Send via email
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        $member = $this->membershipDue->member;
        $expirationDate = $member->membership_end_date->format('F d, Y');
        $remainingDays = now()->diffInDays($member->membership_end_date);

        return (new MailMessage)
            ->subject('Membership Renewal Notice - First Warning')
            ->greeting("Hello {$member->applicant->contact_person_name},")
            ->line("Your PCCI membership is expiring soon!")
            ->line("**Expiration Date:** {$expirationDate} ({$remainingDays} days remaining)")
            ->line("**Amount Due:** ₱" . number_format($this->membershipDue->amount, 2))
            ->line("**Membership Year:** {$this->membershipDue->due_year}")
            ->line("")
            ->line("Please renew your membership to continue enjoying PCCI benefits and networking opportunities.")
            ->action('Renew Membership', config('app.frontend_url') . '/renew-membership')
            ->line("If you have any questions, please contact our office.")
            ->salutation('Best regards, PCCI');
    }
}
