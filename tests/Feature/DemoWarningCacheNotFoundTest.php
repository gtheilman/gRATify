<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;

uses(RefreshDatabase::class);

it('does not cache demo-warning when requested unauthenticated (still public)', function () {
    Cache::flush();
    Cache::spy();

    $this->getJson('/api/demo-warning')
        ->assertOk();

    Cache::shouldHaveReceived('put')->atLeast()->once();
});
