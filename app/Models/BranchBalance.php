<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BranchBalance extends Model
{
    protected $fillable = [
        'branch_id',
        'balance',
    ];

    protected $casts = [
        'balance' => 'decimal:2',
    ];

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    /**
     * Update balance for a branch.
     * 
     * @param int $branchId
     * @param float $amount Signed amount to apply to the balance:
     *                      - Positive for money coming IN (replenish, renewal, etc.)
     *                      - Negative for money going OUT (expense, sangla, etc.)
     * @return void
     */
    public static function updateBalance(int $branchId, float $amount): void
    {
        $branchBalance = self::firstOrCreate(
            ['branch_id' => $branchId],
            ['balance' => 0]
        );
        
        $branchBalance->balance += $amount;
        $branchBalance->save();
    }

    /**
     * Recalculate balance from all non-voided transactions.
     * 
     * @param int $branchId
     * @return void
     */
    public static function recalculateBalance(int $branchId): void
    {
        // Get all non-voided transactions, eager load transaction relationship for type checking
        $transactions = BranchFinancialTransaction::where('branch_id', $branchId)
            ->whereDoesntHave('voided')
            ->with('transaction')
            ->get();
        
        $totalReplenish = $transactions->where('type', 'replenish')->sum('amount');
        $totalExpense = $transactions->where('type', 'expense')->sum('amount');
        $transactionEntries = $transactions->where('type', 'transaction');

        // For "transaction" type:
        // - Sangla entries are money OUT (minus)
        // - Renewal entries are money IN (plus)
        // - Tubos entries are money IN (plus)
        $totalTransactionOut = $transactionEntries
            ->filter(fn (BranchFinancialTransaction $t) => $t->isSanglaTransactionEntry())
            ->sum('amount');

        $totalTransactionIn = $transactionEntries
            ->filter(fn (BranchFinancialTransaction $t) => $t->isRenewalTransactionEntry() || $t->isTubosTransactionEntry() || $t->isPartialTransactionEntry())
            ->sum('amount');
        
        $balance = $totalReplenish + $totalTransactionIn - $totalExpense - $totalTransactionOut;
        
        self::updateOrCreate(
            ['branch_id' => $branchId],
            ['balance' => $balance]
        );
    }
}
