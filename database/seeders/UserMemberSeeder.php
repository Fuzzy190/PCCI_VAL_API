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
            $businessName = $member->applicant->registered_business_name ?? 'Unknown Business';

            // Generate consistent password per user
            $password = substr(md5($email), 0, 10);

            // =========================================================
            // CONDITION: Only 'Red Amber Enterprises' forces password change
            // =========================================================
            $requiresPasswordChange = ($businessName === 'Red Amber Enterprises');

            // Splitting the name into first_name and last_name using representative data
            $user = User::create([
                'first_name' => $member->applicant->rep_first_name ?? $businessName,
                'last_name'  => $member->applicant->rep_surname ?? 'Member',
                'email'      => $email,
                'password'   => Hash::make($password),
                'requires_password_change' => $requiresPasswordChange,
            ]);

            $user->assignRole('member');

            $member->update([
                'user_id' => $user->id,
            ]);

            $fullName = $user->first_name . ' ' . $user->last_name;
            $boolString = $requiresPasswordChange ? 'true' : 'false';

            $credentials[] = "Business: {$businessName} | Rep Name: {$fullName} | Email: {$email} | Password: {$password} | Requires_Password_Change: {$boolString}";
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