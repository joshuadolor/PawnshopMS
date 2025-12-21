<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AdditionalChargeConfig extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'start_day',
        'end_day',
        'percentage',
        'type',
        'transaction_type',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'start_day' => 'integer',
        'end_day' => 'integer',
        'percentage' => 'decimal:2',
    ];

    /**
     * Get the charge amount for a given principal and number of days exceeded.
     */
    public function calculateCharge(float $principal, int $daysExceeded): float
    {
        if ($daysExceeded >= $this->start_day && $daysExceeded <= $this->end_day) {
            return $principal * ($this->percentage / 100);
        }
        return 0;
    }

    /**
     * Find applicable config for given days exceeded, type, and transaction type.
     */
    public static function findApplicable(int $daysExceeded, string $type, string $transactionType): ?self
    {
        return self::where('transaction_type', $transactionType)
            ->where('type', $type)
            ->where('start_day', '<=', $daysExceeded)
            ->where('end_day', '>=', $daysExceeded)
            ->first();
    }
}
