<?php

namespace App\Enums;

enum Verdict: string
{
    case Healthy = 'healthy';
    case NearingEndOfLife = 'eol';
    case Survived = 'survived';
    case Crashed = 'crashed';
    case UsersGone = 'users_gone';

    /**
     * Mirror of the verdict logic in the game's finish() function.
     */
    public static function fromStats(string $outcome, int $health, int $happy, float $age, int $debt, int $breaches): self
    {
        return match (true) {
            $outcome === 'crash' => self::Crashed,
            $outcome === 'users' => self::UsersGone,
            $health >= 60 && $happy >= 50 && $age <= 6 && $breaches === 0 => self::Healthy,
            $age > 8 || $debt >= 70 => self::NearingEndOfLife,
            default => self::Survived,
        };
    }

    public function label(): string
    {
        return match ($this) {
            self::Healthy => 'Healthy site',
            self::NearingEndOfLife => 'Nearing end of life',
            self::Survived => 'Survived',
            self::Crashed => 'The site went down',
            self::UsersGone => 'Users are gone',
        };
    }

    public function completed(): bool
    {
        return ! in_array($this, [self::Crashed, self::UsersGone], true);
    }
}
