<?php

namespace App\Imports;


use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class FoodAndEnergyInflationDataImport implements WithMultipleSheets
{

    public function sheets(): array
    {
        return [
            0 => new EnergyInflationDataImport(),
            1 => new FoodInflationDataImport(),
        ];
    }
}
