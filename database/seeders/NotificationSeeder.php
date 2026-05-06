<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Member;
use App\Notifications\SystemAlertNotification;
use Illuminate\Support\Facades\Notification;

class NotificationSeeder extends Seeder
{
    public function run(): void
    {
        $admins = User::role(['admin', 'super_admin'])->get();
        $treasurers = User::role('treasurer')->get();
        
        // Grab all the migrated members
        $members = Member::with('applicant')->get();
        $count = 0;

        foreach ($members as $member) {
            $businessName = $member->applicant->registered_business_name ?? 'A Legacy Member';

            // 1. Send an individual notification to ALL Admins for this specific business
            if ($admins->count() > 0) {
                Notification::send($admins, new SystemAlertNotification(
                    'Paper Record Digitized',
                    "The legacy manual record for {$businessName} has been successfully migrated to the system.",
                    'fa-database',
                    'text-success'
                ));
            }

            // 2. Send an individual notification to ALL Treasurers for this specific business
            if ($treasurers->count() > 0) {
                Notification::send($treasurers, new SystemAlertNotification(
                    'Legacy Ledger Synced',
                    "The initial manual payment for {$businessName} has been recorded in the global digital ledger.",
                    'fa-file-invoice-dollar',
                    'text-info'
                ));
            }

            $count++;
        }

        $this->command->info("Successfully generated {$count} individual notifications for Admins and {$count} for Treasurers!");
    }
}