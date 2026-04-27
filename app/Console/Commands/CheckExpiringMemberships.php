<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Member;
use Illuminate\Support\Facades\Mail;
use Carbon\Carbon;

class CheckExpiringMemberships extends Command
{
    // The command you will type in terminal to run this
    protected $signature = 'memberships:check-expiring';
    protected $description = 'Check for expiring memberships and send emails via Gmail SMTP';

    public function handle()
    {
        $now = Carbon::now();
        
        // Find all active members who have an expiration date
        $members = Member::with('user')->whereNotNull('membership_end_date')->where('status', 'active')->get();

        $emailsSent = 0;

        foreach ($members as $member) {
            $endDate = Carbon::parse($member->membership_end_date);
            $user = $member->user;

            // Skip if no user is attached
            if (!$user) continue;

            $sendEmail = false;
            $isExpired = false;
            $monthsUntil = 0;

            // CONDITION 1: Expiring in exactly 1 Month
            if ($now->isSameDay($endDate->copy()->subMonth())) {
                 $sendEmail = true;
                 $isExpired = false;
                 $monthsUntil = 1;
            } 
            // CONDITION 2: Expiring Today or Already Past
            elseif ($now->greaterThanOrEqualTo($endDate)) {
                 $sendEmail = true;
                 $isExpired = true;
                 
                 // Update their status to expired in the database
                 $member->update(['status' => 'expired']);
            }

            // ==================== SEND GMAIL SMTP EMAIL ====================
            if ($sendEmail) {
                $memberName = $user->first_name . ' ' . $user->last_name;
                
                $emailData = [
                    'memberName' => $memberName,
                    'isExpired' => $isExpired,
                    'expiryDate' => $endDate->format('F j, Y'),
                    'monthsUntil' => $monthsUntil
                ];

                try {
                    Mail::send('emails.membership_expiry', $emailData, function($message) use ($user, $memberName, $isExpired) {
                        $subject = $isExpired 
                            ? 'Action Required: PCCI Membership Expired' 
                            : 'Reminder: PCCI Membership Expiring Soon';
                            
                        $message->to($user->email, $memberName)
                                ->subject($subject);
                    });
                    
                    $emailsSent++;
                    $this->info("Expiry email sent successfully to: {$user->email}");
                    
                } catch (\Exception $e) {
                    \Log::error("Failed to send expiry email to {$user->email}: " . $e->getMessage());
                    $this->error("Failed to send email to: {$user->email}");
                }
            }
        }
        
        $this->info("Membership check complete. Total emails sent: {$emailsSent}");
    }
}