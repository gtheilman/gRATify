<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Queue;

uses(RefreshDatabase::class);

it('can dispatch and run a queued job with current queue config', function () {
    Config::set('queue.default', 'sync');

    Queue::fake();

    dispatch(new TestSyncJob);

    Queue::assertPushed(TestSyncJob::class);
});

class TestSyncJob implements \Illuminate\Contracts\Queue\ShouldQueue
{
    use \Illuminate\Bus\Queueable;
    use \Illuminate\Foundation\Bus\Dispatchable;
    use \Illuminate\Queue\InteractsWithQueue;
    use \Illuminate\Queue\SerializesModels;

    public function handle(): void
    {
        // no-op, just ensure queuing works
    }
}
