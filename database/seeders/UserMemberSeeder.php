<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Member;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\File;

class UserMemberSeeder extends Seeder
{
    public function run(): void
    {
        $members = Member::with('applicant')->get();

        $credentials = [];

        foreach ($members as $member) {

            $email = $member->applicant->email ?? 'user' . $member->id . '@example.com';

            // Generate consistent password per user
            $password = substr(md5($email), 0, 10);

            // Splitting the name into first_name and last_name using representative data
            $user = User::create([
                'first_name' => $member->applicant->rep_first_name ?? $member->applicant->registered_business_name ?? 'Business',
                'last_name'  => $member->applicant->rep_surname ?? 'Member',
                'email'      => $email,
                'password'   => Hash::make($password),
            ]);

            $user->assignRole('member');

            $member->update([
                'user_id' => $user->id,
            ]);

            $businessName = $member->applicant->registered_business_name ?? 'Unknown Business';
            $credentials[] = "Business: {$businessName} | Rep Name: {$user->name} | Email: {$email} | Password: {$password}";
        }

        // Save credentials to file
        File::put(
            storage_path('app/member_credentials.txt'),
            implode("\n", $credentials)
        );

        $this->command->info('Users seeded successfully.');
        $this->command->info('Credentials saved to storage/app/member_credentials.txt');
    }
}