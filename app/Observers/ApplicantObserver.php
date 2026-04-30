<?php

namespace App\Observers;

use App\Models\Applicant;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;

class ApplicantObserver
{
    public function updated(Applicant $applicant)
    {
        // Only trigger if the status was actually changed
        if ($applicant->isDirty('status')) {
            
            // USE THE CORRECT APPLICANTS TABLE COLUMNS
            $applicantName = $applicant->rep_first_name . ' ' . $applicant->rep_surname;

            try {
                if ($applicant->status === 'approved') {
                    Mail::send('emails.applicant_approved', ['applicantName' => $applicantName], function($message) use ($applicant, $applicantName) {
                        $message->to($applicant->email, $applicantName)
                                ->subject('Action Required: PCCI Valenzuela Application Approved');
                    });
                } elseif ($applicant->status === 'paid') {
                    Mail::send('emails.applicant_approved_paid', ['applicantName' => $applicantName], function($message) use ($applicant, $applicantName) {
                        $message->to($applicant->email, $applicantName)
                                ->subject('Update: PCCI Valenzuela Payment Verified');
                    });
                }
            } catch (\Exception $e) {
                Log::error('Observer Email Error: ' . $e->getMessage());
            }
        }
    }
}