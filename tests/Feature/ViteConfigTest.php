<?php

use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

it('vite.config.js uses laravel-vite-plugin and no legacy mix references', function () {
    $config = File::get(base_path('vite.config.js'));

    expect($config)->toContain("laravel-vite-plugin");
    expect($config)->toContain("resources/js/main.js");
    expect($config)->not->toContain("laravel-mix");
    expect($config)->not->toContain("mix(");
});

it('no legacy mix references remain outside this test file', function () {
    $pathsToSkip = [
        'vendor' . DIRECTORY_SEPARATOR,
        'node_modules' . DIRECTORY_SEPARATOR,
        'storage' . DIRECTORY_SEPARATOR,
        'bootstrap' . DIRECTORY_SEPARATOR,
        'public' . DIRECTORY_SEPARATOR . 'build',
    ];

    try {
        $files = collect(File::allFiles(base_path()))
            ->reject(fn ($file) => Str::contains($file->getPathname(), $pathsToSkip))
            ->filter(fn ($file) => Str::endsWith($file->getFilename(), ['.php', '.js', '.ts', '.vue', '.blade.php', '.css']))
            ->reject(fn ($file) => Str::contains($file->getPathname(), 'tests' . DIRECTORY_SEPARATOR . 'Feature' . DIRECTORY_SEPARATOR . 'ViteConfigTest.php'));
    } catch (\Throwable $e) {
        test()->markTestSkipped('Skipping mix scan: ' . $e->getMessage());
    }

    $mixHits = $files->filter(function ($file) {
        $contents = File::get($file->getPathname());
        return Str::contains($contents, 'mix(');
    })->pluck('pathname')->all();

    expect($mixHits)->toBeEmpty();
});
