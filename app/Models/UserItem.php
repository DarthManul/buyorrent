<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class UserItem extends Model
{
    use HasFactory;

    protected $fillable = ['user_id', 'item_id', 'price', 'type'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function item(): BelongsTo
    {
        return $this->belongsTo(Item::class);
    }

    public function details(): HasOne
    {

        switch ($this->type) {
            case 'buy':
                $relationType = UserItemBuy::class;
                break;
            case 'rent':
                $relationType = UserItemRent::class;
                break;
                
            default:
                $relationType = UserItemExtendRent::class;
                break;
        }
        return $this->hasOne($relationType);
    }
}
