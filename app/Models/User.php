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
        'profile_photo_path',
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
        ];
    }
    
    /**
     * Append custom attributes when the model is converted to an array or JSON.
     */
    protected $appends = [
        'profile_photo_url',
        'name', // Appends the combined name attribute automatically
    ];

    public function member()
    {
        return $this->hasOne(Member::class);
    }
    
    /**
     * Automatically combines first and last name when calling $user->name
     */
    public function getNameAttribute()
    {
        return trim("{$this->first_name} {$this->last_name}");
    }

    /**
     * Get the user's profile photo URL from S3.
     */
    public function getProfilePhotoUrlAttribute()
    {
        if (!$this->profile_photo_path) {
            return null;
        }

        try {
            return Storage::disk('s3')->temporaryUrl(
                $this->profile_photo_path, 
                now()->addMinutes(60)
            );
        } catch (\Exception $e) {
            return null;
        }
    }
}