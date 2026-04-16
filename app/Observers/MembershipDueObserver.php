<?php

namespace App\Observers;

use App\Models\MembershipDue;

class MembershipDueObserver
{
    /**
     * Handle the MembershipDue "created" event.
     */
    public function created(MembershipDue $membershipDue): void
    {
        // Membership dues are handled by the expiration command and member status updates.
    }

    /**
     * Handle the MembershipDue "updated" event.
     */
    public function updated(MembershipDue $membershipDue): void
    {
        // Update member status when due status changes
        $membershipDue->member->updateMembershipStatus();
    }

    /**
     * Handle the MembershipDue "retrieved" event.
     * This fires every time a model is retrieved from the database
     */
    public function retrieved(MembershipDue $membershipDue): void
    {
        // Optionally check warnings when retrieved (useful for scheduled tasks)
        // Uncomment if you want to check warnings every time a due is fetched
        // $membershipDue->checkAndSendExpirationWarnings();
    }
}
