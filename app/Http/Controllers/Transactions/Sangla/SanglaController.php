<?php

namespace App\Http\Controllers\Transactions\Sangla;

use App\Http\Controllers\Controller;
use App\Http\Requests\Transactions\Sangla\StoreSanglaTransactionRequest;
use App\Services\ImageProcessingService;
use App\Models\Branch;
use App\Models\BranchBalance;
use App\Models\BranchFinancialTransaction;
use App\Models\Config;
use App\Models\ItemType;
use App\Models\Transaction;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class SanglaController extends Controller
{
    public function __construct(
        private ImageProcessingService $imageService
    ) {
    }
    /**
     * Show the form for creating a new Sangla transaction.
     */
    public function create(): View
    {
        $itemTypes = ItemType::with(['subtypes', 'tags'])
            ->orderByRaw("CASE WHEN name = 'Jewelry' THEN 0 ELSE 1 END")
            ->orderBy('name', 'asc')
            ->get();

        // Get config values
        $serviceCharge = Config::getValue('sangla_service_charge', 0);
        $interestPeriod = Config::getValue('sangla_interest_period', 'per_month');
        $daysBeforeRedemption = (int) Config::getValue('sangla_days_before_redemption', 90);
        $daysBeforeAuctionSale = (int) Config::getValue('sangla_days_before_auction_sale', 85);

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
            'daysBeforeRedemption' => $daysBeforeRedemption,
            'daysBeforeAuctionSale' => $daysBeforeAuctionSale,
        ]);
    }

    /**
     * Store a newly created Sangla transaction.
     */
    public function store(StoreSanglaTransactionRequest $request): RedirectResponse
    {
        $itemImagePath = null;
        $pawnerIdImagePath = null;
        $pawnTicketImagePath = null;
        
        try {
            $validated = $request->validated();
            $user = $request->user();
            
            // Get service charge from config
            $serviceCharge = Config::getValue('sangla_service_charge', 0);
            
            // Calculate net proceeds: principal - (principal * interest) - service charge
            $principal = (float) $validated['loan_amount'];
            $interestRate = (float) $validated['interest_rate'];
            $interest = $principal * ($interestRate / 100);
            $netProceeds = $principal - $interest - $serviceCharge;
            
            // Generate unique transaction number
            $transactionNumber = $this->generateTransactionNumber();
            
            // Get branch ID and name
            $branch = null;
            if (isset($validated['branch_id'])) {
                $branch = Branch::find($validated['branch_id']);
            } else {
                $branch = $user->branches()->first();
            }
            
            if (!$branch) {
                return redirect()->back()
                    ->withInput()
                    ->with('error', 'No branch assigned. Please contact an administrator.');
            }
            
            $branchId = $branch->id;
            $branchName = $branch->name;
            
            // Store images (with resizing and compression, organized by date and branch name)
            $itemImagePath = $this->imageService->processAndStore(
                $request->file('item_image'),
                'transactions/items',
                $branchName
            );
            $pawnerIdImagePath = $this->imageService->processAndStore(
                $request->file('pawner_id_image'),
                'transactions/pawners',
                $branchName
            );
            $pawnTicketImagePath = $this->imageService->processAndStore(
                $request->file('pawn_ticket_image'),
                'transactions/pawn-tickets',
                $branchName
            );
            
            DB::beginTransaction();
            
            // Create transaction
            $transactionData = [
                'transaction_number' => $transactionNumber,
                'branch_id' => $branchId,
                'user_id' => $user->id,
                'type' => 'sangla',
                'first_name' => $validated['first_name'],
                'last_name' => $validated['last_name'],
                'address' => $validated['address'],
                'appraised_value' => $validated['appraised_value'],
                'loan_amount' => $principal,
                'interest_rate' => $interestRate,
                'interest_rate_period' => $validated['interest_rate_period'],
                'maturity_date' => $validated['maturity_date'],
                'expiry_date' => $validated['expiry_date'],
                'pawn_ticket_number' => $validated['pawn_ticket_number'],
                'pawn_ticket_image_path' => $pawnTicketImagePath,
                'auction_sale_date' => $validated['auction_sale_date'] ?? null,
                'item_type_id' => $validated['item_type'],
                'item_type_subtype_id' => $validated['item_type_subtype'] ?? null,
                'custom_item_type' => $validated['custom_item_type'] ?? null,
                'item_description' => $validated['item_description'],
                'item_image_path' => $itemImagePath,
                'pawner_id_image_path' => $pawnerIdImagePath,
                'grams' => $validated['grams'] ?? null,
                'orcr_serial' => $validated['orcr_serial'] ?? null,
                'service_charge' => $serviceCharge,
                'net_proceeds' => max(0, $netProceeds), // Ensure non-negative
                'status' => 'active',
            ];
            
            $transaction = Transaction::create($transactionData);
            
            // Attach tags if provided
            if ($request->has('item_type_tags') && is_array($request->input('item_type_tags'))) {
                $tagIds = array_filter($request->input('item_type_tags'), function($id) {
                    return !empty($id);
                });
                if (!empty($tagIds)) {
                    $transaction->tags()->attach($tagIds);
                }
            }
            
            // Create financial transaction entry for net proceeds (type: transaction, negative for Sangla)
            if ($netProceeds > 0) {
                $financialTransaction = BranchFinancialTransaction::create([
                    'branch_id' => $branchId,
                    'user_id' => $user->id,
                    'transaction_id' => $transaction->id,
                    'type' => 'transaction',
                    'description' => 'Sangla transaction',
                    'amount' => $netProceeds,
                    'transaction_date' => now()->toDateString(),
                ]);

                // Update branch balance (negative for transaction type)
                BranchBalance::updateBalance($branchId, -$netProceeds);
            }
            
            DB::commit();
            
            return redirect()->route('transactions.index')
                ->with('success', "Sangla transaction #{$transactionNumber} created successfully.");
                
        } catch (\Exception $e) {
            DB::rollBack();
            
            // Delete uploaded images if transaction creation failed
            if (isset($itemImagePath)) {
                Storage::disk('local')->delete($itemImagePath);
            }
            if (isset($pawnerIdImagePath)) {
                Storage::disk('local')->delete($pawnerIdImagePath);
            }
            if (isset($pawnTicketImagePath)) {
                Storage::disk('local')->delete($pawnTicketImagePath);
            }
            
            return redirect()->back()
                ->withInput()
                ->with('error', 'Failed to create transaction: ' . $e->getMessage());
        }
    }
    
    /**
     * Generate a unique transaction number.
     * Format: SNGL-YYYYMMDD-XXXXXX (where XXXXXX is a 6-digit sequential number)
     */
    private function generateTransactionNumber(): string
    {
        $prefix = 'SNGL';
        $date = now()->format('Ymd');
        
        // Get the last transaction number for today
        $lastTransaction = Transaction::where('transaction_number', 'like', "{$prefix}-{$date}-%")
            ->orderBy('transaction_number', 'desc')
            ->first();
        
        if ($lastTransaction) {
            // Extract the sequence number and increment
            $parts = explode('-', $lastTransaction->transaction_number);
            $sequence = (int) end($parts);
            $sequence++;
        } else {
            // First transaction of the day
            $sequence = 1;
        }
        
        // Format sequence as 6-digit number
        $sequenceFormatted = str_pad($sequence, 6, '0', STR_PAD_LEFT);
        
        return "{$prefix}-{$date}-{$sequenceFormatted}";
    }

    /**
     * Show the form for adding an additional item to an existing transaction.
     */
    public function additionalItem(Request $request): View|RedirectResponse
    {
        $pawnTicketNumber = $request->query('pawn_ticket_number');
        
        if (!$pawnTicketNumber) {
            return redirect()->route('transactions.sangla.create')
                ->with('error', 'Pawn ticket number is required.');
        }

        // Find the oldest non-voided transaction with this pawn ticket number
        $firstTransaction = Transaction::where('pawn_ticket_number', $pawnTicketNumber)
            ->where('type', 'sangla')
            ->whereDoesntHave('voided')
            ->orderBy('created_at', 'asc')
            ->first();

        if (!$firstTransaction) {
            return redirect()->route('transactions.sangla.create')
                ->with('error', 'No active transaction found with the provided pawn ticket number. All transactions for this pawn ticket are voided.');
        }

        // Check if more than 6 hours have passed since the first transaction
        $hoursSinceFirstTransaction = now()->diffInHours($firstTransaction->created_at);
        if ($hoursSinceFirstTransaction > 6) {
            return redirect()->back()
                ->withInput()
                ->with('error', 'Cannot add additional item. More than 6 hours have passed since the first transaction was created. The first transaction was created on ' . $firstTransaction->created_at->format('M d, Y h:i A') . '.');
        }

        // Check if there are any child transactions (additional items or renewals) with this pawn ticket number
        $childTransactions = Transaction::where('pawn_ticket_number', $pawnTicketNumber)
            ->where('id', '!=', $firstTransaction->id)
            ->where('type', '!=', 'sangla')
            ->whereDoesntHave('voided')
            ->get();

        if ($childTransactions->count() > 0) {
            $childTypes = $childTransactions->pluck('type')
                ->unique()
                ->values()
                ->toArray();
            
            $childTypesStr = implode(' and ', array_map(function($type) {
                return $type === 'renew' ? 'renewal(s)' : 'additional item(s)';
            }, $childTypes));
            
            return redirect()->route('transactions.sangla.create')
                ->with('error', "Cannot add additional item. This pawn ticket already has {$childTransactions->count()} child transaction(s) ({$childTypesStr}). Additional items can only be added to the original transaction before any renewals or other additional items are created.");
        }

        // Get item types for the form
        $itemTypes = ItemType::with(['subtypes', 'tags'])
            ->orderByRaw("CASE WHEN name = 'Jewelry' THEN 0 ELSE 1 END")
            ->orderBy('name', 'asc')
            ->get();

        // Get config values (no service charge for additional items)
        $serviceCharge = 0; // No service charge for additional items
        $interestPeriod = Config::getValue('sangla_interest_period', 'per_month');
        $daysBeforeRedemption = (int) Config::getValue('sangla_days_before_redemption', 90);
        $daysBeforeAuctionSale = (int) Config::getValue('sangla_days_before_auction_sale', 85);

        // Calculate default maturity date based on interest period
        $today = now();
        if ($interestPeriod === 'per_annum') {
            $defaultMaturityDate = $today->copy()->addYear()->format('Y-m-d');
        } else {
            $defaultMaturityDate = $today->copy()->addMonth()->format('Y-m-d');
        }

        // Get user's branches
        $user = auth()->user();
        
        if ($user->isAdminOrSuperAdmin()) {
            $userBranches = Branch::orderBy('name', 'asc')->get();
            $showBranchSelection = $userBranches->count() > 1;
        } else {
            $userBranches = $user->branches()->orderBy('name', 'asc')->get();
            $showBranchSelection = $userBranches->count() > 1;
        }

        return view('transactions.sangla.additional-item', [
            'firstTransaction' => $firstTransaction,
            'pawnTicketNumber' => $pawnTicketNumber,
            'itemTypes' => $itemTypes,
            'serviceCharge' => $serviceCharge,
            'interestPeriod' => $interestPeriod,
            'defaultMaturityDate' => $defaultMaturityDate,
            'userBranches' => $userBranches,
            'showBranchSelection' => $showBranchSelection,
            'daysBeforeRedemption' => $daysBeforeRedemption,
            'daysBeforeAuctionSale' => $daysBeforeAuctionSale,
        ]);
    }

    /**
     * Store an additional item for an existing transaction.
     */
    public function storeAdditionalItem(StoreSanglaTransactionRequest $request): RedirectResponse
    {
        $pawnTicketNumber = $request->input('pawn_ticket_number');
        
        if (!$pawnTicketNumber) {
            return redirect()->back()
                ->withInput()
                ->with('error', 'Pawn ticket number is required.');
        }

        // Find the oldest non-voided transaction with this pawn ticket number
        $firstTransaction = Transaction::where('pawn_ticket_number', $pawnTicketNumber)
            ->where('type', 'sangla')
            ->whereDoesntHave('voided')
            ->orderBy('created_at', 'asc')
            ->first();

        if (!$firstTransaction) {
            return redirect()->back()
                ->withInput()
                ->with('error', 'No active transaction found with the provided pawn ticket number. All transactions for this pawn ticket are voided.');
        }

        // Check if there are any child transactions (additional items or renewals) with this pawn ticket number
        // Count all transactions with the same pawn ticket number (excluding the first one)
        $childTransactions = Transaction::where('pawn_ticket_number', $pawnTicketNumber)
            ->where('id', '!=', $firstTransaction->id)
            ->whereDoesntHave('voided')
            ->get();

        if ($childTransactions->count() > 0) {
            $childTypes = $childTransactions->pluck('type')
                ->unique()
                ->values()
                ->toArray();
            
            $childTypesStr = implode(' and ', array_map(function($type) {
                return $type === 'renew' ? 'renewal(s)' : 'additional item(s)';
            }, $childTypes));
            
            return redirect()->back()
                ->withInput()
                ->with('error', "Cannot add additional item. This pawn ticket already has {$childTransactions->count()} child transaction(s) ({$childTypesStr}). Additional items can only be added to the original transaction before any renewals or other additional items are created.");
        }

        // Check if more than 6 hours have passed since the first transaction
        $hoursSinceFirstTransaction = now()->diffInHours($firstTransaction->created_at);
        if ($hoursSinceFirstTransaction > 6) {
            return redirect()->back()
                ->withInput()
                ->with('error', 'Cannot add additional item. More than 6 hours have passed since the first transaction was created. The first transaction was created on ' . $firstTransaction->created_at->format('M d, Y h:i A') . '.');
        }

        // For additional items, use the same data from first transaction but with new item details
        // No service charge for additional items
        $serviceCharge = 0;
        
        $itemImagePath = null;
        $pawnerIdImagePath = null;
        $pawnTicketImagePath = null;
        $newPawnerIdImageUploaded = false;
        
        try {
            $validated = $request->validated();
            $user = $request->user();
            
            // For additional items, use values from parent transaction (not from request)
            // But save 0 for loan_amount and net_proceeds since the first transaction represents the summary
            $principal = 0; // Additional items don't have their own principal
            $interestRate = (float) $firstTransaction->interest_rate;
            $appraisedValue = (float) $firstTransaction->appraised_value;
            $interestRatePeriod = $firstTransaction->interest_rate_period;
            
            // Net proceeds is 0 for additional items
            $netProceeds = 0; // Additional items don't have their own net proceeds
            
            // Generate unique transaction number
            $transactionNumber = $this->generateTransactionNumber();
            
            // Use the same branch as the first transaction
            $branch = $firstTransaction->branch;
            $branchId = $branch->id;
            $branchName = $branch->name;
            
            // Store images (with resizing and compression, organized by date and branch name)
            $itemImagePath = $this->imageService->processAndStore(
                $request->file('item_image'),
                'transactions/items',
                $branchName
            );
            
            // Use pawner ID image from first transaction if not provided
            if ($request->hasFile('pawner_id_image')) {
                $pawnerIdImagePath = $this->imageService->processAndStore(
                    $request->file('pawner_id_image'),
                    'transactions/pawners',
                    $branchName
                );
                $newPawnerIdImageUploaded = true;
            } else {
                // Use the same pawner ID image from first transaction
                $pawnerIdImagePath = $firstTransaction->pawner_id_image_path;
            }
            
            // Use the same pawn ticket image from first transaction
            $pawnTicketImagePath = $firstTransaction->pawn_ticket_image_path;
            
            DB::beginTransaction();
            
            // Create transaction with same pawner info but new item details
            $transactionData = [
                'transaction_number' => $transactionNumber,
                'branch_id' => $branchId,
                'user_id' => $user->id,
                'type' => 'sangla',
                'first_name' => $firstTransaction->first_name, // From first transaction
                'last_name' => $firstTransaction->last_name, // From first transaction
                'address' => $firstTransaction->address, // From first transaction
                'appraised_value' => $appraisedValue, // From first transaction
                'loan_amount' => $principal, // 0 for additional items (first transaction is the summary)
                'interest_rate' => $interestRate, // From first transaction
                'interest_rate_period' => $interestRatePeriod, // From first transaction
                'maturity_date' => $firstTransaction->maturity_date, // From first transaction
                'expiry_date' => $firstTransaction->expiry_date, // From first transaction
                'pawn_ticket_number' => $pawnTicketNumber, // Same pawn ticket number
                'pawn_ticket_image_path' => $pawnTicketImagePath, // Same image
                'auction_sale_date' => $firstTransaction->auction_sale_date, // From first transaction
                'item_type_id' => $validated['item_type'],
                'item_type_subtype_id' => $validated['item_type_subtype'] ?? null,
                'custom_item_type' => $validated['custom_item_type'] ?? null,
                'item_description' => $validated['item_description'],
                'item_image_path' => $itemImagePath,
                'pawner_id_image_path' => $pawnerIdImagePath,
                'grams' => $validated['grams'] ?? null,
                'orcr_serial' => $validated['orcr_serial'] ?? null,
                'service_charge' => $serviceCharge, // No service charge
                'net_proceeds' => $netProceeds, // 0 for additional items (first transaction is the summary)
                'status' => 'active',
            ];
            
            $transaction = Transaction::create($transactionData);
            
            // Attach tags if provided
            if ($request->has('item_type_tags') && is_array($request->input('item_type_tags'))) {
                $tagIds = array_filter($request->input('item_type_tags'), function($id) {
                    return !empty($id);
                });
                if (!empty($tagIds)) {
                    $transaction->tags()->attach($tagIds);
                }
            }
            
            // No financial transaction for additional items - the financial transaction
            // was already created when the first item was processed with the service charge
            
            DB::commit();
            
            return redirect()->route('transactions.index')
                ->with('success', "Additional item for transaction #{$transactionNumber} created successfully.");
                
        } catch (\Exception $e) {
            DB::rollBack();
            
            // Delete uploaded images if transaction creation failed
            // Only delete if we created new images (not if we used existing ones)
            if (isset($itemImagePath)) {
                Storage::disk('local')->delete($itemImagePath);
            }
            if (isset($pawnerIdImagePath) && isset($newPawnerIdImageUploaded) && $newPawnerIdImageUploaded) {
                Storage::disk('local')->delete($pawnerIdImagePath);
            }
            
            return redirect()->back()
                ->withInput()
                ->with('error', 'Failed to create additional item: ' . $e->getMessage());
        }
    }
}

