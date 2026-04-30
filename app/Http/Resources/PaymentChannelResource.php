<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PaymentChannelResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'             => $this->id,
            'payment_method' => $this->payment_method,
            'account_name'   => $this->account_name,
            'account_no'     => $this->account_no,
            'account_number' => $this->account_no,
            'amount'         => $this->amount, // <-- ADD THIS LINE
            'is_active'      => (bool) $this->is_active,
            'created_at'     => $this->created_at,
            'updated_at'     => $this->updated_at,
        ];
    }
}