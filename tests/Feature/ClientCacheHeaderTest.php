<?php

declare(strict_types=1);

use Illuminate\Http\Response;

it('sets short-term cache headers for client pages', function () {
    $response = $this->get('/client/demo123');

    $response->assertStatus(Response::HTTP_OK);
    expect($response->headers->get('Cache-Control'))
        ->toContain('public')
        ->toContain('max-age=600');
});

it('does not force cache headers on admin root', function () {
    $response = $this->get('/');

    $response->assertStatus(Response::HTTP_OK);
    expect($response->headers->get('Cache-Control'))
        ->not->toContain('max-age=600');
});
