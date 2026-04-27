<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Http\Requests\StoreApplicantRequest;
use App\Models\Applicant;
use App\Http\Resources\ApplicantResource;
use Illuminate\Support\Facades\Storage;
use App\Services\MailtrapApiService;
use Illuminate\Support\Facades\Mail; // <--- THIS IS THE MISSING IMPORT THAT FIXES THE ERROR

class ApplicantController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $user = $request->user();

        $query = Applicant::query();

        // ROLE-BASED BASE RESTRICTIONS
        if ($user->hasRole('treasurer')) {
            // Treasurer can only see approved or paid (Remove Pending)
            $query->whereIn('status', ['approved', 'paid']);
        } elseif (! $user->hasAnyRole(['super_admin', 'admin'])) {
            return response()->json([
                'message' => 'Unauthorized.'
            ], 403);
        }

        // OPTIONAL FILTERING
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        return ApplicantResource::collection($query->get());
    }

    public function store(StoreApplicantRequest $request)
    {
       $data = $request->validated();

        // Server-controlled fields
        $data['date_submitted'] = now();
        $data['status'] = 'pending';
        $data['membership_type'] = null;

        // 🚀 SWITCHING TO S3 (BACKBLAZE)
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

        // ==================== SEND WELCOME EMAIL ====================
        $applicantName = $applicant->rep_first_name . ' ' . $applicant->rep_surname;
        
        try {
            Mail::send('emails.applicant_welcome', ['applicantName' => $applicantName], function($message) use ($applicant, $applicantName) {
                $message->to($applicant->email, $applicantName)
                        ->subject('Application Received - PCCI Valenzuela');
            });
        } catch (\Exception $e) {
            // Log the error so it doesn't crash the applicant submission if Gmail SMTP fails
            \Log::error('Failed to send applicant welcome email: ' . $e->getMessage());
        }
        // ============================================================

        return new ApplicantResource($applicant);
    }

    /**
     * Display the specified resource.
     */
    public function show(Applicant $applicant)
    {
        $user = auth()->user();

        if ($user->hasRole('treasurer') && 
            !in_array($applicant->status, ['approved', 'paid'])) {

            return response()->json([
                'message' => 'Access denied.'
            ], 403);
        }

        return new ApplicantResource($applicant);
    }



    public function update(Request $request, $id, MailtrapApiService $mailtrap)
    {   
        $user = $request->user();

        // 1. FIX: Manually find the applicant by ID to bypass route binding errors
        $applicant = \App\Models\Applicant::find($id);

        if (!$applicant) {
            return response()->json(['message' => 'Applicant not found.'], 404);
        }

        /**
         * ADMIN / SUPER_ADMIN / TREASURER
         */
        if ($user->hasAnyRole(['super_admin', 'admin', 'treasurer'])) {

            $data = $request->validate([
                'status' => 'required|in:pending,approved,rejected,paid',
                // 2. FIX: Allow string values to match frontend options like 'Associate'
                'membership_type' => 'nullable|string', 
            ]);

            // Ensure Treasurer can ONLY update the status to paid
            if ($user->hasRole('treasurer') && !$user->hasAnyRole(['super_admin', 'admin'])) {
                if ($applicant->status !== 'approved' || $data['status'] !== 'paid') {
                    return response()->json([
                        'message' => 'Treasurers are only authorized to verify payments for approved applications.'
                    ], 403);
                }
            }

            $oldStatus = $applicant->status;

            // ==========================================================
            // 3. FIX: UPDATE THE DATA AND COMMIT TO DATABASE
            // ==========================================================
            $applicant->status = $data['status'];
            
            if (isset($data['membership_type'])) {
                $applicant->membership_type = $data['membership_type'];
            }
            
            // Save the changes to the database
            $applicant->save(); 
            // ==========================================================

            // ==================== NOTIFICATION FLOW ====================
            if (isset($data['status']) && $oldStatus !== $data['status']) {
                $applicantName = $applicant->rep_first_name . ' ' . $applicant->rep_surname;

                try {
                    if ($data['status'] === 'approved') {
                        \Mail::send('emails.applicant_approved', ['applicant' => $applicant], function($message) use ($applicant, $applicantName) {
                            $message->to($applicant->email, $applicantName)
                                    ->subject('Action Required: PCCI Valenzuela Application Approved');
                        });
                    } elseif ($data['status'] === 'paid') {
                        \Mail::send('emails.applicant_approved_paid', ['applicant' => $applicant], function($message) use ($applicant, $applicantName) {
                            $message->to($applicant->email, $applicantName)
                                    ->subject('Update: PCCI Valenzuela Payment Verified');
                        });
                    }
                } catch (\Exception $e) {
                    \Log::error('Failed to send status update email: ' . $e->getMessage());
                }
            }
            
            // Return actual updated data to the frontend
            return response()->json([
                'message' => 'Applicant updated successfully',
                'data' => $applicant
            ], 200);
        }

        return response()->json(['message' => 'Unauthorized action.'], 403);
    }

    /**
     * Remove the specified resource from storage.
     */
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

        // Check S3 disk instead of local
        if (!$filePath || !Storage::disk('s3')->exists($filePath)) {
            return response()->json(['message' => 'File not found on cloud storage'], 404);
        }

        return Storage::disk('s3')->download($filePath);
    }

   public function reject(Request $request, Applicant $applicant)
    {
        // 1. Validate that a rejection reason is provided
        $request->validate([
            'rejection_reason' => 'required|string|max:1000'
        ]);

        // 2. Update the Applicant status
        $applicant->update([
            'status' => 'rejected',
        ]);

        // 3. Send the Rejection Email via Gmail SMTP
        $applicantName = $applicant->rep_first_name . ' ' . $applicant->rep_surname;
        $rejectionReason = $request->rejection_reason;

        $emailData = [
            'applicantName' => $applicantName,
            'status' => 'Rejected',
            // THIS is the fix: Make it a hardcoded string, NOT a variable
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