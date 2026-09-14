<?php

namespace App\Models;

use App\Traits\BelongsToUser;
use Illuminate\Database\Eloquent\Model;

class Purchase extends Model
{
    use BelongsToUser;

    protected $fillable = ['user_id', 'contact_id', 'invoice_number', 'total_amount'];

    /**
     * Faturanın ait olduğu Tedarikçi
     */
    public function contact()
    {
        return $this->belongsTo(Contact::class);
    }

    /**
     * Faturadaki ürün kalemleri
     */
    public function items()
    {
        return $this->hasMany(PurchaseItem::class);
    }
}

