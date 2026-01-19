<?php

use App\Jobs\GenerateDatabaseBackup;
use Illuminate\Support\Facades\Storage;

it('backs up sqlite databases by gzipping the file', function () {
    Storage::fake('local');
    $dbPath = sys_get_temp_dir() . '/gratify-sqlite-' . uniqid() . '.sqlite';
    file_put_contents($dbPath, 'sqlite-content');

    try {
        $job = new GenerateDatabaseBackup([
            'driver' => 'sqlite',
            'database' => $dbPath,
        ]);
        $path = $job->handle();

        expect($path)->toEndWith('.sqlite.gz');
        Storage::disk('local')->assertExists($path);
    } finally {
        @unlink($dbPath);
    }
});

it('backs up sqlsrv databases using sqlcmd', function () {
    Storage::fake('local');
    $binDir = sys_get_temp_dir() . '/sqlcmd-bin-' . uniqid();
    if (!is_dir($binDir)) {
        mkdir($binDir);
    }
    $script = $binDir . '/sqlcmd';
    $scriptBody = <<<'BASH'
#!/usr/bin/env bash
set -e
query=""
prev=""
for arg in "$@"; do
  if [ "$prev" = "-Q" ]; then
    query="$arg"
    break
  fi
  prev="$arg"
done
path=$(printf "%s" "$query" | sed -n "s/.*TO DISK = N'\\([^']*\\)'.*/\\1/p")
if [ -z "$path" ]; then
  exit 1
fi
mkdir -p "$(dirname "$path")"
echo "backup" > "$path"
BASH;
    file_put_contents($script, $scriptBody);
    chmod($script, 0755);

    $oldPath = getenv('PATH') ?: '';
    putenv("PATH={$binDir}:{$oldPath}");

    try {
        $job = new GenerateDatabaseBackup([
            'driver' => 'sqlsrv',
            'database' => 'testdb',
            'host' => 'localhost',
            'port' => 1433,
            'username' => 'sa',
            'password' => 'secret',
        ]);
        $path = $job->handle();

        expect($path)->toEndWith('.bak.gz');
        Storage::disk('local')->assertExists($path);
    } finally {
        putenv("PATH={$oldPath}");
        @unlink($script);
        @rmdir($binDir);
    }
});
