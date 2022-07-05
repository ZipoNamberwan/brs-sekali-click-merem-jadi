<?php

namespace Database\Seeders;

use App\Models\Month;
use App\Models\Year;
use Illuminate\Database\Seeder;

class MasterSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        Month::create([
            'name' => 'Januari',
            'code' => '01'
        ]);
        Month::create([
            'name' => 'Februari',
            'code' => '02'
        ]);
        Month::create([
            'name' => 'Maret',
            'code' => '03'
        ]);
        Month::create([
            'name' => 'April',
            'code' => '04'
        ]);
        Month::create([
            'name' => 'Mei',
            'code' => '05'
        ]);
        Month::create([
            'name' => 'Juni',
            'code' => '06'
        ]);
        Month::create([
            'name' => 'Juli',
            'code' => '07'
        ]);
        Month::create([
            'name' => 'Agustus',
            'code' => '08'
        ]);
        Month::create([
            'name' => 'September',
            'code' => '09'
        ]);
        Month::create([
            'name' => 'Oktober',
            'code' => '10'
        ]);
        Month::create([
            'name' => 'November',
            'code' => '11'
        ]);
        Month::create([
            'name' => 'Desember',
            'code' => '12'
        ]);
        Year::create([
            'name' => '2020',
            'code' => '2020'
        ]);
        Year::create([
            'name' => '2021',
            'code' => '2021'
        ]);
        Year::create([
            'name' => '2022',
            'code' => '2022'
        ]);
        Year::create([
            'name' => '2023',
            'code' => '2023'
        ]);
    }
}
