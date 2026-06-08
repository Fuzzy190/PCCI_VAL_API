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
            'first_name' => $this->first_name,
            'last_name' => $this->last_name,
            'email' => $this->email,
            'contact_number' => $this->contact_number, // REQUIRED: settings blade reads user.contact_number for phone field
            'roles' => $this->getRoleNames(),
            // The API must explicitly send 'photo_url'
            'photo_url' => $this->profile_photo_path
                ? \Illuminate\Support\Facades\Storage::disk('s3')->temporaryUrl($this->profile_photo_path, now()->addMinutes(60))
                : null,
            'created_at' => $this->created_at,
        ];
    }
}
