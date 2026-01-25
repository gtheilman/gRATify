<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $driver = Schema::getConnection()->getDriverName();

        if ($driver === 'mysql') {
            // MySQL requires a prefix length for TEXT indexes.
            try {
                Schema::table('presentations', function (Blueprint $table) {
                    $table->dropIndex('presentations_assessment_user_index');
                });
            } catch (\Throwable $e) {
                // Index may not exist yet; ignore.
            }

            DB::statement('CREATE INDEX presentations_assessment_user_index ON presentations (assessment_id, user_id(191))');
            return;
        }

        // SQLite/Postgres can index the full column normally.
        try {
            Schema::table('presentations', function (Blueprint $table) {
                $table->dropIndex('presentations_assessment_user_index');
            });
        } catch (\Throwable $e) {
            // Ignore if missing.
        }
        Schema::table('presentations', function (Blueprint $table) {
            $table->index(['assessment_id', 'user_id'], 'presentations_assessment_user_index');
        });
    }

    public function down(): void
    {
        $driver = Schema::getConnection()->getDriverName();

        if ($driver === 'mysql') {
            try {
                DB::statement('DROP INDEX presentations_assessment_user_index ON presentations');
            } catch (\Throwable $e) {
                // Ignore if missing.
            }
            // Restore full-length index if desired by non-mysql envs.
            return;
        }

        Schema::table('presentations', function (Blueprint $table) {
            $table->dropIndex('presentations_assessment_user_index');
        });
    }
};
