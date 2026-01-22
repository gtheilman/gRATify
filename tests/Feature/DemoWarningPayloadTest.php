<?php

use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('returns demo warning payload with expected keys', function () {
    $this->getJson('/api/demo-warning')
        ->assertOk()
        ->assertJsonStructure([
            'showWarning',
            'showDemoUsers',
            'showMailpit',
            'mailConfigured',
            'mailEnabled',
        ]);
});
