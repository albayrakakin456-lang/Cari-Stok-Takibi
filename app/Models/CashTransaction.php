<?php

namespace App\Models;

use App\Traits\BelongsToUser;
use Illuminate\Database\Eloquent\Model;

class CashTransaction extends Model
{
    use BelongsToUser;

    protected $fillable = ['user_id', 'contact_id', 'sale_id', 'type', 'amount', 'description'];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
        ];
    }

    public function contact()
    {
        return $this->belongsTo(Contact::class);
    }
}
