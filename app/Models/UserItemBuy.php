<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserItemBuy extends Model
{
    use HasFactory;

    protected $fillable = ['user_item_id', 'buy_date'];

    public function userItem() : BelongsTo
    {
        return $this->belongsTo(UserItem::class);
    }
}
