<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('presentations', function (Blueprint $table) {
            $table->index(['assessment_id', 'user_id'], 'presentations_assessment_user_index');
        });
    }

    public function down(): void
    {
        Schema::table('presentations', function (Blueprint $table) {
            $table->dropIndex('presentations_assessment_user_index');
        });
    }
};
