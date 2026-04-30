<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\MailMessage;

class MembershipDuesFirstWarningEmail extends Notification
{
    use Queueable;

    public $member;

    public function __construct($member)
    {
        $this->member = $member;
    }

    // 1. THIS IS THE MAGIC LINE: Tell Laravel to trigger BOTH channels
    public function via($notifiable)
    {
        return ['mail', 'database']; 
    }

    // 2. The Email Payload (Sent via SMTP/Resend)
    public function toMail($notifiable)
    {
        return (new MailMessage)
                    ->subject('First Warning: Membership Expiring Soon')
                    ->greeting('Hello ' . $this->member->applicant->basic_profile->registered_business_name)
                    ->line('Your membership will expire in 30 days.')
                    ->action('Renew Now', url('/renew'))
                    ->line('Thank you for being a valued member!');
    }

    // 3. The In-App Payload (Saved to the new 'notifications' table)
    public function toDatabase($notifiable)
    {
        $businessName = $this->member->applicant->basic_profile->registered_business_name ?? 'A member';

        return [
            'title'   => 'Membership Expiring Soon',
            'message' => "{$businessName}'s membership expires in 30 days.",
            'icon'    => 'fa-exclamation-triangle',
            'tone'    => 'text-warning'
        ];
    }
}