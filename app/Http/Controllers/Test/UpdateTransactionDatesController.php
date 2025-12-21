<?php

namespace App\Http\Controllers\Test;

use App\Http\Controllers\Controller;
use App\Models\Transaction;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use Illuminate\Support\Facades\DB;

class UpdateTransactionDatesController extends Controller
{
    /**
     * Display a listing of Sangla transactions for date updates.
     */
    public function index(): View
    {
        // Get all pawn ticket numbers that have active (non-voided) tubos transactions
        $pawnTicketsWithActiveTubos = Transaction::where('type', 'tubos')
            ->whereDoesntHave('voided')
            ->pluck('pawn_ticket_number')
            ->filter()
            ->unique()
            ->toArray();

        // Get only active Sangla transactions that don't have active tubos
        $transactions = Transaction::where('type', 'sangla')
            ->whereDoesntHave('voided')
            ->where(function($query) use ($pawnTicketsWithActiveTubos) {
                $query->whereNull('pawn_ticket_number')
                      ->orWhereNotIn('pawn_ticket_number', $pawnTicketsWithActiveTubos);
            })
            ->with(['branch', 'user', 'itemType', 'voided'])
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        // Get all pawn ticket numbers from the displayed transactions
        $pawnTicketNumbers = $transactions->pluck('pawn_ticket_number')->filter()->unique();

        // Get all active (non-voided) tubos transactions for these pawn tickets (should be empty now, but kept for consistency)
        $activeTubosForPawnTickets = collect();
        if ($pawnTicketNumbers->isNotEmpty()) {
            $activeTubosForPawnTickets = Transaction::where('type', 'tubos')
                ->whereIn('pawn_ticket_number', $pawnTicketNumbers->toArray())
                ->whereDoesntHave('voided')
                ->pluck('pawn_ticket_number')
                ->unique();
        }

        // Get latest child transactions (renewals) for each pawn ticket to display their dates
        $latestChildTransactions = collect();
        if ($pawnTicketNumbers->isNotEmpty()) {
            // Get the latest child transaction (renewal or tubos) for each pawn ticket
            foreach ($pawnTicketNumbers as $pawnTicketNumber) {
                $latestChild = Transaction::where('pawn_ticket_number', $pawnTicketNumber)
                    ->whereIn('type', ['renew', 'tubos'])
                    ->whereDoesntHave('voided')
                    ->orderBy('created_at', 'desc')
                    ->first();
                
                if ($latestChild) {
                    $latestChildTransactions->put($pawnTicketNumber, $latestChild);
                }
            }
        }

        return view('test.update-transaction-dates', [
            'transactions' => $transactions,
            'activeTubosForPawnTickets' => $activeTubosForPawnTickets,
            'latestChildTransactions' => $latestChildTransactions,
        ]);
    }

    /**
     * Update dates for a transaction.
     */
    public function update(Request $request, Transaction $transaction): RedirectResponse
    {
        $request->validate([
            'maturity_date' => ['required', 'date'],
            'expiry_date' => ['required', 'date', 'after_or_equal:maturity_date'],
            'auction_sale_date' => ['nullable', 'date', 'after_or_equal:expiry_date'],
        ], [
            'expiry_date.after_or_equal' => 'The expiry redemption date must be after or equal to the maturity date.',
            'auction_sale_date.after_or_equal' => 'The auction sale date must be after or equal to the expiry redemption date.',
        ]);

        // Only allow updating Sangla transactions
        if ($transaction->type !== 'sangla') {
            return redirect()->back()
                ->with('error', 'Only Sangla transactions can be updated.');
        }

        // Check if there are child transactions (renewals) for this pawn ticket
        $pawnTicketNumber = $transaction->pawn_ticket_number;
        $latestChildTransaction = null;
        
        if ($pawnTicketNumber) {
            // Get the latest child transaction (renewal) for this pawn ticket
            $latestChildTransaction = Transaction::where('pawn_ticket_number', $pawnTicketNumber)
                ->whereIn('type', ['renew', 'tubos'])
                ->whereDoesntHave('voided')
                ->orderBy('created_at', 'desc')
                ->first();
        }

        // If there's a child transaction, update that instead of the Sangla transaction
        $transactionToUpdate = $latestChildTransaction ?? $transaction;

        DB::transaction(function () use ($transactionToUpdate, $request) {
            $transactionToUpdate->update([
                'maturity_date' => $request->input('maturity_date'),
                'expiry_date' => $request->input('expiry_date'),
                'auction_sale_date' => $request->input('auction_sale_date'),
            ]);
        });

        $message = $latestChildTransaction 
            ? "Latest child transaction #{$transactionToUpdate->transaction_number} dates updated successfully."
            : "Transaction #{$transaction->transaction_number} dates updated successfully.";

        return redirect()->back()
            ->with('success', $message);
    }
}

