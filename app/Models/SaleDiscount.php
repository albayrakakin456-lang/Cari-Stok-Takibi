<?php

namespace App\Models;

use App\Traits\BelongsToUser;
use Illuminate\Database\Eloquent\Model;

class SaleDiscount extends Model
{
    use BelongsToUser;

    protected $fillable = [
        'user_id',
        'sale_id',
        'sale_item_id',
        'campaign_id',
        'code',
        'campaign_name',
        'campaign_type',
        'description',
        'amount',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'metadata' => 'array',
        ];
    }

    public function sale()
    {
        return $this->belongsTo(Sale::class);
    }

    public function saleItem()
    {
        return $this->belongsTo(SaleItem::class);
    }

    public function campaign()
    {
        return $this->belongsTo(Campaign::class)->withTrashed();
    }
}
