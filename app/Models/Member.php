<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Notifiable;

class Member extends Model
{
    use HasFactory, Notifiable;

    protected $fillable = [
        'applicant_id',
        'user_id',
        'membership_type_id',
        'induction_date',
        'membership_end_date',
        'status',
    ];

    protected $casts = [
        'induction_date' => 'date',
        'membership_end_date' => 'date',
    ];

    // Relationships
    public function applicant()
    {
        return $this->belongsTo(\App\Models\Applicant::class, 'applicant_id', 'id');
    }

    public function membershipType()
    {
        return $this->belongsTo(MembershipType::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function expiringNotifications()
    {
        return $this->hasMany(ExpiringMembershipNotification::class);
    }

    public function membershipDues()
    {
        return $this->hasMany(MembershipDue::class);
    }

    /**
     * Tell Laravel exactly where to send the email for this specific member.
     * This uses the same bulletproof JSON extraction we built for the command.
     */
    public function routeNotificationForMail($notification)
    {
        $applicant = $this->applicant;
        
        if (!$applicant) {
            return null;
        }

        // Method 1: Check if 'email' is a flat column
        if (!empty($applicant->email)) {
            return $applicant->email;
        }

        // Method 2: Check inside 'basic_profile'
        $profile = $applicant->basic_profile;
        if (is_string($profile)) {
            $profile = json_decode($profile, true);
        }

        if (is_array($profile) && !empty($profile['email'])) {
            return $profile['email'];
        }

        if (is_object($profile) && !empty($profile->email)) {
            return $profile->email;
        }

        // Method 3: Check raw database attributes
        $rawProfile = $applicant->getAttributes()['basic_profile'] ?? null;
        if (is_string($rawProfile)) {
            $decoded = json_decode($rawProfile, true);
            if (is_array($decoded) && !empty($decoded['email'])) {
                return $decoded['email'];
            }
        }

        return null;
    }

    /**
     * Check and update member status based on dues payment
     * 
     * Rules:
     * - If current year dues are paid -> status = 'active'
     * - If current year dues are overdue -> status = 'inactive' 
     * - If no dues exist for current year -> status = 'pending'
     */
    public function updateMembershipStatus()
    {
        $currentYear = now()->year;
        $currentYearDue = $this->membershipDues()->where('due_year', $currentYear)->first();

        if (!$currentYearDue) {
            // No dues record for current year
            $this->update(['status' => 'pending']);
            return;
        }

        if ($currentYearDue->status === MembershipDue::STATUS_PAID) {
            // Current year dues are paid
            $this->update(['status' => 'active']);
        } elseif ($currentYearDue->status === MembershipDue::STATUS_UNPAID && $currentYearDue->due_date->isPast()) {
            // Current year dues are unpaid and past due date
            $this->update(['status' => 'inactive']);
        } else {
            // Dues are unpaid but not yet past due date
            $this->update(['status' => 'pending']);
        }
    }

    /**
     * Check if member has paid dues for current year
     */
    public function hasPaidCurrentYearDues()
    {
        $currentYear = now()->year;
        return $this->membershipDues()
            ->where('due_year', $currentYear)
            ->where('status', 'paid')
            ->exists();
    }

    /**
     * Check if member has overdue dues
     */
    public function hasOverdueDues()
    {
        return $this->membershipDues()
            ->where('status', MembershipDue::STATUS_UNPAID)
            ->where('due_date', '<', now())
            ->exists();
    }
}