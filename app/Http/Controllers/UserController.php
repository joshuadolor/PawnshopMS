<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreUserRequest;
use App\Http\Requests\UpdateUserBranchesRequest;
use App\Http\Requests\UpdateUserRoleRequest;
use App\Http\Requests\UpdateUserStatusRequest;
use App\Models\Branch;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;

class UserController extends Controller
{
    /**
     * Display a listing of the users.
     */
    public function index(Request $request): View
    {
        $query = User::query();

        // Admin users should not see superadmin records
        if ($request->user()->isAdmin() && !$request->user()->isSuperAdmin()) {
            $query->where('role', '!=', 'superadmin');
        }

        $users = $query->with('branches')->orderBy('created_at', 'desc')->paginate(15);
        $branches = Branch::orderBy('name', 'asc')->get();

        return view('users.index', [
            'users' => $users,
            'branches' => $branches,
        ]);
    }

    /**
     * Show the form for creating a new user.
     */
    public function create(): View
    {
        return view('users.create');
    }

    /**
     * Store a newly created user in storage.
     */
    public function store(StoreUserRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        // Use provided password or default
        $password = $request->filled('password') 
            ? Hash::make($validated['password']) 
            : Hash::make(env('DEFAULT_PASSWORD', 'password9988'));

        User::create([
            'first_name' => $validated['first_name'],
            'last_name' => $validated['last_name'],
            'username' => $validated['username'],
            'email' => $validated['email'] ?? null,
            'phone_number' => $validated['phone_number'] ?? null,
            'role' => $validated['role'],
            'password' => $password,
            'is_active' => $request->has('is_active') ? (bool)($validated['is_active'] ?? false) : false,
        ]);

        return redirect()->route('users.index')
            ->with('status', 'User created successfully.');
    }

    /**
     * Update the specified user's active status.
     */
    public function updateStatus(UpdateUserStatusRequest $request, User $user): RedirectResponse
    {
        $validated = $request->validated();

        $user->update([
            'is_active' => $validated['is_active'],
        ]);

        $status = $request->boolean('is_active') ? 'activated' : 'deactivated';

        return redirect()->route('users.index')
            ->with('status', "User has been {$status} successfully.");
    }

    /**
     * Update the specified user's role.
     */
    public function updateRole(UpdateUserRoleRequest $request, User $user): RedirectResponse
    {
        $validated = $request->validated();

        $user->update([
            'role' => $validated['role'],
        ]);

        return redirect()->route('users.index')
            ->with('status', "Role has been updated to {$validated['role']} for {$user->name}.");
    }

    /**
     * Reset the specified user's password to default.
     */
    public function resetPassword(Request $request, User $user): RedirectResponse
    {
        // Prevent admin from resetting superadmin password
        if ($request->user()->isAdmin() && $user->isSuperAdmin()) {
            abort(403, 'Unauthorized action.');
        }

        $defaultPassword = env('DEFAULT_PASSWORD', 'password9988');
        
        $user->update([
            'password' => Hash::make($defaultPassword),
        ]);

        return redirect()->route('users.index')
            ->with('status', "Password has been reset to default for {$user->name}.");
    }

    /**
     * Update the specified user's branches.
     */
    public function updateBranches(UpdateUserBranchesRequest $request, User $user): RedirectResponse
    {
        // Prevent admin from modifying superadmin
        if ($request->user()->isAdmin() && $user->isSuperAdmin()) {
            abort(403, 'Unauthorized action.');
        }

        $user->branches()->sync($request->branches);

        return redirect()->route('users.index')
            ->with('status', "Branches have been updated for {$user->name}.");
    }

    /**
     * Remove the specified user from storage.
     */
    public function destroy(Request $request, User $user): RedirectResponse
    {
        // Prevent admin from deleting superadmin
        if ($request->user()->isAdmin() && $user->isSuperAdmin()) {
            abort(403, 'Unauthorized action.');
        }

        // Prevent deleting yourself
        if ($user->id === $request->user()->id) {
            return redirect()->route('users.index')
                ->with('error', 'You cannot delete your own account.');
        }

        $user->delete();

        return redirect()->route('users.index')
            ->with('status', 'User deleted successfully.');
    }
}

