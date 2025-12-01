<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\StoreRegisteredUserRequest;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Hash;
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
     * @throws \Illuminate\Validation\ValidationException
     */
    public function store(StoreRegisteredUserRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        $user = User::create([
            'first_name' => $validated['first_name'],
            'last_name' => $validated['last_name'],
            'username' => $validated['username'],
            'password' => Hash::make($validated['password']),
            'role' => 'staff', // Default role for new registrations
            'is_active' => false, // New users need admin approval
        ]);

        event(new Registered($user));

        // Don't auto-login, user needs to be activated by admin
        return redirect(route('login'))->with('status', 'Your account has been created. Please wait for an administrator to activate your account.');
    }
}
