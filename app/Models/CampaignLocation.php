<?php

namespace App\Models;

use Database\Factories\CampaignLocationFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

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

    public function submissions(): HasMany
    {
        return $this->hasMany(FormSubmission::class, 'location_id');
    }

    public function publicUrl(): string
    {
        return route('public-form.show', $this->public_token);
    }

    protected static function booted(): void
    {
        // Every location gets an unguessable token for its public form link.
        static::creating(function (CampaignLocation $location) {
            $location->public_token ??= Str::random(32);
        });
    }
}
