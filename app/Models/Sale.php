<?php

namespace App\Models;

use App\Traits\BelongsToUser;
use Illuminate\Database\Eloquent\Model;

class Sale extends Model
{
    use BelongsToUser;

    protected $fillable = [
        'user_id',
        'contact_id',
        'invoice_number',
        'external_reference',
        'request_fingerprint',
        'subtotal',
        'campaign_discount',
        'customer_discount',
        'total_amount',
        'status',
        'cancelled_at',
    ];

    protected function casts(): array
    {
        return [
            'subtotal' => 'decimal:2',
            'campaign_discount' => 'decimal:2',
            'customer_discount' => 'decimal:2',
            'total_amount' => 'decimal:2',
            'cancelled_at' => 'datetime',
        ];
    }

    public function contact()
    {
        return $this->belongsTo(Contact::class);
    }

    public function items()
    {
        return $this->hasMany(SaleItem::class);
    }

    public function discounts()
    {
        return $this->hasMany(SaleDiscount::class);
    }
}
