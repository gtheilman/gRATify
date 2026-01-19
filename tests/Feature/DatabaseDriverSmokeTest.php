<?php

it('has postgres and sqlserver database connection config keys', function () {
    $pgsql = config('database.connections.pgsql');
    $sqlsrv = config('database.connections.sqlsrv');

    expect($pgsql)->toBeArray()
        ->and($sqlsrv)->toBeArray();

    foreach (['host', 'port', 'database', 'username', 'password'] as $key) {
        expect(array_key_exists($key, $pgsql))->toBeTrue()
            ->and(array_key_exists($key, $sqlsrv))->toBeTrue();
    }
});

it('loads the postgres PDO driver when available', function () {
    if (! extension_loaded('pdo_pgsql')) {
        $this->markTestSkipped('pdo_pgsql extension is not loaded.');
    }

    expect(PDO::getAvailableDrivers())->toContain('pgsql');
});

it('loads the sqlserver PDO driver when available', function () {
    if (! extension_loaded('pdo_sqlsrv')) {
        $this->markTestSkipped('pdo_sqlsrv extension is not loaded.');
    }

    expect(PDO::getAvailableDrivers())->toContain('sqlsrv');
});
