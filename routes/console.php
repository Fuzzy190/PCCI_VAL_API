<?php

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;
use App\Models\Member;
use App\Models\User;
use Carbon\Carbon;
use App\Notifications\SystemAlertNotification;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Mail;

// ========================================================
// 1. THE MAIN INACTIVE CHECKER
// ========================================================
Artisan::command('check:expiring-memberships', function () {
    $this->info('Scanning for memberships that became inactive today or earlier...');

    $inactiveMembers = Member::with(['user', 'applicant'])
        ->where('status', 'active')
        ->whereNotNull('membership_end_date')
        ->whereDate('membership_end_date', '<=', Carbon::today())
        ->get();

    $admins = User::role(['admin', 'super_admin'])->get();
    $count = 0;

    foreach ($inactiveMembers as $member) {
        // 1. Instantly update DB to inactive
        $member->update(['status' => 'inactive']);

        $profile = $member->applicant->basic_profile ?? [];
        if (is_string($profile)) $profile = json_decode($profile, true);
        
        $businessName = $profile['registered_business_name'] ?? 'A member';
        $memberEmail = $profile['email'] ?? ($member->user->email ?? null);

        // 2. In-App Notifications
        Notification::send($admins, new SystemAlertNotification(
            'Membership Inactive', "{$businessName} is now inactive.", 'fa-exclamation-triangle', 'text-warning'
        ));

        if ($member->user) {
            $member->user->notify(new SystemAlertNotification(
                'Membership Inactive', 'Your PCCI membership is now inactive. Please renew.', 'fa-clock', 'text-warning'
            ));
        }

        // 3. Send Real Email
        if ($memberEmail) {
            try {
                Mail::html("
                    <div style='font-family: Arial; padding: 20px; border-top: 4px solid #be1e38;'>
                        <h2 style='color: #be1e38;'>Membership Inactive</h2>
                        <p>Hello <strong>{$businessName}</strong>,</p>
                        <p>Your PCCI Valenzuela membership is officially inactive. Please log in to your dashboard to submit your renewal payment and restore your benefits.</p>
                        <p>Thank you,<br>PCCI Administration</p>
                    </div>
                ", function($message) use ($memberEmail, $businessName) {
                    $message->from(env('MAIL_FROM_ADDRESS', 'hello@example.com'), env('MAIL_FROM_NAME', 'PCCI'));
                    $message->to($memberEmail, $businessName)->subject('Notice: Your PCCI Membership is Inactive');
                });
                $this->info("Email successfully sent to: {$memberEmail}");
            } catch (\Exception $e) {
                $this->error("Email failed for {$memberEmail}: " . $e->getMessage());
            }
        }
        $count++;
    }
    $this->info("Done! {$count} members were moved to inactive status.");
});

Schedule::command('check:expiring-memberships')->daily();

// ========================================================
// 2. THE TESTING TOOL
// ========================================================
Artisan::command('test:force-inactive', function() {
    $member = Member::where('status', 'active')->first();
    if(!$member) {
        $this->error("No active members found to test with.");
        return;
    }
    $member->update(['membership_end_date' => now()->subDays(5)]);
    $this->info("SUCCESS: Forced user ID {$member->user_id} to have an inactive date.");
    $this->info("Now run: php artisan check:expiring-memberships");
});