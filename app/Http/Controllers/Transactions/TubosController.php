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

class TubosController extends Controller
{
    /**
     * Show the tubos search page.
     */
    public function search(): View
    {
        return view('transactions.tubos.search');
    }

    /**
     * Process the search and show tubos form.
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
            return redirect()->route('transactions.tubos.search')
                ->with('error', 'No active transaction found with the provided pawn ticket number.');
        }

        // Check if this pawn ticket has already been redeemed (has non-voided tubos transaction)
        $hasActiveTubos = Transaction::where('pawn_ticket_number', $pawnTicketNumber)
            ->where('type', 'tubos')
            ->whereDoesntHave('voided')
            ->exists();

        if ($hasActiveTubos) {
            return redirect()->route('transactions.tubos.search')
                ->with('error', 'This pawn ticket has already been redeemed (tubos). Another redemption is not allowed.');
        }

        // Use the oldest Sangla transaction for calculations (one pawn ticket = one computation)
        // The oldest transaction has the actual loan amount (additional items have loan_amount = 0)
        $oldestTransaction = $allTransactions->first();
        $branchId = $oldestTransaction->branch_id;

        // Get the latest transaction (Sangla OR Renewal OR Partial OR Tubos) for date calculations (most current dates)
        // This ensures we use the dates from the most recent renewal/partial/tubos if one exists
        $latestTransactionForDates = Transaction::where('pawn_ticket_number', $pawnTicketNumber)
            ->whereIn('type', ['sangla', 'renew', 'partial', 'tubos'])
            ->whereDoesntHave('voided')
            ->orderBy('created_at', 'desc')
            ->first();

        // If no renewal/partial/tubos exists, use the latest Sangla transaction
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

        // For tubos: Principal amount (current loan_amount after any partial payments)
        $principalAmount = $currentPrincipalAmount;

        // Get service charge from config (one service charge per pawn ticket)
        $serviceCharge = Config::getValue('sangla_service_charge', 0);
        $totalServiceCharge = $serviceCharge; // Only one service charge

        // Calculate additional charges
        // Use the latest transaction's dates (most current state - could be from a renewal/tubos)
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
            $additionalChargeConfig = AdditionalChargeConfig::findApplicable($daysExceeded, $additionalChargeType, 'tubos');
            if ($additionalChargeConfig) {
                // Calculate charge amount: current principal * percentage from config
                $additionalChargeAmount = $currentPrincipalAmount * ($additionalChargeConfig->percentage / 100);
            }
        }

        // Total amount to pay: Principal + Service Charge + Additional Charge
        $totalAmountToPay = $principalAmount + $totalServiceCharge + $additionalChargeAmount;

        // Combine all item descriptions for the tubos transaction
        $combinedDescriptions = $allTransactions->pluck('item_description')->filter()->unique()->values()->implode('; ');

        return view('transactions.tubos.tubos', [
            'transaction' => $oldestTransaction, // Show oldest transaction (for reference)
            'allTransactions' => $allTransactions, // Keep for reference if needed
            'pawnTicketNumber' => $pawnTicketNumber,
            'principalAmount' => $principalAmount,
            'currentPrincipalAmount' => $currentPrincipalAmount, // Pass current principal to view
            'originalPrincipalAmount' => $originalPrincipalAmount, // Pass original principal to view
            'partialTransactions' => $partialTransactions, // Pass partial transactions for history
            'serviceCharge' => $serviceCharge,
            'totalServiceCharge' => $totalServiceCharge,
            'additionalChargeType' => $additionalChargeType,
            'additionalChargeAmount' => $additionalChargeAmount,
            'daysExceeded' => $daysExceeded,
            'additionalChargeConfig' => $additionalChargeConfig,
            'totalAmountToPay' => $totalAmountToPay,
            'combinedDescriptions' => $combinedDescriptions,
            'branchId' => $branchId,
        ]);
    }

    /**
     * Process the tubos (redemption).
     */
    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'pawn_ticket_number' => ['required', 'string', 'max:100'],
            'principal_amount' => ['required', 'numeric', 'min:0'],
            'service_charge' => ['required', 'numeric', 'min:0'],
            'additional_charge_amount' => ['nullable', 'numeric', 'min:0'],
            'transaction_pawn_ticket' => ['required', 'string', 'max:100'],
            'signature' => ['required', 'string'],
        ]);

        $pawnTicketNumber = $request->input('pawn_ticket_number');

        // Find all transactions with this pawn ticket number
        $allTransactions = Transaction::where('pawn_ticket_number', $pawnTicketNumber)
            ->where('type', 'sangla')
            ->whereDoesntHave('voided')
            ->orderBy('created_at', 'asc')
            ->get();

        if ($allTransactions->isEmpty()) {
            return redirect()->route('transactions.tubos.search')
                ->with('error', 'No active transaction found with the provided pawn ticket number.');
        }

        // Check if this pawn ticket has already been redeemed (has non-voided tubos transaction)
        $hasActiveTubos = Transaction::where('pawn_ticket_number', $pawnTicketNumber)
            ->where('type', 'tubos')
            ->whereDoesntHave('voided')
            ->exists();

        if ($hasActiveTubos) {
            return redirect()->route('transactions.tubos.search')
                ->with('error', 'This pawn ticket has already been redeemed (tubos). Another redemption is not allowed.');
        }

        // Use the oldest transaction for tubos data (has actual loan amount)
        $oldestTransaction = $allTransactions->first();
        $branchId = $oldestTransaction->branch_id;
        $principalAmount = (float) $request->input('principal_amount');
        $serviceCharge = (float) $request->input('service_charge');
        $additionalChargeAmount = (float) ($request->input('additional_charge_amount') ?? 0);
        $totalAmount = $principalAmount + $serviceCharge + $additionalChargeAmount;

        // Combine all item descriptions for the tubos transaction
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
            return redirect()->back()
                ->withInput()
                ->with('error', 'Failed to process signature: ' . $e->getMessage());
        }

        // Use database transaction to ensure data integrity
        DB::transaction(function () use ($allTransactions, $request, $branchId, $principalAmount, $serviceCharge, $additionalChargeAmount, $totalAmount, $pawnTicketNumber, $oldestTransaction, $combinedDescriptions, $signaturePath) {
            // Generate tubos transaction number
            $tubosTransactionNumber = $this->generateTubosTransactionNumber();

            // Create Transaction record for tubos (redemption)
            $tubosTransaction = Transaction::create([
                'transaction_number' => $tubosTransactionNumber,
                'branch_id' => $branchId,
                'user_id' => $request->user()->id,
                'type' => 'tubos',
                'first_name' => $oldestTransaction->first_name,
                'last_name' => $oldestTransaction->last_name,
                'address' => $oldestTransaction->address,
                'appraised_value' => $oldestTransaction->appraised_value,
                'loan_amount' => $principalAmount, // Principal amount paid
                'interest_rate' => $oldestTransaction->interest_rate,
                'interest_rate_period' => $oldestTransaction->interest_rate_period,
                'maturity_date' => $oldestTransaction->maturity_date, // Keep original dates
                'expiry_date' => $oldestTransaction->expiry_date, // Keep original dates
                'pawn_ticket_number' => $pawnTicketNumber,
                'pawn_ticket_image_path' => $oldestTransaction->pawn_ticket_image_path,
                'auction_sale_date' => $oldestTransaction->auction_sale_date,
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
                'net_proceeds' => $totalAmount, // Total amount paid (principal + service charge + additional charge)
                'status' => 'redeemed', // Mark as redeemed
                'transaction_pawn_ticket' => $request->input('transaction_pawn_ticket'),
                'note' => $request->input('note'),
            ]);

            // Create financial transaction for the tubos payment
            // Type: "transaction" (same family as Sangla), but this one is an ADD (money coming in)
            BranchFinancialTransaction::create([
                'branch_id' => $branchId,
                'user_id' => $request->user()->id,
                'transaction_id' => $tubosTransaction->id,
                'type' => 'transaction',
                'description' => "Tubos (Redemption) payment - Pawn Ticket #{$pawnTicketNumber}",
                'amount' => $totalAmount, // Positive amount (money coming in: principal + service charge + additional charge)
                'transaction_date' => now()->toDateString(),
            ]);

            // Update branch balance (add the total amount)
            BranchBalance::updateBalance($branchId, $totalAmount);

            // Mark all related Sangla transactions as redeemed
            foreach ($allTransactions as $sanglaTransaction) {
                $sanglaTransaction->update(['status' => 'redeemed']);
            }
        });

        $paymentBreakdown = "Principal: ₱" . number_format($principalAmount, 2) . ", Service Charge: ₱" . number_format($serviceCharge, 2);
        if ($additionalChargeAmount > 0) {
            $paymentBreakdown .= ", Additional Charge: ₱" . number_format($additionalChargeAmount, 2);
        }
        
        return redirect()->route('transactions.index')
            ->with('success', "Transaction(s) with pawn ticket number '{$pawnTicketNumber}' have been redeemed (tubos) successfully. Payment of ₱" . number_format($totalAmount, 2) . " ({$paymentBreakdown}) has been recorded.");
    }

    /**
     * Generate a unique transaction number for tubos transactions.
     */
    private function generateTubosTransactionNumber(): string
    {
        $prefix = 'TBS';
        $date = now()->format('Ymd');
        
        // Get the last tubos transaction number for today
        $lastTransaction = Transaction::where('transaction_number', 'like', "{$prefix}-{$date}-%")
            ->orderBy('transaction_number', 'desc')
            ->first();
        
        if ($lastTransaction) {
            // Extract the sequence number and increment
            $parts = explode('-', $lastTransaction->transaction_number);
            $sequence = (int) end($parts);
            $sequence++;
        } else {
            // First tubos transaction of the day
            $sequence = 1;
        }
        
        // Format sequence as 6-digit number
        $sequenceFormatted = str_pad($sequence, 6, '0', STR_PAD_LEFT);
        
        return "{$prefix}-{$date}-{$sequenceFormatted}";
    }
}

