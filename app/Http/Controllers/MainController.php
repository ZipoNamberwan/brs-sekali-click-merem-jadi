<?php

namespace App\Http\Controllers;

use App\Imports\CurrentDataImport;
use App\Models\Month;
use App\Models\Year;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;

class MainController extends Controller
{
    public function index()
    {
        $currentmonth = date('m');
        $currentyear = date('Y');
        $months = Month::all();
        $years = Year::all();

        return view('brs-index', [
            'months' => $months,
            'years' => $years,
            'currentmonth' => $currentmonth,
            'currentyear' => $currentyear
        ]);
    }

    public function generate(Request $request)
    {
        $request->validate([
            'month' => 'required',
            'year' => 'required',
            'file' => 'required'
        ]);

        Excel::import(new CurrentDataImport, $request->file('file'));

        // $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($request->file('file'));
        // $sheet = $spreadsheet->getActiveSheet();
        // dd($sheet);
    }
}
