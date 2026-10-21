<?php

namespace App\Models;

use App\Enums\AssignmentStatus;
use App\Enums\CampaignStatus;
use App\Enums\FormStatus;
use Database\Factories\AssignmentFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Assignment extends Model
{
    /** @use HasFactory<AssignmentFactory> */
    use HasFactory;

    protected $fillable = [
        'campaign_id',
        'location_id',
        'form_template_id',
        'user_id',
        'starts_at',
        'ends_at',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
            'status' => AssignmentStatus::class,
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

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function submissions(): HasMany
    {
        return $this->hasMany(FormSubmission::class);
    }

    public function scopeVisibleTo(Builder $query, User $user): Builder
    {
        if ($user->isSuperadmin()) {
            return $query;
        }

        if ($user->isClient()) {
            return $query->whereHas('campaign', fn (Builder $campaigns) => $campaigns->where('client_id', $user->client_id));
        }

        return $query->where('user_id', $user->id);
    }

    public function isFillableNow(): bool
    {
        $campaignActive = $this->campaign?->status === CampaignStatus::Active;
        $formReady = in_array($this->formTemplate?->status, [FormStatus::Published, FormStatus::Locked], true);
        $assignmentOpen = in_array($this->status, [AssignmentStatus::Scheduled, AssignmentStatus::Active], true);
        $inWindow = now()->between($this->starts_at, $this->ends_at);

        return $campaignActive && $formReady && $assignmentOpen && $inWindow;
    }
}
