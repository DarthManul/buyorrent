<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserItemExtendRent extends Model
{
    use HasFactory;

    protected $fillable = ['user_item_id', 'user_item_rent_id', 'start_date', 'end_date'];

    public function userItem(): BelongsTo
    {
        return $this->belongsTo(UserItemRent::class);
    }
}
