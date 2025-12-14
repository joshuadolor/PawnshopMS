<?php

namespace App\Http\Controllers\Transactions\Sangla;

use App\Http\Controllers\Controller;
use App\Http\Requests\Transactions\Sangla\StoreSanglaTransactionRequest;
use App\Services\ImageProcessingService;
use App\Models\Branch;
use App\Models\Config;
use App\Models\ItemType;
use App\Models\Transaction;
use Illuminate\Http\RedirectResponse;
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
}

