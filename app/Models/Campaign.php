<?php

namespace App\Models;

use App\Enums\CampaignType;
use App\Traits\BelongsToUser;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

class Campaign extends Model
{
    use BelongsToUser, SoftDeletes;

    protected $fillable = [
        'user_id',
        'name',
        'code',
        'type',
        'description',
        'parameters',
        'priority',
        'is_active',
        'is_exclusive',
        'starts_at',
        'ends_at',
    ];

    protected function casts(): array
    {
        return [
            'type' => CampaignType::class,
            'parameters' => 'array',
            'priority' => 'integer',
            'is_active' => 'boolean',
            'is_exclusive' => 'boolean',
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
        ];
    }

    public function scopeActiveAt(Builder $query, ?Carbon $at = null): Builder//birden fazla kampanyayı aktif olabilir.
    {
        $at ??= now();

        return $query
            ->where('is_active', true)
            ->where(fn (Builder $query) => $query
                ->whereNull('starts_at')
                ->orWhere('starts_at', '<=', $at))
            ->where(fn (Builder $query) => $query
                ->whereNull('ends_at')
                ->orWhere('ends_at', '>=', $at));
    }

    public function isActiveAt(?Carbon $at = null): bool
    {
        $at ??= now();

        return $this->is_active
            && ($this->starts_at === null || $this->starts_at->lte($at))
            && ($this->ends_at === null || $this->ends_at->gte($at));
    }

    public function targets()
    {
        return $this->hasMany(CampaignTarget::class);
    }

    public function saleDiscounts()
    {
        return $this->hasMany(SaleDiscount::class);
    }
}
