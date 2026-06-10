<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\UpdateOwnApplicantRequest;
use App\Http\Resources\ApplicantResource;

class MemberApplicationController extends Controller
{
    // public function show()
    // {
    //     // $application = auth()->user()->member->applicant;
    //     $user = auth()->user();

    //     if (!$user->member) {
    //         return response()->json(['message' => 'Member not found'], 404);
    //     }

    //     $application = $user->member->applicant;

    //     if (!$application) {
    //         return response()->json(['message' => 'Application not found'], 404);
    //     }

    //     return new ApplicantResource($application);
    // }

    public function show()
    {
        $user = auth()->user()->load('member.applicant'); // eager load

        if (!$user->member) {
            return response()->json(['message' => 'Member not found'], 404);
        }

        if (!$user->member->applicant) {
            return response()->json(['message' => 'Application not found'], 404);
        }

        return new ApplicantResource($user->member->applicant);
    }

    public function update(UpdateOwnApplicantRequest $request)
    {
        try {
            $user = auth()->user();

            // Safe null check using ?-> in case member relationship is broken
            $application = $user->member?->applicant;

            if (!$application) {
                return response()->json(['message' => 'Application not found'], 404);
            }

            $data = $request->validated();

            // Handle S3 Files
            if ($request->hasFile('photo')) {
                $data['photo_path'] = $request->file('photo')->store('applicants/photos', 's3');
            }
            if ($request->hasFile('mayors_permit')) {
                $data['mayors_permit_path'] = $request->file('mayors_permit')->store('applicants/documents', 's3');
            }
            if ($request->hasFile('dti_sec')) {
                $data['dti_sec_path'] = $request->file('dti_sec')->store('applicants/documents', 's3');
            }

            // Update the Applicant table
            $application->update($data);

            // Sync phone number to User table
            $newPhone = $data['telephone_no'] ?? $data['rep_contact_no'] ?? null;
            if ($newPhone) {
                $user->update(['contact_number' => $newPhone]);
            }

            return new ApplicantResource($application);
        } catch (\Exception $e) {
            // CRITICAL: This will send the exact error to your frontend instead of a blind 500!
            \Log::error('Update Crash: ' . $e->getMessage());
            return response()->json([
                'message' => 'CRASH REPORT: ' . $e->getMessage() . ' on line ' . $e->getLine()
            ], 500);
        }
    }
}
