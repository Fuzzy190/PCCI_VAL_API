<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Http\Requests\StoreApplicantRequest;
use App\Models\Applicant;
use App\Http\Resources\ApplicantResource;
use Illuminate\Support\Facades\Storage;
use App\Services\MailtrapApiService;
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
            Mail::send('emails.applicant_welcome', ['applicantName' => $applicantName], function($message) use ($applicant, $applicantName) {
                $message->to($applicant->email, $applicantName)
                        ->subject('Application Received - PCCI Valenzuela');
            });
        } catch (\Exception $e) {
            \Log::error('Failed to send applicant welcome email: ' . $e->getMessage());
        }

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

    public function update(Request $request, $id, MailtrapApiService $mailtrap)
    {   
        $user = $request->user();
        $applicant = \App\Models\Applicant::find($id);

        if (!$applicant) {
            return response()->json(['message' => 'Applicant not found.'], 404);
        }

        if ($user->hasAnyRole(['super_admin', 'admin', 'treasurer'])) {

            $data = $request->validate([
                'status' => 'required|in:pending,approved,rejected,paid',
                'membership_type' => 'nullable|string', 
            ]);

            if ($user->hasRole('treasurer') && !$user->hasAnyRole(['super_admin', 'admin'])) {
                if ($applicant->status !== 'approved' || $data['status'] !== 'paid') {
                    return response()->json(['message' => 'Treasurers are only authorized to verify payments.'], 403);
                }
            }

            $oldStatus = $applicant->status;

            $applicant->status = $data['status'];
            
            if (isset($data['membership_type'])) {
                $applicant->membership_type = $data['membership_type'];
            }
            
            // Saving this will trigger the ApplicantObserver if the status changed!
            $applicant->save(); 

            // NOTE: We do not need the Mail::send block here anymore because the ApplicantObserver handles it automatically!
            
            return response()->json([
                'message' => 'Applicant updated successfully',
                'data' => $applicant
            ], 200);
        }

        return response()->json(['message' => 'Unauthorized action.'], 403);
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
            Mail::send('emails.applicant_status', $emailData, function($message) use ($applicant, $applicantName) {
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