<?php

namespace App\Http\Controllers;

use App\Jobs\GenerateDatabaseBackup;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\Response;

/**
 * Streams an on-demand DB backup for admins with explicit failure messaging.
 */
class AdminBackupController extends Controller
{
    /**
     * Generate and download a compressed database backup for admins.
     */
    public function download(Request $request): Response
    {
        $user = auth('web')->user();
        $role = $user?->role;
        $normalizedRole = $role === 'poobah' ? 'admin' : $role;
        if (! $user || $normalizedRole !== 'admin') {
            return $this->errorResponse('forbidden', null, 403);
        }

        $connection = config('database.default');
        $db = config("database.connections.{$connection}");

        // Fail fast if DB config is missing (avoids confusing process errors).
        if (! $db || empty($db['database'])) {
            return response()->json(['message' => 'Database configuration not found.'], 500);
        }

        try {
            $path = (new GenerateDatabaseBackup($db, $this->newBackupPath($db['driver'] ?? $connection)))->handle();
        } catch (\Throwable $e) {
            return response()->json(['message' => $e->getMessage()], 500);
        }

        // Guard against unexpected job returns so download() doesn't explode.
        if ($path === '') {
            return response()->json(['message' => 'Backup failed: invalid backup path returned.'], 500);
        }

        if (! Storage::disk('local')->exists($path)) {
            return response()->json(['message' => 'Backup failed: file not found after export.'], 500);
        }

        return $this->downloadAndDelete($path);
    }

    private function newBackupPath(string $driver): string
    {
        if ($driver === 'sqlite') {
            return 'backups/db-backup-sqlite.gz';
        }

        $suffix = match ($driver) {
            'sqlsrv' => 'bak.gz',
            default => 'sql.gz',
        };

        return 'backups/db-backup-' . now()->format('Ymd_His') . '.' . $suffix;
    }

    private function downloadAndDelete(string $path): Response
    {
        $disk = Storage::disk('local');
        $localPath = $disk->path($path);
        $filename = basename($path);

        return response()
            ->download($localPath, $filename, ['Content-Type' => 'application/gzip'])
            ->deleteFileAfterSend(true);
    }
}
