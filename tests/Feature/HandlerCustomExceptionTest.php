<?php

use App\Exceptions\CustomException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;

uses(RefreshDatabase::class);

it('renders the custom exception view for CustomException', function () {
    $handler = app(\App\Exceptions\Handler::class);

    $response = $handler->render(request(), new CustomException('boom'));

    expect($response->getStatusCode())->toBe(500);
    expect((string) $response->getContent())->toContain('Something went wrong.');
});
