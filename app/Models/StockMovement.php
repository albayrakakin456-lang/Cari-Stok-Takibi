<?php

namespace App\Models;

use App\Traits\BelongsToUser;
use Illuminate\Database\Eloquent\Model;

class StockMovement extends Model
{
    use BelongsToUser;

    protected $fillable = ['user_id', 'product_id', 'type', 'quantity', 'description'];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}
