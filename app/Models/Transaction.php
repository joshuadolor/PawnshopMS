<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Transaction extends Model
{
    protected $fillable = [
        'transaction_number',
        'branch_id',
        'user_id',
        'type',
        'first_name',
        'last_name',
        'address',
        'appraised_value',
        'loan_amount',
        'interest_rate',
        'interest_rate_period',
        'maturity_date',
        'expiry_date',
        'pawn_ticket_number',
        'pawn_ticket_image_path',
        'auction_sale_date',
        'item_type_id',
        'item_type_subtype_id',
        'custom_item_type',
        'item_description',
        'item_image_path',
        'pawner_id_image_path',
        'grams',
        'orcr_serial',
        'service_charge',
        'net_proceeds',
        'status',
    ];

    protected $casts = [
        'appraised_value' => 'decimal:2',
        'loan_amount' => 'decimal:2',
        'interest_rate' => 'decimal:2',
        'maturity_date' => 'date',
        'expiry_date' => 'date',
        'auction_sale_date' => 'date',
        'grams' => 'decimal:1',
        'service_charge' => 'decimal:2',
        'net_proceeds' => 'decimal:2',
    ];

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function itemType(): BelongsTo
    {
        return $this->belongsTo(ItemType::class);
    }

    public function itemTypeSubtype(): BelongsTo
    {
        return $this->belongsTo(ItemTypeSubtype::class);
    }

    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(ItemTypeTag::class, 'transaction_item_type_tags');
    }

    public function voided(): HasOne
    {
        return $this->hasOne(VoidedTransaction::class, 'transaction_id');
    }

    public function isVoided(): bool
    {
        return $this->voided !== null;
    }

    public function getPawnerNameAttribute(): string
    {
        return trim("{$this->first_name} {$this->last_name}");
    }
}
