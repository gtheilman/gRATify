<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;

uses(RefreshDatabase::class);

it('does not serve cached /api/user to unauthenticated users', function () {
    Cache::flush();
    $user = User::factory()->create();
    Cache::put("auth.user.{$user->id}", ['id' => $user->id], 2);

    $this->getJson('/api/user')->assertStatus(401);
});
