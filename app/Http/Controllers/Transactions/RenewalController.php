<?php

namespace App\Http\Controllers\Transactions;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Transactions\ComputationController;
use App\Models\Transaction;
use App\Models\Config;
use App\Models\AdditionalChargeConfig;
use App\Models\BranchFinancialTransaction;
use App\Models\BranchBalance;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;
use Carbon\Carbon;

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

        // Find all Sangla transactions with this pawn ticket number (including additional items)
        $allTransactions = Transaction::where('pawn_ticket_number', $pawnTicketNumber)
            ->where('type', 'sangla')
            ->whereDoesntHave('voided')
            ->with(['branch', 'itemType', 'itemTypeSubtype', 'tags'])
            ->orderBy('created_at', 'asc')
            ->get();

        if ($allTransactions->isEmpty()) {
            return redirect()->route('transactions.renewal.search')
                ->with('error', 'No active transaction found with the provided pawn ticket number.');
        }

        // Check if this pawn ticket has already been redeemed (has non-voided tubos transaction)
        $hasActiveTubos = Transaction::where('pawn_ticket_number', $pawnTicketNumber)
            ->where('type', 'tubos')
            ->whereDoesntHave('voided')
            ->exists();

        if ($hasActiveTubos) {
            return redirect()->route('transactions.renewal.search')
                ->with('error', 'This pawn ticket has already been redeemed (tubos). Renewal is not allowed for redeemed transactions.');
        }

        // Use the oldest Sangla transaction for basic info (one pawn ticket = one computation)
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

        // Get all partial transactions to show history of principal reductions
        $partialTransactions = Transaction::where('pawn_ticket_number', $pawnTicketNumber)
            ->where('type', 'partial')
            ->whereDoesntHave('voided')
            ->orderBy('created_at', 'asc')
            ->get();

        // Get the latest partial transaction to check if principal has been reduced
        $latestPartialTransaction = $partialTransactions->last();

        // Original principal amount
        $originalPrincipalAmount = (float) $oldestTransaction->loan_amount;

        // Use the reduced principal from partial transaction if it exists, otherwise use the original
        $currentPrincipalAmount = $latestPartialTransaction 
            ? (float) $latestPartialTransaction->loan_amount 
            : $originalPrincipalAmount;

        // Calculate interest from the current principal amount (after any partial payments)
        $totalInterest = $currentPrincipalAmount * ((float) $oldestTransaction->interest_rate / 100);

        // Get service charge from config (one service charge per pawn ticket)
        $serviceCharge = Config::getValue('sangla_service_charge', 0);
        $totalServiceCharge = $serviceCharge; // Only one service charge

        // Calculate additional charges
        // Use the latest transaction's dates (most current state - could be from a renewal)
        $actualToday = Carbon::today();
        $expiryRedemptionDate = $latestTransactionForDates->expiry_date;
        $maturityDate = $latestTransactionForDates->maturity_date;
        
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

        // Debug: Log the dates being compared
        Log::info('Renewal Additional Charge Calculation', [
            'pawn_ticket' => $pawnTicketNumber,
            'back_date' => $backDate,
            'actual_today' => $actualToday->format('Y-m-d'),
            'reference_date' => $today->format('Y-m-d'),
            'maturity_date' => $maturityDate ? $maturityDate->format('Y-m-d') : null,
            'expiry_date' => $expiryRedemptionDate ? $expiryRedemptionDate->format('Y-m-d') : null,
            'loan_amount' => $currentPrincipalAmount,
        ]);

        // If back_date is NOT checked, calculate additional charges
        if (!$backDate) {
            // First, check if expiry redemption date is exceeded (today > expiry date)
            if ($expiryRedemptionDate && $today->gt($expiryRedemptionDate)) {
                // Expiry redemption date is exceeded - use EC (Exceeded Charge)
                // Count days exceeded from expiry redemption date to today
                $daysExceeded = $expiryRedemptionDate->diffInDays($today);
                $additionalChargeType = 'EC';
                Log::info('EC Charge Applied', ['days_exceeded' => $daysExceeded]);
            } elseif ($maturityDate && $today->gt($maturityDate)) {
                // Expiry redemption date is NOT exceeded, but maturity date is exceeded - use LD (Late Days)
                // Count days exceeded from maturity date to today
                $daysExceeded = $maturityDate->diffInDays($today);
                $additionalChargeType = 'LD';
                Log::info('LD Charge Applied', ['days_exceeded' => $daysExceeded]);
            }

            // Get the percentage from additionalChargeConfig table based on days exceeded and type
            if ($daysExceeded > 0 && $additionalChargeType) {
                $additionalChargeConfig = AdditionalChargeConfig::findApplicable($daysExceeded, $additionalChargeType, 'renewal');
                Log::info('Config Lookup', [
                    'days_exceeded' => $daysExceeded,
                    'type' => $additionalChargeType,
                    'config_found' => $additionalChargeConfig ? 'yes' : 'no',
                    'config_percentage' => $additionalChargeConfig ? $additionalChargeConfig->percentage : null,
                ]);
                if ($additionalChargeConfig) {
                    // Calculate charge amount: current principal * percentage from config
                    $additionalChargeAmount = $currentPrincipalAmount * ((float) $additionalChargeConfig->percentage / 100);
                    Log::info('Additional Charge Calculated', ['amount' => $additionalChargeAmount]);
                }
            }
        }

        // Calculate late days charge using ComputationController
        // If back_date is checked, skip late days charge (set to 0)
        $computationController = new ComputationController();
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
                $latestTransactionForDates->maturity_date ? Carbon::parse($latestTransactionForDates->maturity_date) : null
            );
            $lateDaysChargeBreakdown = $computationController->getLateDaysChargeBreakdown(
                $oldestTransaction, 
                $today, 
                $currentPrincipalAmount,
                (float) $oldestTransaction->interest_rate,
                $latestTransactionForDates->maturity_date ? Carbon::parse($latestTransactionForDates->maturity_date) : null
            );
        }

        $totalAmountToPay = $totalInterest + $totalServiceCharge + $additionalChargeAmount + $lateDaysCharge;

        // Combine all item descriptions for the renewal transaction
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

        return view('transactions.renewal.renew', [
            'transaction' => $oldestTransaction, // Show oldest transaction (for reference)
            'latestTransaction' => $latestTransactionForDates, // Show latest transaction (for current dates)
            'allTransactions' => $allTransactions, // Keep for reference if needed
            'pawnTicketNumber' => $pawnTicketNumber,
            'currentPrincipalAmount' => $currentPrincipalAmount, // Pass current principal to view
            'originalPrincipalAmount' => $originalPrincipalAmount, // Pass original principal to view
            'partialTransactions' => $partialTransactions, // Pass partial transactions for history
            'totalInterest' => $totalInterest,
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
            'totalAmountToPay' => $totalAmountToPay,
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
            'service_charge' => ['required', 'numeric', 'min:0'],
            'back_date' => ['nullable', 'boolean'],
        ]);

        $pawnTicketNumber = $request->input('pawn_ticket_number');

        // Find all transactions with this pawn ticket number
        $allTransactions = Transaction::where('pawn_ticket_number', $pawnTicketNumber)
            ->where('type', 'sangla')
            ->whereDoesntHave('voided')
            ->orderBy('created_at', 'asc')
            ->get();

        if ($allTransactions->isEmpty()) {
            return redirect()->route('transactions.renewal.search')
                ->with('error', 'No active transaction found with the provided pawn ticket number.');
        }

        // Check if this pawn ticket has already been redeemed (has non-voided tubos transaction)
        $hasActiveTubos = Transaction::where('pawn_ticket_number', $pawnTicketNumber)
            ->where('type', 'tubos')
            ->whereDoesntHave('voided')
            ->exists();

        if ($hasActiveTubos) {
            return redirect()->route('transactions.renewal.search')
                ->with('error', 'This pawn ticket has already been redeemed (tubos). Renewal is not allowed for redeemed transactions.');
        }

        // Use the oldest transaction for renewal data (has actual loan amount)
        $oldestTransaction = $allTransactions->first();
        $branchId = $oldestTransaction->branch_id;
        
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
        
        $interestAmount = (float) $request->input('interest_amount');
        $serviceCharge = (float) $request->input('service_charge');
        $additionalChargeAmount = (float) ($request->input('additional_charge_amount') ?? 0);
        $lateDaysCharge = (float) ($request->input('late_days_charge_amount') ?? 0);
        $backDate = (bool) $request->input('back_date', false);
        $totalAmount = $interestAmount + $serviceCharge + $additionalChargeAmount + $lateDaysCharge;

        // Combine all item descriptions for the renewal transaction
        $combinedDescriptions = $allTransactions->pluck('item_description')->filter()->unique()->values()->implode('; ');

        // Use database transaction to ensure data integrity
        DB::transaction(function () use ($allTransactions, $request, $branchId, $interestAmount, $serviceCharge, $additionalChargeAmount, $lateDaysCharge, $backDate, $totalAmount, $pawnTicketNumber, $oldestTransaction, $combinedDescriptions, $currentPrincipalAmount) {
            // Note: We do NOT update the parent transaction dates - they remain as historical records
            // Only the renewal transaction will have the new extended dates

            // Generate renewal transaction number
            $renewalTransactionNumber = $this->generateRenewalTransactionNumber();

            // Create Transaction record for renewal
            $renewalTransaction = Transaction::create([
                'transaction_number' => $renewalTransactionNumber,
                'branch_id' => $branchId,
                'user_id' => $request->user()->id,
                'type' => 'renew',
                'first_name' => $oldestTransaction->first_name,
                'last_name' => $oldestTransaction->last_name,
                'address' => $oldestTransaction->address,
                'appraised_value' => $oldestTransaction->appraised_value,
                'loan_amount' => $currentPrincipalAmount, // Use current principal (after any partial payments)
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
                'grams' => $oldestTransaction->grams,
                'orcr_serial' => $oldestTransaction->orcr_serial,
                'service_charge' => $serviceCharge, // Service charge for renewals
                'late_days_charge' => $lateDaysCharge, // Late days charge
                'back_date' => $backDate, // Back date flag
                'net_proceeds' => $totalAmount, // For renewals, net_proceeds is the total amount paid (interest + service charge + additional charge + late days charge)
                'status' => 'active',
                'note' => $request->input('note'),
            ]);

            // Create financial transaction for the renewal payment (interest + service charge)
            // Type: "transaction" (same family as Sangla), but this one is an ADD (money coming in)
            BranchFinancialTransaction::create([
                'branch_id' => $branchId,
                'user_id' => $request->user()->id,
                'transaction_id' => $renewalTransaction->id,
                'type' => 'transaction',
                'description' => "Renewal payment - Pawn Ticket #{$pawnTicketNumber}",
                'amount' => $totalAmount, // Positive amount (money coming in: interest + service charge)
                'transaction_date' => now()->toDateString(),
            ]);

            // Update branch balance (add the total amount)
            BranchBalance::updateBalance($branchId, $totalAmount);
        });

        $paymentBreakdown = "Interest: ₱" . number_format($interestAmount, 2) . ", Service Charge: ₱" . number_format($serviceCharge, 2);
        if ($additionalChargeAmount > 0) {
            $paymentBreakdown .= ", Additional Charge: ₱" . number_format($additionalChargeAmount, 2);
        }
        if ($lateDaysCharge > 0) {
            $paymentBreakdown .= ", Late Days Charge: ₱" . number_format($lateDaysCharge, 2);
        }
        
        return redirect()->route('transactions.index')
            ->with('success', "Transaction(s) with pawn ticket number '{$pawnTicketNumber}' have been renewed successfully. Payment of ₱" . number_format($totalAmount, 2) . " ({$paymentBreakdown}) has been recorded.");
    }

    /**
     * Generate a unique transaction number for renewal transactions.
     */
    private function generateRenewalTransactionNumber(): string
    {
        $prefix = 'RNW';
        $date = now()->format('Ymd');
        
        // Get the last renewal transaction number for today
        $lastTransaction = Transaction::where('transaction_number', 'like', "{$prefix}-{$date}-%")
            ->orderBy('transaction_number', 'desc')
            ->first();
        
        if ($lastTransaction) {
            // Extract the sequence number and increment
            $parts = explode('-', $lastTransaction->transaction_number);
            $sequence = (int) end($parts);
            $sequence++;
        } else {
            // First renewal transaction of the day
            $sequence = 1;
        }
        
        // Format sequence as 6-digit number
        $sequenceFormatted = str_pad($sequence, 6, '0', STR_PAD_LEFT);
        
        return "{$prefix}-{$date}-{$sequenceFormatted}";
    }
}

