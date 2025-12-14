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
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

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

        $transactions = $query->orderBy('created_at', 'desc')->paginate(20);

        // Get branches for filter (admin/superadmin only)
        $branches = null;
        if ($user->isAdminOrSuperAdmin()) {
            $branches = Branch::orderBy('name', 'asc')->get();
        }

        return view('transactions.index', [
            'transactions' => $transactions,
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
                $financialTransaction = BranchFinancialTransaction::where('type', 'transaction')
                    ->where('description', 'Sangla transaction')
                    ->where('branch_id', $transaction->branch_id)
                    ->where('amount', $transaction->net_proceeds)
                    ->whereDate('transaction_date', $transaction->created_at->toDateString())
                    ->whereDoesntHave('voided')
                    ->whereNull('transaction_id') // Only match records without transaction_id
                    ->first();
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
                // Since it's type 'transaction', it was negative, so we reverse by adding it back
                BranchBalance::updateBalance($financialTransaction->branch_id, (float) $financialTransaction->amount);
            }
        });

        return redirect()->back()
            ->with('success', "Transaction #{$transaction->transaction_number} has been voided.");
    }
}
