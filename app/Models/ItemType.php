<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ItemType extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
    ];

    /**
     * Get the subtypes for the item type.
     */
    public function subtypes()
    {
        return $this->hasMany(ItemTypeSubtype::class);
    }

    /**
     * Check if the item type has subtypes.
     */
    public function hasSubtypes(): bool
    {
        return $this->subtypes()->count() > 0;
    }

    /**
     * Get the tags for the item type.
     */
    public function tags()
    {
        return $this->hasMany(ItemTypeTag::class);
    }

    /**
     * Check if the item type has tags.
     */
    public function hasTags(): bool
    {
        return $this->tags()->count() > 0;
    }
}
