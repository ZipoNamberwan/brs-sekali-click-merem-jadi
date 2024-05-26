<?php

namespace App\Imports;

use App\Models\InflationData;
use App\Models\Month;
use App\Models\Year;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithStartRow;

class InflationDataImport implements ToCollection, WithStartRow
{

    public function collection(Collection $rows)
    {
        $todelete = InflationData::where([
            'month_id' => Month::where('code', $rows[0][1])->first()->id,
            'year_id' => Year::where('code', $rows[0][0])->first()->id
        ])->get()->pluck('id');

        foreach ($rows as $row) {
            if ($row[0] != null && $row[1] != null && $row[2] != null) {
                if ($row[2] == '3574') {
                    $month = Month::where('code', $row[1])->first();
                    $year = Year::where('code', $row[0])->first();
                    InflationData::create([
                        'month_id' => $month->id,
                        'year_id' => $year->id,
                        'area_code' => $row[2],
                        'area_name' => $row[3],
                        'item_code' => $row[4],
                        'item_name' => $row[5],
                        'flag' => $row[6],
                        'NK' => $row[7],
                        'IHK' => $row[8],
                        'INFMOM' => $row[9],
                        'INFYTD' => $row[10],
                        'INFYOY' => $row[11],
                        'ANDILMOM' => $row[12],
                        'ANDILYTD' => $row[13],
                        'ANDILYOY' => $row[14],
                    ]);
                }
            }
        }

        InflationData::whereIn('id', $todelete)->delete();
    }

    public function startRow(): int
    {
        return 4;
    }
}
