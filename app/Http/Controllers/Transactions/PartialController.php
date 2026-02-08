<?php

namespace App\Http\Controllers\Transactions;

use App\Http\Controllers\Controller;
use App\Models\Transaction;
use App\Models\Config;
use App\Models\AdditionalChargeConfig;
use App\Models\BranchFinancialTransaction;
use App\Models\BranchBalance;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Carbon\Carbon;

class PartialController extends Controller
{
    /**
     * Show the partial search page.
     */
    public function search(): View
    {
        return view('transactions.partial.search');
    }

    /**
     * Process the search and show partial form.
     */
    public function find(Request $request): View|RedirectResponse
    {
        // Allow pawn_ticket_number from POST, GET, or session (for redirects after validation errors)
        $pawnTicketNumber = $request->input('pawn_ticket_number') 
            ?? $request->query('pawn_ticket_number') 
            ?? session('pawn_ticket_number');
        
        if (!$pawnTicketNumber) {
            $request->validate([
                'pawn_ticket_number' => ['required', 'string', 'max:100'],
            ]);
            $pawnTicketNumber = $request->input('pawn_ticket_number');
        }

        // Find all Sangla transactions with this pawn ticket number (including additional items and redeemed ones)
        $allTransactions = Transaction::where('pawn_ticket_number', $pawnTicketNumber)
            ->where('type', 'sangla')
            ->whereDoesntHave('voided')
            ->with(['branch', 'itemType', 'itemTypeSubtype', 'tags'])
            ->orderBy('created_at', 'asc')
            ->get();
        
        // Get tubos transaction for this pawn ticket to identify items redeemed via tubos
        $tubosTransaction = Transaction::where('pawn_ticket_number', $pawnTicketNumber)
            ->where('type', 'tubos')
            ->whereDoesntHave('voided')
            ->first();
        
        // Get all partial transactions for this pawn ticket to find redemption info
        $partialTransactionsForRedemption = Transaction::where('pawn_ticket_number', $pawnTicketNumber)
            ->where('type', 'partial')
            ->whereDoesntHave('voided')
            ->orderBy('created_at', 'asc')
            ->get();

        if ($allTransactions->isEmpty()) {
            return redirect()->route('transactions.partial.search')
                ->with('error', 'No active transaction found with the provided pawn ticket number.');
        }

        // Check if this pawn ticket has already been redeemed (has non-voided tubos transaction)
        $hasActiveTubos = Transaction::where('pawn_ticket_number', $pawnTicketNumber)
            ->where('type', 'tubos')
            ->whereDoesntHave('voided')
            ->exists();

        if ($hasActiveTubos) {
            return redirect()->route('transactions.partial.search')
                ->with('error', 'This pawn ticket has already been redeemed (tubos). Partial payment is not allowed for redeemed transactions.');
        }

        // Use the oldest Sangla transaction for calculations (one pawn ticket = one computation)
        // The oldest transaction has the actual loan amount (additional items have loan_amount = 0)
        $oldestTransaction = $allTransactions->first();
        $branchId = $oldestTransaction->branch_id;

        // Get the latest transaction (Sangla OR Renewal OR Partial) for date calculations (most current dates)
        // This ensures we use the dates from the most recent renewal/partial if one exists
        $latestTransactionForDates = Transaction::where('pawn_ticket_number', $pawnTicketNumber)
            ->whereIn('type', ['sangla', 'renew', 'partial'])
            ->whereDoesntHave('voided')
            ->orderBy('created_at', 'desc')
            ->first();

        // If no renewal/partial exists, use the latest Sangla transaction
        if (!$latestTransactionForDates) {
            $latestTransactionForDates = $allTransactions->last();
        }

        // Get the original principal amount from the oldest Sangla transaction
        $originalPrincipalAmount = (float) $oldestTransaction->loan_amount;

        // Get all partial transactions for this pawn ticket (for history display)
        $partialTransactions = Transaction::where('pawn_ticket_number', $pawnTicketNumber)
            ->where('type', 'partial')
            ->whereDoesntHave('voided')
            ->orderBy('created_at', 'asc')
            ->get();

        // Get the latest partial transaction to check if principal has been reduced
        $latestPartialTransaction = $partialTransactions->last();

        // Use the reduced principal from partial transaction if it exists, otherwise use the original
        $currentPrincipalAmount = $latestPartialTransaction 
            ? (float) $latestPartialTransaction->loan_amount 
            : $originalPrincipalAmount;

        // Calculate renewal amount (interest + service charge + additional charges)
        //
        // For partial display:
        // - When parent Sangla is no_advance=true and advance_paid=false, the "advance interest" is still unpaid.
        //   We compute that interest based on the ORIGINAL principal (same basis as Sangla).
        // - Otherwise, we also compute interest based on the ORIGINAL principal (reference/guide).
        $interestPrincipalBasis = $originalPrincipalAmount;
        $totalInterest = $interestPrincipalBasis * ((float) $oldestTransaction->interest_rate / 100);

        // Allocation rule for partials when no_advance=true and advance_paid=false:
        // Payment goes to INTEREST first, then any remainder reduces principal.
        $shouldAllocatePaymentToAdvanceInterestFirst = (bool) $oldestTransaction->no_advance && !(bool) $oldestTransaction->advance_paid;
        $advanceInterestDue = $shouldAllocatePaymentToAdvanceInterestFirst ? $totalInterest : 0.0;

        // Get service charge from config (one service charge per pawn ticket)
        $serviceCharge = Config::getValue('sangla_service_charge', 0);
        $totalServiceCharge = $serviceCharge; // Only one service charge

        // Calculate additional charges
        // Use the latest transaction's dates (most current state - could be from a renewal/partial)
        $actualToday = Carbon::today();
        $expiryRedemptionDate = $latestTransactionForDates->expiry_date ? Carbon::parse($latestTransactionForDates->expiry_date) : null;
        $maturityDate = $latestTransactionForDates->maturity_date ? Carbon::parse($latestTransactionForDates->maturity_date) : null;
        
        // Check if transaction exceeds maturity date (is overdue)
        $isOverdue = false;
        if ($maturityDate) {
            $maturityDateCarbon = $maturityDate instanceof Carbon ? $maturityDate : Carbon::parse($maturityDate);
            $isOverdue = $actualToday->gt($maturityDateCarbon);
        }
        
        // Check if back_date is requested (from input or old input)
        $backDate = $request->has('back_date') ? (bool) $request->input('back_date') : (bool) old('back_date', false);
        
        // If back_date is checked, use maturity date as reference date (as if today is the maturity date)
        $today = $backDate && $maturityDate ? Carbon::parse($maturityDate) : $actualToday;
        
        $daysExceeded = 0;
        $additionalChargeType = null;
        $additionalChargeAmount = 0;
        $additionalChargeConfig = null;

        // If back_date is NOT checked, calculate additional charges
        if (!$backDate) {
            // First, check if expiry redemption date is exceeded
            if ($expiryRedemptionDate && $today->gt($expiryRedemptionDate)) {
                // Expiry redemption date is exceeded - use EC (Exceeded Charge)
                // Count days exceeded from expiry redemption date to today
                $daysExceeded = $expiryRedemptionDate->diffInDays($today);
                $additionalChargeType = 'EC';
            } elseif ($maturityDate && $today->gt($maturityDate)) {
                // Expiry redemption date is NOT exceeded, but maturity date is exceeded - use LD (Late Days)
                // Count days exceeded from maturity date to today
                $daysExceeded = $maturityDate->diffInDays($today);
                $additionalChargeType = 'LD';
            }

            // Get the percentage from additionalChargeConfig table based on days exceeded and type
            if ($daysExceeded > 0 && $additionalChargeType) {
                $additionalChargeConfig = AdditionalChargeConfig::findApplicable($daysExceeded, $additionalChargeType, 'renewal');
                if ($additionalChargeConfig) {
                    // Calculate charge amount: current principal * percentage from config
                    $additionalChargeAmount = $currentPrincipalAmount * ((float) $additionalChargeConfig->percentage / 100);
                }
            }
        }

        // Calculate late days charge using ComputationController
        // If back_date is checked, skip late days charge (set to 0)
        $computationController = new \App\Http\Controllers\Transactions\ComputationController();
        if ($backDate) {
            $lateDaysCharge = 0;
            $lateDaysChargeBreakdown = [
                'loan_amount' => $currentPrincipalAmount,
                'interest_rate' => (float) $oldestTransaction->interest_rate,
                'interest' => round($currentPrincipalAmount * ((float) $oldestTransaction->interest_rate / 100), 2),
                'maturity_date' => $maturityDate ? $maturityDate->format('Y-m-d') : null,
                'reference_date' => $today->format('Y-m-d'),
                'late_days' => 0,
                'is_late' => false,
                'late_days_charge' => 0,
                'formula' => '(interest / 30) * late_days',
                'calculation' => 'No late days charge (back dated)',
            ];
        } else {
            $lateDaysCharge = $computationController->computeLateDaysCharge(
                $oldestTransaction, 
                $today, 
                $currentPrincipalAmount, 
                (float) $oldestTransaction->interest_rate,
                $maturityDate
            );
            $lateDaysChargeBreakdown = $computationController->getLateDaysChargeBreakdown(
                $oldestTransaction, 
                $today, 
                $currentPrincipalAmount,
                (float) $oldestTransaction->interest_rate,
                $maturityDate
            );
        }

        // Calculate minimum renewal amount (for display and validation only)
        // The computation is still: new principal = current principal - partial amount paid
        $minimumRenewalAmount = $totalInterest + $totalServiceCharge + $additionalChargeAmount + $lateDaysCharge;

        // Combine all item descriptions for the partial transaction
        $combinedDescriptions = $allTransactions->pluck('item_description')->filter()->unique()->values()->implode('; ');

        // Get config values for date calculations
        $daysBeforeRedemption = (int) Config::getValue('sangla_days_before_redemption', 90);
        $daysBeforeAuctionSale = (int) Config::getValue('sangla_days_before_auction_sale', 85);
        $interestPeriod = Config::getValue('sangla_interest_period', 'per_month');

        // Calculate default new maturity date based on interest period
        // If back_date is checked, start from maturity date instead of today
        $defaultMaturityDate = match ($interestPeriod) {
            'per_annum' => $today->copy()->addYear()->format('Y-m-d'),
            'per_month' => $today->copy()->addMonth()->format('Y-m-d'),
            default => $today->copy()->addMonth()->format('Y-m-d'),
        };
        
        // Calculate default expiry date: maturity date + days before redemption
        $defaultExpiryDate = Carbon::parse($defaultMaturityDate)->addDays($daysBeforeRedemption)->format('Y-m-d');
        
        // Calculate default auction sale date: expiry date + days before auction sale
        $defaultAuctionSaleDate = Carbon::parse($defaultExpiryDate)->addDays($daysBeforeAuctionSale)->format('Y-m-d');

        return view('transactions.partial.partial', [
            'transaction' => $oldestTransaction, // Show oldest transaction (for reference)
            'latestTransaction' => $latestTransactionForDates, // Show latest transaction (for current dates)
            'allTransactions' => $allTransactions, // All items including redeemed ones
            'tubosTransaction' => $tubosTransaction, // Tubos transaction if exists
            'partialTransactionsForRedemption' => $partialTransactionsForRedemption, // Partial transactions for redemption info
            'pawnTicketNumber' => $pawnTicketNumber,
            'totalInterest' => $totalInterest,
            'interestPrincipalBasis' => $interestPrincipalBasis,
            'shouldAllocatePaymentToAdvanceInterestFirst' => $shouldAllocatePaymentToAdvanceInterestFirst,
            'advanceInterestDue' => $advanceInterestDue,
            'serviceCharge' => $serviceCharge,
            'totalServiceCharge' => $totalServiceCharge,
            'additionalChargeType' => $additionalChargeType,
            'additionalChargeAmount' => $additionalChargeAmount,
            'daysExceeded' => $daysExceeded,
            'additionalChargeConfig' => $additionalChargeConfig,
            'lateDaysCharge' => $lateDaysCharge,
            'lateDaysChargeBreakdown' => $lateDaysChargeBreakdown,
            'backDate' => $backDate,
            'isOverdue' => $isOverdue,
            'maturityDate' => $maturityDate ? $maturityDate->format('Y-m-d') : null,
            'currentPrincipalAmount' => $currentPrincipalAmount,
            'originalPrincipalAmount' => $originalPrincipalAmount, // Pass original principal to view
            'partialTransactions' => $partialTransactions, // Pass partial transactions for history
            'minimumRenewalAmount' => $minimumRenewalAmount, // Pass minimum renewal amount for validation
            'combinedDescriptions' => $combinedDescriptions,
            'branchId' => $branchId,
            'daysBeforeRedemption' => $daysBeforeRedemption,
            'daysBeforeAuctionSale' => $daysBeforeAuctionSale,
            'defaultMaturityDate' => $defaultMaturityDate,
            'defaultExpiryDate' => $defaultExpiryDate,
            'defaultAuctionSaleDate' => $defaultAuctionSaleDate,
        ]);
    }

