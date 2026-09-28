<?php

namespace App\Models;

use App\Traits\BelongsToUser;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class WebhookEndpoint extends Model
{
    use BelongsToUser, SoftDeletes;

    protected $fillable = ['user_id', 'url', 'secret', 'events', 'active'];

    protected $hidden = ['secret'];

    protected function casts(): array
    {
        return [
            'secret' => 'encrypted',
            'events' => 'array',
            'active' => 'boolean',
        ];
    }

    public function deliveries()
    {
        return $this->hasMany(WebhookDelivery::class);
    }
}
