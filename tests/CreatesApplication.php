<?php

namespace Tests;

use Illuminate\Contracts\Console\Kernel;

trait CreatesApplication
{
    /**
     * Creates the application.
     *
     * @return \Illuminate\Foundation\Application
     */
    public function createApplication()
    {
        $routesCache = __DIR__ . '/../bootstrap/cache/routes-v7.php';
        if (file_exists($routesCache)) {
            @unlink($routesCache);
        }
        $testingRoutesCache = __DIR__ . '/../bootstrap/cache/routes.testing.php';
        if (file_exists($testingRoutesCache)) {
            @unlink($testingRoutesCache);
        }

        $app = require __DIR__ . '/../bootstrap/app.php';

        $app->make(Kernel::class)->bootstrap();

        return $app;
    }
}
