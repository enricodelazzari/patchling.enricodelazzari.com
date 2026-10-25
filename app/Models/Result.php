<?php

namespace App\Models;

use App\Enums\Verdict;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['name', 'claim_token', 'score', 'verdict', 'months', 'revenue', 'features', 'squashed', 'breaches', 'health', 'happy'])]
#[Hidden(['claim_token'])]
class Result extends Model
{
    use HasFactory, HasUlids;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'verdict' => Verdict::class,
        ];
    }

    /**
     * Composite score: revenue and time survived count, a healthy site at the
     * end counts more, and every successful attack costs dearly.
     */
    public static function scoreFor(Verdict $verdict, int $months, int $revenue, int $health, int $happy, int $breaches): int
    {
        $score = $revenue * 10 + $months * 10;

        if ($verdict->completed()) {
            $score += ($health + $happy) * 5;
        }

        if ($verdict === Verdict::Healthy) {
            $score += 500;
        }

        return max(0, $score - $breaches * 150);
    }

    /**
     * @return BelongsTo<Game, $this>
     */
    public function game(): BelongsTo
    {
        return $this->belongsTo(Game::class);
    }

    /**
     * @param  Builder<static>  $query
     */
    public function scopeRanked(Builder $query): void
    {
        $query->whereNotNull('name')->orderByDesc('score')->oldest();
    }

    /**
     * Position on the leaderboard, or the position it would take once named.
     */
    public function position(): int
    {
        return static::query()
            ->whereNotNull('name')
            ->whereKeyNot($this->getKey())
            ->where(fn (Builder $query) => $query
                ->where('score', '>', $this->score)
                ->orWhere(fn (Builder $query) => $query
                    ->where('score', $this->score)
                    ->where('created_at', '<', $this->created_at)))
            ->count() + 1;
    }

    public function ownedBy(string $token): bool
    {
        return hash_equals($this->claim_token, hash('sha256', $token));
    }
}