    /**
     * Process the partial payment.
     */
    public function store(Request $request): RedirectResponse
    {
        try {
            $validated = $request->validate([
                'pawn_ticket_number' => ['required', 'string', 'max:100'],
                'maturity_date' => ['required', 'date'],
                'expiry_date' => ['required', 'date', 'after_or_equal:maturity_date'],
                'auction_sale_date' => ['nullable', 'date', 'after_or_equal:expiry_date'],
                'partial_amount' => ['required', 'numeric'], // Allow negative values
                'transaction_pawn_ticket' => ['required', 'string', 'max:100'],
                'back_date' => ['nullable', 'boolean'],
                'late_days_charge_amount' => ['nullable', 'numeric', 'min:0'],
                'signature_photo' => ['nullable', 'image', 'mimes:jpeg,png,jpg', 'max:5120'],
                'signature_canvas' => ['nullable', 'string'],
                'selected_items' => ['nullable', 'array'],
                'selected_items.*' => ['integer', 'exists:transactions,id'],
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            $pawnTicketNumber = $request->input('pawn_ticket_number', '');
            return redirect()->route('transactions.partial.find', ['pawn_ticket_number' => $pawnTicketNumber])
                ->withInput()
                ->withErrors($e->errors());
        }

        $pawnTicketNumber = $request->input('pawn_ticket_number');
        $selectedItemIds = $request->input('selected_items', []);
        
        // Debug: Log what we received
        Log::info('=== PARTIAL STORE START ===', [
            'pawn_ticket_number' => $pawnTicketNumber,
            'selected_items_raw' => $request->input('selected_items'),
            'selected_items_type' => gettype($request->input('selected_items')),
            'all_request_keys' => array_keys($request->except(['_token', 'signature_photo', 'signature_canvas'])),
        ]);
        
        // Ensure selectedItemIds is an array and contains only integers
        if (!is_array($selectedItemIds)) {
            Log::warning('Partial Store - selected_items is not an array', ['type' => gettype($selectedItemIds), 'value' => $selectedItemIds]);
            $selectedItemIds = [];
        }
        $selectedItemIds = array_filter(array_map('intval', $selectedItemIds));
        
        Log::info('Partial Store - Processed Selected Items', [
            'selected_item_ids' => $selectedItemIds,
            'count' => count($selectedItemIds),
            'is_empty' => empty($selectedItemIds),
        ]);

        // Find all transactions with this pawn ticket number (including redeemed ones for display)
        // But we'll filter out redeemed items when processing
        $allTransactions = Transaction::where('pawn_ticket_number', $pawnTicketNumber)
            ->where('type', 'sangla')
            ->whereDoesntHave('voided')
            ->orderBy('created_at', 'asc')
            ->get();
        
        // Filter out redeemed items for processing
        $activeTransactions = $allTransactions->where('status', '!=', 'redeemed');

        if ($allTransactions->isEmpty()) {
            return redirect()->route('transactions.partial.search')
                ->with('error', 'No active transaction found with the provided pawn ticket number.');
        }

        // Check if this pawn ticket has already been redeemed (has non-voided tubos transaction)
        // But allow if we're processing selected items (partial tubos)
        $hasActiveTubos = Transaction::where('pawn_ticket_number', $pawnTicketNumber)
            ->where('type', 'tubos')
            ->whereDoesntHave('voided')
            ->exists();

        if ($hasActiveTubos && empty($selectedItemIds)) {
            return redirect()->route('transactions.partial.search')
                ->with('error', 'This pawn ticket has already been redeemed (tubos). Partial payment is not allowed for redeemed transactions.');
        }

        // Separate selected items from remaining items (only from active transactions)
        $selectedTransactions = $activeTransactions->whereIn('id', $selectedItemIds);
        $remainingTransactions = $activeTransactions->whereNotIn('id', $selectedItemIds);
        
        Log::info('Partial Store - Transaction Separation', [
            'all_transactions_count' => $allTransactions->count(),
            'all_transaction_ids' => $allTransactions->pluck('id')->toArray(),
            'selected_item_ids' => $selectedItemIds,
            'selected_transactions_count' => $selectedTransactions->count(),
            'selected_transaction_ids' => $selectedTransactions->pluck('id')->toArray(),
            'remaining_transactions_count' => $remainingTransactions->count(),
            'remaining_transaction_ids' => $remainingTransactions->pluck('id')->toArray(),
        ]);

        // If items are selected, mark them as redeemed (but don't create tubos transaction)
        $selectedItemsMessage = '';
        if (!empty($selectedItemIds) && $selectedTransactions->isNotEmpty()) {
            Log::info('Partial Store - Processing Selected Items', [
                'selected_item_ids' => $selectedItemIds,
                'selected_transactions_count' => $selectedTransactions->count(),
            ]);
            // Calculate principal allocation for selected items to adjust remaining principal
            // Use appraised value ratio if available, otherwise equal split
            $totalAppraisedValue = $allTransactions->sum('appraised_value');
            $selectedAppraisedValue = $selectedTransactions->sum('appraised_value');
            
            // Get current principal
            $latestPartialTransaction = Transaction::where('pawn_ticket_number', $pawnTicketNumber)
                ->where('type', 'partial')
                ->whereDoesntHave('voided')
                ->orderBy('created_at', 'desc')
                ->first();
            
            $currentPrincipalAmount = $latestPartialTransaction 
                ? (float) $latestPartialTransaction->loan_amount 
                : (float) $allTransactions->first()->loan_amount;
            
            // Calculate principal for selected items (to subtract from remaining)
            $selectedPrincipalAmount = 0;
            if ($totalAppraisedValue > 0 && $selectedAppraisedValue > 0) {
                // Allocate based on appraised value ratio
                $selectedPrincipalAmount = $currentPrincipalAmount * ($selectedAppraisedValue / $totalAppraisedValue);
            } else {
                // Equal split
                $selectedPrincipalAmount = $currentPrincipalAmount * ($selectedTransactions->count() / $allTransactions->count());
            }
            
            // Mark selected transactions as redeemed (no tubos transaction created)
            // Store the count for later use in the transaction
            $selectedItemsToRedeem = $selectedItemIds;
            $selectedItemsMessage = "Selected " . count($selectedItemsToRedeem) . " item(s) will be marked as redeemed. ";
            
            Log::info('Partial Store - Prepared for Redemption', [
                'selected_items_to_redeem' => $selectedItemsToRedeem,
                'count' => count($selectedItemsToRedeem),
            ]);
            
            // Update current principal to exclude selected items
            $currentPrincipalAmount = $currentPrincipalAmount - $selectedPrincipalAmount;
            
            // If all items are selected, we're done (just mark them as redeemed)
            if ($remainingTransactions->isEmpty()) {
                return redirect()->route('transactions.index')
                    ->with('success', $selectedItemsMessage . "All items have been marked as redeemed.");
            }
            
            // Update activeTransactions to only include remaining items for partial processing
            $activeTransactions = $remainingTransactions;
        }

        // Use the oldest transaction for basic info (has actual loan amount)
        $oldestTransaction = $activeTransactions->first();
        $branchId = $oldestTransaction->branch_id;
        $partialAmount = (float) $request->input('partial_amount');
        $backDate = $request->has('back_date') ? (bool) $request->input('back_date') : false;
        $lateDaysCharge = (float) ($request->input('late_days_charge_amount') ?? 0);

        // Get the latest partial transaction to check if principal has been reduced
        $latestPartialTransaction = Transaction::where('pawn_ticket_number', $pawnTicketNumber)
            ->where('type', 'partial')
            ->whereDoesntHave('voided')
            ->orderBy('created_at', 'desc')
            ->first();

        // Use the reduced principal from partial transaction if it exists, otherwise use the original
        $currentPrincipalAmount = $latestPartialTransaction 
            ? (float) $latestPartialTransaction->loan_amount 
            : (float) $oldestTransaction->loan_amount;

        // Calculate renewal amount to validate minimum payment (reference/guide only)
        $parentSangla = $allTransactions->first(); // Oldest Sangla (true parent)
        $originalPrincipalAmount = $parentSangla ? (float) $parentSangla->loan_amount : $currentPrincipalAmount;

        // Interest basis for the reference amount is the ORIGINAL principal
        $interestPrincipalBasis = $originalPrincipalAmount;
        $totalInterest = $interestPrincipalBasis * ((float) $oldestTransaction->interest_rate / 100);
        $serviceCharge = Config::getValue('sangla_service_charge', 0);
        
        // Get latest transaction for date calculations
        $latestTransactionForDates = Transaction::where('pawn_ticket_number', $pawnTicketNumber)
            ->whereIn('type', ['sangla', 'renew', 'partial'])
            ->whereDoesntHave('voided')
            ->orderBy('created_at', 'desc')
            ->first();
        
        if (!$latestTransactionForDates) {
            $latestTransactionForDates = $allTransactions->last();
        }

        // Calculate additional charges
        $actualToday = Carbon::today();
        $expiryRedemptionDate = $latestTransactionForDates->expiry_date ? Carbon::parse($latestTransactionForDates->expiry_date) : null;
        $maturityDate = $latestTransactionForDates->maturity_date ? Carbon::parse($latestTransactionForDates->maturity_date) : null;
        
        // If back_date is checked, use maturity date as reference date
        $today = $backDate && $maturityDate ? Carbon::parse($maturityDate) : $actualToday;
        
        $additionalChargeAmount = 0;

        // If back_date is NOT checked, calculate additional charges
        if (!$backDate) {
            if ($expiryRedemptionDate && $today->gt($expiryRedemptionDate)) {
                $daysExceeded = $expiryRedemptionDate->diffInDays($today);
                $additionalChargeConfig = AdditionalChargeConfig::findApplicable($daysExceeded, 'EC', 'renewal');
                if ($additionalChargeConfig) {
                    $additionalChargeAmount = $currentPrincipalAmount * ((float) $additionalChargeConfig->percentage / 100);
                }
            } elseif ($maturityDate && $today->gt($maturityDate)) {
                $daysExceeded = $maturityDate->diffInDays($today);
                $additionalChargeConfig = AdditionalChargeConfig::findApplicable($daysExceeded, 'LD', 'renewal');
                if ($additionalChargeConfig) {
                    $additionalChargeAmount = $currentPrincipalAmount * ((float) $additionalChargeConfig->percentage / 100);
                }
            }
        }

        $minimumRenewalAmount = $totalInterest + $serviceCharge + $additionalChargeAmount + $lateDaysCharge;

        // Minimum renewal amount is just a guide, not a hard validation
        // Allow any value (positive or negative) - negative values increase principal

        // Calculate new principal amount
        // If partial amount is negative, it increases the principal (pawner adds more money)
        // If partial amount is positive, it decreases the principal (pawner pays)
        //
        // Special rule:
        // If parent Sangla is no_advance=true and advance_paid=false, payment is allocated to INTEREST first,
        // then any remainder reduces principal.
        $advanceInterestDue = 0.0;
        $advanceInterestPaid = 0.0;
        $principalPaid = 0.0;
        $markAdvancePaid = false;
        $totalPaymentReceived = abs($partialAmount);
        $interestOnNewPrincipal = 0.0;
        $isNormalPartialMode = false;

        if ($partialAmount > 0) {
            if ($parentSangla && (bool) $parentSangla->no_advance && !(bool) $parentSangla->advance_paid) {
                $advanceInterestDue = $originalPrincipalAmount * ((float) $oldestTransaction->interest_rate / 100);
                $advanceInterestPaid = min($partialAmount, $advanceInterestDue);
                $principalPaid = max(0.0, $partialAmount - $advanceInterestPaid);
                $markAdvancePaid = $advanceInterestDue > 0 && $advanceInterestPaid >= $advanceInterestDue;
                // In no-advance unpaid mode, we keep "cash received" as the entered partial amount (existing behavior).
                $totalPaymentReceived = $partialAmount;
            } else {
                $principalPaid = $partialAmount;
                $isNormalPartialMode = true;
            }
        }

        $newPrincipalAmount = $currentPrincipalAmount - $principalPaid;
        if ($partialAmount < 0) {
            // Negative partial increases principal
            $newPrincipalAmount = $currentPrincipalAmount - $partialAmount;
        }
        if ($newPrincipalAmount < 0) {
            $newPrincipalAmount = 0;
        }

        // For normal partial mode (no_advance=false OR advance_paid=true):
        // To proceed, customer must pay:
        // - the partial amount (principal reduction)
        // - interest computed on the NEW principal
        // - service charge (+ other computed charges)
        if ($isNormalPartialMode && $partialAmount > 0) {
            $interestOnNewPrincipal = $newPrincipalAmount * ((float) $oldestTransaction->interest_rate / 100);
            $totalPaymentReceived = $partialAmount
                + $interestOnNewPrincipal
                + (float) $serviceCharge
                + (float) $additionalChargeAmount
                + (float) $lateDaysCharge;
        }

        // Combine all item descriptions for the partial transaction (from active transactions only)
        $combinedDescriptions = $activeTransactions->pluck('item_description')->filter()->unique()->values()->implode('; ');

        // Process signature for partial payment (base64 to image file)
        $signaturePath = null;
        
        if (true) {
            $branch = $oldestTransaction->branch;
            $branchName = $branch->name;
            
            try {
                // Handle signature photo
                if ($request->hasFile('signature_photo')) {
                    $imageService = app(\App\Services\ImageProcessingService::class);
                    $signaturePath = $imageService->processAndStore(
                        $request->file('signature_photo'),
                        'transactions/signatures',
                        $branchName
                    );
                } elseif ($request->input('signature_canvas')) {
                    // Handle canvas signature (base64)
                    $signatureData = $request->input('signature_canvas');
                    if (strpos($signatureData, 'data:image') === 0) {
                        list($type, $data) = explode(';', $signatureData);
                        list(, $data) = explode(',', $data);
                        $imageData = base64_decode($data);
                        
                        // Create directory structure: transactions/signatures/YYYY-MM-DD/branch-name/
                        $dateFolder = now()->format('Y-m-d');
                        $sanitizedBranchName = preg_replace('/[^a-zA-Z0-9_-]/', '_', $branchName);
                        $directory = "transactions/signatures/{$dateFolder}/{$sanitizedBranchName}";
                        
                        // Generate unique filename
                        $filename = 'signature_' . uniqid() . '_' . time() . '.png';
                        $filePath = "{$directory}/{$filename}";
                        
                        // Store the image
                        Storage::disk('local')->put($filePath, $imageData);
                        $signaturePath = $filePath;
                    }
                }
            } catch (\Exception $e) {
                // Continue without signature if processing fails (signature is optional)
            }
        }

        // Use database transaction to ensure data integrity
        // Include selected items redemption in the same transaction
        $selectedItemsToRedeem = isset($selectedItemIds) && !empty($selectedItemIds) ? $selectedItemIds : [];
        
        Log::info('Partial Store - Before DB Transaction', [
            'selected_items_to_redeem' => $selectedItemsToRedeem,
            'pawn_ticket_number' => $pawnTicketNumber,
            'partial_amount' => $partialAmount,
        ]);
        
        DB::transaction(function () use ($activeTransactions, $request, $branchId, $partialAmount, $lateDaysCharge, $backDate, $pawnTicketNumber, $oldestTransaction, $combinedDescriptions, $signaturePath, $newPrincipalAmount, $totalInterest, $serviceCharge, $additionalChargeAmount, $selectedItemsToRedeem, $markAdvancePaid, $totalPaymentReceived, $isNormalPartialMode, $interestOnNewPrincipal) {
            // First, mark selected items as redeemed (if any)
            if (!empty($selectedItemsToRedeem)) {
                Log::info('Partial Store - Inside DB Transaction - Updating Selected Items', [
                    'selected_items_to_redeem' => $selectedItemsToRedeem,
                    'pawn_ticket_number' => $pawnTicketNumber,
                ]);
                
                // Check current status before update
                $beforeUpdate = Transaction::whereIn('id', $selectedItemsToRedeem)
                    ->get(['id', 'status', 'pawn_ticket_number', 'type'])
                    ->toArray();
                
                Log::info('Partial Store - Before Update Status', [
                    'transactions_before' => $beforeUpdate,
                ]);
                
                $updatedCount = Transaction::whereIn('id', $selectedItemsToRedeem)
                    ->where('pawn_ticket_number', $pawnTicketNumber)
                    ->where('type', 'sangla')
                    ->where('status', '!=', 'redeemed') // Only update if not already redeemed
                    ->update(['status' => 'redeemed']);
                
                Log::info('Partial Store - Update Result', [
                    'updated_count' => $updatedCount,
                    'selected_items_to_redeem' => $selectedItemsToRedeem,
                ]);
                
                // Check status after update
                $afterUpdate = Transaction::whereIn('id', $selectedItemsToRedeem)
                    ->get(['id', 'status', 'pawn_ticket_number', 'type'])
                    ->toArray();
                
                Log::info('Partial Store - After Update Status', [
                    'transactions_after' => $afterUpdate,
                ]);
                
                if ($updatedCount === 0 && !empty($selectedItemsToRedeem)) {
                    Log::error('Partial Store - Update Failed', [
                        'selected_items_to_redeem' => $selectedItemsToRedeem,
                        'updated_count' => $updatedCount,
                    ]);
                    throw new \Exception('Failed to mark selected items as redeemed. The selected items may have already been redeemed or do not exist.');
                }
            }
            
            // Generate partial transaction number
            $partialTransactionNumber = $this->generatePartialTransactionNumber();

            // Create Transaction record for partial payment
            $partialTransaction = Transaction::create([
                'transaction_number' => $partialTransactionNumber,
                'branch_id' => $branchId,
                'user_id' => $request->user()->id,
                'type' => 'partial',
                'first_name' => $oldestTransaction->first_name,
                'last_name' => $oldestTransaction->last_name,
                'address' => $oldestTransaction->address,
                'appraised_value' => $oldestTransaction->appraised_value,
                'loan_amount' => $newPrincipalAmount, // New reduced principal amount
                'interest_rate' => $oldestTransaction->interest_rate,
                'interest_rate_period' => $oldestTransaction->interest_rate_period,
                'maturity_date' => $request->input('maturity_date'),
                'expiry_date' => $request->input('expiry_date'),
                'pawn_ticket_number' => $pawnTicketNumber,
                'pawn_ticket_image_path' => $oldestTransaction->pawn_ticket_image_path,
                'auction_sale_date' => $request->input('auction_sale_date'),
                'item_type_id' => $oldestTransaction->item_type_id,
                'item_type_subtype_id' => $oldestTransaction->item_type_subtype_id,
                'custom_item_type' => $oldestTransaction->custom_item_type,
                'item_description' => $combinedDescriptions, // Combined descriptions from all transactions
                'item_image_path' => $oldestTransaction->item_image_path,
                'pawner_id_image_path' => $oldestTransaction->pawner_id_image_path,
                'signature_path' => $signaturePath,
                'grams' => $oldestTransaction->grams,
                'orcr_serial' => $oldestTransaction->orcr_serial,
                'service_charge' => $serviceCharge,
                'late_days_charge' => $lateDaysCharge,
                'back_date' => $backDate,
                // For partials we store the total cash received for this transaction in net_proceeds.
                // - In normal mode: partial + interest(on new principal) + service charge (+ other charges)
                // - Otherwise: keep existing behavior (abs(partial))
                'net_proceeds' => $partialAmount > 0 ? $totalPaymentReceived : abs($partialAmount),
                'status' => 'active',
                'transaction_pawn_ticket' => $request->input('transaction_pawn_ticket'),
                'note' => $request->input('note'),
            ]);

            // Create financial transaction for the partial payment
            // Type: "transaction" (same family as Sangla), but this one is an ADD (money coming in)
            // If partial amount is negative, it's money going out (pawner adding to principal) - treat as expense
            // If partial amount is positive, it's money coming in (pawner paying)
            if ($partialAmount >= 0) {
                // Positive: Payment (money coming in)
                BranchFinancialTransaction::create([
                    'branch_id' => $branchId,
                    'user_id' => $request->user()->id,
                    'transaction_id' => $partialTransaction->id,
                    'type' => 'transaction',
                    'description' => "Partial payment - Pawn Ticket #{$pawnTicketNumber}",
                    'amount' => $isNormalPartialMode ? $totalPaymentReceived : $partialAmount, // Positive amount (cash received)
                    'transaction_date' => now()->toDateString(),
                ]);
            } else {
                // Negative: Principal increase (money going out) - treat as expense
                BranchFinancialTransaction::create([
                    'branch_id' => $branchId,
                    'user_id' => $request->user()->id,
                    'transaction_id' => $partialTransaction->id,
                    'type' => 'expense',
                    'description' => "Principal increase - Pawn Ticket #{$pawnTicketNumber}",
                    'amount' => abs($partialAmount), // Store as positive for expense
                    'transaction_date' => now()->toDateString(),
                ]);
            }

            // Update branch balance (add the partial amount - negative values will decrease balance)
            BranchBalance::updateBalance($branchId, $partialAmount > 0 ? $totalPaymentReceived : $partialAmount);

            // Mark advance_paid only when the advance interest has been fully covered by partial payment.
            if ($markAdvancePaid) {
                Transaction::where('pawn_ticket_number', $pawnTicketNumber)
                    ->where('type', 'sangla')
                    ->whereDoesntHave('voided')
                    ->where('no_advance', true)
                    ->update(['advance_paid' => true]);
            }
            
            Log::info('Partial Store - DB Transaction Completed Successfully', [
                'selected_items_to_redeem' => $selectedItemsToRedeem ?? [],
            ]);
        });
        
        Log::info('=== PARTIAL STORE END ===', [
            'pawn_ticket_number' => $pawnTicketNumber,
            'selected_items_redeemed' => $selectedItemsToRedeem ?? [],
        ]);

        if ($isNormalPartialMode && $partialAmount > 0) {
            $partialMessage = "Partial payment of ₱" . number_format($partialAmount, 2) . " for pawn ticket number '{$pawnTicketNumber}' has been recorded successfully. Principal reduced from ₱" . number_format($currentPrincipalAmount, 2) . " to ₱" . number_format($newPrincipalAmount, 2) . ". Amount paid includes Interest (₱" . number_format($interestOnNewPrincipal, 2) . " on new principal) and Service Charge (₱" . number_format((float) $serviceCharge, 2) . "). Total received: ₱" . number_format($totalPaymentReceived, 2) . ".";
        } elseif ($partialAmount > 0 && $advanceInterestDue > 0) {
            if ($principalPaid > 0) {
                $partialMessage = "Partial payment of ₱" . number_format($partialAmount, 2) . " for pawn ticket number '{$pawnTicketNumber}' has been recorded successfully. ₱" . number_format($advanceInterestPaid, 2) . " applied to advance interest, and ₱" . number_format($principalPaid, 2) . " applied to principal. Principal updated from ₱" . number_format($currentPrincipalAmount, 2) . " to ₱" . number_format($newPrincipalAmount, 2) . ".";
            } else {
                $partialMessage = "Partial payment of ₱" . number_format($partialAmount, 2) . " for pawn ticket number '{$pawnTicketNumber}' has been recorded successfully. ₱" . number_format($advanceInterestPaid, 2) . " applied to advance interest. Principal remains ₱" . number_format($currentPrincipalAmount, 2) . ".";
            }
        } else {
            $partialMessage = $partialAmount >= 0
                ? "Partial payment of ₱" . number_format($partialAmount, 2) . " for pawn ticket number '{$pawnTicketNumber}' has been recorded successfully. Principal reduced from ₱" . number_format($currentPrincipalAmount, 2) . " to ₱" . number_format($newPrincipalAmount, 2) . "."
                : "Principal increase of ₱" . number_format(abs($partialAmount), 2) . " for pawn ticket number '{$pawnTicketNumber}' has been recorded successfully. Principal increased from ₱" . number_format($currentPrincipalAmount, 2) . " to ₱" . number_format($newPrincipalAmount, 2) . ".";
        }
        
        // Combine messages if items were selected
        $successMessage = (isset($selectedItemsMessage) ? $selectedItemsMessage : '') . $partialMessage;
        
        return redirect()->route('transactions.index')
            ->with('success', $successMessage);
    }

    /**
     * Generate a unique transaction number for partial transactions.
     */
    private function generatePartialTransactionNumber(): string
    {
        $prefix = 'PRT';
        $date = now()->format('Ymd');
        
        // Get the last partial transaction number for today
        $lastTransaction = Transaction::where('transaction_number', 'like', "{$prefix}-{$date}-%")
            ->orderBy('transaction_number', 'desc')
            ->first();
        
        if ($lastTransaction) {
            // Extract the sequence number and increment
            $parts = explode('-', $lastTransaction->transaction_number);
            $sequence = (int) end($parts);
            $sequence++;
        } else {
            // First partial transaction of the day
            $sequence = 1;
        }
        
        // Format sequence as 6-digit number
        $sequenceFormatted = str_pad($sequence, 6, '0', STR_PAD_LEFT);
        
        return "{$prefix}-{$date}-{$sequenceFormatted}";
    }
}

