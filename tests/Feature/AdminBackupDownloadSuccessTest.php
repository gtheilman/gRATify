<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\Response;

uses(RefreshDatabase::class);

it('allows admins to download backups with gzip content type', function () {
    Storage::fake('local');
    $user = User::factory()->create(['role' => 'admin']);
    $dbPath = sys_get_temp_dir() . '/gratify-backup-' . uniqid('', true) . '.sqlite';
    file_put_contents($dbPath, 'sqlite-content');
    config([
        'database.default' => 'sqlite',
        'database.connections.sqlite.database' => $dbPath,
    ]);

    try {
        $this->actingAs($user, 'web')
            ->get('/api/admin/backup/download')
            ->assertStatus(Response::HTTP_OK)
            ->assertHeader('content-type', 'application/gzip');
    } finally {
        @unlink($dbPath);
    }
});
