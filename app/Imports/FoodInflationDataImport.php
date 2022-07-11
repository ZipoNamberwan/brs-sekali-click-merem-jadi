<?php

namespace App\Imports;

use App\Models\FoodInflationData;
use App\Models\Month;
use App\Models\Year;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithStartRow;
use Illuminate\Support\Collection;

class FoodInflationDataImport implements ToCollection, WithStartRow
{
    public function collection(Collection $rows)
    {
        FoodInflationData::where([
            'month_id' => Month::where('code', $rows[0][1])->first()->id,
            'year_id' => Year::where('code', $rows[0][0])->first()->id
        ])->delete();

        foreach ($rows as $row) {
            if ($row[0] != null && $row[1] != null && $row[2] != null && $row[2] == '3574') {
                $month = Month::where('code', $row[1])->first();
                $year = Year::where('code', $row[0])->first();
                FoodInflationData::create([
                    'month_id' => $month->id,
                    'year_id' => $year->id,
                    'area_code' => $row[2],
                    'IHK' => $row[3],
                    'INFMOM' => $row[4],
                    'INFYTD' => $row[5],
                    'INFYOY' => $row[6],
                    'ANDILMOM' => $row[7],
                    'ANDILYTD' => $row[8],
                    'ANDILYOY' => $row[9],
                ]);
            }
        }
    }

    public function startRow(): int
    {
        return 2;
    }
}
