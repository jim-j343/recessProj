<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Route; // Added to check if routes exist
use Illuminate\Validation\Rules;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class RegisteredUserController extends Controller
{
    /**
     * Display the registration view.
     */
    public function create(): View
    {
        return view('auth.register');
    }

    /**
     * Handle an incoming registration request.
     *
     * @throws ValidationException
     */
    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'username' => ['required', 'string', 'max:80', 'unique:users,username'],
            'email'    => ['required', 'string', 'lowercase', 'email', 'max:150', 'unique:users,email'],
            'role'     => ['required', 'in:student,lecturer,system_admin'], // Added system_admin here
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
            'rules_accepted' => ['accepted'],
        ]);

        $user = User::create([
            'username'        => $request->username,
            'email'           => $request->email,
            'password_hash'   => Hash::make($request->password), // Aligns with migration
            'system_role'     => $request->role,                 // Aligns with migration
            'status'          => 'active',                       // Aligns with migration
            'agreed_to_rules' => true,                           // Aligns with migration
        ]);

        event(new Registered($user));

        Auth::login($user);

        // 1. Redirect System Admin (With safety check so it won't crash if the route isn't built yet)
        if ($user->system_role === 'system_admin') {
            return Route::has('admin.dashboard')
                ? redirect()->route('admin.dashboard')
                : redirect()->route('dashboard')->with('status', 'Admin dashboard coming soon!');
        }

        // 2. Redirect Student
        if ($user->system_role === 'student') {
            return Route::has('student.dashboard')
                ? redirect()->route('student.dashboard')
                : redirect()->route('dashboard')->with('status', 'Student dashboard coming soon!');
        }

        // 3. Redirect Lecturer
        if ($user->system_role === 'lecturer') {
            return Route::has('lecturer.dashboard')
                ? redirect()->route('lecturer.dashboard')
                : redirect()->route('dashboard')->with('status', 'Lecturer dashboard coming soon!');
        }

        return redirect(route('dashboard', absolute: false));
    }
}
