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
}
