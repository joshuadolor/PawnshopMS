<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreBranchFinancialTransactionRequest;
use App\Http\Requests\VoidBranchFinancialTransactionRequest;
use App\Models\Branch;
use App\Models\BranchBalance;
use App\Models\BranchFinancialTransaction;
use App\Models\VoidedBranchFinancialTransaction;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class BranchFinancialTransactionController extends Controller
{
    /**
     * Display a listing of financial transactions.
     */
    public function index(Request $request): View
    {
        $user = $request->user();
        $query = BranchFinancialTransaction::with(['branch', 'user', 'voided.voidedBy']);
        // Note: Voided transactions are shown in table but excluded from calculations

        // Default to today's transactions
        if (!$request->filled('date_from') && !$request->filled('date_to') && !$request->has('all_dates')) {
            $query->whereDate('transaction_date', today());
        }

        // Staff can only see their own transactions
        if ($user->isStaff()) {
            $query->where('user_id', $user->id);
            // Get user's branches
            $userBranches = $user->branches()->pluck('branches.id')->toArray();
            if (!empty($userBranches)) {
                $query->whereIn('branch_id', $userBranches);
            }
        } elseif ($request->filled('branch_id')) {
            // Admin/Superadmin can filter by branch
            $query->where('branch_id', $request->branch_id);
        }

        // Filter by type
        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }

        // Filter by date range
        if ($request->filled('date_from')) {
            $query->whereDate('transaction_date', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $query->whereDate('transaction_date', '<=', $request->date_to);
        }

        // Search
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where('description', 'like', "%{$search}%");
        }

        $transactions = $query->orderBy('transaction_date', 'desc')
            ->orderBy('created_at', 'desc')
            ->paginate(50);

        // Get balances for each branch (Admin/Superadmin only) - from stored balances
        $branchBalances = [];
        if ($user->isAdminOrSuperAdmin()) {
            $branches = Branch::with('balance')->get();
            
            foreach ($branches as $branch) {
                $balance = $branch->getCurrentBalance();
                
                $branchBalances[$branch->id] = [
                    'branch' => $branch,
                    'balance' => $balance,
                ];
            }
        }

        // Get branches for filter (admin/superadmin only)
        $allBranches = null;
        if ($user->isAdminOrSuperAdmin()) {
            $allBranches = Branch::orderBy('name', 'asc')->get();
        }

        // Build base query for summary stats
        // For staff: show all transactions for today from their assigned branches (not filtered by user)
        // For admin/superadmin: respect filters
        // Exclude voided transactions from all calculations
        $summaryQuery = BranchFinancialTransaction::query()
            ->whereDoesntHave('voided') // Exclude voided transactions
            ->when($user->isStaff(), function ($q) use ($user) {
                // Filter by staff's assigned branches
                $userBranches = $user->branches()->pluck('branches.id')->toArray();
                if (!empty($userBranches)) {
                    $q->whereIn('branch_id', $userBranches);
                }
            })
            ->when(!$user->isStaff() && $request->filled('branch_id'), function ($q) use ($request) {
                $q->where('branch_id', $request->branch_id);
            })
            ->when(!$request->filled('date_from') && !$request->filled('date_to') && !$request->has('all_dates'), function ($q) {
                // Default to today for all users
                $q->whereDate('transaction_date', today());
            })
            ->when($request->filled('date_from'), function ($q) use ($request) {
                $q->whereDate('transaction_date', '>=', $request->date_from);
            })
            ->when($request->filled('date_to'), function ($q) use ($request) {
                $q->whereDate('transaction_date', '<=', $request->date_to);
            });

        $totalReplenish = (clone $summaryQuery)->where('type', 'replenish')->sum('amount');
        $totalExpense = (clone $summaryQuery)->where('type', 'expense')->sum('amount');
        $totalTransaction = (clone $summaryQuery)->where('type', 'transaction')->sum('amount');
        
        // Transactions are negative (Sangla) or positive (Renew), so subtract from balance
        $netBalance = $totalReplenish - $totalExpense - $totalTransaction;

        return view('branch-financial-transactions.index', [
            'transactions' => $transactions,
            'branches' => $allBranches,
            'branchBalances' => $branchBalances,
            'summary' => [
                'total_replenish' => $totalReplenish,
                'total_expense' => $totalExpense,
                'total_transaction' => $totalTransaction,
                'net_balance' => $netBalance,
            ],
            'filters' => [
                'branch_id' => $request->branch_id ?? null,
                'type' => $request->type ?? null,
                'date_from' => $request->date_from ?? null,
                'date_to' => $request->date_to ?? null,
                'search' => $request->search ?? null,
                'all_dates' => $request->has('all_dates'),
            ],
        ]);
    }

    /**
     * Show the form for creating a new transaction.
     */
    public function create(Request $request): View
    {
        $user = $request->user();
        
        // Staff can only create for their branches
        $defaultBranchId = null;
        if ($user->isStaff()) {
            $branches = $user->branches()->orderBy('name', 'asc')->get();
            // Get the first assigned branch as default
            $defaultBranchId = $branches->first()?->id;
        } else {
            $branches = Branch::orderBy('name', 'asc')->get();
        }

        return view('branch-financial-transactions.create', [
            'branches' => $branches,
            'defaultBranchId' => $defaultBranchId,
        ]);
    }

    /**
     * Store a newly created transaction.
     */
    public function store(StoreBranchFinancialTransactionRequest $request): RedirectResponse
    {
        $user = $request->user();

        // Staff can only create for their branches
        if ($user->isStaff()) {
            $userBranches = $user->branches()->pluck('branches.id')->toArray();
            if (!in_array($request->branch_id, $userBranches)) {
                return redirect()->back()
                    ->withInput()
                    ->with('error', 'You can only create transactions for your assigned branches.');
            }
        }

        DB::transaction(function () use ($request, $user) {
            $transaction = BranchFinancialTransaction::create([
                'branch_id' => $request->branch_id,
                'user_id' => $user->id,
                'type' => $request->type,
                'description' => $request->description,
                'amount' => $request->amount,
                'transaction_date' => $request->transaction_date,
            ]);

            // Update branch balance
            $amount = match($request->type) {
                'replenish' => (float) $request->amount, // Positive
                'expense', 'transaction' => -(float) $request->amount, // Negative
                default => 0,
            };
            
            BranchBalance::updateBalance($request->branch_id, $amount);
        });

        return redirect()->route('branch-financial-transactions.index')
            ->with('success', 'Financial transaction created successfully.');
    }

    /**
     * Void a financial transaction.
     */
    public function void(VoidBranchFinancialTransactionRequest $request, BranchFinancialTransaction $branchFinancialTransaction): RedirectResponse
    {
        // Only admins and superadmins can void
        if (!$request->user()->isAdminOrSuperAdmin()) {
            abort(403, 'Unauthorized access.');
        }

        // Check if transaction is already voided
        if ($branchFinancialTransaction->isVoided()) {
            return redirect()->back()
                ->with('error', 'This financial transaction is already voided.');
        }

        // Create void record and update balance within a transaction
        DB::transaction(function () use ($branchFinancialTransaction, $request) {
            VoidedBranchFinancialTransaction::create([
                'branch_financial_transaction_id' => $branchFinancialTransaction->id,
                'voided_by' => $request->user()->id,
                'reason' => $request->reason,
                'voided_at' => now(),
            ]);

            // Reverse the transaction amount in the balance
            $amount = match($branchFinancialTransaction->type) {
                'replenish' => -(float) $branchFinancialTransaction->amount, // Reverse positive
                'expense', 'transaction' => (float) $branchFinancialTransaction->amount, // Reverse negative
                default => 0,
            };
            
            BranchBalance::updateBalance($branchFinancialTransaction->branch_id, $amount);
        });

        return redirect()->back()
            ->with('success', 'Financial transaction has been voided.');
    }
}
