<?php

namespace App\Notifications;

use App\Models\Applicant;
use Illuminate\Notifications\Notification;

class NewApplicantNotification extends Notification
{

    public Applicant $applicant;

    public function __construct(Applicant $applicant)
    {
        $this->applicant = $applicant;
    }

    public function via($notifiable): array
    {
        return ['database'];
    }

    public function toDatabase($notifiable): array
    {
        return [
            'title' => 'New Applicant Submitted',
            'message' => 'A new applicant has been submitted: ' . ($this->applicant->registered_business_name ?? 'Applicant #' . $this->applicant->id) . '.',
            'applicant_id' => $this->applicant->id,
            'business_name' => $this->applicant->registered_business_name,
            'submitted_at' => $this->applicant->date_submitted?->toDateTimeString() ?? now()->toDateTimeString(),
        ];
    }
}
