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
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('family');
            $table->string('national_code')->nullable();
            $table->string('birth_city')->nullable();
            $table->string('address')->nullable();
            $table->tinyInteger('sarbazi')->nullable();
            $table->tinyInteger('married')->nullable();
            $table->string('degree')->nullable();
            $table->string('reshte')->nullable();
            $table->string('picture')->nullable();
            $table->integer('state')->nullable();
            $table->integer('city')->nullable();
            $table->string('mobile');
            $table->string('sms')->nullable();
            $table->dateTime('birthdate')->nullable();
            $table->rememberToken();
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
