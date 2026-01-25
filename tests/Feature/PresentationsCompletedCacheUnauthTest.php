<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;

uses(RefreshDatabase::class);

it('does not serve cached completed presentations to unauthenticated users', function () {
    Cache::flush();
    Cache::put('presentations.completed.guest', [], 2);

    $this->getJson('/api/presentations/completed')->assertStatus(401);
});
