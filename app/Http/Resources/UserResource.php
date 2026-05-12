<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'name' => $this->name, // Leave this here so nothing else breaks!
            'first_name' => $this->first_name, // <--- ADD THIS
            'last_name' => $this->last_name,   // <--- ADD THIS
            'email' => $this->email,
            'roles' => $this->getRoleNames(),
            'created_at' => $this->created_at,
        ];
    }
}
