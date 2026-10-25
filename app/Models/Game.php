<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Prunable;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Game extends Model
{
    use HasFactory, HasUlids, Prunable;

    /**
     * Real-time seconds per in-game month, as in the game's MONTH_S.
     */
    public const int MONTH_SECONDS = 5;

    public const int TOTAL_MONTHS = 36;

    /**
     * @return HasOne<Result, $this>
     */
    public function result(): HasOne
    {
        return $this->hasOne(Result::class);
    }

    /**
     * Shortest real time in which a game can reach the given month.
     * A small margin absorbs clock and network jitter.
     */
    public function minimumSecondsFor(int $months): float
    {
        return max(0, $months - 1) * self::MONTH_SECONDS * 0.9;
    }

    /**
     * Abandoned games never get a result: drop them after a day.
     *
     * @return Builder<static>
     */
    public function prunable(): Builder
    {
        return static::where('created_at', '<=', now()->subDay())->doesntHave('result');
    }
}
