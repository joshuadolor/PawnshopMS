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

        // Find all Sangla transactions with this pawn ticket number (including additional items)
        $allTransactions = Transaction::where('pawn_ticket_number', $pawnTicketNumber)
            ->where('type', 'sangla')
            ->whereDoesntHave('voided')
            ->with(['branch', 'itemType', 'itemTypeSubtype', 'tags'])
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
        // This is the minimum amount that must be paid
        $totalInterest = $currentPrincipalAmount * ((float) $oldestTransaction->interest_rate / 100);

        // Get service charge from config (one service charge per pawn ticket)
        $serviceCharge = Config::getValue('sangla_service_charge', 0);
        $totalServiceCharge = $serviceCharge; // Only one service charge

        // Calculate additional charges
        // Use the latest transaction's dates (most current state - could be from a renewal/partial)
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

        // Calculate minimum renewal amount (for display and validation only)
        // The computation is still: new principal = current principal - partial amount paid
        $minimumRenewalAmount = $totalInterest + $totalServiceCharge + $additionalChargeAmount;

        // Combine all item descriptions for the partial transaction
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

        return view('transactions.partial.partial', [
            'transaction' => $oldestTransaction, // Show oldest transaction (for reference)
            'allTransactions' => $allTransactions, // Keep for reference if needed
            'pawnTicketNumber' => $pawnTicketNumber,
            'totalInterest' => $totalInterest,
            'serviceCharge' => $serviceCharge,
            'totalServiceCharge' => $totalServiceCharge,
            'additionalChargeType' => $additionalChargeType,
            'additionalChargeAmount' => $additionalChargeAmount,
            'daysExceeded' => $daysExceeded,
            'additionalChargeConfig' => $additionalChargeConfig,
            'currentPrincipalAmount' => $currentPrincipalAmount,
            'originalPrincipalAmount' => $originalPrincipalAmount, // Pass original principal to view
            'partialTransactions' => $partialTransactions, // Pass partial transactions for history
            'minimumRenewalAmount' => $minimumRenewalAmount, // Pass minimum renewal amount for validation
            'combinedDescriptions' => $combinedDescriptions,
            'branchId' => $branchId,
            'daysBeforeRedemption' => $daysBeforeRedemption,
            'daysBeforeAuctionSale' => $daysBeforeAuctionSale,
            'defaultMaturityDate' => $defaultMaturityDate,
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
                'maturity_date' => ['required', 'date', 'after_or_equal:today'],
                'expiry_date' => ['required', 'date', 'after_or_equal:maturity_date'],
                'auction_sale_date' => ['nullable', 'date', 'after_or_equal:expiry_date'],
                'partial_amount' => ['required', 'numeric', 'min:0'],
                'signature' => ['required', 'string'],
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            $pawnTicketNumber = $request->input('pawn_ticket_number', '');
            return redirect()->route('transactions.partial.find', ['pawn_ticket_number' => $pawnTicketNumber])
                ->withInput()
                ->withErrors($e->errors());
        }

        $pawnTicketNumber = $request->input('pawn_ticket_number');

        // Find all transactions with this pawn ticket number
        $allTransactions = Transaction::where('pawn_ticket_number', $pawnTicketNumber)
            ->where('type', 'sangla')
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

        // Use the oldest transaction for basic info (has actual loan amount)
        $oldestTransaction = $allTransactions->first();
        $branchId = $oldestTransaction->branch_id;
        $partialAmount = (float) $request->input('partial_amount');

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

        // Calculate renewal amount to validate minimum payment
        $totalInterest = $currentPrincipalAmount * ((float) $oldestTransaction->interest_rate / 100);
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
        $today = Carbon::today();
        $expiryRedemptionDate = $latestTransactionForDates->expiry_date ? Carbon::parse($latestTransactionForDates->expiry_date) : null;
        $maturityDate = $latestTransactionForDates->maturity_date ? Carbon::parse($latestTransactionForDates->maturity_date) : null;
        $additionalChargeAmount = 0;

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

        $minimumRenewalAmount = $totalInterest + $serviceCharge + $additionalChargeAmount;

        // Validate that partial amount is at least the minimum renewal amount
        if ($partialAmount < $minimumRenewalAmount) {
            return redirect()->route('transactions.partial.find', ['pawn_ticket_number' => $pawnTicketNumber])
                ->withInput()
                ->withErrors(['partial_amount' => "Partial amount must be at least the minimum renewal amount (₱" . number_format($minimumRenewalAmount, 2) . ")."]);
        }

        // Validate that partial amount is not greater than current principal
        if ($partialAmount > $currentPrincipalAmount) {
            return redirect()->route('transactions.partial.find', ['pawn_ticket_number' => $pawnTicketNumber])
                ->withInput()
                ->withErrors(['partial_amount' => "Partial amount cannot exceed the current principal amount (₱" . number_format($currentPrincipalAmount, 2) . ")."]);
        }

        // Calculate new principal amount
        // Simple calculation: New principal = Current principal - Partial amount paid
        $newPrincipalAmount = $currentPrincipalAmount - $partialAmount;

        // Ensure new principal is not negative
        if ($newPrincipalAmount < 0) {
            $newPrincipalAmount = 0;
        }

        // Combine all item descriptions for the partial transaction
        $combinedDescriptions = $allTransactions->pluck('item_description')->filter()->unique()->values()->implode('; ');

        // Process signature (base64 to image file)
        $signaturePath = null;
        $branch = $oldestTransaction->branch;
        $branchName = $branch->name;
        
        try {
            $signatureData = $request->input('signature');
            if ($signatureData && strpos($signatureData, 'data:image') === 0) {
                // Extract base64 data
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
        } catch (\Exception $e) {
            return redirect()->route('transactions.partial.find', ['pawn_ticket_number' => $pawnTicketNumber])
                ->withInput()
                ->with('error', 'Failed to process signature: ' . $e->getMessage());
        }

        // Use database transaction to ensure data integrity
        DB::transaction(function () use ($allTransactions, $request, $branchId, $partialAmount, $pawnTicketNumber, $oldestTransaction, $combinedDescriptions, $signaturePath, $newPrincipalAmount, $totalInterest, $serviceCharge, $additionalChargeAmount) {
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
                'net_proceeds' => $partialAmount, // Total amount paid
                'status' => 'active',
                'note' => $request->input('note'),
            ]);

            // Create financial transaction for the partial payment
            // Type: "transaction" (same family as Sangla), but this one is an ADD (money coming in)
            BranchFinancialTransaction::create([
                'branch_id' => $branchId,
                'user_id' => $request->user()->id,
                'transaction_id' => $partialTransaction->id,
                'type' => 'transaction',
                'description' => "Partial payment - Pawn Ticket #{$pawnTicketNumber}",
                'amount' => $partialAmount, // Positive amount (money coming in)
                'transaction_date' => now()->toDateString(),
            ]);

            // Update branch balance (add the partial amount)
            BranchBalance::updateBalance($branchId, $partialAmount);
        });

        return redirect()->route('transactions.index')
            ->with('success', "Partial payment of ₱" . number_format($partialAmount, 2) . " for pawn ticket number '{$pawnTicketNumber}' has been recorded successfully. Principal reduced from ₱" . number_format($currentPrincipalAmount, 2) . " to ₱" . number_format($newPrincipalAmount, 2) . ".");
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

