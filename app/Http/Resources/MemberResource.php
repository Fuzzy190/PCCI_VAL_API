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
        return [
            'id' => $this->id,

            // 👇 reuse applicant resource
            'applicant' => new ApplicantResource($this->whenLoaded('applicant')),

            // 👇 ADD THIS LINE SO FRONTEND GETS THE UPDATED USER INFO!
            'user' => new UserResource($this->whenLoaded('user')),

            MemberResource::mergeWhen($this->membershipType, [
                'membership_type' => [
                    'id' => $this->membershipType?->id,
                    'name' => $this->membershipType?->name,
                ],
            ]),
            'induction_date' => $this->induction_date,
            'membership_end_date' => $this->membership_end_date,
            'status' => $this->status,
            'created_by' => $this->whenLoaded('createdBy', function () {
                return [
                    'id' => $this->createdBy?->id,
                    'name' => $this->createdBy?->name,
                    'email' => $this->createdBy?->email,
                ];
            }),
            'created_at' => $this->created_at,
        ];
    }
}
