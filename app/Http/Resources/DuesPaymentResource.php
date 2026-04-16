<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use App\Http\Resources\UserResource;

class DuesPaymentResource extends JsonResource
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
            'membership_due_id' => $this->membership_due_id,
            'member_id' => $this->member_id,
            'submitted_by_user_id' => $this->submitted_by_user_id,
            'received_by_user_id' => $this->received_by_user_id,
            'or_number' => $this->or_number,
            'amount' => $this->amount,
            'payment_date' => $this->payment_date,
            'payment_method' => $this->payment_method,
            'reference_number' => $this->reference_number,
            'status' => $this->status,
            'receipt_image_url' => $this->receipt_image_url,
            'notes' => $this->notes,
            'membership_due' => new MembershipDueResource($this->whenLoaded('membershipDue')),
            'member' => new MemberResource($this->whenLoaded('member')),
            'submitted_by' => new UserResource($this->whenLoaded('submittedBy')),
            'received_by' => new UserResource($this->whenLoaded('receivedBy')),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
