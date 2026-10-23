<?php

namespace App\Models;

use App\Enums\CampaignStatus;
use App\Enums\FormStatus;
use Database\Factories\CampaignFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Campaign extends Model
{
    /** @use HasFactory<CampaignFactory> */
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'client_id',
        'name',
        'starts_on',
        'ends_on',
        'status',
        'mask_identity_for_client',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'starts_on' => 'date',
            'ends_on' => 'date',
            'status' => CampaignStatus::class,
            'mask_identity_for_client' => 'boolean',
        ];
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function products(): BelongsToMany
    {
        return $this->belongsToMany(Product::class, 'campaign_products')->withTimestamps();
    }

    public function locations(): HasMany
    {
        return $this->hasMany(CampaignLocation::class);
    }

    public function formTemplates(): HasMany
    {
        return $this->hasMany(FormTemplate::class);
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
            return $query->where('client_id', $user->client_id);
        }

        return $query->whereRaw('1 = 0');
    }

    /**
     * The form the public links serve: the newest published (or locked) version.
     */
    public function currentForm(): ?FormTemplate
    {
        return $this->formTemplates()
            ->whereIn('status', [FormStatus::Published, FormStatus::Locked])
            ->latest('id')
            ->first();
    }

    public function isAcceptingEntries(): bool
    {
        return $this->status === CampaignStatus::Active && $this->currentForm() !== null;
    }
}
