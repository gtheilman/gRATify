<?php

it('defaults broadcasting to log and includes standard connections', function () {
    expect(config('broadcasting.default'))->toBe(env('BROADCAST_DRIVER', 'log'));
    expect(config('broadcasting.connections'))->toHaveKeys(['pusher', 'redis', 'log', 'null']);
});
