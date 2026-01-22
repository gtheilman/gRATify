<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\Process\ExecutableFinder;
use Symfony\Component\Process\Process;
use Throwable;

/**
 * Creates a compressed database backup across supported drivers.
 */
class GenerateDatabaseBackup implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    private ?string $targetPath = null;

    /**
     * @param array<string, mixed> $config
     */
    public function __construct(private readonly array $config, ?string $targetPath = null)
    {
        $this->targetPath = $targetPath;
    }

    public function handle(): string
    {
        $disk = Storage::disk('local');
        $disk->makeDirectory('backups');
        $backupDir = $disk->path('backups');
        if (! is_dir($backupDir) || ! is_writable($backupDir)) {
            // Fail fast to avoid spawning dump processes when storage isn't writable.
            throw new \RuntimeException(
                'Backup failed: storage/app/private/backups is not writable. '
                . 'Ensure the directory exists and is writable by the app user.'
            );
        }

        $driver = $this->config['driver'] ?? 'mysql';

        if ($driver === 'sqlite') {
            return $this->backupSqlite($disk);
        }

        if ($driver === 'sqlsrv') {
            return $this->backupSqlsrv($disk, $backupDir);
        }

        // MySQL/Postgres path: run the native client, then compress output.
        [$command, $env] = $this->buildDumpCommand($driver, $this->config);

        $process = new Process($command);
        $process->setTimeout(120);
        $process->run(null, $env);

        if (! $process->isSuccessful()) {
            throw new \RuntimeException('Backup failed: ' . $process->getErrorOutput());
        }

        $sql = $process->getOutput();
        $compressed = gzencode($sql);
        if ($compressed === false) {
            throw new \RuntimeException('Backup failed: unable to compress dump output.');
        }

        $filename = $this->targetPath ?: 'backups/db-backup-' . now()->format('Ymd_His') . '.sql.gz';
        $disk->put($filename, $compressed);

        return $filename;
    }

    public function failed(Throwable $exception): void
    {
        $disk = Storage::disk('local');
        $disk->makeDirectory('backups');
        // Persist a simple error log to aid support/debugging.
        $message = sprintf(
            "[%s] Backup job failed: %s\n",
            now()->toDateTimeString(),
            $exception->getMessage()
        );
        $disk->append('backups/backup-errors.log', $message);
    }

    /**
     * @param array<string, mixed> $config
     * @return array{0: list<string>, 1: array<string, string>}
     */
    private function buildDumpCommand(string $driver, array $config): array
    {
        $finder = new ExecutableFinder();

        return match ($driver) {
            'mysql' => [
                [
                    $this->resolveMysqlDumpBinary($finder),
                    '--user=' . $config['username'],
                    '--host=' . ($config['host'] ?? '127.0.0.1'),
                    '--port=' . ($config['port'] ?? 3306),
                    '--no-tablespaces',
                    $config['database'],
                ],
                [
                    'MYSQL_PWD' => $config['password'] ?? '',
                ],
            ],
            'pgsql' => [
                [
                    $this->resolveBinary($finder, 'pg_dump', 'PostgreSQL client is not installed (pg_dump not found).'),
                    '-h', $config['host'] ?? '127.0.0.1',
                    '-p', (string) ($config['port'] ?? 5432),
                    '-U', $config['username'],
                    $config['database'],
                ],
                [
                    'PGPASSWORD' => $config['password'] ?? '',
                ],
            ],
            default => throw new \RuntimeException("Unsupported database driver: {$driver}"),
        };
    }

    private function backupSqlite(FilesystemAdapter $disk): string
    {
        $dbPath = $this->config['database'] ?? '';
        if (! $dbPath || $dbPath === ':memory:') {
            throw new \RuntimeException('Backup failed: SQLite database path is not configured.');
        }
        if (! file_exists($dbPath)) {
            throw new \RuntimeException("Backup failed: SQLite database not found at {$dbPath}.");
        }

        $filename = $this->targetPath ?: 'backups/db-backup-' . now()->format('Ymd_His') . '.sqlite.gz';
        $sql = file_get_contents($dbPath);
        if ($sql === false) {
            throw new \RuntimeException("Backup failed: unable to read SQLite database at {$dbPath}.");
        }
        $compressed = gzencode($sql);
        if ($compressed === false) {
            throw new \RuntimeException('Backup failed: unable to compress SQLite database.');
        }
        $disk->put($filename, $compressed);

        return $filename;
    }

    private function backupSqlsrv(FilesystemAdapter $disk, string $backupDir): string
    {
        $finder = new ExecutableFinder();
        $sqlcmd = $this->resolveBinary($finder, 'sqlcmd', 'SQL Server tools are not installed (sqlcmd not found).');

        $database = $this->config['database'] ?? '';
        if (! $database) {
            throw new \RuntimeException('Backup failed: SQL Server database name is not configured.');
        }

        $host = $this->config['host'] ?? 'localhost';
        $port = $this->config['port'] ?? 1433;
        $server = $port ? "{$host},{$port}" : $host;
        $username = $this->config['username'] ?? '';
        $password = $this->config['password'] ?? '';

        $bakPath = $backupDir . DIRECTORY_SEPARATOR . 'db-backup-' . now()->format('Ymd_His') . '.bak';
        $query = sprintf("BACKUP DATABASE [%s] TO DISK = N'%s' WITH INIT, COPY_ONLY", $database, $bakPath);

        $process = new Process([
            $sqlcmd,
            '-S', $server,
            '-U', $username,
            '-P', $password,
            '-Q', $query,
        ]);
        $process->setTimeout(120);
        $process->run();

        if (! $process->isSuccessful()) {
            throw new \RuntimeException('Backup failed: ' . $process->getErrorOutput());
        }

        if (! file_exists($bakPath)) {
            throw new \RuntimeException('Backup failed: SQL Server backup file was not created.');
        }

        $filename = $this->targetPath ?: 'backups/db-backup-' . now()->format('Ymd_His') . '.bak.gz';
        $bakContents = file_get_contents($bakPath);
        if ($bakContents === false) {
            @unlink($bakPath);
            throw new \RuntimeException('Backup failed: unable to read SQL Server backup file.');
        }
        $compressed = gzencode($bakContents);
        if ($compressed === false) {
            @unlink($bakPath);
            throw new \RuntimeException('Backup failed: unable to compress SQL Server backup file.');
        }
        $disk->put($filename, $compressed);
        @unlink($bakPath);

        return $filename;
    }

    private function resolveMysqlDumpBinary(ExecutableFinder $finder): string
    {
        $binary = $finder->find('mysqldump') ?: $finder->find('mariadb-dump');
        if (!$binary) {
            throw new \RuntimeException(
                "MySQL client is not installed (mysqldump/mariadb-dump not found).\n"
                . "Install the client tools on the host:\n"
                . "Debian/Ubuntu: apt-get install default-mysql-client\n"
                . "RHEL/CentOS/Fedora: dnf install mysql\n"
                . "Alpine: apk add mysql-client\n"
            );
        }
        return $binary;
    }

    private function resolveBinary(ExecutableFinder $finder, string $binary, string $message): string
    {
        $resolved = $finder->find($binary);
        if (!$resolved) {
            $installHint = match ($binary) {
                'pg_dump' => "Install the client tools on the host:\n"
                    . "Debian/Ubuntu: apt-get install postgresql-client\n"
                    . "RHEL/CentOS/Fedora: dnf install postgresql\n"
                    . "Alpine: apk add postgresql-client\n",
                'sqlcmd' => "Install the client tools on the host:\n"
                    . "Debian/Ubuntu: apt-get install mssql-tools18\n"
                    . "RHEL/CentOS/Fedora: dnf install mssql-tools18\n",
                default => '',
            };
            throw new \RuntimeException(trim($message . "\n" . $installHint));
        }
        return $resolved;
    }
}
