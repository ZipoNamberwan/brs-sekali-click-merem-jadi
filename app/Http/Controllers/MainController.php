<?php

namespace App\Http\Controllers;

use App\Imports\CurrentDataImport;
use App\Helpers\Utilities;
use App\Models\CurrentData;
use App\Models\Month;
use App\Models\Year;
use Exception;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;

class MainController extends Controller
{
    public function index()
    {
        $currentmonth = Month::where(['code' => date('m')])->first()->id;
        $currentyear = Year::where(['code' => date('Y')])->first()->id;
        $months = Month::all();
        $years = Year::all();

        return view('brs-index', [
            'months' => $months,
            'years' => $years,
            'currentmonth' => $currentmonth,
            'currentyear' => $currentyear
        ]);
    }

    public function showUpload()
    {
        return view('brs-upload');
    }

    public function upload(Request $request)
    {
        $request->validate([
            'file' => 'required'
        ]);

        try {
            Excel::import(new CurrentDataImport, $request->file('file'));
            return redirect('/upload')->with('success-upload', 'Upload Berhasil');
        } catch (Exception $e) {
            return redirect('/upload')->with('error-upload', 'Upload Gagal. ' . $e);
        }
    }

    public function generate(Request $request)
    {
        $request->validate([
            'month' => 'required',
            'year' => 'required',
        ]);

        $date = Year::find($request->year)->code . '-' . Month::find($request->month)->code . '-01';
        $currentyear = date('Y', strtotime($date . ' -1 months'));
        $currentmonth = date('m', strtotime($date . ' -1 months'));

        $currentdata = CurrentData::where([
            'month_id' => Month::where(['code' => $currentmonth])->first()->id,
            'year_id' => Year::where(['code' => $currentyear])->first()->id
        ]);

        $prevdate = $currentyear . '-' . $currentmonth . '-01';
        $prevyear = date('Y', strtotime($prevdate . ' -1 months'));
        $prevmonth = date('m', strtotime($prevdate . ' -1 months'));

        $kelompok = $currentdata->where(['flag' => 1])->get();
        $kelompok_result = [];
        foreach ($kelompok as $k) {
            $paragraph_array = [];

            if ($k->INFMOM == 0) {
                $paragraph_array['first'] = 'Kelompok ini pada ' . $k->monthdetail->name . ' ' . $k->yeardetail->name . ' tidak mengalami perubahan atau tidak memberikan andil/sumbangan terhadap inflasi Kota Probolinggo.';
            } else {
                //paragraf pertama

                $previousdata = CurrentData::where([
                    'month_id' => Month::where(['code' => $prevmonth])->first()->id,
                    'year_id' => Year::where(['code' => $prevyear])->first()->id,
                    'item_code' => $k->item_code
                ])->first();

                $paragraph_array['first'] = 'Kelompok ini pada ' . $k->monthdetail->name . ' ' . $k->yeardetail->name .
                    ' mengalami ' . Utilities::getInfTypeString($k->INFMOM) . ' sebesar ' .
                    Utilities::getAbsoluteValue($k->INFMOM) . ' persen atau terjadi ' . Utilities::getInfTrendString($k->INFMOM) .
                    ' indeks dari ' . $previousdata->IHK . ' pada ' . $previousdata->monthdetail->name . ' ' . $previousdata->yeardetail->name .
                    ' menjadi ' . $k->IHK . ' pada ' . $k->monthdetail->name . ' ' . $k->yeardetail->name;

                //paragraf pertama

                //paragraf kedua
                $subkelompok =  CurrentData::where([
                    'month_id' => Month::where(['code' => $currentmonth])->first()->id,
                    'year_id' => Year::where(['code' => $currentyear])->first()->id,
                    'flag' => 2,
                ])->where('item_code', 'LIKE', $k->item_code . '%')->get();

                $subkelompok_group = [
                    'inf' => [],
                    'def' => [],
                    'still' => []
                ];
                foreach ($subkelompok as $sk) {
                    if ($sk->INFMOM > 0) $subkelompok_group['inf'][] = $sk;
                    else if ($sk->INFMOM == 0) $subkelompok_group['still'][] = $sk;
                    else $subkelompok_group['def'][] = $sk;
                }
                $sentence = [
                    'first' => [],
                    'second' => []
                ];
                foreach ($subkelompok_group as $key => $value) {
                    $s = [];
                    foreach ($value as $v) {
                        $s[] = 'subkelompok ' . strtolower($v->item_name) . ($v->INFMOM != 0 ? (' sebesar ' . $v->INFMOM . ' persen') : '');
                    }
                    if (count($value) != 0) {
                        if ($key == 'inf') {
                            $sentence['first'][] = count($value) . ' subkelompok mengalami inflasi';
                            $sentence['second'][] = 'Subkelompok yang mengalami inflasi yaitu: ' . Utilities::getSentenceFromArray($s);
                        } else if ($key == 'def') {
                            $sentence['first'][] = count($value) . ' subkelompok mengalami deflasi';
                            $sentence['second'][] = 'Subkelompok yang mengalami deflasi yaitu: ' . Utilities::getSentenceFromArray($s);
                        } else {
                            $sentence['first'][] = count($value) . ' subkelompok tidak mengalami perubahan';
                            $sentence['second'][] = 'Subkelompok yang tidak mengalami perubahan yaitu: ' . Utilities::getSentenceFromArray($s);
                        }
                    }
                }

                $paragraph_array['second'] = 'Dari ' . count($subkelompok) . ' subkelompok pada kelompok ini, ' . Utilities::getSentenceFromArray($sentence['first']) . '. ' . Utilities::getSentenceFromArray($sentence['second'], '. ', '. Sedangkan ');
                //paragraf kedua

                //paragraf ketiga
                $komoditas = CurrentData::where([
                    'month_id' => Month::where(['code' => $currentmonth])->first()->id,
                    'year_id' => Year::where(['code' => $currentyear])->first()->id,
                    'flag' => 3,
                ])->where('item_code', 'LIKE', $k->item_code . '%')->get();

                $komoditas_group = [
                    'inf' => [],
                    'def' => []
                ];
                foreach ($komoditas as $kom) {
                    if ($kom->INFMOM > 0) $komoditas_group['inf'][] = $kom;
                    else if ($kom->INFMOM < 0) $komoditas_group['def'][] = $kom;
                }

                $sentence = [];
                foreach ($komoditas_group as $key => $value) {
                    $s = [];
                    foreach ($value as $v) {
                        $s[] = strtolower($v->item_name) . ' sebesar ' . $v->ANDILMOM . ' persen';
                    }
                    if ($key == 'inf') {
                        if (count($value) > 1) {
                            $sentence[] = 'Komoditas yang memberikan andil/sumbangan inflasi, yaitu ' . Utilities::getSentenceFromArray($s);
                        } else if (count($value)  == 1) {
                            $sentence[] = 'Satu-satunya komoditas yang memberikan andil/sumbangan inflasi, yaitu ' . Utilities::getSentenceFromArray($s);
                        } else {
                            $sentence[] = 'Tidak ada komoditas yang memberikan andil/sumbangan inflasi';
                        }
                    } else if ($key == 'def') {
                        if (count($value) > 1) {
                            $sentence[] = 'Komoditas yang memberikan andil/sumbangan deflasi, yaitu ' . Utilities::getSentenceFromArray($s);
                        } else if (count($value)  == 1) {
                            $sentence[] = 'Satu-satunya komoditas yang memberikan andil/sumbangan deflasi, yaitu ' . Utilities::getSentenceFromArray($s);
                        } else {
                            $sentence[] = 'Sementara, tidak ada komoditas yang memberikan andil/sumbangan deflasi';
                        }
                    }
                }
                $paragraph_array['third'] = 'Kelompok ini pada ' . $k->monthdetail->name . ' ' . $k->yeardetail->name . ' memberikan andil/sumbangan ' . Utilities::getInfTypeString($k->ANDILMOM) . ' sebesar ' . Utilities::getAbsoluteValue($k->ANDILMOM) . ' persen. ' . Utilities::getSentenceFromArray($sentence, '. ', '. ') . '.';
                //paragraf ketiga
            }

            $kelompok_result[$k->item_name] = $paragraph_array;
        }
        dd($kelompok_result);
    }
}
