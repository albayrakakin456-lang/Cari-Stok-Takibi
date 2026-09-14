<?php

namespace App\Models;

use App\Traits\BelongsToUser;
use Illuminate\Database\Eloquent\Model;

class Contact extends Model
{
    use BelongsToUser;

    protected $fillable = ['user_id', 'name', 'phone', 'email', 'address', 'type', 'note', 'balance'];

    public function sales()
    {
        return $this->hasMany(Sale::class);
    }

    public function cashTransactions()
    {
        return $this->hasMany(CashTransaction::class);
    }

    public function purchases()
    {
        return $this->hasMany(Purchase::class);
    }
}
