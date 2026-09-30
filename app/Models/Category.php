<?php

namespace App\Models;

use App\Traits\BelongsToUser;
use Illuminate\Database\Eloquent\Model;

class Category extends Model
{
    use BelongsToUser;

    protected $fillable = ['user_id', 'name'];

    public function products()
    {
        return $this->hasMany(Product::class);
    }
}
