<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ItemTypeTag extends Model
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
     * Get the item type that owns the tag.
     */
    public function itemType()
    {
        return $this->belongsTo(ItemType::class);
    }
}
