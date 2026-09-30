<?php

namespace App\Models;

use App\Enums\CampaignTargetType;
use Illuminate\Database\Eloquent\Model;

class CampaignTarget extends Model
{
    protected $fillable = [
        'campaign_id',
        'target_type',
        'target_id',
    ];

    protected function casts(): array
    {
        return [
            'target_type' => CampaignTargetType::class,
            'target_id' => 'integer',
        ];
    }

    public function campaign()
    {
        return $this->belongsTo(Campaign::class);
    }
}
