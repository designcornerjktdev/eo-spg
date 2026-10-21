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
        'assignment_id',
        'form_template_id',
        'submitted_by',
        'answers',
        'consent_accepted',
        'consent_text_version',
        'qualified',
        'submitted_at',
        'status',
        'voided_at',
        'voided_by',
        'void_reason',
        'corrects_submission_id',
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
        ];
    }

    public function campaign(): BelongsTo
    {
        return $this->belongsTo(Campaign::class);
    }

    public function assignment(): BelongsTo
    {
        return $this->belongsTo(Assignment::class);
    }

    public function formTemplate(): BelongsTo
    {
        return $this->belongsTo(FormTemplate::class);
    }

    public function submitter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'submitted_by');
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
            return $query->whereHas('campaign', fn (Builder $campaigns) => $campaigns->where('client_id', $user->client_id));
        }

        return $query->where('submitted_by', $user->id);
    }

    public function isVoid(): bool
    {
        return $this->status === SubmissionStatus::Void;
    }

    /**
     * SPG hanya boleh memperbaiki/membatalkan datanya sendiri di hari yang sama (zona waktu aplikasi).
     */
    public function isCorrectableBySubmitter(): bool
    {
        return ! $this->isVoid() && $this->submitted_at?->isToday();
    }
}
