<?php

namespace App\Http\Controllers\Transactions\Sangla;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreSanglaTransactionRequest;
use App\Models\Branch;
use App\Models\Config;
use App\Models\ItemType;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class SanglaController extends Controller
{
    /**
     * Show the form for creating a new Sangla transaction.
     */
    public function create(): View
    {
        $itemTypes = ItemType::where('name', '!=', 'Other')
            ->orderByRaw("CASE WHEN name = 'Jewelry' THEN 0 ELSE 1 END")
            ->orderBy('name', 'asc')
            ->get();

        // Append "Other" as the last item if it exists
        $other = ItemType::where('name', 'Other')->first();
        if ($other) {
            $itemTypes->push($other);
        }

        // Get config values
        $serviceCharge = Config::getValue('sangla_service_charge', 0);
        $interestPeriod = Config::getValue('sangla_interest_period', 'per_month');

        // Calculate default maturity date based on interest period
        $today = now();
        if ($interestPeriod === 'per_annum') {
            $defaultMaturityDate = $today->copy()->addYear()->format('Y-m-d');
        } else {
            // per_month or others - default to 1 month
            $defaultMaturityDate = $today->copy()->addMonth()->format('Y-m-d');
        }

        // Get user's branches
        $user = auth()->user();
        
        // Admins and superadmins can access all branches
        if ($user->isAdminOrSuperAdmin()) {
            $userBranches = Branch::orderBy('name', 'asc')->get();
            $showBranchSelection = $userBranches->count() > 1;
        } else {
            // Staff users only see their assigned branches
            $userBranches = $user->branches()->orderBy('name', 'asc')->get();
            $showBranchSelection = $userBranches->count() > 1;
        }

        return view('transactions.sangla.create', [
            'itemTypes' => $itemTypes,
            'serviceCharge' => $serviceCharge,
            'interestPeriod' => $interestPeriod,
            'defaultMaturityDate' => $defaultMaturityDate,
            'userBranches' => $userBranches,
            'showBranchSelection' => $showBranchSelection,
        ]);
    }

    /**
     * Store a newly created Sangla transaction.
     */
    public function store(StoreSanglaTransactionRequest $request): RedirectResponse
    {
        // TODO: Store the transaction in the database
        // For now, just redirect back with success message
        
        return redirect()->route('dashboard')
            ->with('success', 'Sangla transaction created successfully.');
    }
}

