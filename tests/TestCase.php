<?php

namespace Tests;

use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Routing\Middleware\ThrottleRequests;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->guardTestDatabase();
        $this->withoutMiddleware(VerifyCsrfToken::class);
        $this->withoutMiddleware(ThrottleRequests::class);
        $this->withoutMiddleware('throttle');
    }

    protected function guardTestDatabase(): void
    {
        if (env('ALLOW_NON_SQLITE_TEST_DATABASE')) {
            return;
        }

        $driver = (string) config('database.default');
        $database = (string) config("database.connections.$driver.database");

        if ($driver !== 'sqlite' || $database !== ':memory:') {
            throw new \RuntimeException(sprintf(
                'Unsafe test database configuration detected (driver=%s, database=%s). ' .
                'Tests are blocked to prevent data loss. Use sqlite :memory: or set ALLOW_NON_SQLITE_TEST_DATABASE=1 to override.',
                $driver,
                $database,
            ));
        }
    }

    protected function withCsrfToken(array $headers = []): self
    {
        $token = 'test-csrf-token';
        $this->withSession(['_token' => $token]);

        return $this->withHeaders(array_merge($headers, [
            'X-CSRF-TOKEN' => $token,
        ]));
    }
}
