<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Member;
use App\Models\MembershipDue;
use App\Models\ExpiringMembershipNotification;
use App\Services\MailtrapApiService;
use Carbon\Carbon;

class CheckExpiringMemberships extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'check:expiring-memberships';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create expiring membership notifications for members whose membership_end_date is 1, 2, or 3 months away, or already expired.';

    protected MailtrapApiService $mailtrap;

    public function __construct(MailtrapApiService $mailtrap)
    {
        parent::__construct();
        $this->mailtrap = $mailtrap;
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $now = Carbon::now(config('app.timezone', 'UTC'));

        $this->info("Checking at {$now}");

        // Check for future expiries (1-3 months ahead)
        $futureMembers = Member::whereNotNull('membership_end_date')
            ->whereDate('membership_end_date', '>=', $now->toDateString())
            ->whereDate('membership_end_date', '<=', $now->copy()->addMonths(3)->toDateString())
            ->with(['user', 'applicant'])
            ->get();

        $this->info("Found {$futureMembers->count()} future expiring members");

        foreach ($futureMembers as $member) {
            $monthsUntil = (int) round($now->diffInMonths($member->membership_end_date, false));

            if (! in_array($monthsUntil, [3, 2, 1], true)) {
                continue;
            }

            $message = "Membership will expire in {$monthsUntil} month(s) on {$member->membership_end_date}";
            $subject = "Membership Expiry Notice";
            
            $html = view('emails.membership_expiry', [
                'memberName' => $member->user?->name ?? $member->applicant?->registered_business_name ?? 'Member',
                'expiryDate' => Carbon::parse($member->membership_end_date)->format('F j, Y'),
                'isExpired' => false,
                'monthsUntil' => $monthsUntil,
            ])->render();

            $this->createNotificationIfMissing($member, $message, $subject, false, $html);
        }

        // Check for already expired memberships
        $expiredMembers = Member::whereNotNull('membership_end_date')
            ->whereDate('membership_end_date', '<', $now->toDateString())
            ->with(['user', 'applicant'])
            ->get();

        $this->info("Found {$expiredMembers->count()} expired members");

        foreach ($expiredMembers as $member) {
            MembershipDue::ensureExpiredDueForMember($member);

            $message = "Membership expired on {$member->membership_end_date}";
            $subject = "Membership Expired";

            $html = view('emails.membership_expiry', [
                'memberName' => $member->user?->name ?? $member->applicant?->registered_business_name ?? 'Member',
                'expiryDate' => Carbon::parse($member->membership_end_date)->format('F j, Y'),
                'isExpired' => true,
                'monthsUntil' => 0,
            ])->render();

            $this->createNotificationIfMissing($member, $message, $subject, true, $html);
        }
    }

    protected function createNotificationIfMissing(Member $member, string $message, string $subject, bool $isExpired = false, ?string $html = null): void
    {
        $existing = ExpiringMembershipNotification::where('member_id', $member->id)
            ->where('message', $message)
            ->exists();

        if ($existing) {
            return;
        }

        if ($isExpired) {
            ExpiringMembershipNotification::where('member_id', $member->id)
                ->where('message', 'like', 'Membership expired%')
                ->delete();
        }

        $notification = ExpiringMembershipNotification::create([
            'member_id' => $member->id,
            'message' => $message,
        ]);

        $this->sendEmailNotification($member, $subject, $message, $html);
    }

    protected function sendEmailNotification(Member $member, string $subject, string $body, ?string $html = null): void
    {
        $toEmail = $member->user?->email ?? $member->applicant?->email;
        $toName = $member->user?->name ?? $member->applicant?->registered_business_name ?? 'Member';

        if (!$toEmail) {
            return;
        }

        $this->mailtrap->sendMail($toEmail, $toName, $subject, $body, $html);
    }
}