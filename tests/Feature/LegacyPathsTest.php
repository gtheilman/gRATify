<?php

use Illuminate\Http\Response;

// Legacy paths are no longer served; we expect 404 to avoid stale links.
it('returns 404 for legacy /gratserver path', function () {
    $this->get('/gratserver')
        ->assertStatus(Response::HTTP_NOT_FOUND);
});

it('returns 404 for legacy /gratclient path', function () {
    $this->get('/gratclient/tbl/demo123')
        ->assertStatus(Response::HTTP_NOT_FOUND);
});
