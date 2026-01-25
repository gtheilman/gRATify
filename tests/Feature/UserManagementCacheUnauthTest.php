<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;

uses(RefreshDatabase::class);

it('does not serve cached user list to unauthenticated users', function () {
    Cache::flush();
    Cache::put('user-management.users.guest', [], 2);

    $this->getJson('/api/user-management/users')->assertStatus(401);
});
