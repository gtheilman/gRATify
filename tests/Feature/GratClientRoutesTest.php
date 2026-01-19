<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Routing\ViewController;
use App\Http\Controllers\ApplicationController;

uses(RefreshDatabase::class);

it('serves the SPA shell for client and admin paths', function () {
    $clientRoute = app('router')->getRoutes()->match(Request::create('/client/demo'));
    $adminRoute = app('router')->getRoutes()->match(Request::create('/dashboard'));

    expect($clientRoute->getAction()['controller'] ?? null)->toBe(ApplicationController::class);
    expect($adminRoute->getAction()['controller'] ?? null)->toBe(ApplicationController::class);
    expect($clientRoute->uri())->toBe('{any?}');
    expect($adminRoute->uri())->toBe('{any?}');
});

it('serves SPA shell when manifest exists (skip if missing)', function () {
    if (! file_exists(public_path('build/manifest.json'))) {
        $this->markTestSkipped('Vite manifest not present; run npm run build to generate it.');
    }

    $this->get('/client/demo')
        ->assertOk()
        ->assertSee('gRAT - TBL Team Assessments');
});
