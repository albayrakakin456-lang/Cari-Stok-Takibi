<?php

namespace App\Models;

use App\Traits\BelongsToUser;
use Illuminate\Database\Eloquent\Model;

class Sale extends Model
{
    use BelongsToUser;

    protected $fillable = ['user_id', 'contact_id', 'invoice_number', 'total_amount'];

    public function contact()
    {
        return $this->belongsTo(Contact::class);
    }

    public function items()
    {
        return $this->hasMany(SaleItem::class);
    }
}
