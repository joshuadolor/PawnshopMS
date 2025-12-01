<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreSanglaTransactionRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class TransactionController extends Controller
{
    /**
     * Show the form for creating a new Sangla transaction.
     */
    public function createSangla(): View
    {
        return view('transactions.sangla.create');
    }

    /**
     * Store a newly created Sangla transaction.
     */
    public function storeSangla(StoreSanglaTransactionRequest $request): RedirectResponse
    {
        // TODO: Store the transaction in the database
        // For now, just redirect back with success message
        
        return redirect()->route('dashboard')
            ->with('success', 'Sangla transaction created successfully.');
    }
}

