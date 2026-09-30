<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SaleItem extends Model
{
    protected $fillable = ['sale_id', 'product_id', 'quantity', 'original_price', 'discount_amount', 'tax_rate', 'tax_amount', 'unit_price', 'total'];

    protected function casts(): array
    {
        return [
            'quantity' => 'integer',
            'original_price' => 'decimal:2',
            'discount_amount' => 'decimal:2',
            'tax_rate' => 'integer',
            'tax_amount' => 'decimal:2',
            'unit_price' => 'decimal:2',
            'total' => 'decimal:2',
        ];
    }

    public function sale()
    {
        return $this->belongsTo(Sale::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function discounts()
    {
        return $this->hasMany(SaleDiscount::class);
    }
}
