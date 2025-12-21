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

        // Use the oldest Sangla transaction for calculations (one pawn ticket = one computation)
        // The oldest transaction has the actual loan amount (additional items have loan_amount = 0)
        $oldestTransaction = $allTransactions->first();
        $branchId = $oldestTransaction->branch_id;

        // Get the latest transaction (Sangla OR Renewal) for date calculations (most current dates)
        // This ensures we use the dates from the most recent renewal if one exists
        $latestTransactionForDates = Transaction::where('pawn_ticket_number', $pawnTicketNumber)
            ->whereIn('type', ['sangla', 'renew'])
            ->whereDoesntHave('voided')
            ->orderBy('created_at', 'desc')
            ->first();

        // If no renewal exists, use the latest Sangla transaction
        if (!$latestTransactionForDates) {
            $latestTransactionForDates = $allTransactions->last();
        }

        // Calculate interest from the oldest transaction only
        $totalInterest = (float) $oldestTransaction->loan_amount * ((float) $oldestTransaction->interest_rate / 100);

        // Get service charge from config (one service charge per pawn ticket)
        $serviceCharge = Config::getValue('sangla_service_charge', 0);
        $totalServiceCharge = $serviceCharge; // Only one service charge

        // Calculate additional charges
        // Use the latest transaction's dates (most current state - could be from a renewal)
        $today = Carbon::today();
        $expiryRedemptionDate = $latestTransactionForDates->expiry_date ? Carbon::parse($latestTransactionForDates->expiry_date) : null;
        $maturityDate = $latestTransactionForDates->maturity_date ? Carbon::parse($latestTransactionForDates->maturity_date) : null;
        $daysExceeded = 0;
        $additionalChargeType = null;
        $additionalChargeAmount = 0;
        $additionalChargeConfig = null;

        // First, check if expiry redemption date is exceeded
        if ($expiryRedemptionDate && $today->gt($expiryRedemptionDate)) {
            // Expiry redemption date is exceeded - use EC (Exceeded Charge)
            // Count days exceeded from expiry redemption date to today
            $daysExceeded = abs($expiryRedemptionDate->diffInDays($today, false));
            $additionalChargeType = 'EC';
        } elseif ($maturityDate && $today->gt($maturityDate)) {
            // Expiry redemption date is NOT exceeded, but maturity date is exceeded - use LD (Late Days)
            // Count days exceeded from maturity date to today
            $daysExceeded = abs($maturityDate->diffInDays($today, false));
            $additionalChargeType = 'LD';
        }

        // Get the percentage from additionalChargeConfig table based on days exceeded and type
        if ($daysExceeded > 0 && $additionalChargeType) {
            $additionalChargeConfig = AdditionalChargeConfig::findApplicable($daysExceeded, $additionalChargeType, 'renewal');
            if ($additionalChargeConfig) {
                // Calculate charge amount: loan_amount * percentage from config
                $additionalChargeAmount = $oldestTransaction->loan_amount * ($additionalChargeConfig->percentage / 100);
            }
        }

        $totalAmountToPay = $totalInterest + $totalServiceCharge + $additionalChargeAmount;

        // Combine all item descriptions for the renewal transaction
        $combinedDescriptions = $allTransactions->pluck('item_description')->filter()->unique()->values()->implode('; ');

        // Get config values for date calculations
        $daysBeforeRedemption = (int) Config::getValue('sangla_days_before_redemption', 90);
        $daysBeforeAuctionSale = (int) Config::getValue('sangla_days_before_auction_sale', 85);
        $interestPeriod = Config::getValue('sangla_interest_period', 'per_month');

        // Calculate default new maturity date based on interest period
        $defaultMaturityDate = match ($interestPeriod) {
            'per_annum' => $today->copy()->addYear()->format('Y-m-d'),
            'per_month' => $today->copy()->addMonth()->format('Y-m-d'),
            default => $today->copy()->addMonth()->format('Y-m-d'),
        };

        return view('transactions.renewal.renew', [
            'transaction' => $oldestTransaction, // Show oldest transaction (has actual loan amount)
            'allTransactions' => $allTransactions, // Keep for reference if needed
            'pawnTicketNumber' => $pawnTicketNumber,
            'totalInterest' => $totalInterest,
            'serviceCharge' => $serviceCharge,
            'totalServiceCharge' => $totalServiceCharge,
            'additionalChargeType' => $additionalChargeType,
            'additionalChargeAmount' => $additionalChargeAmount,
            'daysExceeded' => $daysExceeded,
            'additionalChargeConfig' => $additionalChargeConfig,
            'totalAmountToPay' => $totalAmountToPay,
            'combinedDescriptions' => $combinedDescriptions,
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
            'service_charge' => ['required', 'numeric', 'min:0'],
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

        // Use the oldest transaction for renewal data (has actual loan amount)
        $oldestTransaction = $allTransactions->first();
        $branchId = $oldestTransaction->branch_id;
        $interestAmount = (float) $request->input('interest_amount');
        $serviceCharge = (float) $request->input('service_charge');
        $additionalChargeAmount = (float) ($request->input('additional_charge_amount') ?? 0);
        $totalAmount = $interestAmount + $serviceCharge + $additionalChargeAmount;

        // Combine all item descriptions for the renewal transaction
        $combinedDescriptions = $allTransactions->pluck('item_description')->filter()->unique()->values()->implode('; ');

        // Use database transaction to ensure data integrity
        DB::transaction(function () use ($allTransactions, $request, $branchId, $interestAmount, $serviceCharge, $additionalChargeAmount, $totalAmount, $pawnTicketNumber, $oldestTransaction, $combinedDescriptions) {
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
                'loan_amount' => $oldestTransaction->loan_amount, // Use oldest transaction's loan amount
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
                'net_proceeds' => $totalAmount, // For renewals, net_proceeds is the total amount paid (interest + service charge + additional charge)
                'status' => 'active',
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

