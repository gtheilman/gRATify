<?php

use Illuminate\Support\Facades\File;

it('uses a single Vite config rooted at the app base', function () {
    $configPath = base_path('vite.config.js');
    expect(File::exists($configPath))->toBeTrue();

    $config = File::get($configPath);

    expect($config)->not()->toContain("base: '/'");
    expect($config)->toContain("input: ['resources/js/main.js'");
});
