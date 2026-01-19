<?php

return [
    // Default scoring scheme identifier
    'default' => env('SCORING_SCHEME', 'geometric-decay'),

    // Map of available scoring strategies
    'schemes' => [
        'geometric-decay' => \App\Services\Scoring\GeometricDecayScoring::class,
        'linear-decay' => \App\Services\Scoring\LinearDecayScoring::class,
    ],
];
