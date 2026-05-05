<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\MailMessage;

class MembershipDuesInactiveNoticeEmail extends Notification
{
    use Queueable;
    
    public $member;

    public function __construct($member)
    {
        $this->member = $member;
    }

    public function via($notifiable)
    {
        return ['mail', 'database'];
    }

    protected function getBusinessName()
    {
        $applicant = $this->member->applicant;
        if (!$applicant) return 'Member';

        $profile = $applicant->basic_profile;
        if (is_string($profile)) $profile = json_decode($profile, true);

        if (is_array($profile) && !empty($profile['registered_business_name'])) return $profile['registered_business_name'];
        if (is_object($profile) && !empty($profile->registered_business_name)) return $profile->registered_business_name;

        return trim(($applicant->rep_first_name ?? '') . ' ' . ($applicant->rep_surname ?? '')) ?: 'Member';
    }

    public function toMail($notifiable)
    {
        $businessName = $this->getBusinessName();
        $expirationDate = $this->member->membership_end_date ? $this->member->membership_end_date->format('F d, Y') : 'recently';

        return (new MailMessage)
            ->subject('Notice: Your PCCI Membership is now Inactive')
            ->view('emails.membership_expiry', [
                'memberName' => $businessName,
                'expiryDate' => $expirationDate
            ]);
    }
    
    public function toDatabase($notifiable)
    {
        return [
            'title'   => 'Membership Inactive',
            'message' => "Your membership has officially become inactive. Please renew.",
            'icon'    => 'fa-lock',
            'tone'    => 'text-danger'
        ];
    }
}