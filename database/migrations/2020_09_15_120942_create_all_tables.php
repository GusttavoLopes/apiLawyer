<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateAllTables extends Migration
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
            $table->string('avatar')->default('default.png');
            $table->string('email')->unique();
            $table->string('password');
        });
        Schema::create('userfavorites', function (Blueprint $table) {
            $table->id();
            $table->integer('id_user');
            $table->integer('id_lawyer');
        });
        Schema::create('userappointments', function (Blueprint $table) {
            $table->id();
            $table->integer('id_user');
            $table->integer('id_lawyer');
            $table->datetime('ap_datetime');
        });

        Schema::create('lawyers', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('avatar')->default('default.png');
            $table->float('stars')->default(0);
            $table->string('latitude')->nullable();
            $table->string('longitude')->nullable();
        });
        Schema::create('lawyersphotos', function (Blueprint $table) {
            $table->id();
            $table->integer('id_lawyer');
            $table->string('url');
        });
        Schema::create('lawyersreviews', function (Blueprint $table) {
            $table->id();
            $table->integer('id_lawyer');
            $table->float('rate');
        });
        Schema::create('lawyersservices', function (Blueprint $table) {
            $table->id();
            $table->integer('id_lawyer');
            $table->string('name');
            $table->float('price');
        });
        Schema::create('lawyerstestimonials', function (Blueprint $table) {
            $table->id();
            $table->integer('id_lawyer');
            $table->string('name');
            $table->float('rate');
            $table->string('body');
        });
        Schema::create('lawyersavailability', function (Blueprint $table) {
            $table->id();
            $table->integer('id_lawyer');
            $table->integer('weekday');
            $table->text('hours');
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
        Schema::dropIfExists('userfavorites');
        Schema::dropIfExists('userappointments');
        Schema::dropIfExists('lawyers');
        Schema::dropIfExists('lawyersphotos');
        Schema::dropIfExists('lawyersreviews');
        Schema::dropIfExists('lawyersservices');
        Schema::dropIfExists('lawyerstestimonials');
        Schema::dropIfExists('lawyersavailability');
    }
}
