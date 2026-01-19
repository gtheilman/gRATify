<?php

use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('serves the SPA application view for arbitrary web routes', function () {
    $this->get('/some/random/path')
        ->assertOk()
        ->assertViewIs('application');
});
