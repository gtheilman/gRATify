<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateUsersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (Schema::hasTable('users')) {
            Schema::table('users', function (Blueprint $table) {
                if (!Schema::hasColumn('users', 'role')) {
                    $table->string('role')->nullable()->after('password');
                }
                if (!Schema::hasColumn('users', 'username')) {
                    $table->string('username')->unique()->nullable();
                }
                $table->string('company')->nullable();
            });
            return;
        }

        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // equals displayName
            $table->string('username')->unique();;
            $table->string('company')->nullable();
            $table->string('email')->unique();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password');
            $table->string('role')->nullable();;
            $table->rememberToken()->nullable();
            $table->timestamps();
        });
    }


    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('users');
    }
}
