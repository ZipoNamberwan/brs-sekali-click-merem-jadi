<?php

namespace App\Imports;

use App\Models\InflationData;
use App\Models\InflationDataByArea;
use App\Models\Month;
use App\Models\Year;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithStartRow;

class InflationNewBase implements ToCollection, WithStartRow
{
    public function collection(Collection $rows)
    {
        foreach ($rows as $row) {
            if ($row[0] != null && $row[1] != null && $row[2] != null) {
                $code = $row[2];
                $area = $row[0];

                $i = 0;
                $startMonth = 5;

                foreach ($row as $cell) {
                    if ($i >= $startMonth) {

                        $inf = InflationData::where([
                            'month_id' => ($i - ($startMonth - 1)),
                            'year_id' => Year::where(['code' => '2023'])->first()->id,
                            'item_code' => $code,
                            'area_code' => $area,
                        ])->first();

                        if ($inf != null) {
                            $inf->update([
                                'IHK' => $cell,
                                'new_base' => true,
                            ]);
                        }
                    }
                    $i++;
                }
            }
        }
    }

    public function startRow(): int
    {
        return 4;
    }
}
