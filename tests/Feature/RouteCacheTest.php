<?php

use Illuminate\Support\Facades\Artisan;

it('can cache routes without errors', function () {
    // Clear cached routes first.
    Artisan::call('route:clear');

    $exit = Artisan::call('route:cache');
    expect($exit)->toBe(0);
});
