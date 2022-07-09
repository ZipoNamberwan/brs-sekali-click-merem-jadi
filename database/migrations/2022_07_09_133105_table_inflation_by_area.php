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
        Schema::create('inflation_data_by_area', function (Blueprint $table) {
            $table->id()->autoincrement();
            $table->foreignId('month_id')->constrained('month');
            $table->foreignId('year_id')->constrained('year');
            $table->string('area_code');
            $table->string('area_name');
            $table->double('IHK', 10, 4);
            $table->double('INFMOM', 10, 4);
            $table->double('INFYTD', 10, 4);
            $table->double('INFYOY', 10, 4);
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
