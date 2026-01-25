<?php

use Illuminate\Support\Facades\File;

it('password page loads the browser build of qrcode', function () {
    $path = resource_path('js/pages/assessments/password.vue');

    expect(File::exists($path))->toBeTrue();

    $contents = File::get($path);

    expect($contents)
        ->toContain("import('qrcode/lib/browser')")
        ->not->toContain("import QRCode from 'qrcode'");
});
