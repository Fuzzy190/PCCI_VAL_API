<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Http\Requests\StoreApplicantRequest;
use App\Models\Applicant;
use App\Models\User;
use App\Http\Resources\ApplicantResource;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Notification;
use App\Notifications\NewApplicantNotification;
// use App\Services\MailtrapApiService;
use Illuminate\Support\Facades\Mail;

class ApplicantController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $query = Applicant::query();

        if ($user->hasRole('treasurer')) {
            $query->whereIn('status', ['approved', 'paid']);
        } elseif (! $user->hasAnyRole(['super_admin', 'admin'])) {
            return response()->json(['message' => 'Unauthorized.'], 403);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        return ApplicantResource::collection($query->get());
    }

    public function store(StoreApplicantRequest $request)
    {
        $data = $request->validated();

        $data['date_submitted'] = now();
        $data['status'] = 'pending';
        $data['membership_type'] = null;

        if ($request->hasFile('mayors_permit')) {
            $data['mayors_permit_path'] = $request->file('mayors_permit')->store('applicants/documents', 's3');
        }

        if ($request->hasFile('dti_sec')) {
            $data['dti_sec_path'] = $request->file('dti_sec')->store('applicants/documents', 's3');
        }

        if ($request->hasFile('proof_of_payment')) {
            $data['proof_of_payment_path'] = $request->file('proof_of_payment')->store('applicants/documents', 's3');
        }

        $applicant = Applicant::create($data);

        // USE CORRECT APPLICANTS COLUMNS
        $applicantName = $applicant->rep_first_name . ' ' . $applicant->rep_surname;

        try {
            Mail::send('emails.applicant_welcome', ['applicantName' => $applicantName], function ($message) use ($applicant, $applicantName) {
                $message->to($applicant->email, $applicantName)
                    ->subject('Application Received - PCCI Valenzuela');
            });
        } catch (\Exception $e) {
            \Log::error('Failed to send applicant welcome email: ' . $e->getMessage());
        }

        Notification::send(User::role(['admin', 'super_admin'])->get(), new NewApplicantNotification($applicant));

        return new ApplicantResource($applicant);
    }

    public function show(Applicant $applicant)
    {
        $user = auth()->user();

        if ($user->hasRole('treasurer') && !in_array($applicant->status, ['approved', 'paid'])) {
            return response()->json(['message' => 'Access denied.'], 403);
        }

        return new ApplicantResource($applicant);
    }

    public function update(Request $request, Applicant $applicant)
    {
        // 1. Safely track old and new status with lowercase to prevent case-mismatches
        $oldStatus = strtolower($applicant->status ?? '');

        $applicant->update($request->all());

        $newStatus = strtolower($applicant->status ?? '');

        // 2. Bulletproof Company Name Extraction
        $profile = $applicant->basic_profile;
        if (is_string($profile)) {
            $profile = json_decode($profile, true); // Decode if it's a JSON string
        }
        $businessName = $applicant->registered_business_name
            ?? ($profile['registered_business_name'] ?? null)
            ?? (trim(($applicant->rep_first_name ?? '') . ' ' . ($applicant->rep_surname ?? '')) ?: null)
            ?? 'Applicant #' . $applicant->id;

        $actorName = $request->user()->name ?? 'System';

        // --- NOTIFICATION LOGIC ---

        // 1. Admin Approves -> Notify Treasurers (BLUE / text-primary)
        if ($oldStatus !== 'approved' && $newStatus === 'approved') {
            $treasurers = User::role('treasurer')->get();
            Notification::send($treasurers, new \App\Notifications\SystemAlertNotification(
                'New Approval for Review',
                "{$actorName} approved {$businessName} #{$applicant->id}. Please review their Proof of Payment.",
                'fa-user-check',
                'text-primary'
            ));
        }

        // 2. Treasurer Records Payment -> Notify Admin (GREEN / text-success)
        if ($oldStatus !== 'paid' && $newStatus === 'paid') {
            // Convert to Member
            $userEmail = $profile['email'] ?? null;
            $user = User::where('email', $userEmail)->orWhere('id', $applicant->user_id)->first();
            if ($user) {
                $user->update(['is_member' => true]);
            }

            $admins = User::role(['admin', 'super_admin'])->get();
            Notification::send($admins, new \App\Notifications\SystemAlertNotification(
                'Payment Verified',
                "{$actorName} verified payment for {$businessName}. They are now approved and ready for member account creation.",
                'fa-check-circle',
                'text-success'
            ));
        }

        // 3. Treasurer Rejects / Cancels -> Notify Admin (RED / text-danger)
        if ($oldStatus !== 'rejected' && $newStatus === 'rejected') {
            $admins = \App\Models\User::role(['admin', 'super_admin'])->get();
            \Illuminate\Support\Facades\Notification::send($admins, new \App\Notifications\SystemAlertNotification(
                'Application Rejected',
                "{$actorName} rejected the payment proof for {$businessName}.",
                'fa-times-circle',
                'text-danger'
            ));
        }

        if ($oldStatus !== 'cancelled' && $newStatus === 'cancelled') {
            $admins = \App\Models\User::role(['admin', 'super_admin'])->get();
            \Illuminate\Support\Facades\Notification::send($admins, new \App\Notifications\SystemAlertNotification(
                'Application Cancelled',
                "The application for {$businessName} has been cancelled by {$actorName}.",
                'fa-times-circle',
                'text-danger'
            ));
        }

        // 4. Inactive -> Notify Admin (YELLOW / text-warning)
        if ($oldStatus !== 'inactive' && $newStatus === 'inactive') {
            $admins = \App\Models\User::role(['admin', 'super_admin'])->get();
            \Illuminate\Support\Facades\Notification::send($admins, new \App\Notifications\SystemAlertNotification(
                'Application Inactive',
                "The application for {$businessName} has become inactive.",
                'fa-exclamation-triangle',
                'text-warning'
            ));
        }

        return response()->json([
            'message' => 'Status updated and notifications distributed.',
            'status' => $newStatus
        ]);
    }

    public function destroy(Applicant $applicant)
    {
        $applicant->delete();
        return response()->noContent();
    }

    public function downloadDocument(Applicant $applicant, $type)
    {
        $user = auth()->user();
        if (! $user->hasAnyRole(['super_admin', 'admin'])) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $filePath = match ($type) {
            'mayors_permit' => $applicant->mayors_permit_path,
            'dti_sec' => $applicant->dti_sec_path,
            'proof_of_payment' => $applicant->proof_of_payment_path,
            default => null,
        };

        if (!$filePath || !Storage::disk('s3')->exists($filePath)) {
            return response()->json(['message' => 'File not found on cloud storage'], 404);
        }

        return Storage::disk('s3')->download($filePath);
    }

    public function reject(Request $request, Applicant $applicant)
    {
        $request->validate([
            'rejection_reason' => 'required|string|max:1000'
        ]);

        $applicant->update([
            'status' => 'rejected',
        ]);

        // USE CORRECT APPLICANTS COLUMNS
        $applicantName = $applicant->rep_first_name . ' ' . $applicant->rep_surname;
        $rejectionReason = $request->rejection_reason;

        $emailData = [
            'applicantName' => $applicantName,
            'status' => 'Rejected',
            'messageText' => "We regret to inform you that your application or payment has been rejected. Please review the specific reason below and contact our administration for further instructions.",
            'rejectionReason' => $rejectionReason,
            'isWarning' => true
        ];

        try {
            Mail::send('emails.applicant_status', $emailData, function ($message) use ($applicant, $applicantName) {
                $message->to($applicant->email, $applicantName)
                    ->subject('Action Required: PCCI Valenzuela Application Rejected');
            });
        } catch (\Exception $e) {
            \Log::error('Failed to send applicant rejection email: ' . $e->getMessage());
        }

        return response()->json([
            'message' => 'Applicant has been rejected and notified via email.',
            'data' => new ApplicantResource($applicant)
        ]);
    }
}
