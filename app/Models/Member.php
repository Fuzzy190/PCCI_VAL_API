<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Member extends Model
{
    use HasFactory;

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
     * Check and update member status based on dues payment
     * 
     * Rules:
     * - If current year dues are paid → status = 'active'
     * - If current year dues are overdue → status = 'inactive' 
     * - If no dues exist for current year → status = 'pending'
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
