<?php

use Illuminate\Support\Facades\File;

it('produces separate admin and client vite entries when manifest exists', function () {
    $manifestPath = public_path('build/manifest.json');

    if (! File::exists($manifestPath)) {
        $this->markTestSkipped('Vite manifest not present; run npm run build to generate it.');
    }

    $manifest = json_decode(File::get($manifestPath), true);

    expect($manifest)->toBeArray();

    // Both admin and client entry points should be present as distinct bundles.
    expect($manifest)->toHaveKey('resources/js/main.js');
    expect($manifest)->toHaveKey('resources/js/gratclient/main.js');

    $adminFile = $manifest['resources/js/main.js']['file'] ?? null;
    $clientFile = $manifest['resources/js/gratclient/main.js']['file'] ?? null;

    expect($adminFile)->not->toBeNull();
    expect($clientFile)->not->toBeNull();
    expect($adminFile)->not->toBe($clientFile);
});
