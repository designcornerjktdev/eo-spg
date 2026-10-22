<?php

namespace App\Domain\Assignments\Services;

use App\Enums\AssignmentStatus;
use App\Enums\UserRole;
use App\Models\Assignment;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class AssignmentScheduler
{
    /**
     * @param  array<string, mixed>  $attributes
     */
    public function create(array $attributes): Assignment
    {
        $this->assertSpg((int) $attributes['user_id']);
        $this->assertWindow($attributes['starts_at'], $attributes['ends_at']);

        return DB::transaction(function () use ($attributes) {
            $this->assertNoOverlap(
                (int) $attributes['user_id'],
                $attributes['starts_at'],
                $attributes['ends_at'],
            );

            return Assignment::query()->create($attributes);
        });
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function update(Assignment $assignment, array $attributes): Assignment
    {
        if (isset($attributes['user_id'])) {
            $this->assertSpg((int) $attributes['user_id']);
        }

        $startsAt = $attributes['starts_at'] ?? $assignment->starts_at;
        $endsAt = $attributes['ends_at'] ?? $assignment->ends_at;
        $userId = (int) ($attributes['user_id'] ?? $assignment->user_id);

        $this->assertWindow($startsAt, $endsAt);

        return DB::transaction(function () use ($assignment, $attributes, $userId, $startsAt, $endsAt) {
            $this->assertNoOverlap($userId, $startsAt, $endsAt, $assignment->id);

            $assignment->update($attributes);

            return $assignment->refresh();
        });
    }

    private function assertSpg(int $userId): void
    {
        $user = User::query()->findOrFail($userId);

        if ($user->role !== UserRole::Spg) {
            throw ValidationException::withMessages([
                'user_id' => 'Assignments can only be given to SPG accounts.',
            ]);
        }
    }

    private function assertWindow(mixed $startsAt, mixed $endsAt): void
    {
        if (Carbon::parse($endsAt)->lte(Carbon::parse($startsAt))) {
            throw ValidationException::withMessages([
                'ends_at' => 'End time must be after the start time.',
            ]);
        }
    }

    private function assertNoOverlap(int $userId, mixed $startsAt, mixed $endsAt, ?int $ignoreId = null): void
    {
        // Lock the SPG row so two concurrent saves cannot both pass the overlap check.
        User::query()->whereKey($userId)->lockForUpdate()->first();

        // Normalize to the DB format; datetime-local input ("2026-10-21T15:00") compares wrongly as a string in SQLite.
        $startsAt = Carbon::parse($startsAt)->format('Y-m-d H:i:s');
        $endsAt = Carbon::parse($endsAt)->format('Y-m-d H:i:s');

        $overlap = Assignment::query()
            ->where('user_id', $userId)
            ->where('status', '!=', AssignmentStatus::Cancelled)
            ->when($ignoreId, fn ($query) => $query->whereKeyNot($ignoreId))
            ->where('starts_at', '<', $endsAt)
            ->where('ends_at', '>', $startsAt)
            ->exists();

        if ($overlap) {
            throw ValidationException::withMessages([
                'starts_at' => 'This SPG already has another assignment at this time.',
            ]);
        }
    }
}
