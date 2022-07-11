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
        Schema::create('food_inflation', function (Blueprint $table) {
            $table->id()->autoincrement();
            $table->foreignId('month_id')->constrained('month');
            $table->foreignId('year_id')->constrained('year');
            $table->string('area_code');
            $table->double('IHK', 10, 4);
            $table->double('INFMOM', 10, 4);
            $table->double('INFYTD', 10, 4);
            $table->double('INFYOY', 10, 4);
            $table->double('ANDILMOM', 12, 8);
            $table->double('ANDILYTD', 12, 8);
            $table->double('ANDILYOY', 12, 8);
        });
        Schema::create('energy_inflation', function (Blueprint $table) {
            $table->id()->autoincrement();
            $table->foreignId('month_id')->constrained('month');
            $table->foreignId('year_id')->constrained('year');
            $table->string('area_code');
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
        Schema::dropIfExists('food_inflation');
        Schema::dropIfExists('energy_inflation');
    }
};
