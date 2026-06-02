<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Support\Facades\Storage;

class User extends Authenticatable
{
    use HasFactory, Notifiable, HasRoles, HasApiTokens;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'first_name', // Changed
        'last_name',  // Changed
        'email',
        'password',
        "contact_number",
        'profile_photo_path',
        'requires_password_change'
    ];

    /**
     * The attributes that should be hidden for serialization.
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'requires_password_change' => 'boolean'
        ];
    }

    /**
     * Append custom attributes when the model is converted to an array or JSON.
     */
    protected $appends = [
        'profile_photo_url',
        'photo_url', // Backwards compatibility for frontend
        'name', // Appends the combined name attribute automatically
    ];

    public function member()
    {
        return $this->hasOne(Member::class);
    }

    public function getNameAttribute()
    {
        return "{$this->first_name} {$this->last_name}";
    }

    /**
     * Automatically convert the database path to a secure, temporary Backblaze URL.
     */
    /**
     * Automatically convert the database path to a secure, temporary Backblaze URL.
     */
    public function getPhotoUrlAttribute()
    {
        if ($this->profile_photo_path) {
            try {
                // We completely removed the exists() check so it loads instantly!
                return \Illuminate\Support\Facades\Storage::disk('s3')->temporaryUrl(
                    $this->profile_photo_path,
                    now()->addMinutes(60)
                );
            } catch (\Exception $e) {
                \Log::error('Backblaze Error: ' . $e->getMessage());
                return null;
            }
        }
        return null;
    }

    /**
     * Get the user's profile photo URL from S3.
     */
    public function getProfilePhotoUrlAttribute()
    {
        if ($this->profile_photo_path) {
            try {
                return \Illuminate\Support\Facades\Storage::disk('s3')->temporaryUrl(
                    $this->profile_photo_path,
                    now()->addMinutes(60)
                );
            } catch (\Exception $e) {
                return null;
            }
        }
        return null;
    }
}
