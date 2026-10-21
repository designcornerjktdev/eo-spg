<?php

namespace App\Models;

use App\Enums\FormStatus;
use Database\Factories\FormTemplateFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class FormTemplate extends Model
{
    /** @use HasFactory<FormTemplateFactory> */
    use HasFactory;

    protected $fillable = [
        'campaign_id',
        'name',
        'version',
        'status',
        'requires_consent',
        'consent_text',
        'qualification_enabled',
        'qualification_rule',
    ];

    protected function casts(): array
    {
        return [
            'status' => FormStatus::class,
            'requires_consent' => 'boolean',
            'qualification_enabled' => 'boolean',
            'qualification_rule' => 'array',
        ];
    }

    public function campaign(): BelongsTo
    {
        return $this->belongsTo(Campaign::class);
    }

    public function fields(): HasMany
    {
        return $this->hasMany(FormField::class)->orderBy('sort_order');
    }

    public function submissions(): HasMany
    {
        return $this->hasMany(FormSubmission::class);
    }

    public function isLocked(): bool
    {
        return $this->status === FormStatus::Locked || $this->submissions()->exists();
    }
}
