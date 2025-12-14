<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BranchFinancialTransaction extends Model
{
    protected $fillable = [
        'branch_id',
        'user_id',
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

    public function isExpense(): bool
    {
        return $this->type === 'expense';
    }

    public function isReplenish(): bool
    {
        return $this->type === 'replenish';
    }
}
