<?php

uses()->group('config');

it('keeps phpunit and pest pinned to Laravel 12-compatible majors', function () {
    $composer = json_decode(file_get_contents(base_path('composer.json')), true);
    $requireDev = $composer['require-dev'] ?? [];

    expect($requireDev['phpunit/phpunit'] ?? '')
        ->toContain('^11');

    expect($requireDev['pestphp/pest'] ?? '')
        ->toStartWith('^3');
});
