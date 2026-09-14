<?php

namespace App\Traits;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;

trait BelongsToUser
{
    /**
     * Trait boot edildiginde Global Scope ve Creating eventini baglar.
     */
    protected static function bootBelongsToUser(): void
    {
        // 1. Global Scope: Oturum acmis kullanicinin verilerini filtrele
        static::addGlobalScope('user', function (Builder $builder) {
            if (auth()->check()) {
                $builder->where($builder->getModel()->getTable() . '.user_id', auth()->id());
            }
        });

        // 2. Creating Event: Kayit acilirken user_id atanmadiysa otomatik auth()->id() ata
        static::creating(function ($model) {
            if (auth()->check() && empty($model->user_id)) {
                $model->user_id = auth()->id();
            }
        });
    }

    /**
     * Bu kaydin ait oldugu Kullanici (User)
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
