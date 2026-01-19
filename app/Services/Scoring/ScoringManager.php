<?php

namespace App\Services\Scoring;

use InvalidArgumentException;

class ScoringManager
{
    public function forScheme(?string $scheme = null): ScoringStrategy
    {
        $scheme = $scheme ?: config('scoring.default', 'geometric-decay');
        $map = config('scoring.schemes', []);
        $class = $map[$scheme] ?? null;

        if (! $class || ! class_exists($class)) {
            throw new InvalidArgumentException("Unknown scoring scheme: {$scheme}");
        }

        $instance = app($class);
        if (! $instance instanceof ScoringStrategy) {
            throw new InvalidArgumentException("Scoring scheme {$scheme} does not implement ScoringStrategy");
        }

        return $instance;
    }
}
