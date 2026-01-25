<?php

use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;

uses(RefreshDatabase::class);

it('sends a reset notification for a known email', function () {
    Notification::fake();
    $user = User::factory()->create(['email' => 'reset-email@example.com']);

    config()->set('mail.default', 'smtp');
    config()->set('mail.mailers.smtp', [
        'transport' => 'smtp',
        'host' => 'smtp.example.com',
        'port' => 2525,
    ]);

    $this->postJson('/api/auth/password/email', ['email' => $user->email])
        ->assertOk();

    Notification::assertSentTo($user, ResetPassword::class);
});

it('does not send a reset notification for unknown email but still responds ok', function () {
    Notification::fake();

    config()->set('mail.default', 'smtp');
    config()->set('mail.mailers.smtp', [
        'transport' => 'smtp',
        'host' => 'smtp.example.com',
        'port' => 2525,
    ]);

    $this->postJson('/api/auth/password/email', ['email' => 'missing@example.com'])
        ->assertOk()
        ->assertJsonStructure(['status', 'sent']);

    Notification::assertNothingSent();
});
