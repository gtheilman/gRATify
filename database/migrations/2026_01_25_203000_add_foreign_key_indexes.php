<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('assessments', function (Blueprint $table) {
            $table->index('user_id', 'assessments_user_id_index');
        });

        Schema::table('questions', function (Blueprint $table) {
            $table->index('assessment_id', 'questions_assessment_id_index');
            $table->index(['assessment_id', 'sequence'], 'questions_assessment_sequence_index');
        });

        Schema::table('answers', function (Blueprint $table) {
            $table->index('question_id', 'answers_question_id_index');
            $table->index(['question_id', 'sequence'], 'answers_question_sequence_index');
        });

        Schema::table('presentations', function (Blueprint $table) {
            $table->index('assessment_id', 'presentations_assessment_id_index');
        });
    }

    public function down(): void
    {
        Schema::table('presentations', function (Blueprint $table) {
            $table->dropIndex('presentations_assessment_id_index');
        });

        Schema::table('answers', function (Blueprint $table) {
            $table->dropIndex('answers_question_sequence_index');
            $table->dropIndex('answers_question_id_index');
        });

        Schema::table('questions', function (Blueprint $table) {
            $table->dropIndex('questions_assessment_sequence_index');
            $table->dropIndex('questions_assessment_id_index');
        });

        Schema::table('assessments', function (Blueprint $table) {
            $table->dropIndex('assessments_user_id_index');
        });
    }
};
