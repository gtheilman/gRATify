<?php

declare(strict_types=1);

use Illuminate\Support\Facades\File;

it('does not scan admin-only component dirs for auto-imports', function () {
    $config = File::get(base_path('vite.config.js'));
    // The auto-import config should not include admin-only component dirs.
    expect($config)->not->toMatch('/dirs:\s*\[[^\]]*@layouts/s');
    expect($config)->not->toMatch('/dirs:\s*\[[^\]]*@core\/components/s');
    // But it should include the gratclient components dir.
    expect($config)->toContain("resources/js/gratclient/components");
});
