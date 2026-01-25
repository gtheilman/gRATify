<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $driver = Schema::getConnection()->getDriverName();
        if ($driver === 'sqlite') {
            // SQLite cannot add foreign keys to existing tables reliably.
            return;
        }

        Schema::table('assessments', function (Blueprint $table) {
            $table->foreign('user_id', 'assessments_user_id_fk')
                ->references('id')->on('users')
                ->onDelete('restrict');
        });

        Schema::table('questions', function (Blueprint $table) {
            $table->foreign('assessment_id', 'questions_assessment_id_fk')
                ->references('id')->on('assessments')
                ->onDelete('cascade');
        });

        Schema::table('answers', function (Blueprint $table) {
            $table->foreign('question_id', 'answers_question_id_fk')
                ->references('id')->on('questions')
                ->onDelete('cascade');
        });

        Schema::table('presentations', function (Blueprint $table) {
            $table->foreign('assessment_id', 'presentations_assessment_id_fk')
                ->references('id')->on('assessments')
                ->onDelete('cascade');
        });

        Schema::table('attempts', function (Blueprint $table) {
            $table->foreign('presentation_id', 'attempts_presentation_id_fk')
                ->references('id')->on('presentations')
                ->onDelete('cascade');
            $table->foreign('answer_id', 'attempts_answer_id_fk')
                ->references('id')->on('answers')
                ->onDelete('cascade');
        });
    }

    public function down(): void
    {
        $driver = Schema::getConnection()->getDriverName();
        if ($driver === 'sqlite') {
            return;
        }

        Schema::table('attempts', function (Blueprint $table) {
            $table->dropForeign('attempts_presentation_id_fk');
            $table->dropForeign('attempts_answer_id_fk');
        });

        Schema::table('presentations', function (Blueprint $table) {
            $table->dropForeign('presentations_assessment_id_fk');
        });

        Schema::table('answers', function (Blueprint $table) {
            $table->dropForeign('answers_question_id_fk');
        });

        Schema::table('questions', function (Blueprint $table) {
            $table->dropForeign('questions_assessment_id_fk');
        });

        Schema::table('assessments', function (Blueprint $table) {
            $table->dropForeign('assessments_user_id_fk');
        });
    }
};
