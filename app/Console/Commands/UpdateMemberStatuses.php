<?php

namespace App\Console\Commands;

use App\Models\Member;
use Illuminate\Console\Command;

class UpdateMemberStatuses extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'members:update-statuses {--year= : Update statuses for specific year}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Update member statuses based on dues payment status';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $year = $this->option('year') ?? now()->year;

        $this->info("Updating member statuses for year {$year}...");

        $members = Member::with(['membershipDues' => function ($query) use ($year) {
            $query->where('due_year', $year);
        }])->get();

        $updated = 0;
        $active = 0;
        $inactive = 0;
        $pending = 0;

        foreach ($members as $member) {
            $oldStatus = $member->status;
            $member->updateMembershipStatus();

            if ($oldStatus !== $member->status) {
                $updated++;
            }

            switch ($member->status) {
                case 'active':
                    $active++;
                    break;
                case 'inactive':
                    $inactive++;
                    break;
                case 'pending':
                    $pending++;
                    break;
            }
        }

        $this->info("Member status update completed:");
        $this->line("- Total members processed: {$members->count()}");
        $this->line("- Statuses updated: {$updated}");
        $this->line("- Active members: {$active}");
        $this->line("- Inactive members: {$inactive}");
        $this->line("- Pending members: {$pending}");

        return Command::SUCCESS;
    }
}
