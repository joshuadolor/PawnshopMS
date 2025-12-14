<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreBranchFinancialTransactionRequest;
use App\Models\Branch;
use App\Models\BranchFinancialTransaction;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class BranchFinancialTransactionController extends Controller
{
    /**
     * Display a listing of financial transactions.
     */
    public function index(Request $request): View
    {
        $user = $request->user();
        $query = BranchFinancialTransaction::with(['branch', 'user']);

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

        // Calculate balances for each branch (Admin/Superadmin only)
        $branchBalances = [];
        if ($user->isAdminOrSuperAdmin()) {
            $branches = Branch::with('financialTransactions')->get();
            
            foreach ($branches as $branch) {
                $totalReplenish = $branch->financialTransactions()
                    ->where('type', 'replenish')
                    ->sum('amount');
                $totalExpense = $branch->financialTransactions()
                    ->where('type', 'expense')
                    ->sum('amount');
                $balance = $totalReplenish - $totalExpense;
                
                $branchBalances[$branch->id] = [
                    'branch' => $branch,
                    'balance' => $balance,
                    'total_replenish' => $totalReplenish,
                    'total_expense' => $totalExpense,
                ];
            }
        }

        // Get branches for filter (admin/superadmin only)
        $allBranches = null;
        if ($user->isAdminOrSuperAdmin()) {
            $allBranches = Branch::orderBy('name', 'asc')->get();
        }

        // Calculate summary stats
        $totalReplenish = BranchFinancialTransaction::where('type', 'replenish')
            ->when($user->isStaff(), function ($q) use ($user) {
                $q->where('user_id', $user->id);
            })
            ->when($request->filled('branch_id'), function ($q) use ($request) {
                $q->where('branch_id', $request->branch_id);
            })
            ->sum('amount');

        $totalExpense = BranchFinancialTransaction::where('type', 'expense')
            ->when($user->isStaff(), function ($q) use ($user) {
                $q->where('user_id', $user->id);
            })
            ->when($request->filled('branch_id'), function ($q) use ($request) {
                $q->where('branch_id', $request->branch_id);
            })
            ->sum('amount');

        $netBalance = $totalReplenish - $totalExpense;

        return view('branch-financial-transactions.index', [
            'transactions' => $transactions,
            'branches' => $allBranches,
            'branchBalances' => $branchBalances,
            'summary' => [
                'total_replenish' => $totalReplenish,
                'total_expense' => $totalExpense,
                'net_balance' => $netBalance,
            ],
            'filters' => [
                'branch_id' => $request->branch_id ?? null,
                'type' => $request->type ?? null,
                'date_from' => $request->date_from ?? null,
                'date_to' => $request->date_to ?? null,
                'search' => $request->search ?? null,
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
        if ($user->isStaff()) {
            $branches = $user->branches()->orderBy('name', 'asc')->get();
        } else {
            $branches = Branch::orderBy('name', 'asc')->get();
        }

        return view('branch-financial-transactions.create', [
            'branches' => $branches,
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

        BranchFinancialTransaction::create([
            'branch_id' => $request->branch_id,
            'user_id' => $user->id,
            'type' => $request->type,
            'description' => $request->description,
            'amount' => $request->amount,
            'transaction_date' => $request->transaction_date,
        ]);

        return redirect()->route('branch-financial-transactions.index')
            ->with('success', 'Financial transaction created successfully.');
    }
}
