<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ItemTypeSubtype extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'item_type_id',
        'name',
    ];

    /**
     * Get the item type that owns the subtype.
     */
    public function itemType()
    {
        return $this->belongsTo(ItemType::class);
    }
}
