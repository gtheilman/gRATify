<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('appeals', function (Blueprint $table) {
            $table->id();
            $table->timestamps();
            $table->foreignId('presentation_id');
            $table->foreignId('question_id');
            $table->text('body');
            $table->unique(['presentation_id', 'question_id'], 'appeals_presentation_question_unique');
            $table->index('presentation_id', 'appeals_presentation_idx');
            $table->index('question_id', 'appeals_question_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('appeals');
    }
};
