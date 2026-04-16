<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DuesNotificationResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'type' => $this->type,
            'title' => $this->title,
            'message' => $this->message,
            'data' => $this->data,
            'is_read' => (bool) $this->is_read,
            'read_at' => $this->read_at?->format('Y-m-d H:i:s'),
            'created_at' => $this->created_at->format('Y-m-d H:i:s'),
            'member' => [
                'id' => $this->member->id,
                'name' => $this->member->applicant?->contact_person_name ?? 'N/A',
            ],
            'membership_due' => [
                'id' => $this->membershipDue->id,
                'due_year' => $this->membershipDue->due_year,
                'amount' => $this->membershipDue->amount,
                'status' => $this->membershipDue->status,
            ],
        ];
    }
}
