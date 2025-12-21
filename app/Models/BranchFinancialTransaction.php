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

    /**
     * Determine if this "transaction" type row represents a Sangla (money OUT).
     *
     * Currently we identify Sangla-related financial rows by their description.
     * - "Sangla transaction"
     * - "Sangla transaction (additional item)"
     */
    public function isSanglaTransactionEntry(): bool
    {
        if (!$this->isTransaction()) {
            return false;
        }

        $description = (string) $this->description;

        return str_starts_with($description, 'Sangla transaction');
    }

    /**
     * Determine if this "transaction" type row represents a Renewal (money IN).
     *
     * We identify renewal rows by the description starting with "Renewal payment"
     * or by checking if the associated transaction type is 'renew'.
     */
    public function isRenewalTransactionEntry(): bool
    {
        if (!$this->isTransaction()) {
            return false;
        }

        // Check by description
        $description = (string) $this->description;
        if (str_starts_with($description, 'Renewal payment') || str_starts_with($description, 'Renewal interest payment')) {
            return true;
        }

        // Check by associated transaction type
        if ($this->transaction && $this->transaction->type === 'renew') {
            return true;
        }

        return false;
    }

    /**
     * Determine if this "transaction" type row represents a Tubos (money IN).
     *
     * We identify tubos rows by the description starting with "Tubos" or "Redemption"
     * or by checking if the associated transaction type is 'tubos'.
     */
    public function isTubosTransactionEntry(): bool
    {
        if (!$this->isTransaction()) {
            return false;
        }

        // Check by description
        $description = (string) $this->description;
        if (str_starts_with($description, 'Tubos') || str_contains($description, 'Redemption')) {
            return true;
        }

        // Check by associated transaction type
        if ($this->transaction && $this->transaction->type === 'tubos') {
            return true;
        }

        return false;
    }

    /**
     * Determine if this "transaction" type row represents a Partial Payment (money IN).
     *
     * We identify partial rows by the description starting with "Partial payment"
     * or by checking if the associated transaction type is 'partial'.
     */
    public function isPartialTransactionEntry(): bool
    {
        if (!$this->isTransaction()) {
            return false;
        }

        // Check by description
        $description = (string) $this->description;
        if (str_starts_with($description, 'Partial payment')) {
            return true;
        }

        // Check by associated transaction type
        if ($this->transaction && $this->transaction->type === 'partial') {
            return true;
        }

        return false;
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
