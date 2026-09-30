<?php

namespace App\Models;

use App\Traits\BelongsToUser;
use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    use BelongsToUser;

    protected $fillable = ['user_id', 'category_id', 'name', 'code', 'barcode', 'purchase_price', 'sale_price', 'tax_rate', 'stock', 'min_stock'];

    protected function casts(): array
    {
        return [
            'purchase_price' => 'decimal:2',
            'sale_price' => 'decimal:2',
            'tax_rate' => 'integer',
            'stock' => 'integer',
            'min_stock' => 'integer',
        ];
    }

    public function stockMovements()
    {
        return $this->hasMany(StockMovement::class);
    }

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function saleItems()
    {
        return $this->hasMany(SaleItem::class);
    }

    public function purchaseItems()
    {
        return $this->hasMany(PurchaseItem::class);
    }
}
