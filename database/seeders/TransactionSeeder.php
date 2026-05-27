<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Transaction;
use App\Models\Member;
use App\Models\User;
use App\Models\MembershipType;

class TransactionSeeder extends Seeder
{
    public function run(): void
    {
        $treasurer = User::role('treasurer')->first();
        $members = Member::with('applicant')->get();

        // DYNAMIC PRICING: Fetch actual membership types from the database
        $membershipTypes = MembershipType::all()->keyBy('id');

        $transactions = [];
        $orCounter = 1;

        foreach ($members as $index => $member) {
            // Find this specific member's membership type pricing
            $type = $membershipTypes->get($member->membership_type_id);

            // Set dynamic prices (fallback to 5000/3000 ONLY if DB is empty)
            $initialPrice = $type ? $type->price : 5000.00;
            $renewalPrice = ($type && $type->renewal_price) ? $type->renewal_price : $initialPrice;

            // 1. Initial Registration
            $transactions[] = [
                'or_number' => 'OR-' . str_pad($orCounter++, 4, '0', STR_PAD_LEFT),
                'transaction_type' => 'initial_registration',
                'applicant_id' => $member->applicant_id,
                'member_id' => null,
                'membership_due_id' => null,
                'amount' => $initialPrice, // Dynamic Initial Price
                'payment_method' => 'cash',
                'status' => 'approved',
                'processed_by_user_id' => $treasurer?->id,
                'notes' => 'Paper Record Migration: Initial Application Fee.',
                'created_at' => now()->subMonths(12),
                'updated_at' => now()->subMonths(12),
            ];

            // // 2. Renewal
            // if ($index % 2 == 0) {
            //     $transactions[] = [
            //         'or_number' => 'OR-' . str_pad($orCounter++, 4, '0', STR_PAD_LEFT),
            //         'transaction_type' => 'renewal',
            //         'applicant_id' => null,
            //         'member_id' => $member->id,
            //         'membership_due_id' => null,
            //         'amount' => $renewalPrice, // Dynamic Renewal Price
            //         'payment_method' => 'bank_transfer',
            //         'status' => 'approved',
            //         'processed_by_user_id' => $treasurer?->id,
            //         'notes' => 'Paper Record Migration: Annual Renewal.',
            //         'created_at' => now()->subDays(rand(1, 30)),
            //         'updated_at' => now()->subDays(rand(1, 30)),
            //     ];
            // }
        }

        Transaction::insert($transactions);
        $this->command->info(count($transactions) . ' legacy transactions seeded with dynamic pricing!');
    }
}
