<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('attempts', function (Blueprint $table) {
            $table->unique(['presentation_id', 'answer_id'], 'attempts_presentation_answer_unique');
            $table->index('presentation_id', 'attempts_presentation_idx');
            $table->index('answer_id', 'attempts_answer_idx');
        });
    }

    public function down(): void
    {
        Schema::table('attempts', function (Blueprint $table) {
            $table->dropUnique('attempts_presentation_answer_unique');
            $table->dropIndex('attempts_presentation_idx');
            $table->dropIndex('attempts_answer_idx');
        });
    }
};
