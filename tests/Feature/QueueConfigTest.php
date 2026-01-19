<?php

use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('queue config matches expected defaults for Laravel 12', function () {
    $queue = config('queue');

    expect($queue['default'])->toBe(env('QUEUE_CONNECTION', 'database'));

    expect($queue['connections'])->toHaveKeys(['sync', 'database', 'beanstalkd', 'sqs', 'redis', 'deferred', 'failover']);
    expect($queue['connections']['redis'])->toHaveKeys(['driver', 'connection', 'queue', 'retry_after', 'block_for', 'after_commit']);
    expect($queue['failed']['driver'])->toBe(env('QUEUE_FAILED_DRIVER', 'database-uuids'));
    expect($queue['batching']['table'])->toBe('job_batches');
});
