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
        Schema::create('inflation_data', function (Blueprint $table) {
            $table->id()->autoincrement();
            $table->foreignId('month_id')->constrained('month');
            $table->foreignId('year_id')->constrained('year');
            $table->string('area_code');
            $table->string('area_name');
            $table->string('item_code');
            $table->string('item_name');
            $table->integer('flag');
            $table->string('NK');
            $table->double('IHK', 10, 4);
            $table->double('INFMOM', 10, 4);
            $table->double('INFYTD', 10, 4);
            $table->double('INFYOY', 10, 4);
            $table->double('ANDILMOM', 12, 8);
            $table->double('ANDILYTD', 12, 8);
            $table->double('ANDILYOY', 12, 8);
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
