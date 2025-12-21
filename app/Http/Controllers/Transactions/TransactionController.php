<?php

namespace App\Http\Controllers\Transactions;

use App\Http\Controllers\Controller;
use App\Http\Requests\VoidTransactionRequest;
use App\Models\Branch;
use App\Models\BranchFinancialTransaction;
use App\Models\BranchBalance;
use App\Models\Transaction;
use App\Models\VoidedBranchFinancialTransaction;
use App\Models\VoidedTransaction;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Illuminate\Support\Str;

class TransactionController extends Controller
{
    /**
     * Display a listing of transactions.
     */
    public function index(Request $request): View
    {
        $user = $request->user();
        $query = Transaction::with(['branch', 'user', 'itemType', 'itemTypeSubtype', 'tags', 'voided']);

        // Staff users only see transactions for today
        if ($user->isStaff()) {
            $query->where('branch_id', $user->branches()->first()->id);
            $query->whereDate('created_at', today());
        } else {
            // Admin and Superadmin can filter by date
            if ($request->filled('date')) {
                $query->whereDate('created_at', $request->date);
            } elseif ($request->has('today_only') && $request->boolean('today_only')) {
                $query->whereDate('created_at', today());
            }
        }

        // Search by item description
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('item_description', 'like', "%{$search}%")
                  ->orWhere('first_name', 'like', "%{$search}%")
                  ->orWhere('last_name', 'like', "%{$search}%")
                  ->orWhere('transaction_number', 'like', "%{$search}%");
            });
        }

        // Filter by branch (for staff, only their branches)
        if ($user->isStaff()) {
            $userBranchIds = $user->branches()->pluck('branches.id')->toArray();
            if (!empty($userBranchIds)) {
                $query->whereIn('branch_id', $userBranchIds);
            }
        } elseif ($request->filled('branch_id')) {
            $query->where('branch_id', $request->branch_id);
        }

        // Get all transactions (sangla and renew) - we'll group by pawn ticket in the view
        $transactions = $query->orderBy('created_at', 'desc')
            ->paginate(20);

        // Get all unique pawn ticket numbers from the current page
        $pawnTicketNumbers = $transactions->pluck('pawn_ticket_number')->filter()->unique()->values();

        // Fetch all renewals for these pawn tickets (even if they're on different pages)
        $renewalsForPawnTickets = collect();
        if ($pawnTicketNumbers->isNotEmpty()) {
            $renewalQuery = Transaction::with(['branch', 'user', 'itemType', 'itemTypeSubtype', 'tags', 'voided'])
                ->where('type', 'renew')
                ->whereIn('pawn_ticket_number', $pawnTicketNumbers->toArray());

            // Apply same filters as main query
            if ($user->isStaff()) {
                $renewalQuery->where('branch_id', $user->branches()->first()->id);
                $renewalQuery->whereDate('created_at', today());
            } else {
                if ($request->filled('date')) {
                    $renewalQuery->whereDate('created_at', $request->date);
                } elseif ($request->has('today_only') && $request->boolean('today_only')) {
                    $renewalQuery->whereDate('created_at', today());
                }
            }

            if ($request->filled('search')) {
                $search = $request->search;
                $renewalQuery->where(function ($q) use ($search) {
                    $q->where('item_description', 'like', "%{$search}%")
                      ->orWhere('first_name', 'like', "%{$search}%")
                      ->orWhere('last_name', 'like', "%{$search}%")
                      ->orWhere('transaction_number', 'like', "%{$search}%");
                });
            }

            if ($user->isStaff()) {
                $userBranchIds = $user->branches()->pluck('branches.id')->toArray();
                if (!empty($userBranchIds)) {
                    $renewalQuery->whereIn('branch_id', $userBranchIds);
                }
            } elseif ($request->filled('branch_id')) {
                $renewalQuery->where('branch_id', $request->branch_id);
            }

            $renewalsForPawnTickets = $renewalQuery->get();
        }

        // Get branches for filter (admin/superadmin only)
        $branches = null;
        if ($user->isAdminOrSuperAdmin()) {
            $branches = Branch::orderBy('name', 'asc')->get();
        }

        return view('transactions.index', [
            'transactions' => $transactions,
            'renewalsForPawnTickets' => $renewalsForPawnTickets,
            'branches' => $branches,
            'filters' => [
                'date' => $request->date ?? null,
                'today_only' => $request->boolean('today_only', false),
                'search' => $request->search ?? null,
                'branch_id' => $request->branch_id ?? null,
            ],
        ]);
    }

    /**
     * Void a transaction.
     */
    public function void(VoidTransactionRequest $request, Transaction $transaction): RedirectResponse
    {
        // Check if transaction is already voided
        if ($transaction->isVoided()) {
            return redirect()->back()
                ->with('error', 'This transaction is already voided.');
        }

        // Check if transaction is older than 6 hours
        $hoursSinceCreation = $transaction->created_at->diffInHours(now());
        if ($hoursSinceCreation > 6) {
            return redirect()->back()
                ->with('error', 'Cannot void this transaction. Transactions can only be voided within 6 hours of creation.');
        }

        // For Sangla transactions, check if there are any non-voided child transactions
        if ($transaction->type === 'sangla') {
            $pawnTicketNumber = $transaction->pawn_ticket_number;
            if ($pawnTicketNumber) {
                $hasChildTransactions = Transaction::where('pawn_ticket_number', $pawnTicketNumber)
                    ->where('id', '!=', $transaction->id)
                    ->whereDoesntHave('voided')
                    ->exists();
                
                if ($hasChildTransactions) {
                    return redirect()->back()
                        ->with('error', 'Cannot void this transaction. There are active child transactions (additional items or renewals) associated with this pawn ticket number. Please void the child transactions first.');
                }
            }
        }

        // Create void record and void associated financial transaction within a transaction
        DB::transaction(function () use ($transaction, $request) {
            VoidedTransaction::create([
                'transaction_id' => $transaction->id,
                'voided_by' => $request->user()->id,
                'reason' => $request->reason,
                'voided_at' => now(),
            ]);

            // Find and void the associated financial transaction (type: transaction)
            // First try to find by transaction_id (for records created after migration)
            $financialTransaction = BranchFinancialTransaction::where('transaction_id', $transaction->id)
                ->where('type', 'transaction')
                ->whereDoesntHave('voided')
                ->first();

            // If not found by transaction_id, try to find by matching description, branch, amount, and date
            // This handles records created before the transaction_id column was added
            if (!$financialTransaction) {
                if ($transaction->type === 'sangla') {
                    // For Sangla transactions, match by "Sangla transaction" description
                    // Amount is stored as positive in BranchFinancialTransaction
                    $financialTransaction = BranchFinancialTransaction::where('type', 'transaction')
                        ->where(function($q) {
                            $q->where('description', 'Sangla transaction')
                              ->orWhere('description', 'Sangla transaction (additional item)');
                        })
                        ->where('branch_id', $transaction->branch_id)
                        ->where('amount', $transaction->net_proceeds) // Amount is positive
                        ->whereDate('transaction_date', $transaction->created_at->toDateString())
                        ->whereDoesntHave('voided')
                        ->whereNull('transaction_id') // Only match records without transaction_id
                        ->first();
                } elseif ($transaction->type === 'renew') {
                    // For Renewal transactions, match by "Renewal interest payment" description
                    $pawnTicketNumber = $transaction->pawn_ticket_number;
                    if ($pawnTicketNumber) {
                        $financialTransaction = BranchFinancialTransaction::where('type', 'transaction')
                            ->where('description', "Renewal interest payment - Pawn Ticket #{$pawnTicketNumber}")
                            ->where('branch_id', $transaction->branch_id)
                            ->where('amount', $transaction->net_proceeds) // Renewal amounts are positive
                            ->whereDate('transaction_date', $transaction->created_at->toDateString())
                            ->whereDoesntHave('voided')
                            ->whereNull('transaction_id') // Only match records without transaction_id
                            ->first();
                    }
                }
            }

            if ($financialTransaction) {
                // Update transaction_id if it was NULL (for old records)
                if (!$financialTransaction->transaction_id) {
                    $financialTransaction->update(['transaction_id' => $transaction->id]);
                }

                // Create void record for financial transaction
                VoidedBranchFinancialTransaction::create([
                    'branch_financial_transaction_id' => $financialTransaction->id,
                    'voided_by' => $request->user()->id,
                    'reason' => "Associated transaction #{$transaction->transaction_number} was voided: {$request->reason}",
                    'voided_at' => now(),
                ]);

                // Reverse the transaction amount in the balance
                if ($transaction->type === 'sangla') {
                    // Sangla: amount was negative (money out), so we reverse by adding it back
                    BranchBalance::updateBalance($financialTransaction->branch_id, (float) $financialTransaction->amount);
                } elseif ($transaction->type === 'renew') {
                    // Renewal: amount was positive (money in), so we reverse by subtracting it
                    BranchBalance::updateBalance($financialTransaction->branch_id, -(float) $financialTransaction->amount);
                }
            }
        });

        return redirect()->back()
            ->with('success', "Transaction #{$transaction->transaction_number} has been voided.");
    }

    /**
     * Get all related transactions (Sangla) for a given pawn ticket number.
     */
    public function getRelatedTransactions(string $pawnTicketNumber): JsonResponse
    {
        $transactions = Transaction::with(['itemType', 'itemTypeSubtype', 'tags'])
            ->where('pawn_ticket_number', $pawnTicketNumber)
            ->where('type', 'sangla')
            ->whereDoesntHave('voided')
            ->orderBy('created_at', 'asc')
            ->get();

        $items = $transactions->map(function ($transaction) {
            return [
                'id' => $transaction->id,
                'transaction_number' => $transaction->transaction_number,
                'item_image_path' => $transaction->item_image_path ? route('images.show', ['path' => $transaction->item_image_path]) : null,
                'item_type' => $transaction->itemType->name,
                'item_subtype' => $transaction->itemTypeSubtype ? $transaction->itemTypeSubtype->name : null,
                'custom_item_type' => $transaction->custom_item_type,
                'item_description' => $transaction->item_description,
                'tags' => $transaction->tags->map(function ($tag) {
                    return $tag->name;
                })->toArray(),
            ];
        });

        return response()->json([
            'items' => $items,
        ]);
    }

    /**
     * Void all Sangla transactions for a given pawn ticket number.
     */
    public function voidPawnTicket(VoidTransactionRequest $request, string $pawnTicketNumber): RedirectResponse
    {
        // Find all Sangla transactions with this pawn ticket number
        $transactions = Transaction::where('pawn_ticket_number', $pawnTicketNumber)
            ->where('type', 'sangla')
            ->whereDoesntHave('voided')
            ->get();

        if ($transactions->isEmpty()) {
            return redirect()->back()
                ->with('error', 'No active Sangla transactions found for this pawn ticket number.');
        }

        // Check if any transaction is older than 6 hours
        $oldestTransaction = $transactions->sortBy('created_at')->first();
        $hoursSinceCreation = $oldestTransaction->created_at->diffInHours(now());
        if ($hoursSinceCreation > 6) {
            return redirect()->back()
                ->with('error', 'Cannot void this pawn ticket. Transactions can only be voided within 6 hours of creation.');
        }

        // Check if there are any non-voided child transactions (renewals)
        $hasChildTransactions = Transaction::where('pawn_ticket_number', $pawnTicketNumber)
            ->where('type', 'renew')
            ->whereDoesntHave('voided')
            ->exists();

        if ($hasChildTransactions) {
            return redirect()->back()
                ->with('error', 'Cannot void this pawn ticket. There are active renewal transactions associated with this pawn ticket number. Please void the renewal transactions first.');
        }

        // Void all transactions within a database transaction
        DB::transaction(function () use ($transactions, $request, $pawnTicketNumber) {
            foreach ($transactions as $transaction) {
                // Check if already voided (shouldn't happen due to query, but safety check)
                if ($transaction->isVoided()) {
                    continue;
                }

                // Create void record
                VoidedTransaction::create([
                    'transaction_id' => $transaction->id,
                    'voided_by' => $request->user()->id,
                    'reason' => "Pawn ticket #{$pawnTicketNumber} voided: {$request->reason}",
                    'voided_at' => now(),
                ]);

                // Find and void the associated financial transaction
                $financialTransaction = BranchFinancialTransaction::where('transaction_id', $transaction->id)
                    ->where('type', 'transaction')
                    ->whereDoesntHave('voided')
                    ->first();

                // If not found by transaction_id, try to find by matching description
                if (!$financialTransaction) {
                    $financialTransaction = BranchFinancialTransaction::where('type', 'transaction')
                        ->where(function($q) {
                            $q->where('description', 'Sangla transaction')
                              ->orWhere('description', 'Sangla transaction (additional item)');
                        })
                        ->where('branch_id', $transaction->branch_id)
                        ->where('amount', $transaction->net_proceeds)
                        ->whereDate('transaction_date', $transaction->created_at->toDateString())
                        ->whereDoesntHave('voided')
                        ->whereNull('transaction_id')
                        ->first();
                }

                if ($financialTransaction) {
                    // Update transaction_id if it was NULL
                    if (!$financialTransaction->transaction_id) {
                        $financialTransaction->update(['transaction_id' => $transaction->id]);
                    }

                    // Create void record for financial transaction
                    VoidedBranchFinancialTransaction::create([
                        'branch_financial_transaction_id' => $financialTransaction->id,
                        'voided_by' => $request->user()->id,
                        'reason' => "Associated transaction #{$transaction->transaction_number} was voided: {$request->reason}",
                        'voided_at' => now(),
                    ]);

                    // Reverse the transaction amount in the balance (Sangla: amount was negative, so we add it back)
                    BranchBalance::updateBalance($financialTransaction->branch_id, (float) $financialTransaction->amount);
                }
            }
        });

        $transactionCount = $transactions->count();
        return redirect()->back()
            ->with('success', "Pawn ticket #{$pawnTicketNumber} has been voided. {$transactionCount} transaction(s) voided.");
    }
}
