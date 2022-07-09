<?php

namespace App\Imports;

use App\Models\InflationDataByArea;
use App\Models\Month;
use App\Models\Year;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithStartRow;

class InflationDataByAreaImport implements ToCollection, WithStartRow
{
    public function collection(Collection $rows)
    {
        InflationDataByArea::where([
            'month_id' => Month::where('code', $rows[0][1])->first()->id,
            'year_id' => Year::where('code', $rows[0][0])->first()->id
        ])->delete();

        foreach ($rows as $row) {
            $month = Month::where('code', $row[1])->first();
            $year = Year::where('code', $row[0])->first();
            InflationDataByArea::create([
                'month_id' => $month->id,
                'year_id' => $year->id,
                'area_code' => $row[2],
                'area_name' => $row[3],
                'IHK' => $row[5],
                'INFMOM' => $row[6],
                'INFYTD' => $row[7],
                'INFYOY' => $row[8],
            ]);
        }
    }

    public function startRow(): int
    {
        return 4;
    }
}
