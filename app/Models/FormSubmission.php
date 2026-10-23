<?php

namespace App\Models;

use App\Enums\SubmissionStatus;
use Database\Factories\FormSubmissionFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class FormSubmission extends Model
{
    /** @use HasFactory<FormSubmissionFactory> */
    use HasFactory;

    protected $fillable = [
        'campaign_id',
        'location_id',
        'latitude',
        'longitude',
        'location_accuracy_meters',
        'distance_meters',
        'form_template_id',
        'answers',
        'consent_accepted',
        'consent_text_version',
        'qualified',
        'submitted_at',
        'status',
        'voided_at',
        'voided_by',
        'void_reason',
    ];

    protected function casts(): array
    {
        return [
            'answers' => 'array',
            'consent_accepted' => 'boolean',
            'qualified' => 'boolean',
            'submitted_at' => 'datetime',
            'status' => SubmissionStatus::class,
            'voided_at' => 'datetime',
            'latitude' => 'float',
            'longitude' => 'float',
        ];
    }

    public function campaign(): BelongsTo
    {
        return $this->belongsTo(Campaign::class);
    }

    public function location(): BelongsTo
    {
        return $this->belongsTo(CampaignLocation::class, 'location_id');
    }

    public function formTemplate(): BelongsTo
    {
        return $this->belongsTo(FormTemplate::class);
    }

    public function voider(): BelongsTo
    {
        return $this->belongsTo(User::class, 'voided_by');
    }

    public function fieldValues(): HasMany
    {
        return $this->hasMany(SubmissionFieldValue::class);
    }

    public function scopeVisibleTo(Builder $query, User $user): Builder
    {
        if ($user->isSuperadmin()) {
            return $query;
        }

        if ($user->isClient()) {
            return $query->whereHas('campaign', fn (Builder $campaigns) => $campaigns->visibleTo($user));
        }

        return $query->whereRaw('1 = 0');
    }

    public function isVoid(): bool
    {
        return $this->status === SubmissionStatus::Void;
    }
}
