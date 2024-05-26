<?php

namespace App\Http\Controllers;

use App\Imports\InflationDataByAreaImportNew;
use App\Imports\InflationDataImport;
use App\Imports\InflationNewBase;
use App\Models\InflationData;
use App\Models\InflationDataByArea;
use App\Models\Month;
use App\Models\Year;
use DateTime;
use Exception;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;

class UploadController extends Controller
{
    function showUpload(Request $request)
    {
        $selectedMonth = null;
        if ($request->month == null) {
            $selectedMonth = date('Y-m-d');
        } else {
            $selectedMonth = date('Y') . '-' . sprintf('%02d', $request->month) . '-15';
        }
        $current = $this->getCustomMonth($selectedMonth);
        // $current = $this->getCustomMonth('2024-05-01');

        // Clone the original date for safe modifications
        $previousMonth = clone $current;
        $sameMonthLastYear = clone $current;
        $sameMonthTwoYearsAgo = clone $current;

        // Modify the cloned dates
        $previousMonth->modify('-1 month');
        $sameMonthLastYear->modify('-1 year');
        $sameMonthTwoYearsAgo->modify('-2 years');

        $currentstatus = count(InflationData::where([
            'month_id' => Month::where(['code' => $current->format('m')])->first()->id,
            'year_id' => Year::where(['code' => $current->format('Y')])->first()->id,
        ])->get()) > 0;

        $previousMonthstatus = count(InflationData::where([
            'month_id' => Month::where(['code' => $previousMonth->format('m')])->first()->id,
            'year_id' => Year::where(['code' => $previousMonth->format('Y')])->first()->id,
        ])->get()) > 0;

        $n1status = count(InflationData::where([
            'month_id' => Month::where(['code' => $sameMonthLastYear->format('m')])->first()->id,
            'year_id' => Year::where(['code' => $sameMonthLastYear->format('Y')])->first()->id,
        ])->get()) > 0;

        $n2status = count(InflationData::where([
            'month_id' => Month::where(['code' => $sameMonthTwoYearsAgo->format('m')])->first()->id,
            'year_id' => Year::where(['code' => $sameMonthTwoYearsAgo->format('Y')])->first()->id,
        ])->get()) > 0;

        $areas = InflationDataByArea::where([
            'month_id' => Month::where(['code' => $current->format('m')])->first()->id,
            'year_id' => Year::where(['code' => $current->format('Y')])->first()->id,
        ])->get();

        $arealength = count($areas);
        $areaname = $areas->pluck('area_name');

        $newbasecollection = InflationData::where('month_id', Month::where(['code' => $current->format('m')])->first()->id)
            ->where('year_id', Year::where(['code' => '2023'])->first()->id)
            ->where(function ($query) {
                $query->where('flag', 0)
                    ->orWhere('flag', 1);
            })->get()->pluck('new_base');

        $newbase = $newbasecollection->isNotEmpty() && $newbasecollection->every(function ($value) {
            return $value === 1;
        });

        $data = [
            'current' => $this->translateDateToIndonesian($current->format('Y-m-d')),
            'previousMonth' => $this->translateDateToIndonesian($previousMonth->format('Y-m-d')),
            'n1' => $this->translateDateToIndonesian($sameMonthLastYear->format('Y-m-d')),
            'n2' => $this->translateDateToIndonesian($sameMonthTwoYearsAgo->format('Y-m-d')),
            'currentstatus' => $currentstatus,
            'previousMonthstatus' => $previousMonthstatus,
            'n1status' => $n1status,
            'n2status' => $n2status,
            'arealength' => $arealength,
            'areanames' => $areaname->implode(', '),
            'newbase' => $newbase,
            'months' => Month::all(),
            'currentmonth' => $current->format('m')
        ];


        return view('brs-upload-new', $data);
    }

    function uploadInflation(Request $request)
    {
        $request->validate([
            'file-inf' => 'required',
        ]);

        try {
            if ($request->file('file-inf') != null) {
                Excel::import(new InflationDataImport, $request->file('file-inf'));
            }

            return redirect('/upload-new')->with('success-upload', 'Upload Data Inflasi ' . $request->label . ' Berhasil');
        } catch (Exception $e) {
            return redirect('/upload-new')->with('error-upload', 'Upload Data Inflasi ' . $request->label . ' Gagal. Cek apakah file yang diupload sudah sesuai');
        }
    }

    function uploadArea(Request $request)
    {
        $request->validate([
            'file-inf-area.*' => 'required',
        ]);

        try {
            if ($request->hasFile('file-inf-area')) {
                foreach ($request->file('file-inf-area') as $file) {
                    Excel::import(new InflationDataByAreaImportNew, $file);
                }
            }

            return redirect('/upload-new')->with('success-upload', 'Upload Data Inflasi ' . $request->label . ' Kab/Kota Berhasil');
        } catch (Exception $e) {
            return redirect('/upload-new')->with('error-upload', 'Upload Data Inflasi ' . $request->label . ' Kab/Kota Gagal. Cek apakah file yang diupload sudah sesuai');
        }
    }

    function uploadNewBase(Request $request)
    {
        $request->validate([
            'file-inf-base' => 'required',
        ]);

        try {
            if ($request->hasFile('file-inf-base')) {
                Excel::import(new InflationNewBase, $request->file('file-inf-base'));
            }

            return redirect('/upload-new')->with('success-upload', 'Upload Data IHK Tahun Dasar Baru (IHK 2022=100) Berhasil');
        } catch (Exception $e) {
            dd($e);
            return redirect('/upload-new')->with('error-upload', 'Upload Data IHK Tahun Dasar Baru (IHK 2022=100) Gagal. Cek apakah file yang diupload sudah sesuai');
        }
    }

    function getCustomMonth($date)
    {
        // Create a DateTime object from the input date
        $dateTime = new DateTime($date);

        // Extract the day and month from the date
        $day = (int)$dateTime->format('d');
        $month = (int)$dateTime->format('m');

        // Determine the month based on the day and month
        if ($day >= 8) {
            return $dateTime; // Current month if day is 8 or later
        } else {
            // Move to the previous month
            $dateTime->modify('-1 month');
            return $dateTime; // Previous month if day is before 8
        }
    }

    function translateDateToIndonesian($date)
    {
        // Array mapping English month names to Indonesian month names
        $months = [
            'January' => 'Januari',
            'February' => 'Februari',
            'March' => 'Maret',
            'April' => 'April',
            'May' => 'Mei',
            'June' => 'Juni',
            'July' => 'Juli',
            'August' => 'Agustus',
            'September' => 'September',
            'October' => 'Oktober',
            'November' => 'November',
            'December' => 'Desember'
        ];

        // Create a DateTime object from the input date
        $dateTime = new DateTime($date);

        // Format the date to "Month YYYY"
        $formattedDate = $dateTime->format('F Y');

        // Extract the month and year
        list($month, $year) = explode(' ', $formattedDate);

        // Translate the month to Indonesian
        $translatedMonth = $months[$month];

        // Return the translated date
        return "$translatedMonth $year";
    }
}
