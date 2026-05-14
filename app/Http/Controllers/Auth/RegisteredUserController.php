<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Hash;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use App\Http\Resources\UserResource;

class RegisteredUserController extends Controller
{
    // GET /v1/users
    public function index()
    {
        $users = User::all();
        return UserResource::collection($users);
    }

    // GET /v1/users/{user}
    public function show(User $user)
    {
        return new UserResource($user);
    }

    // GET /v1/users/roles/{role}
    public function getByRole($role)
    {
        $users = User::whereHas('roles', function ($query) use ($role) {
            $query->where('name', $role);
        })->get();

        return UserResource::collection($users);
    }


    /**
     * Handle an incoming registration request.
     */
    public function store(Request $request)
    {
        // 1. Validate the incoming data (Notice we don't require the frontend to send a password anymore)
        $request->validate([
            'first_name' => ['required', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:' . User::class],
            'role' => ['required', 'string'] // Ensure the role is passed
        ]);

        // 2. Generate a highly secure temporary password on the backend
        $plainPassword = Str::random(10) . 'A!1a'; // Example: 8xK9pL2qA!1a

        // 3. Create the User in the database
        $user = User::create([
            'first_name' => $request->first_name,
            'last_name' => $request->last_name,
            'email' => $request->email,
            'password' => Hash::make($plainPassword),
            'requires_password_change' => true, // Force them to change it on first login
        ]);

        // 4. Assign the correct Role (CRITICAL: Otherwise they cannot log in as admin)
        if (class_exists('\\Spatie\\Permission\\Models\\Role')) {
            $user->assignRole($request->role);
        }

        // 5. Explicitly email the NEW user with their plain-text password
        // (Make sure you have an 'emails.new_admin_user' blade file, or use raw text for now)
        try {
            Mail::raw("Hello {$user->first_name},\n\nYour PCCI Admin Account has been created!\n\nEmail: {$user->email}\nTemporary Password: {$plainPassword}\n\nPlease log in and change your password immediately.", function ($message) use ($user) {
                $message->to($user->email) // Send to the NEW user, NOT the admin creating it!
                    ->subject('Your PCCI Admin Account Credentials');
            });
        } catch (\Exception $e) {
            \Log::error('Failed to send admin credentials email: ' . $e->getMessage());
        }

        event(new Registered($user));

        // Return the plain password in the JSON response ONLY so the Admin's screen can display it in the success modal
        return response()->json([
            'message' => 'User registered successfully.',
            'user' => $user,
            'generated_password' => $plainPassword
        ], 201);
    }
}
