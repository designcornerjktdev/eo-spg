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
                'user_id' => 'Assignment hanya bisa diberikan kepada akun SPG.',
            ]);
        }
    }

    private function assertWindow(mixed $startsAt, mixed $endsAt): void
    {
        if (Carbon::parse($endsAt)->lte(Carbon::parse($startsAt))) {
            throw ValidationException::withMessages([
                'ends_at' => 'Waktu selesai harus setelah waktu mulai.',
            ]);
        }
    }

    private function assertNoOverlap(int $userId, mixed $startsAt, mixed $endsAt, ?int $ignoreId = null): void
    {
        // Kunci baris SPG agar dua penyimpanan bersamaan tidak sama-sama lolos cek bentrok.
        User::query()->whereKey($userId)->lockForUpdate()->first();

        // Normalisasi ke format DB; input datetime-local ("2026-10-21T15:00") salah dibandingkan sebagai string di SQLite.
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
                'starts_at' => 'Jadwal SPG bentrok dengan assignment lain.',
            ]);
        }
    }
}
