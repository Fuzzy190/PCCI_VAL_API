<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Member;
use App\Notifications\MembershipDuesFirstWarningEmail;
use App\Notifications\MembershipDuesSecondWarningEmail;
use App\Notifications\MembershipDuesFinalWarningEmail;
use App\Notifications\MembershipDuesInactiveNoticeEmail;
use Carbon\Carbon;

class CheckExpiringMemberships extends Command
{
    protected $signature = 'memberships:check-expiring';
    protected $description = 'Check for expiring memberships and report warning/inactive counts.';

    /**
     * Bulletproof method to extract the applicant's email address
     */
    private function getMemberEmail($member)
    {
        $applicant = $member->applicant;
        
        if (!$applicant) {
            return null;
        }

        if (!empty($applicant->email)) return $applicant->email;

        $profile = $applicant->basic_profile;
        if (is_string($profile)) $profile = json_decode($profile, true);

        if (is_array($profile) && !empty($profile['email'])) return $profile['email'];
        if (is_object($profile) && !empty($profile->email)) return $profile->email;

        $rawProfile = $applicant->getAttributes()['basic_profile'] ?? null;
        if (is_string($rawProfile)) {
            $decoded = json_decode($rawProfile, true);
            if (is_array($decoded) && !empty($decoded['email'])) {
                return $decoded['email'];
            }
        }

        return null;
    }

    public function handle(): void
    {
        $today = Carbon::now()->startOfDay();

        // Target Dates formatting to ensure perfect matching
        $target3Months = $today->copy()->addMonths(3)->format('Y-m-d');
        $target2Months = $today->copy()->addMonths(2)->format('Y-m-d');
        $target1Month  = $today->copy()->addMonths(1)->format('Y-m-d');
        
        // Target for ALREADY EXPIRED (Exactly 1 day ago so we don't spam them every day)
        $targetInactive = $today->copy()->subDay()->format('Y-m-d');

        $firstWarningCount   = 0;
        $secondWarningCount  = 0;
        $thirdWarningCount   = 0;
        $inactiveNoticeCount = 0;

        // 1. Send 1st Warning (3 Months Before)
        $membersExpiringIn3Months = Member::with('applicant')->whereDate('membership_end_date', $target3Months)->get();
        foreach ($membersExpiringIn3Months as $member) {
            if ($this->getMemberEmail($member)) {
                $member->notify(new MembershipDuesFirstWarningEmail($member));
                $firstWarningCount++;
            }
        }

        // 2. Send 2nd Warning (2 Months Before)
        $membersExpiringIn2Months = Member::with('applicant')->whereDate('membership_end_date', $target2Months)->get();
        foreach ($membersExpiringIn2Months as $member) {
            if ($this->getMemberEmail($member)) {
                $member->notify(new MembershipDuesSecondWarningEmail($member));
                $secondWarningCount++;
            }
        }

        // 3. Send 3rd Warning (1 Month Before)
        $membersExpiringIn1Month = Member::with('applicant')->whereDate('membership_end_date', $target1Month)->get();
        foreach ($membersExpiringIn1Month as $member) {
            if ($this->getMemberEmail($member)) {
                $member->notify(new MembershipDuesFinalWarningEmail($member));
                $thirdWarningCount++;
            }
        }

        // 4. Send Inactive Notice (Exactly 1 Day After Expiration)
        $membersAlreadyInactive = Member::with('applicant')->whereDate('membership_end_date', $targetInactive)->get();
        foreach ($membersAlreadyInactive as $member) {
            if ($this->getMemberEmail($member)) {
                $member->notify(new MembershipDuesInactiveNoticeEmail($member));
                $inactiveNoticeCount++;
            }
        }

        $totalInactiveCount = Member::where('status', 'inactive')->count();

        // Final Console Output
        $this->info('--- Membership Expiration Check Complete ---');
        $this->line("1st Warning Count: {$firstWarningCount}");
        $this->line("2nd Warning Count: {$secondWarningCount}");
        $this->line("3rd Warning Count: {$thirdWarningCount}");
        $this->line("Inactive Notices Sent Today: {$inactiveNoticeCount}");
        $this->line("--------------------------------------------");
        $this->info("Total Inactive Members in System: {$totalInactiveCount}");
    }
}