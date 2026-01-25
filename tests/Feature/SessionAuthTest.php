<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Support\Facades\Hash;

uses(RefreshDatabase::class);

class TestCsrfMiddleware extends VerifyCsrfToken
{
    protected function runningUnitTests()
    {
        return false;
    }
}

it('issues a csrf cookie for the spa flow', function () {
    $this->app->bind(VerifyCsrfToken::class, TestCsrfMiddleware::class);

    $this->withMiddleware()
        ->get('/csrf-cookie')
        ->assertNoContent()
        ->assertCookie('XSRF-TOKEN');
});

it('rejects login without csrf when middleware is enabled', function () {
    $this->app->bind(VerifyCsrfToken::class, TestCsrfMiddleware::class);
    $this->withMiddleware();

    User::factory()->create([
        'email' => 'csrf@example.com',
        'password' => Hash::make('secret123'),
    ]);

    $this->postJson('/login', [
        'email' => 'csrf@example.com',
        'password' => 'secret123',
    ])->assertStatus(419);
});

it('authenticates session requests against api routes', function () {
    User::factory()->create([
        'email' => 'session@example.com',
        'password' => Hash::make('secret123'),
    ]);

    $this->withCsrfToken()->postJson('/login', [
        'email' => 'session@example.com',
        'password' => 'secret123',
    ])->assertOk();

    $this->getJson('/api/auth/me')->assertOk();
    $this->getJson('/api/assessments')->assertOk();
});

it('invalidates the session on logout', function () {
    User::factory()->create([
        'email' => 'logout@example.com',
        'password' => Hash::make('secret123'),
    ]);

    $this->withCsrfToken()->postJson('/login', [
        'email' => 'logout@example.com',
        'password' => 'secret123',
    ])->assertOk();

    $this->withCsrfToken()->postJson('/logout')->assertOk();
    $this->getJson('/api/auth/me')->assertStatus(401);
});
