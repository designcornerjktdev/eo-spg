<?php

namespace App\Models;

use Database\Factories\CampaignLocationFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CampaignLocation extends Model
{
    /** @use HasFactory<CampaignLocationFactory> */
    use HasFactory;

    protected $fillable = [
        'campaign_id',
        'name',
        'area',
        'address',
        'latitude',
        'longitude',
    ];

    protected function casts(): array
    {
        return [
            'latitude' => 'float',
            'longitude' => 'float',
        ];
    }

    public function campaign(): BelongsTo
    {
        return $this->belongsTo(Campaign::class);
    }

    public function assignments(): HasMany
    {
        return $this->hasMany(Assignment::class, 'location_id');
    }
}
