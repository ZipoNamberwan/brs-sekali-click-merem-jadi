<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('month', function (Blueprint $table) {
            $table->id()->autoincrement();
            $table->string('name');
            $table->string('code');
        });
        Schema::create('year', function (Blueprint $table) {
            $table->id()->autoincrement();
            $table->string('name');
            $table->string('code');
        });
        Schema::create('periode', function (Blueprint $table) {
            $table->id()->autoincrement();
            $table->foreignId('month_id')->constrained('month');
            $table->foreignId('year_id')->constrained('year');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        //
    }
};
