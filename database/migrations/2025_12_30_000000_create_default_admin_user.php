<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        // Seed the default admin user during migration.
        DB::table('users')->updateOrInsert(
            ['email' => 'admin@example.com'],
            [
                'name' => 'Admin User',
                'username' => 'admin',
                'role' => 'admin',
                // Hash for the plaintext password "admin"
                'password' => '$2y$12$Lar5T5y8docuOFsdx98FRevUlRMZRP/40zpowaLJHz2ZtN9b/pww2',
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );
    }

    public function down(): void
    {
        DB::table('users')->where('email', 'admin@example.com')->delete();
    }
};
