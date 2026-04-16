<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return ['Laravel' => app()->version()];
});


use Illuminate\Support\Facades\Mail;
use App\Mail\ApplicantApprovedPaid;
use App\Mail\TestEmail;

Route::get('/test-email', function() {
    $dummy = (object) ['first_name' => 'Test', 'email' => 'jeditorres@gmail.com'];
    Mail::to($dummy->email)->send(new \App\Mail\ApplicantApprovedPaid($dummy));
    return 'Email sent!';
});

Route::get('/test-mailtrap', function() {
    try {
        Mail::to('test@example.com')->send(new TestEmail());
        return response()->json([
            'status' => 'success',
            'message' => 'Test email sent! Check your Mailtrap inbox.',
            'mail_config' => [
                'driver' => config('mail.default'),
                'host' => config('mail.mailers.smtp.host'),
                'port' => config('mail.mailers.smtp.port'),
                'encryption' => config('mail.mailers.smtp.encryption'),
            ]
        ]);
    } catch (\Exception $e) {
        return response()->json([
            'status' => 'error',
            'message' => $e->getMessage(),
            'mail_config' => [
                'driver' => config('mail.default'),
                'host' => config('mail.mailers.smtp.host'),
                'port' => config('mail.mailers.smtp.port'),
                'encryption' => config('mail.mailers.smtp.encryption'),
            ]
        ], 500);
    }
});


// use Illuminate\Support\Facades\Artisan;

// Route::get('/refresh-db', function () {
//     try {
//         // Clear caches to ensure new Env variables are read
//         Artisan::call('config:clear');
//         Artisan::call('cache:clear');

//         // Run migrations with force (required for production/render)
//         // This will drop tables and re-run seeders
//         Artisan::call('migrate:fresh', [
//             '--force' => true,
//             '--seed' => true
//         ]);

//         return response()->json([
//             'status' => 'success',
//             'message' => 'Database wiped, migrated, and seeded successfully!'
//         ]);
//     } catch (\Exception $e) {
//         return response()->json([
//             'status' => 'error',
//             'message' => $e->getMessage()
//         ], 500);
//     }
// });



// Removed duplicate auth route registration so auth routes are only exposed through the API.
// require __DIR__.'/auth.php';
