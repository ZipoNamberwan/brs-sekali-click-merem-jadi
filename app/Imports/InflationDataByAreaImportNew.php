<?php

namespace App\Imports;

use App\Models\InflationDataByArea;
use App\Models\Month;
use App\Models\Year;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithStartRow;

class InflationDataByAreaImportNew implements ToCollection, WithStartRow
{
    public function collection(Collection $rows)
    {
        $todelete = InflationDataByArea::where([
            'month_id' => Month::where('code', $rows[0][1])->first()->id,
            'year_id' => Year::where('code', $rows[0][0])->first()->id,
            'area_code' => $rows[0][2],
        ])->get()->pluck('id');

        foreach ($rows as $row) {
            if ($row[0] != null && $row[1] != null && $row[2] != null) {
                $month = Month::where('code', $row[1])->first();
                $year = Year::where('code', $row[0])->first();
                InflationDataByArea::create([
                    'month_id' => $month->id,
                    'year_id' => $year->id,
                    'area_code' => $row[2],
                    'area_name' => $row[3],
                    'IHK' => $row[8],
                    'INFMOM' => $row[9],
                    'INFYTD' => $row[10],
                    'INFYOY' => $row[11],
                ]);
            }

            break;
        }

        InflationDataByArea::whereIn('id', $todelete)->delete();
    }

    public function startRow(): int
    {
        return 4;
    }
}
