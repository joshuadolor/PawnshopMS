<?php

namespace App\Http\Controllers\Transactions\Sangla;

use App\Http\Controllers\Controller;
use App\Http\Requests\Transactions\Sangla\StoreSanglaTransactionRequest;
use App\Models\Branch;
use App\Models\Config;
use App\Models\ItemType;
use App\Models\Transaction;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class SanglaController extends Controller
{
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
        $itemImagePath = null;
        $pawnerIdImagePath = null;
        
        Log::info('=== TRANSACTION STORE METHOD CALLED ===');
        Log::info('Request data:', $request->all());
        
        try {
            Log::info('Step 1: Starting validation');
            $validated = $request->validated();
            Log::info('Step 2: Validation passed', ['validated' => $validated]);
            
            $user = $request->user();
            Log::info('Step 3: User retrieved', ['user_id' => $user->id, 'user_name' => $user->name]);
            
            // Get service charge from config
            $serviceCharge = Config::getValue('sangla_service_charge', 0);
            Log::info('Step 4: Service charge retrieved', ['service_charge' => $serviceCharge]);
            
            // Calculate net proceeds: principal - (principal * interest) - service charge
            $principal = (float) $validated['loan_amount'];
            $interestRate = (float) $validated['interest_rate'];
            $interest = $principal * ($interestRate / 100);
            $netProceeds = $principal - $interest - $serviceCharge;
            Log::info('Step 5: Calculated amounts', [
                'principal' => $principal,
                'interest_rate' => $interestRate,
                'interest' => $interest,
                'net_proceeds' => $netProceeds
            ]);
            
            // Generate unique transaction number
            $transactionNumber = $this->generateTransactionNumber();
            Log::info('Step 6: Transaction number generated', ['transaction_number' => $transactionNumber]);
            
            // Get branch ID
            if (isset($validated['branch_id'])) {
                $branchId = $validated['branch_id'];
                Log::info('Step 7: Branch ID from validated data', ['branch_id' => $branchId]);
            } else {
                $userBranch = $user->branches()->first();
                if (!$userBranch) {
                    Log::error('Step 7: No branch assigned to user');
                    return redirect()->back()
                        ->withInput()
                        ->with('error', 'No branch assigned. Please contact an administrator.');
                }
                $branchId = $userBranch->id;
                Log::info('Step 7: Branch ID from user branches', ['branch_id' => $branchId]);
            }
            
            // Store images
            Log::info('Step 8: Storing images');
            $itemImagePath = $request->file('item_image')->store('transactions/items', 'public');
            $pawnerIdImagePath = $request->file('pawner_id_image')->store('transactions/pawners', 'public');
            Log::info('Step 9: Images stored', [
                'item_image_path' => $itemImagePath,
                'pawner_id_image_path' => $pawnerIdImagePath
            ]);
            
            Log::info('Step 10: Starting database transaction');
            DB::beginTransaction();
            
            // Create transaction
            Log::info('Step 11: Creating transaction record', [
                'transaction_number' => $transactionNumber,
                'branch_id' => $branchId,
                'user_id' => $user->id
            ]);
            
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
            
            Log::info('Step 12: Transaction data prepared', $transactionData);
            
            $transaction = Transaction::create($transactionData);
            Log::info('Step 13: Transaction created successfully', ['transaction_id' => $transaction->id]);
            
            // Attach tags if provided
            if ($request->has('item_type_tags') && is_array($request->input('item_type_tags'))) {
                $tagIds = array_filter($request->input('item_type_tags'), function($id) {
                    return !empty($id);
                });
                if (!empty($tagIds)) {
                    Log::info('Step 14: Attaching tags', ['tag_ids' => $tagIds]);
                    $transaction->tags()->attach($tagIds);
                    Log::info('Step 15: Tags attached successfully');
                }
            }
            
            Log::info('Step 16: Committing database transaction');
            DB::commit();
            Log::info('Step 17: Database transaction committed successfully');
            
            Log::info('=== TRANSACTION CREATED SUCCESSFULLY ===', [
                'transaction_id' => $transaction->id,
                'transaction_number' => $transactionNumber
            ]);
            
            return redirect()->route('transactions.index')
                ->with('success', "Sangla transaction #{$transactionNumber} created successfully.");
                
        } catch (\Exception $e) {
            DB::rollBack();
            
            // Delete uploaded images if transaction creation failed
            if (isset($itemImagePath)) {
                Storage::disk('public')->delete($itemImagePath);
            }
            if (isset($pawnerIdImagePath)) {
                Storage::disk('public')->delete($pawnerIdImagePath);
            }
            
            // Log the error for debugging
            Log::error('Transaction creation failed: ' . $e->getMessage(), [
                'exception' => $e,
                'trace' => $e->getTraceAsString(),
            ]);
            
            return redirect()->back()
                ->withInput()
                ->with('error', 'Failed to create transaction: ' . $e->getMessage());
        }
    }
    
    /**
     * Generate a unique transaction number.
     * Format: SANGLA-YYYYMMDD-XXXXXX (where XXXXXX is a 6-digit sequential number)
     */
    private function generateTransactionNumber(): string
    {
        $prefix = 'SANGLA';
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
}

