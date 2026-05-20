<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\PaymentChannel;

class PaymentChannelSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Define all your payment channels here
        $channels = [
            [
                'payment_method' => 'China Bank',
                'account_name'   => 'PCCI Valenzuela',
                'account_no'     => '1054 0000 5989',
                'is_active'      => true
            ],
            [
                'payment_method' => 'GCash',
                'account_name'   => 'PCCI Valenzuela',
                'account_no'     => '0917 000 0000', // Replace with real GCash number
                'is_active'      => false
            ],
            [
                'payment_method' => 'BDO',
                'account_name'   => 'PCCI Valenzuela',
                'account_no'     => '0012 3456 7890', // Replace with real BDO account number
                'is_active'      => false
            ]
        ];

        // Loop through and insert/update them in the database
        foreach ($channels as $channel) {
            PaymentChannel::updateOrCreate(
                [
                    // We use account_no as the unique key to check if it already exists
                    'account_no' => $channel['account_no']
                ],
                $channel
            );
        }
    }
}
