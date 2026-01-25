<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;

uses(RefreshDatabase::class);

it('does not serve cached shortlink providers to unauthenticated users', function () {
    Cache::flush();
    Cache::put('shortlink-providers.guest', ['bitly' => true, 'tinyurl' => true], 2);

    $this->getJson('/api/shortlink-providers')->assertStatus(401);
});
