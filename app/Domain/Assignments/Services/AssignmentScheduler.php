<?php

namespace App\Domain\Assignments\Services;

use App\Enums\AssignmentStatus;
use App\Enums\UserRole;
use App\Models\Assignment;
use App\Models\User;
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
        $this->assertNoOverlap(
            (int) $attributes['user_id'],
            $attributes['starts_at'],
            $attributes['ends_at'],
        );

        return Assignment::query()->create($attributes);
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
        $this->assertNoOverlap($userId, $startsAt, $endsAt, $assignment->id);

        $assignment->update($attributes);

        return $assignment->refresh();
    }

    private function assertSpg(int $userId): void
    {
        $user = User::query()->findOrFail($userId);

        if ($user->role !== UserRole::Spg) {
            throw ValidationException::withMessages([
                'user_id' => 'Assignment hanya bisa diberikan kepada akun SPG.',
            ]);
        }
    }

    private function assertWindow(mixed $startsAt, mixed $endsAt): void
    {
        if (strtotime((string) $endsAt) <= strtotime((string) $startsAt)) {
            throw ValidationException::withMessages([
                'ends_at' => 'Waktu selesai harus setelah waktu mulai.',
            ]);
        }
    }

    private function assertNoOverlap(int $userId, mixed $startsAt, mixed $endsAt, ?int $ignoreId = null): void
    {
        $overlap = Assignment::query()
            ->where('user_id', $userId)
            ->where('status', '!=', AssignmentStatus::Cancelled)
            ->when($ignoreId, fn ($query) => $query->whereKeyNot($ignoreId))
            ->where('starts_at', '<', $endsAt)
            ->where('ends_at', '>', $startsAt)
            ->exists();

        if ($overlap) {
            throw ValidationException::withMessages([
                'starts_at' => 'Jadwal SPG bentrok dengan assignment lain.',
            ]);
        }
    }
}
