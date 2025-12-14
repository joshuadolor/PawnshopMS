<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class BranchFinancialTransaction extends Model
{
    protected $fillable = [
        'branch_id',
        'user_id',
        'transaction_id',
        'type',
        'description',
        'amount',
        'transaction_date',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'transaction_date' => 'date',
    ];

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function transaction(): BelongsTo
    {
        return $this->belongsTo(Transaction::class);
    }

    public function isExpense(): bool
    {
        return $this->type === 'expense';
    }

    public function isReplenish(): bool
    {
        return $this->type === 'replenish';
    }

    public function isTransaction(): bool
    {
        return $this->type === 'transaction';
    }

    public function voided(): HasOne
    {
        return $this->hasOne(VoidedBranchFinancialTransaction::class, 'branch_financial_transaction_id');
    }

    public function isVoided(): bool
    {
        return $this->voided !== null;
    }
}
