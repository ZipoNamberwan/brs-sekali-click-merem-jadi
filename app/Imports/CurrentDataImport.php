<?php

namespace App\Imports;

use App\Models\CurrentData;
use App\Models\Month;
use App\Models\Year;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithStartRow;

class CurrentDataImport implements ToModel, WithStartRow
{
    /**
     * @param array $row
     *
     * @return \Illuminate\Database\Eloquent\Model|null
     */
    public function model(array $row)
    {
        $month = Month::where('code', $row[1])->first();
        $year = Year::where('code', $row[0])->first();
        return new CurrentData([
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

    public function startRow(): int
    {
        return 2;
    }
}
