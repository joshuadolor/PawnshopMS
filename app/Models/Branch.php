<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Branch extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'address',
        'contact_number',
    ];

    /**
     * The users that belong to the branch.
     */
    public function users()
    {
        return $this->belongsToMany(User::class);
    }

    /**
     * The financial transactions for the branch.
     */
    public function financialTransactions()
    {
        return $this->hasMany(BranchFinancialTransaction::class);
    }

    /**
     * The balance for the branch.
     */
    public function balance()
    {
        return $this->hasOne(BranchBalance::class);
    }

    /**
     * Get the current balance for the branch.
     */
    public function getCurrentBalance(): float
    {
        $balance = $this->balance;
        return $balance ? (float) $balance->balance : 0.0;
    }
}
