<?php

namespace App\Http\Resources;

use App\Http\Resources\ApplicantResource;
use App\Models\Member;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MemberResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $membershipType = $this->whenLoaded('membershipType', function () {
            return [
                'id'   => $this->membershipType?->id,
                'name' => $this->membershipType?->name,
            ];
        }, function () {
            // Fallback: if not loaded but relation ID exists, still return what we have
            return $this->membership_type_id ? [
                'id'   => $this->membership_type_id,
                'name' => $this->membershipType?->name ?? null,
            ] : ['id' => null, 'name' => null];
        });

        return [
            'id' => $this->id,
            'applicant' => new ApplicantResource($this->whenLoaded('applicant')),
            'user' => new UserResource($this->whenLoaded('user')),

            // Both snake_case and camelCase so every frontend path finds it
            'membership_type'  => $membershipType,
            'membershipType'   => $membershipType,

            'induction_date'      => $this->induction_date,
            'membership_end_date' => $this->membership_end_date,
            'status'              => $this->status,
            'created_at'          => $this->created_at,
        ];
    }
}
