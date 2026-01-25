<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateAssessmentsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('assessments', function (Blueprint $table) {
            $table->id();
            $table->timestamps();
            $table->string('title'); //The name of the quiz
            $table->foreignId('user_id');
            $table->integer('time_limit')->nullable($value = true);
            $table->string('penalty_method')->nullable(); //percent or logarithmic
            $table->mediumText('course')->nullable($value = true);
            $table->mediumText('short_url')->nullable($value = true);
            $table->datetime('scheduled_at')->nullable($value = true);
            $table->longText('memo')->nullable($value = true);
            $table->string('password')->unique();
            $table->boolean('active')->nullable($value = true);
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('assessments');
    }
}
