<?php

use Illuminate\Support\Facades\Password;

it('returns not configured when mailer is disabled', function () {
    config()->set('mail.default', 'log');
    config()->set('mail.mailers.log', ['transport' => 'log']);

    $this->postJson('/api/auth/password/email', ['email' => 'user@example.com'])
        ->assertOk()
        ->assertJson([
            'sent' => false,
            'status' => 'Password reset email is not configured.',
        ]);
});

it('sends reset link when mailer is configured', function () {
    config()->set('mail.default', 'smtp');
    config()->set('mail.mailers.smtp', [
        'transport' => 'smtp',
        'host' => 'smtp.example.com',
        'port' => 1025,
        'username' => 'user',
        'password' => 'pass',
    ]);

    Password::shouldReceive('sendResetLink')
        ->once()
        ->andReturn(Password::RESET_LINK_SENT);

    $this->postJson('/api/auth/password/email', ['email' => 'user@example.com'])
        ->assertOk()
        ->assertJson([
            'sent' => true,
        ]);
});
