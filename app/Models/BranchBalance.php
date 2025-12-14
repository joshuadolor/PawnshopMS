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
     * @param float $amount Positive for replenish, negative for expense/transaction
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
        // Get all non-voided transactions
        $transactions = BranchFinancialTransaction::where('branch_id', $branchId)
            ->whereDoesntHave('voided')
            ->get();
        
        $totalReplenish = $transactions->where('type', 'replenish')->sum('amount');
        $totalExpense = $transactions->where('type', 'expense')->sum('amount');
        $totalTransaction = $transactions->where('type', 'transaction')->sum('amount');
        
        $balance = $totalReplenish - $totalExpense - $totalTransaction;
        
        self::updateOrCreate(
            ['branch_id' => $branchId],
            ['balance' => $balance]
        );
    }
}
