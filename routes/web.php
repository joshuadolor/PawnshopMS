<?php

use App\Http\Controllers\Branch\BranchController;
use App\Http\Controllers\BranchFinancialTransactionController;
use App\Http\Controllers\Config\ConfigController;
use App\Http\Controllers\ImageController;
use App\Http\Controllers\ItemType\ItemTypeController;
use App\Http\Controllers\ItemsController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\Transactions\Sangla\SanglaController;
use App\Http\Controllers\Transactions\TransactionController;
use App\Http\Controllers\Transactions\RenewalController;
use App\Http\Controllers\User\UserController;
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

    // Image serving route (authenticated)
    Route::get('/images/{path}', [ImageController::class, 'show'])
        ->where('path', '.*')
        ->name('images.show');

    // Transaction Routes
    Route::prefix('transactions')->name('transactions.')->group(function () {
        Route::get('/', [TransactionController::class, 'index'])->name('index');
        Route::post('/{transaction}/void', [TransactionController::class, 'void'])->name('void');
        Route::post('/void-pawn-ticket/{pawnTicketNumber}', [TransactionController::class, 'voidPawnTicket'])->name('void-pawn-ticket');
        Route::get('/related/{pawnTicketNumber}', [TransactionController::class, 'getRelatedTransactions'])->name('related');
        Route::get('/sangla/create', [SanglaController::class, 'create'])->name('sangla.create');
        Route::post('/sangla', [SanglaController::class, 'store'])->name('sangla.store');
        Route::get('/sangla/additional-item', [SanglaController::class, 'additionalItem'])->name('sangla.additional-item');
        Route::post('/sangla/additional-item', [SanglaController::class, 'storeAdditionalItem'])->name('sangla.store-additional-item');
        
        // Renewal Routes
        Route::get('/renewal/search', [RenewalController::class, 'search'])->name('renewal.search');
        Route::post('/renewal/find', [RenewalController::class, 'find'])->name('renewal.find');
        Route::post('/renewal', [RenewalController::class, 'store'])->name('renewal.store');
    });

    // Branch Financial Transactions Routes
    Route::resource('branch-financial-transactions', BranchFinancialTransactionController::class)->only(['index', 'create', 'store']);
    Route::post('/branch-financial-transactions/{branchFinancialTransaction}/void', [BranchFinancialTransactionController::class, 'void'])->name('branch-financial-transactions.void');

    // User Management Routes (Admin and Superadmin only)
    Route::middleware(EnsureUserIsAdmin::class)->group(function () {
        Route::resource('users', UserController::class)->except(['show', 'edit', 'update']);
        Route::patch('/users/{user}/status', [UserController::class, 'updateStatus'])->name('users.update-status');
        Route::patch('/users/{user}/role', [UserController::class, 'updateRole'])->name('users.update-role');
        Route::patch('/users/{user}/branches', [UserController::class, 'updateBranches'])->name('users.update-branches');
        Route::post('/users/{user}/reset-password', [UserController::class, 'resetPassword'])->name('users.reset-password');
        
        // Item Type Management Routes
        Route::resource('item-types', ItemTypeController::class)->except(['show', 'edit', 'update']);
        Route::post('/item-types/{itemType}/subtypes', [ItemTypeController::class, 'storeSubtype'])->name('item-types.subtypes.store');
        Route::put('/item-types/{itemType}/subtypes/{subtype}', [ItemTypeController::class, 'updateSubtype'])->name('item-types.subtypes.update');
        Route::delete('/item-types/{itemType}/subtypes/{subtype}', [ItemTypeController::class, 'destroySubtype'])->name('item-types.subtypes.destroy');
        Route::post('/item-types/{itemType}/tags', [ItemTypeController::class, 'storeTag'])->name('item-types.tags.store');
        Route::put('/item-types/{itemType}/tags/{tag}', [ItemTypeController::class, 'updateTag'])->name('item-types.tags.update');
        Route::delete('/item-types/{itemType}/tags/{tag}', [ItemTypeController::class, 'destroyTag'])->name('item-types.tags.destroy');
        
        // Branch Management Routes
        Route::resource('branches', BranchController::class)->except(['show', 'edit', 'update']);
        
        // Configuration Management Routes
        Route::get('/configs', [ConfigController::class, 'index'])->name('configs.index');
        Route::put('/configs', [ConfigController::class, 'update'])->name('configs.update');
        
        // Additional Charge Configurations Routes
        Route::resource('config/additional-charge-configs', \App\Http\Controllers\Config\AdditionalChargeConfigController::class)->names([
            'index' => 'config.additional-charge-configs.index',
            'create' => 'config.additional-charge-configs.create',
            'store' => 'config.additional-charge-configs.store',
            'edit' => 'config.additional-charge-configs.edit',
            'update' => 'config.additional-charge-configs.update',
            'destroy' => 'config.additional-charge-configs.destroy',
        ]);
        
        // Items Management Routes
        Route::get('/items', [ItemsController::class, 'index'])->name('items.index');
    });
});

require __DIR__.'/auth.php';
