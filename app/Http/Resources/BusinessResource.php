<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

/**
 * BusinessResource
 *
 * Wraps a Member model (with eager-loaded applicant).
 * This is the PUBLIC business directory endpoint — no auth token is present,
 * so we must expose all displayable fields here directly instead of delegating
 * to ApplicantResource (which strips everything for unauthenticated requests).
 *
 * Route: GET /api/v1/business          → BusinessController::index()
 *        GET /api/v1/business/{member} → BusinessController::show()
 */
class BusinessResource extends JsonResource
{
    private function getS3Url($path, $minutes = 60)
    {
        if (!$path) return null;
        try {
            return Storage::disk('s3')->temporaryUrl($path, now()->addMinutes($minutes));
        } catch (\Exception $e) {
            return null;
        }
    }

    public function toArray(Request $request): array
    {
        // $this->resource is a Member model
        $member    = $this->resource;
        $applicant = $member->applicant; // eager-loaded in BusinessController

        // ── Safe defaults so we never get null-access errors ──────────────────
        $telephone  = $applicant?->telephone_no     ?? null;
        $repPhone   = $applicant?->rep_contact_no   ?? null;
        $email      = $applicant?->email            ?? null;

        return [
            // ── Member identifiers ────────────────────────────────────────────
            'id'                  => $member->id,
            'status'              => $member->status,
            'induction_date'      => $member->induction_date?->toDateString(),
            'membership_end_date' => $member->membership_end_date?->toDateString(),

            // ── Business name (top-level for quick lookup in the blade) ───────
            'registered_business_name' => $applicant?->registered_business_name,
            'trade_name'               => $applicant?->trade_name,

            // ── Photo ─────────────────────────────────────────────────────────
            'photo_url' => $this->getS3Url($applicant?->photo_path),

            // ── Contact info exposed at top level (no auth needed) ────────────
            // telephone_no  → business landline stored directly on the applicant
            // rep_contact_no → representative's mobile stored directly on the applicant
            'telephone_no'  => $telephone,
            'rep_contact_no' => $repPhone,
            'email'         => $email,

            // ── Industry / tagline / about ────────────────────────────────────
            'industry'         => $applicant?->industry,
            'business_tagline' => $applicant?->business_tagline,
            'about_description' => $applicant?->about_description,
            'business_hours'   => $applicant?->business_hours,
            'tags'             => $applicant?->tags,

            // ── Location ─────────────────────────────────────────────────────
            'business_location' => [
                'business_address'  => $applicant?->business_address,
                'city_municipality' => $applicant?->city_municipality,
                'province'          => $applicant?->province,
                'region'            => $applicant?->region,
                'zip_code'          => $applicant?->zip_code,
                'location_link'     => $applicant?->location_link ?? null,
            ],

            // ── Full nested applicant block (mirrors ApplicantResource admin shape) ──
            // Kept for backwards-compat with any frontend code reading nested paths.
            'applicant' => $applicant ? [
                'id'     => $applicant->id,
                'email'  => $email,
                'telephone_no'   => $telephone,
                'rep_contact_no' => $repPhone,

                'basic_profile' => [
                    'registered_business_name' => $applicant->registered_business_name,
                    'trade_name'               => $applicant->trade_name,
                    'telephone_no'             => $telephone,   // ← key fix: exposed publicly
                    'email'                    => $email,
                    'website'                  => $applicant->website_socmed,
                    'tin_no'                   => $applicant->tin_no,
                    'business_location' => [
                        'business_address'  => $applicant->business_address,
                        'city_municipality' => $applicant->city_municipality,
                        'province'          => $applicant->province,
                        'region'            => $applicant->region,
                        'zip_code'          => $applicant->zip_code,
                        'location_link'     => $applicant->location_link ?? null,
                    ],
                ],

                'official_representative' => [
                    'first_name'  => $applicant->rep_first_name,
                    'mid_name'    => $applicant->rep_mid_name,
                    'surname'     => $applicant->rep_surname,
                    'designation' => $applicant->rep_designation,
                    'contact_no'  => $member->user?->contact_number ?? $applicant->rep_contact_no,
                ],
                'organization_membership' => [
                    'name_of_organization' => $applicant->name_of_organization,
                    'registration_number'  => $applicant->registration_number,
                    'date_of_registration' => $applicant->date_of_registration?->toDateString(),
                    'type_of_company'      => $applicant->type_of_company,
                    'number_of_employees'  => $applicant->number_of_employees,
                    'year_established'     => $applicant->year_established,
                ],

                'business_additional_data' => [
                    'industry'          => $applicant->industry,
                    'about_description' => $applicant->about_description,
                    'business_tagline'  => $applicant->business_tagline,
                    'business_hours'    => $applicant->business_hours,
                    'tags'              => $applicant->tags,
                ],
            ] : null,
        ];
    }
}
