<?php

use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('renders the password reset page with a CSRF bootstrap request', function () {
    $response = $this->get('/password/reset/test-token?email=user@example.com');

    $response->assertOk();
    $response->assertSee('/csrf-cookie', false);
    $response->assertSee('X-XSRF-TOKEN', false);
    $response->assertSee("credentials: 'same-origin'", false);
});
