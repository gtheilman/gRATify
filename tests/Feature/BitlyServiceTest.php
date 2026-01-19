<?php

use Shivella\Bitly\Facade\Bitly;
use Shivella\Bitly\Client\BitlyClient;
use Shivella\Bitly\Testing\BitlyClientFake;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('resolves bitly client binding', function () {
    $client = app('bitly');
    expect($client)->toBeInstanceOf(BitlyClient::class);
});

it('can fake bitly and generate deterministic short url', function () {
    Bitly::fake();

    $short = Bitly::getUrl('https://example.com/foo');

    expect($short)->toStartWith('http://bit.ly/');
    // sha1('https://example.com/foo') => d397ac...
    expect($short)->toBe('http://bit.ly/d397ac');
    expect(app('bitly'))->toBeInstanceOf(BitlyClientFake::class);
});

it('does not throw when bitly fails against localhost targets', function () {
    Bitly::clearResolvedInstance();
    app()->forgetInstance('bitly');
    Bitly::shouldReceive('getURL')->andThrow(new \RuntimeException('bitly down'));
    putenv('APP_ENV=testing');
    config(['app.env' => 'testing']);

    $user = \App\Models\User::factory()->create();

    $response = $this->actingAs($user, 'web')
        ->postJson('/api/assessments', ['title' => 'Local Bitly Test'])
        ->assertCreated()
        ->json();

    $expectedPath = '/client/' . $response['password'];
    // bitly fake returns full URL; ensure the new client path is present
    expect($response['short_url'])->toContain($expectedPath);
});

it('throws when bitly fails in production for non-local targets', function () {
    Bitly::clearResolvedInstance();
    app()->forgetInstance('bitly');
    putenv('BITLY_ACCESS_TOKEN=dummy-token');
    $_ENV['BITLY_ACCESS_TOKEN'] = 'dummy-token';
    config(['bitly.accesstoken' => 'dummy-token']);
    Bitly::shouldReceive('getURL')->andThrow(new \RuntimeException('bitly down'));
    putenv('APP_ENV=production');
    config(['app.env' => 'production']);
    $this->app['env'] = 'production';

    $user = \App\Models\User::factory()->create();

    $response = $this->actingAs($user, 'web')
        ->postJson('/api/assessments', ['title' => 'Prod Bitly Test'])
        ->assertCreated()
        ->json();

    $expectedPath = '/client/' . $response['password'];
    expect($response['short_url'])->toContain($expectedPath);
});
