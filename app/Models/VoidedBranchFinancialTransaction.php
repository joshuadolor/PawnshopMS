<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VoidedBranchFinancialTransaction extends Model
{
    protected $fillable = [
        'branch_financial_transaction_id',
        'voided_by',
        'reason',
        'voided_at',
    ];

    protected $casts = [
        'voided_at' => 'datetime',
    ];

    public function branchFinancialTransaction(): BelongsTo
    {
        return $this->belongsTo(BranchFinancialTransaction::class, 'branch_financial_transaction_id');
    }

    public function voidedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'voided_by');
    }
}
