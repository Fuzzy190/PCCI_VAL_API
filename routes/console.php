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
// 1. THE MAIN EXPIRY CHECKER
// ========================================================
Artisan::command('check:expiring-memberships', function () {
    $this->info('Scanning for memberships that expired today or earlier...');

    $expiredMembers = Member::with(['user', 'applicant'])
        ->where('status', 'active')
        ->whereNotNull('membership_end_date')
        ->whereDate('membership_end_date', '<=', Carbon::today())
        ->get();

    $admins = User::role(['admin', 'super_admin'])->get();
    $count = 0;

    foreach ($expiredMembers as $member) {
        // 1. Instantly update DB to expired
        $member->update(['status' => 'expired']);

        $profile = $member->applicant->basic_profile ?? [];
        if (is_string($profile)) $profile = json_decode($profile, true);
        
        $businessName = $profile['registered_business_name'] ?? 'A member';
        $memberEmail = $profile['email'] ?? ($member->user->email ?? null);

        // 2. In-App Notifications
        Notification::send($admins, new SystemAlertNotification(
            'Membership Expired', "{$businessName} has expired.", 'fa-exclamation-triangle', 'text-warning'
        ));

        if ($member->user) {
            $member->user->notify(new SystemAlertNotification(
                'Membership Expired', 'Your PCCI membership has expired. Please renew.', 'fa-clock', 'text-warning'
            ));
        }

        // 3. Send Real Email (Fixed with explicitly defined from address)
        if ($memberEmail) {
            try {
                Mail::html("
                    <div style='font-family: Arial; padding: 20px; border-top: 4px solid #be1e38;'>
                        <h2 style='color: #be1e38;'>Membership Expired</h2>
                        <p>Hello <strong>{$businessName}</strong>,</p>
                        <p>Your PCCI Valenzuela membership has officially expired. Please log in to your dashboard to submit your renewal payment and restore your benefits.</p>
                        <p>Thank you,<br>PCCI Administration</p>
                    </div>
                ", function($message) use ($memberEmail, $businessName) {
                    // Force the FROM address to prevent SMTP blocking
                    $message->from(env('MAIL_FROM_ADDRESS', 'hello@example.com'), env('MAIL_FROM_NAME', 'PCCI'));
                    $message->to($memberEmail, $businessName)->subject('Notice: Your PCCI Membership Has Expired');
                });
                $this->info("Email successfully sent to: {$memberEmail}");
            } catch (\Exception $e) {
                $this->error("Email failed for {$memberEmail}: " . $e->getMessage());
            }
        }
        $count++;
    }
    $this->info("Done! {$count} members were moved to expired status.");
});

Schedule::command('check:expiring-memberships')->daily();

// ========================================================
// 2. THE TESTING TOOL (Run this first!)
// ========================================================
Artisan::command('test:force-expire', function() {
    $member = Member::where('status', 'active')->first();
    if(!$member) {
        $this->error("No active members found to test with.");
        return;
    }
    // Force their date into the past so the checker finds them
    $member->update(['membership_end_date' => now()->subDays(5)]);
    $this->info("SUCCESS: Forced user ID {$member->user_id} to have an expired date.");
    $this->info("Now run: php artisan check:expiring-memberships");
});