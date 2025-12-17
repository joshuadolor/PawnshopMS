<?php

namespace App\Http\Controllers\Transactions;

use App\Http\Controllers\Controller;
use App\Models\Transaction;
use App\Models\Config;
use App\Models\BranchFinancialTransaction;
use App\Models\BranchBalance;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class RenewalController extends Controller
{
    /**
     * Show the renewal search page.
     */
    public function search(): View
    {
        return view('transactions.renewal.search');
    }

    /**
     * Process the search and show renewal form.
     */
    public function find(Request $request): View|RedirectResponse
    {
        $request->validate([
            'pawn_ticket_number' => ['required', 'string', 'max:100'],
        ]);

        $pawnTicketNumber = $request->input('pawn_ticket_number');

        // Find all transactions with this pawn ticket number (including additional items)
        $transactions = Transaction::where('pawn_ticket_number', $pawnTicketNumber)
            ->where('type', 'sangla')
            ->whereDoesntHave('voided')
            ->with(['branch', 'itemType', 'itemTypeSubtype', 'tags'])
            ->orderBy('created_at', 'asc')
            ->get();

        if ($transactions->isEmpty()) {
            return redirect()->route('transactions.renewal.search')
                ->with('error', 'No active transaction found with the provided pawn ticket number.');
        }

        // Calculate total interest amount for all transactions
        $totalInterest = 0;
        $firstTransaction = $transactions->first();
        $branchId = $firstTransaction->branch_id;

        foreach ($transactions as $transaction) {
            // Interest = loan_amount * (interest_rate / 100)
            $interest = (float) $transaction->loan_amount * ((float) $transaction->interest_rate / 100);
            $totalInterest += $interest;
        }

        // Get config values for date calculations
        $daysBeforeRedemption = (int) Config::getValue('sangla_days_before_redemption', 90);
        $daysBeforeAuctionSale = (int) Config::getValue('sangla_days_before_auction_sale', 85);
        $interestPeriod = Config::getValue('sangla_interest_period', 'per_month');

        // Calculate default new maturity date based on interest period
        $today = now();
        $defaultMaturityDate = match ($interestPeriod) {
            'per_annum' => $today->copy()->addYear()->format('Y-m-d'),
            'per_month' => $today->copy()->addMonth()->format('Y-m-d'),
            default => $today->copy()->addMonth()->format('Y-m-d'),
        };

        return view('transactions.renewal.renew', [
            'transactions' => $transactions,
            'pawnTicketNumber' => $pawnTicketNumber,
            'totalInterest' => $totalInterest,
            'branchId' => $branchId,
            'daysBeforeRedemption' => $daysBeforeRedemption,
            'daysBeforeAuctionSale' => $daysBeforeAuctionSale,
            'defaultMaturityDate' => $defaultMaturityDate,
        ]);
    }

    /**
     * Process the renewal.
     */
    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'pawn_ticket_number' => ['required', 'string', 'max:100'],
            'maturity_date' => ['required', 'date', 'after_or_equal:today'],
            'expiry_date' => ['required', 'date', 'after_or_equal:maturity_date'],
            'auction_sale_date' => ['nullable', 'date', 'after_or_equal:expiry_date'],
            'interest_amount' => ['required', 'numeric', 'min:0'],
        ]);

        $pawnTicketNumber = $request->input('pawn_ticket_number');

        // Find all transactions with this pawn ticket number
        $transactions = Transaction::where('pawn_ticket_number', $pawnTicketNumber)
            ->where('type', 'sangla')
            ->whereDoesntHave('voided')
            ->get();

        if ($transactions->isEmpty()) {
            return redirect()->route('transactions.renewal.search')
                ->with('error', 'No active transaction found with the provided pawn ticket number.');
        }

        $firstTransaction = $transactions->first();
        $branchId = $firstTransaction->branch_id;
        $interestAmount = (float) $request->input('interest_amount');

        // Use database transaction to ensure data integrity
        DB::transaction(function () use ($transactions, $request, $branchId, $interestAmount, $pawnTicketNumber) {
            // Update all transactions with the new dates
            $transactions->each(function ($transaction) use ($request) {
                $transaction->update([
                    'maturity_date' => $request->input('maturity_date'),
                    'expiry_date' => $request->input('expiry_date'),
                    'auction_sale_date' => $request->input('auction_sale_date'),
                    'status' => 'active', // Reset status to active on renewal
                ]);
            });

            // Create financial transaction for the renewal interest payment
            // Type: "transaction" (same family as Sangla), but this one is an ADD (money coming in)
            BranchFinancialTransaction::create([
                'branch_id' => $branchId,
                'user_id' => $request->user()->id,
                'type' => 'transaction',
                'description' => "Renewal interest payment - Pawn Ticket #{$pawnTicketNumber}",
                'amount' => $interestAmount, // Positive amount (money coming in)
                'transaction_date' => now()->toDateString(),
            ]);

            // Update branch balance (add the interest amount)
            BranchBalance::updateBalance($branchId, $interestAmount);
        });

        return redirect()->route('transactions.index')
            ->with('success', "Transaction(s) with pawn ticket number '{$pawnTicketNumber}' have been renewed successfully. Interest payment of ₱" . number_format($interestAmount, 2) . " has been recorded.");
    }
}

