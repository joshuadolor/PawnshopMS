<?php

use App\Http\Controllers\BranchController;
use App\Http\Controllers\ConfigController;
use App\Http\Controllers\ItemTypeController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\Transactions\Sangla\SanglaController;
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

    // Transaction Routes
    Route::prefix('transactions')->name('transactions.')->group(function () {
        Route::get('/sangla/create', [SanglaController::class, 'create'])->name('sangla.create');
        Route::post('/sangla', [SanglaController::class, 'store'])->name('sangla.store');
    });

    // User Management Routes (Admin and Superadmin only)
    Route::middleware(EnsureUserIsAdmin::class)->group(function () {
        Route::resource('users', UserController::class)->except(['show', 'edit', 'update']);
        Route::patch('/users/{user}/status', [UserController::class, 'updateStatus'])->name('users.update-status');
        Route::patch('/users/{user}/role', [UserController::class, 'updateRole'])->name('users.update-role');
        Route::patch('/users/{user}/branches', [UserController::class, 'updateBranches'])->name('users.update-branches');
        Route::post('/users/{user}/reset-password', [UserController::class, 'resetPassword'])->name('users.reset-password');
        
        // Item Type Management Routes
        Route::resource('item-types', ItemTypeController::class)->except(['show', 'edit', 'update']);
        
        // Branch Management Routes
        Route::resource('branches', BranchController::class)->except(['show', 'edit', 'update']);
        
        // Configuration Management Routes
        Route::get('/configs', [ConfigController::class, 'index'])->name('configs.index');
        Route::put('/configs', [ConfigController::class, 'update'])->name('configs.update');
    });
});

require __DIR__.'/auth.php';
