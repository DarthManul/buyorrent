<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class UserItemRent extends Model
{
    use HasFactory;

    protected $fillable = ['user_item_id', 'start_date', 'end_date'];

    public function userItem(): BelongsTo
    {
        return $this->belongsTo(UserItem::class);
    }

    public function extensions(): HasMany
    {
        return $this->hasMany(UserItemExtendRent::class);
    }
}
