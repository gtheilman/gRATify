<?php

use Illuminate\Support\Facades\File;

it('CI workflow runs optimize:clear and pest', function () {
    $path = base_path('.github/workflows/ci.yml');
    expect(File::exists($path))->toBeTrue();

    $yaml = File::get($path);
    expect($yaml)->toContain('optimize:clear');
    expect($yaml)->toContain('./vendor/bin/pest');
});
