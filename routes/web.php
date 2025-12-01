<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\UserController;
use App\Http\Middleware\EnsureUserIsAdmin;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect()->route('dashboard');
});

Route::get('/dashboard', function () {
    return view('dashboard');
})->middleware('auth')->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    // User Management Routes (Admin and Superadmin only)
    Route::middleware(EnsureUserIsAdmin::class)->group(function () {
        Route::resource('users', UserController::class)->except(['show', 'edit', 'update']);
        Route::patch('/users/{user}/status', [UserController::class, 'updateStatus'])->name('users.update-status');
        Route::patch('/users/{user}/role', [UserController::class, 'updateRole'])->name('users.update-role');
        Route::post('/users/{user}/reset-password', [UserController::class, 'resetPassword'])->name('users.reset-password');
    });
});

require __DIR__.'/auth.php';
