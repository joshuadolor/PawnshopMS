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
            'allTransactions' => $allTransactions, // Keep for reference if needed
            'pawnTicketNumber' => $pawnTicketNumber,
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
        $newPrincipalAmount = $currentPrincipalAmount - $partialAmount;

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
        DB::transaction(function () use ($allTransactions, $request, $branchId, $partialAmount, $lateDaysCharge, $backDate, $pawnTicketNumber, $oldestTransaction, $combinedDescriptions, $signaturePath, $newPrincipalAmount, $totalInterest, $serviceCharge, $additionalChargeAmount) {
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
                'net_proceeds' => abs($partialAmount), // Use absolute value for net proceeds
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
                    'amount' => $partialAmount, // Positive amount
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
            BranchBalance::updateBalance($branchId, $partialAmount);
        });

        $message = $partialAmount >= 0
            ? "Partial payment of ₱" . number_format($partialAmount, 2) . " for pawn ticket number '{$pawnTicketNumber}' has been recorded successfully. Principal reduced from ₱" . number_format($currentPrincipalAmount, 2) . " to ₱" . number_format($newPrincipalAmount, 2) . "."
            : "Principal increase of ₱" . number_format(abs($partialAmount), 2) . " for pawn ticket number '{$pawnTicketNumber}' has been recorded successfully. Principal increased from ₱" . number_format($currentPrincipalAmount, 2) . " to ₱" . number_format($newPrincipalAmount, 2) . ".";
        
        return redirect()->route('transactions.index')
            ->with('success', $message);
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

