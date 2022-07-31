<?php

namespace App\Http\Controllers;

use App\Imports\InflationDataImport;
use App\Helpers\Utilities;
use App\Imports\FoodAndEnergyInflationDataImport;
use App\Imports\InflationDataByAreaImport;
use App\Models\EnergyInflationData;
use App\Models\FoodInflationData;
use App\Models\InflationData;
use App\Models\InflationDataByArea;
use App\Models\Month;
use App\Models\Year;
use Exception;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use PhpOffice\PhpSpreadsheet\Spreadsheet;

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
            'file-inf' => 'required',
            'file-inf-area' => 'required'
        ]);

        try {
            Excel::import(new InflationDataImport, $request->file('file-inf'));
            Excel::import(new InflationDataByAreaImport, $request->file('file-inf-area'));
            Excel::import(new FoodAndEnergyInflationDataImport, $request->file('file-inf-food-energy'));

            return redirect('/upload')->with('success-upload', 'Upload Berhasil');
        } catch (Exception $e) {
            return redirect('/upload')->with('error-upload', 'Upload Gagal. Cek apakah file yang diupload sudah sesuai');
        }
    }

    public function generate(Request $request)
    {
        $request->validate([
            'month' => 'required',
            'year' => 'required',
        ]);

        $date = Year::find($request->year)->code . '-' . Month::find($request->month)->code . '-01';
        $currentyear = date('Y', strtotime($date));
        $currentmonth = date('m', strtotime($date));

        $prevdate = $currentyear . '-' . $currentmonth . '-01';
        $prevyear = date('Y', strtotime($prevdate . ' -1 months'));
        $prevmonth = date('m', strtotime($prevdate . ' -1 months'));

        $yearminus1 = date('Y', strtotime($date . ' -12 months'));
        $yearminus2 = date('Y', strtotime($date . ' -24 months'));
        $yearminus3 = date('Y', strtotime($date . ' -36 months'));

        $currentmonth = Month::where(['code' => $currentmonth])->first();
        $currentyear = Year::where(['code' => $currentyear])->first();

        $prevmonth = Month::where(['code' => $prevmonth])->first();
        $prevyear = Year::where(['code' => $prevyear])->first();
        $yearminus1 = Year::where(['code' => $yearminus1])->first();
        $yearminus2 = Year::where(['code' => $yearminus2])->first();
        $yearminus3 = Year::where(['code' => $yearminus3])->first();

        $result = [];

        $infcurrent = InflationData::where([
            'month_id' => $currentmonth->id,
            'year_id' => $currentyear->id,
            'flag' => 0
        ])->get();
        $infprev = InflationData::where([
            'month_id' => $prevmonth->id,
            'year_id' => $prevyear->id,
            'flag' => 0
        ])->get();
        $infyearminus1 = InflationData::where([
            'month_id' => $currentmonth->id,
            'year_id' => $yearminus1->id,
            'flag' => 0
        ])->get();
        $infyearminus2 = InflationData::where([
            'month_id' => $currentmonth->id,
            'year_id' => $yearminus2->id,
            'flag' => 0
        ])->get();

        if (count($infcurrent) > 0 && count($infprev) > 0 && count($infyearminus1) > 0 && count($infyearminus2) > 0) {

            $infcurrent = InflationData::where([
                'month_id' => $currentmonth->id,
                'year_id' => $currentyear->id,
                'flag' => 0
            ])->first();
            $infprev = InflationData::where([
                'month_id' => $prevmonth->id,
                'year_id' => $prevyear->id,
                'flag' => 0
            ])->first();

            //Judul
            $result['Perkembangan Indeks Harga Konsumen Kota Probolinggo '
                . $infcurrent->monthdetail->name . ' ' . $infcurrent->yeardetail->name] =
                $infcurrent->monthdetail->name . ' ' . $infcurrent->yeardetail->name .
                ' ' . Utilities::getInfTypeString($infcurrent->INFMOM) . ' bulanan Kota Probolinggo sebesar ' .
                Utilities::getFormattedNumber($infcurrent->INFMOM) . ' persen, ' . Utilities::getInfTypeString($infcurrent->INFYTD) . ' tahun kalender sebesar ' .
                Utilities::getFormattedNumber($infcurrent->INFYTD) . ' persen dan ' . Utilities::getInfTypeString($infcurrent->INFYOY) . ' tahunan sebesar ' .
                Utilities::getFormattedNumber($infcurrent->INFYOY) . ' persen.';
            //Judul

            //Intro
            $result['Intro'] = [
                'first' => '',
                'second' => '',
                'third' => '',
            ];

            $infbyarea = InflationDataByArea::where([
                'month_id' => $currentmonth->id,
                'year_id' => $currentyear->id
            ])->get();

            $jatimarea = [
                'inf' => collect(),
                'def' => collect()
            ];

            foreach ($infbyarea as $area) {
                if (substr($area->area_code, 0, 2) == '35') {
                    if ($area->INFMOM > 0)
                        $jatimarea['inf']->push($area);
                    else if ($area->INFMOM < 0)
                        $jatimarea['def']->push($area);
                }
            }

            $jatimarea['inf'] = $jatimarea['inf']->sort(function ($a, $b) {
                if ($a->INFMOM == $b->INFMOM) {
                    return 0;
                }
                return ($a->INFMOM < $b->INFMOM) ? 1 : -1;
            });

            $jatimarea['def'] = $jatimarea['def']->sort(function ($a, $b) {
                if ($a->INFMOM == $b->INFMOM) {
                    return 0;
                }
                return ($a->INFMOM < $b->INFMOM) ? -1 : 1;
            });

            $result['Intro']['first'] = 'Pada ' . $infcurrent->monthdetail->name . ' ' . $infcurrent->yeardetail->name .
                ' Kota Probolinggo terjadi ' . Utilities::getInfTypeString($infcurrent->INFMOM) .
                ' sebesar ' . Utilities::getFormattedNumber($infcurrent->INFMOM) .
                ' persen dengan Indeks Harga Konsumen (IHK) sebesar ' . Utilities::getFormattedNumber($infcurrent->IHK) .

                '. Dari ' . (count($jatimarea['inf']) + count($jatimarea['def'])) . ' kota IHK di Jawa Timur, ' .
                (count($jatimarea['inf']) > 0 ? ((count($jatimarea['inf']) == (count($jatimarea['inf']) + count($jatimarea['def'])) ? 'seluruh' : (count($jatimarea['inf']))) . ' kota mengalami inflasi') : '') .
                (count($jatimarea['inf']) > 0 && count($jatimarea['def']) > 0 ? ' dan ' : '') .
                (count($jatimarea['def']) > 0 ? ((count($jatimarea['def']) == (count($jatimarea['def']) + count($jatimarea['def'])) ? 'seluruh' : (count($jatimarea['def']))) . ' kota mengalami deflasi') : '') .
                '.' .

                (count($jatimarea['inf']) > 0 ? (count($jatimarea['inf']) == 1 ?
                    (' Inflasi terjadi di ' . Utilities::getAreaType($jatimarea['inf']->first()->area_code) . ' ' . ucfirst(strtolower($jatimarea['inf']->first()->area_name)) . ' sebesar ' . Utilities::getFormattedNumber($jatimarea['inf']->first()->INFMOM) . ' persen dengan IHK sebesar ' . Utilities::getFormattedNumber($jatimarea['inf']->first()->IHK) . '. ')
                    : (' Inflasi tertinggi terjadi di ' . Utilities::getAreaType($jatimarea['inf']->first()->area_code) . ' ' .
                        ucfirst(strtolower($jatimarea['inf']->first()->area_name)) . ' sebesar ' . Utilities::getFormattedNumber($jatimarea['inf']->first()->INFMOM) .
                        ' persen dengan IHK sebesar ' . Utilities::getFormattedNumber($jatimarea['inf']->first()->IHK) .

                        ' dan inflasi terendah terjadi di ' . Utilities::getAreaType($jatimarea['inf']->last()->area_code) . ' ' .
                        ucfirst(strtolower($jatimarea['inf']->last()->area_name)) . ' sebesar ' . Utilities::getFormattedNumber($jatimarea['inf']->last()->INFMOM) .
                        ' persen dengan IHK sebesar ' . Utilities::getFormattedNumber($jatimarea['inf']->last()->IHK) . '.')) : '') .

                (count($jatimarea['def']) > 0 ? (count($jatimarea['def']) == 1 ?
                    (' Deflasi terjadi di ' . Utilities::getAreaType($jatimarea['def']->first()->area_code) . ' ' . ucfirst(strtolower($jatimarea['def']->first()->area_name)) . ' sebesar ' . Utilities::getFormattedNumber($jatimarea['def']->first()->INFMOM) . ' persen dengan IHK sebesar ' . Utilities::getFormattedNumber($jatimarea['def']->first()->IHK) . '. ')
                    : (' Deflasi tertinggi terjadi di ' . Utilities::getAreaType($jatimarea['def']->first()->area_code) . ' ' .
                        ucfirst(strtolower($jatimarea['def']->first()->area_name)) . ' sebesar ' . Utilities::getFormattedNumber($jatimarea['def']->first()->INFMOM) .
                        ' persen dengan IHK sebesar ' . Utilities::getFormattedNumber($jatimarea['def']->first()->IHK) .

                        ' dan deflasi terendah terjadi di ' . Utilities::getAreaType($jatimarea['def']->last()->area_code) . ' ' .
                        ucfirst(strtolower($jatimarea['def']->last()->area_name)) . ' sebesar ' . Utilities::getFormattedNumber($jatimarea['def']->last()->INFMOM) .
                        ' persen dengan IHK sebesar ' . Utilities::getFormattedNumber($jatimarea['def']->last()->IHK) . '.')) : '');

            $result['Intro']['third'] = 'Tingkat ' . Utilities::getInfTypeString($infcurrent->INFYTD) . ' tahun kalender '
                . $infcurrent->monthdetail->name . ' ' . $infcurrent->yeardetail->name .
                ' sebesar ' . Utilities::getFormattedNumber($infcurrent->INFYTD) .
                ' persen dan tingkat ' . Utilities::getInfTypeString($infcurrent->INFYOY) . ' tahun ke tahun (' . $infcurrent->monthdetail->name . ' ' . $infcurrent->yeardetail->name .
                ' terhadap ' . $infcurrent->monthdetail->name . ' ' . $yearminus1->name . ') sebesar ' .
                Utilities::getFormattedNumber($infcurrent->INFYOY) . ' persen.';
            //Intro

            $infcurrent = InflationData::where([
                'month_id' => $currentmonth->id,
                'year_id' => $currentyear->id,
            ])->get();
            $infprev = InflationData::where([
                'month_id' => $prevmonth->id,
                'year_id' => $prevyear->id,
            ])->get();


            //Bab 1. Paragraf pertama
            $infcurrent = InflationData::where([
                'month_id' => $currentmonth->id,
                'year_id' => $currentyear->id,
                'flag' => 0
            ])->first();
            $infprev = InflationData::where([
                'month_id' => $prevmonth->id,
                'year_id' => $prevyear->id,
                'flag' => 0
            ])->first();
            $first_pg = 'Perkembangan harga berbagai komoditas pada ' . $infcurrent->monthdetail->name . ' ' . $infcurrent->yeardetail->name .
                ' secara umum menunjukkan adanya ' . Utilities::getInfTrendString($infcurrent->INFMOM) .
                '. Berdasarkan hasil pemantauan BPS di pasar tradisional dan pasar modern di Kota Probolinggo yaitu: Pasar Baru; Pasar Wonoasih; dan GM Hypermart, pada ' .
                $infcurrent->monthdetail->name . ' ' . $infcurrent->yeardetail->name . ' terjadi ' .
                Utilities::getInfTypeString($infcurrent->INFMOM) . ' sebesar ' .
                Utilities::getFormattedNumber($infcurrent->INFMOM) . ' persen, atau terjadi ' .
                Utilities::getInfTrendString($infcurrent->INFMOM) . ' Indeks Harga Konsumen (IHK) dari ' .
                Utilities::getFormattedNumber($infprev->IHK) . ' pada ' . $infprev->monthdetail->name . ' ' . $infprev->yeardetail->name . ' menjadi ' .
                Utilities::getFormattedNumber($infcurrent->IHK) . ' pada ' . $infcurrent->monthdetail->name . ' ' . $infcurrent->yeardetail->name .
                '. Tingkat ' . Utilities::getInfTypeString($infcurrent->INFYTD) . ' tahun kalender ' . $infcurrent->monthdetail->name . ' ' . $infcurrent->yeardetail->name . ' sebesar ' . Utilities::getFormattedNumber($infcurrent->INFYTD) .
                ' persen dan tingkat ' . Utilities::getInfTypeString($infcurrent->INFYOY) . ' tahun ke tahun (' . $infcurrent->monthdetail->name . ' ' . $infcurrent->yeardetail->name .
                ' terhadap ' . $infcurrent->monthdetail->name  . ' ' . $yearminus1->name . ') sebesar ' . Utilities::getFormattedNumber($infcurrent->INFYOY) . ' persen.';

            $result['Indeks Harga Konsumen/Inflasi Menurut Kelompok']['first'] = $first_pg;

            //Bab 1. Paragraf pertama

            //Bab 1. Paragraf kedua

            $infcurrent = InflationData::where([
                'month_id' => $currentmonth->id,
                'year_id' => $currentyear->id,
                'flag' => 0
            ])->first();
            $kelinfcurrent = InflationData::where([
                'month_id' => $currentmonth->id,
                'year_id' => $currentyear->id,
                'flag' => 1
            ])->get();
            $kelompok_group = [
                'inf' => [],
                'def' => [],
                'still' => []
            ];

            foreach ($kelinfcurrent as $k) {
                if ($k->INFMOM > 0) $kelompok_group['inf'][] = $k;
                else if ($k->INFMOM < 0) $kelompok_group['def'][] = $k;
                else $kelompok_group['still'][] = $k;
            }
            $sentence_group = [
                'inf' => [],
                'def' => [],
                'still' => []
            ];

            foreach ($kelompok_group as $key => $value) {
                $s = [];
                foreach ($value as $v) {
                    if ($key != 'still')
                        $s[] = 'kelompok ' . strtolower($v->item_name) . ' sebesar ' . Utilities::getFormattedNumber($v->INFMOM) . ' persen';
                    else $s[] = 'kelompok ' . strtolower($v->item_name);
                }
                $sentence_group[$key] = Utilities::getSentenceFromArray($s, '; ', '; dan ');
            }

            $second_pg = ucfirst(Utilities::getInfTypeString($infcurrent->INFMOM)) . ' terjadi karena adanya ' .
                Utilities::getInfTrendString($infcurrent->INFMOM) .
                ' harga yang ditunjukkan oleh ' . Utilities::getInfTrendString($infcurrent->INFMOM) . ' indeks ' .
                (Utilities::isInflation($infcurrent->INFMOM) ? count($kelompok_group['inf']) : count($kelompok_group['def'])) .
                ' kelompok pengeluaran, yaitu: ' . (Utilities::isInflation($infcurrent->INFMOM) ? $sentence_group['inf'] : $sentence_group['def']) .

                ((!Utilities::isInflation($infcurrent->INFMOM) ? count($kelompok_group['inf']) : count($kelompok_group['def'])) > 0 ?
                    ('. Sedangkan ' . (!Utilities::isInflation($infcurrent->INFMOM) ? count($kelompok_group['inf']) : count($kelompok_group['def'])) .
                        ' kelompok pengeluaran yang mengalami ' . Utilities::getInfTrendString($infcurrent->INFMOM, true) . ' indeks adalah ' .
                        (!Utilities::isInflation($infcurrent->INFMOM) ? $sentence_group['inf'] : $sentence_group['def'])) : '') .

                (count($kelompok_group['still']) > 0 ?
                    ('. Sementara itu, ' . count($kelompok_group['still']) . ' kelompok pengeluaran yang tidak mengalami perubahan indeks, yaitu: ' . $sentence_group['still'] . '.') : '.');

            $result['Indeks Harga Konsumen/Inflasi Menurut Kelompok']['second'] = $second_pg;
            $result['Intro']['second'] = $second_pg;

            //Bab 1. Paragraf kedua

            //Bab 1. Paragraf keempat
            $sentence_group = [
                'inf' => [],
                'def' => [],
                'still' => []
            ];

            foreach ($kelompok_group as $key => $value) {
                $s = [];
                foreach ($value as $v) {
                    if ($key != 'still')
                        $s[] = 'kelompok ' . strtolower($v->item_name) . ' sebesar ' . Utilities::getFormattedNumber($v->ANDILMOM, 4) . ' persen';
                    else $s[] = 'kelompok ' . strtolower($v->item_name);
                }
                $sentence_group[$key] = Utilities::getSentenceFromArray($s, '; ', '; dan ');
            }

            $fourth_pg = 'Pada ' . $infcurrent->monthdetail->name . ' ' . $infcurrent->yeardetail->name . ' dari '
                . count($kelinfcurrent) . ' kelompok pengeluaran, ' .
                ((Utilities::isInflation($infcurrent->INFMOM) ? count($kelompok_group['inf']) : count($kelompok_group['def'])) > 0 ? (((Utilities::isInflation($infcurrent->INFMOM) ? count($kelompok_group['inf']) : count($kelompok_group['def']))) . ' kelompok memberikan andil/sumbangan ' . Utilities::getInfTypeString($infcurrent->INFMOM) . ', ') : '') .
                ((!Utilities::isInflation($infcurrent->INFMOM) ? count($kelompok_group['inf']) : count($kelompok_group['def'])) > 0 ? (((!Utilities::isInflation($infcurrent->INFMOM) ? count($kelompok_group['inf']) : count($kelompok_group['def']))) . ' kelompok memberikan andil/sumbangan ' . Utilities::getInfTypeString($infcurrent->INFMOM, true) . ', ') : '') .
                (count($kelompok_group['still']) > 0 ? 'dan ' . count($kelompok_group['still']) . ' kelompok tidak memberikan andil/sumbangan terhadap ' . Utilities::getInfTypeString($infcurrent->INFMOM) . ' Kota Probolinggo' : '') .


                ((Utilities::isInflation($infcurrent->INFMOM) ? count($kelompok_group['inf']) : count($kelompok_group['def'])) > 0 ? ('. Kelompok pengeluaran yang memberikan andil/sumbangan ' . Utilities::getInfTypeString($infcurrent->INFMOM) . ', yaitu: ' . (Utilities::isInflation($infcurrent->INFMOM) ? $sentence_group['inf'] : $sentence_group['def']) . '.')  : '') .

                ((!Utilities::isInflation($infcurrent->INFMOM) ? count($kelompok_group['inf']) : count($kelompok_group['def'])) > 0 ? (' Kelompok pengeluaran yang memberikan andil/sumbangan ' . Utilities::getInfTypeString($infcurrent->INFMOM, true) . ', yaitu: ' . (!Utilities::isInflation($infcurrent->INFMOM) ? $sentence_group['inf'] : $sentence_group['def']) . '.')  : '') .

                (count($kelompok_group['still']) > 0 ? (' Sementara kelompok pengeluaran yang tidak memberikan andil/sumbangan terhadap ' . Utilities::getInfTypeString($infcurrent->INFMOM) . ' Kota Probolinggo, yaitu ' . $sentence_group['still'] . '.') : '');

            $result['Indeks Harga Konsumen/Inflasi Menurut Kelompok']['fourth'] = $fourth_pg;

            //Bab 1. Paragraf keempat

            //Bab 1. Paragraf ketiga



            //Bab 1. Paragraf ketiga

            //Bab 1. Detail Inflasi per Kelompok
            $infcurrent = InflationData::where([
                'month_id' => $currentmonth->id,
                'year_id' => $currentyear->id
            ]);
            $kelompok = $infcurrent->where(['flag' => 1])->get();
            $kelompok_result = [];
            foreach ($kelompok as $k) {
                $paragraph_array = [];

                if ($k->INFMOM == 0) {
                    $paragraph_array['first'] = 'Kelompok ini pada ' . $k->monthdetail->name . ' ' . $k->yeardetail->name . ' tidak mengalami perubahan atau tidak memberikan andil/sumbangan terhadap inflasi Kota Probolinggo.';
                } else {
                    //paragraf pertama

                    $infprev = InflationData::where([
                        'month_id' => $prevmonth->id,
                        'year_id' => $prevyear->id,
                        'item_code' => $k->item_code
                    ])->first();

                    $paragraph_array['first'] = 'Kelompok ini pada ' . $k->monthdetail->name . ' ' . $k->yeardetail->name .
                        ' mengalami ' . Utilities::getInfTypeString($k->INFMOM) . ' sebesar ' .
                        Utilities::getFormattedNumber($k->INFMOM) . ' persen atau terjadi ' . Utilities::getInfTrendString($k->INFMOM) .
                        ' indeks dari ' . Utilities::getFormattedNumber($infprev->IHK) . ' pada ' . $infprev->monthdetail->name . ' ' . $infprev->yeardetail->name .
                        ' menjadi ' . Utilities::getFormattedNumber($k->IHK) . ' pada ' . $k->monthdetail->name . ' ' . $k->yeardetail->name;

                    //paragraf pertama

                    //paragraf kedua
                    $subkelompok =  InflationData::where([
                        'month_id' => $currentmonth->id,
                        'year_id' => $currentyear->id,
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
                            $s[] = 'subkelompok ' . strtolower($v->item_name) . (($v->INFMOM != 0 ? (' sebesar ' . Utilities::getFormattedNumber($v->INFMOM) . ' persen') : ''));
                        }
                        if (count($value) != 0) {
                            if ($key == 'inf') {
                                $sentence['first'][] = count($value) . ' subkelompok mengalami inflasi';
                                $sentence['second'][] = 'Subkelompok yang mengalami inflasi yaitu: ' . Utilities::getSentenceFromArray($s, '; ');
                            } else if ($key == 'def') {
                                $sentence['first'][] = count($value) . ' subkelompok mengalami deflasi';
                                $sentence['second'][] = 'Subkelompok yang mengalami deflasi yaitu: ' . Utilities::getSentenceFromArray($s, '; ');
                            } else {
                                $sentence['first'][] = count($value) . ' subkelompok tidak mengalami perubahan';
                                $sentence['second'][] = 'Subkelompok yang tidak mengalami perubahan yaitu: ' . Utilities::getSentenceFromArray($s, '; ');
                            }
                        }
                    }

                    $paragraph_array['second'] = 'Dari ' . count($subkelompok) . ' subkelompok pada kelompok ini, ' . Utilities::getSentenceFromArray($sentence['first']) . '. ' . Utilities::getSentenceFromArray($sentence['second'], '. ', '. Sedangkan ');
                    //paragraf kedua

                    //paragraf ketiga
                    $komoditas = InflationData::where([
                        'month_id' => $currentmonth->id,
                        'year_id' => $currentyear->id,
                        'flag' => 3,
                    ])->where('item_code', 'LIKE', $k->item_code . '%')->get();

                    $komoditas_group = [
                        'inf' => collect(),
                        'def' => collect()
                    ];
                    foreach ($komoditas as $kom) {
                        if ($kom->INFMOM > 0) $komoditas_group['inf']->push($kom);
                        else if ($kom->INFMOM < 0) $komoditas_group['def']->push($kom);
                    }

                    $komoditas_group['inf'] = $komoditas_group['inf']->sort(function ($a, $b) {
                        if ($a->ANDILMOM == $b->ANDILMOM) {
                            return 0;
                        }
                        return ($a->ANDILMOM < $b->ANDILMOM) ? 1 : -1;
                    });

                    $komoditas_group['def'] = $komoditas_group['def']->sort(function ($a, $b) {
                        if ($a->ANDILMOM == $b->ANDILMOM) {
                            return 0;
                        }
                        return ($a->ANDILMOM < $b->ANDILMOM) ? -1 : 1;
                    });

                    if ($k->item_code == '01') {
                        $komoditas_group['inf'] = $komoditas_group['inf']->take(10);
                        $komoditas_group['def'] = $komoditas_group['def']->take(10);
                    }

                    $sentence = [];
                    $domninant_wd = $k->item_code == '01' ? 'dominan' : '';

                    foreach ($komoditas_group as $key => $value) {
                        $s = [];
                        foreach ($value as $v) {
                            $s[] = strtolower($v->item_name) . ' sebesar ' . Utilities::getFormattedNumber($v->ANDILMOM, 4) . ' persen';
                        }
                        if ($key == 'inf') {
                            if (count($value) > 1) {
                                $sentence[] = 'Komoditas yang ' . $domninant_wd . ' memberikan andil/sumbangan inflasi, yaitu ' . Utilities::getSentenceFromArray($s);
                            } else if (count($value)  == 1) {
                                $sentence[] = 'Satu-satunya komoditas yang memberikan andil/sumbangan inflasi, yaitu ' . Utilities::getSentenceFromArray($s);
                            } else {
                                $sentence[] = 'Tidak ada komoditas yang memberikan andil/sumbangan inflasi';
                            }
                        } else if ($key == 'def') {
                            if (count($value) > 1) {
                                $sentence[] = 'Komoditas yang ' . $domninant_wd . ' memberikan andil/sumbangan deflasi, yaitu ' . Utilities::getSentenceFromArray($s);
                            } else if (count($value)  == 1) {
                                $sentence[] = 'Satu-satunya komoditas yang memberikan andil/sumbangan deflasi, yaitu ' . Utilities::getSentenceFromArray($s);
                            } else {
                                $sentence[] = 'Sementara, tidak ada komoditas yang memberikan andil/sumbangan deflasi';
                            }
                        }
                    }
                    $paragraph_array['third'] = 'Kelompok ini pada ' . $k->monthdetail->name . ' ' . $k->yeardetail->name . ' memberikan andil/sumbangan ' . Utilities::getInfTypeString($k->ANDILMOM) . ' sebesar ' . Utilities::getFormattedNumber($k->ANDILMOM, 4) . ' persen. ' . Utilities::getSentenceFromArray($sentence, '. ', '. ') . '.';
                    //paragraf ketiga
                }

                $kelompok_result[$k->item_name] = $paragraph_array;
            }
            //Bab 1. Detail Inflasi per Kelompok

            $result['Indeks Harga Konsumen/Inflasi Menurut Kelompok']['kelompok_result'] = $kelompok_result;

            //Bab 2
            $infcurrent = InflationData::where([
                'month_id' => $currentmonth->id,
                'year_id' => $currentyear->id,
                'flag' => 0
            ])->first();
            $infyearminus1 = InflationData::where([
                'month_id' => $currentmonth->id,
                'year_id' => $yearminus1->id,
                'flag' => 0
            ])->first();
            $infyearminus2 = InflationData::where([
                'month_id' => $currentmonth->id,
                'year_id' => $yearminus2->id,
                'flag' => 0
            ])->first();

            $sentence = 'Tingkat ' . Utilities::getInfTypeString($infcurrent->INFYTD) . ' tahun kalender ' .
                $infcurrent->monthdetail->name . ' ' . $infcurrent->yeardetail->name .
                ' sebesar ' . Utilities::getFormattedNumber($infcurrent->INFYTD) .
                ' persen dan tingkat ' . Utilities::getInfTypeString($infcurrent->INFYOY) . ' tahun ke tahun (' . $infcurrent->monthdetail->name . ' ' . $infcurrent->yeardetail->name .
                ' terhadap ' . $infcurrent->monthdetail->name . ' ' . $yearminus1->name . ') sebesar ' .
                Utilities::getFormattedNumber($infcurrent->INFYOY) . ' persen. ' .
                'Sedangkan tingkat ' . Utilities::getInfTypeString($infyearminus1->INFYTD) . ' tahun kalender ' . $infyearminus1->monthdetail->name . ' ' . $infyearminus1->yeardetail->name . ' adalah sebesar ' . Utilities::getFormattedNumber($infyearminus1->INFYTD) . ' persen dan tingkat ' . Utilities::getInfTypeString($infyearminus2->INFYTD) . ' tahun kalender ' . $infyearminus2->monthdetail->name . ' ' . $infyearminus2->yeardetail->name . ' adalah sebesar ' . Utilities::getFormattedNumber($infyearminus2->INFYTD) . ' persen. ' .
                'Sementara itu, tingkat ' . Utilities::getInfTypeString($infyearminus1->INFYOY) . ' tahun ke tahun untuk ' . $infyearminus1->monthdetail->name . ' ' . $infyearminus1->yeardetail->name . ' terhadap ' . $infyearminus2->monthdetail->name . ' ' . $infyearminus2->yeardetail->name . ' adalah sebesar ' . Utilities::getFormattedNumber($infyearminus1->INFYOY) . ' persen dan tingkat ' . Utilities::getInfTypeString($infyearminus2->INFYOY) . ' tahun ke tahun untuk ' . $infyearminus2->monthdetail->name . ' ' . $infyearminus2->yeardetail->name . ' terhadap ' . $infyearminus2->monthdetail->name . ' ' . $yearminus3->name . ' adalah sebesar ' . Utilities::getFormattedNumber($infyearminus2->INFYOY) . ' persen.';

            $result['Perbandingan Inflasi Tahunan'] = $sentence;

            //Bab 2

            //Bab 3
            $result['Indeks Harga Konsumen dan Inflasi Antarkota di Jawa Timur'] = [];

            $intro = 'Pada ' . $infcurrent->monthdetail->name . ' ' . $infcurrent->yeardetail->name . ' secara nasional Indeks Harga Konsumen (IHK) dihitung pada 90 kota IHK, dengan penghitungan tahun dasar 2018=100.';
            $result['Indeks Harga Konsumen dan Inflasi Antarkota di Jawa Timur'][] = $intro;

            $area_sentence = [
                'inf' => [],
                'def' => []
            ];
            foreach ($area_sentence as $key => $value) {
                for ($i = 1; $i < count($jatimarea[$key]); $i++) {
                    $area_sentence[$key][] = Utilities::getAreaType($jatimarea[$key][$i]->area_code) . ' ' . ucfirst(strtolower($jatimarea[$key][$i]->area_name)) . ' sebesar ' . Utilities::getFormattedNumber($jatimarea[$key][$i]->INFMOM);
                }
            }
            // dd($jatimarea);

            $sentence =
                'Dari ' . (count($jatimarea['inf']) + count($jatimarea['def'])) . ' kota IHK di Jawa Timur, ' .
                (count($jatimarea['inf']) > 0 ? ((count($jatimarea['inf']) == (count($jatimarea['inf']) + count($jatimarea['def'])) ? 'seluruh' : (count($jatimarea['inf']))) . ' kota mengalami inflasi') : '') .
                (count($jatimarea['inf']) > 0 && count($jatimarea['def']) > 0 ? ' dan ' : '') .
                (count($jatimarea['def']) > 0 ? ((count($jatimarea['def']) == (count($jatimarea['def']) + count($jatimarea['def'])) ? 'seluruh' : (count($jatimarea['def']))) . ' kota mengalami deflasi') : '') .
                '.' .

                (count($jatimarea['inf']) > 0 ? (count($jatimarea['inf']) == 1 ?
                    (' Inflasi terjadi di ' . Utilities::getAreaType($jatimarea['inf']->first()->area_code) . ' ' . ucfirst(strtolower($jatimarea['inf']->first()->area_name)) . ' sebesar ' . Utilities::getFormattedNumber($jatimarea['inf']->first()->INFMOM) . ' persen. ')
                    : (' Inflasi tertinggi terjadi di ' . Utilities::getAreaType($jatimarea['inf']->first()->area_code) . ' ' .
                        ucfirst(strtolower($jatimarea['inf']->first()->area_name)) . ' sebesar ' . Utilities::getFormattedNumber($jatimarea['inf']->first()->INFMOM) .
                        ' persen diikuti ' . Utilities::getSentenceFromArray($area_sentence['inf']) . '.')) : '') .

                (count($jatimarea['def']) > 0 ? (count($jatimarea['def']) == 1 ?
                    (' Deflasi terjadi di ' . Utilities::getAreaType($jatimarea['def']->first()->area_code) . ' ' . ucfirst(strtolower($jatimarea['def']->first()->area_name)) . ' sebesar ' . Utilities::getFormattedNumber($jatimarea['def']->first()->INFMOM) . ' persen.')
                    : (' Deflasi tertinggi terjadi di ' . Utilities::getAreaType($jatimarea['def']->first()->area_code) . ' ' .
                        ucfirst(strtolower($jatimarea['def']->first()->area_name)) . ' sebesar ' . Utilities::getFormattedNumber($jatimarea['def']->first()->INFMOM) .
                        ' persen diikuti ' . Utilities::getSentenceFromArray($area_sentence['def']) . '.')) : '') .
                ' (Lihat Tabel 3)';

            $result['Indeks Harga Konsumen dan Inflasi Antarkota di Jawa Timur']['Jawa Timur'] = $sentence;


            $infbyarea = InflationDataByArea::where([
                'month_id' => $currentmonth->id,
                'year_id' => $currentyear->id
            ])->get();

            $jawaarea = [
                'inf' => collect(),
                'def' => collect()
            ];

            foreach ($infbyarea as $area) {
                if (substr($area->area_code, 0, 1) == '3') {
                    if ($area->INFMOM > 0)
                        $jawaarea['inf']->push($area);
                    else if ($area->INFMOM < 0)
                        $jawaarea['def']->push($area);
                }
            }

            $jawaarea['inf'] = $jawaarea['inf']->sort(function ($a, $b) {
                if ($a->INFMOM == $b->INFMOM) {
                    return 0;
                }
                return ($a->INFMOM < $b->INFMOM) ? 1 : -1;
            });

            $jawaarea['def'] = $jawaarea['def']->sort(function ($a, $b) {
                if ($a->INFMOM == $b->INFMOM) {
                    return 0;
                }
                return ($a->INFMOM < $b->INFMOM) ? -1 : 1;
            });

            $sentence = 'Pada ' . $infcurrent->monthdetail->name . ' ' . $infcurrent->yeardetail->name .
                ' dari seluruh kota IHK di wilayah Pulau Jawa yang berjumlah ' . (count($jawaarea['inf']) + count($jawaarea['def'])) . ', ' .
                (count($jawaarea['inf']) > 0 ? ((count($jawaarea['inf']) == (count($jawaarea['inf']) + count($jawaarea['def'])) ? 'seluruh' : (count($jawaarea['inf']))) . ' kota mengalami inflasi') : '') .
                (count($jawaarea['inf']) > 0 && count($jawaarea['def']) > 0 ? ' dan ' : '') .
                (count($jawaarea['def']) > 0 ? ((count($jawaarea['def']) == (count($jawaarea['def']) + count($jawaarea['def'])) ? 'seluruh' : (count($jawaarea['def']))) . ' kota mengalami deflasi') : '') .
                '.' .

                (count($jawaarea['inf']) > 0 ? (count($jawaarea['inf']) == 1 ?
                    (' Inflasi terjadi di ' . Utilities::getAreaType($jawaarea['inf']->first()->area_code) . ' ' . ucfirst(strtolower($jawaarea['inf']->first()->area_name)) . ' sebesar ' . Utilities::getFormattedNumber($jawaarea['inf']->first()->INFMOM) . ' persen dengan IHK sebesar ' . Utilities::getFormattedNumber($jawaarea['inf']->first()->IHK) . '. ')
                    : (' Inflasi tertinggi terjadi di ' . Utilities::getAreaType($jawaarea['inf']->first()->area_code) . ' ' .
                        ucfirst(strtolower($jawaarea['inf']->first()->area_name)) . ' sebesar ' . Utilities::getFormattedNumber($jawaarea['inf']->first()->INFMOM) .
                        ' persen dengan IHK sebesar ' . Utilities::getFormattedNumber($jawaarea['inf']->first()->IHK) .

                        ' dan inflasi terendah terjadi di ' . Utilities::getAreaType($jawaarea['inf']->last()->area_code) . ' ' .
                        ucfirst(strtolower($jawaarea['inf']->last()->area_name)) . ' sebesar ' . Utilities::getFormattedNumber($jawaarea['inf']->last()->INFMOM) .
                        ' persen dengan IHK sebesar ' . Utilities::getFormattedNumber($jawaarea['inf']->last()->IHK) . '.')) : '') .

                (count($jawaarea['def']) > 0 ? (count($jawaarea['def']) == 1 ?
                    (' Deflasi terjadi di ' . Utilities::getAreaType($jawaarea['def']->first()->area_code) . ' ' . ucfirst(strtolower($jawaarea['def']->first()->area_name)) . ' sebesar ' . Utilities::getFormattedNumber($jawaarea['def']->first()->INFMOM) . ' persen dengan IHK sebesar ' . Utilities::getFormattedNumber($jawaarea['def']->first()->IHK) . '. ')
                    : (' Deflasi tertinggi terjadi di ' . Utilities::getAreaType($jawaarea['def']->first()->area_code) . ' ' .
                        ucfirst(strtolower($jawaarea['def']->first()->area_name)) . ' sebesar ' . Utilities::getFormattedNumber($jawaarea['def']->first()->INFMOM) .
                        ' persen dengan IHK sebesar ' . Utilities::getFormattedNumber($jawaarea['def']->first()->IHK) .

                        ' dan deflasi terendah terjadi di ' . Utilities::getAreaType($jawaarea['def']->last()->area_code) . ' ' .
                        ucfirst(strtolower($jawaarea['def']->last()->area_name)) . ' sebesar ' . Utilities::getFormattedNumber($jawaarea['def']->last()->INFMOM) .
                        ' persen dengan IHK sebesar ' . Utilities::getFormattedNumber($jawaarea['def']->last()->IHK) . '.')) : '') . ' (Lihat tabel 4).';

            $result['Indeks Harga Konsumen dan Inflasi Antarkota di Jawa Timur']['Pulau Jawa'] = $sentence;

            //Bab 3

            //Bab 4
            $energyinflation = EnergyInflationData::where([
                'month_id' => $currentmonth->id,
                'year_id' => $currentyear->id
            ])->first();
            $prevenergyinflation = EnergyInflationData::where([
                'month_id' => $prevmonth->id,
                'year_id' => $prevyear->id
            ])->first();

            $first_sentence = 'Komponen energi pada ' . $energyinflation->monthdetail->name . ' ' . $energyinflation->yeardetail->name;
            if (Utilities::isEnergyFoodInfStill($energyinflation->INFMOM)) {
                $first_sentence = $first_sentence . ' tidak mengalami perubahan indeks dibandingkan dengan bulan sebelumnya, yaitu ' . Utilities::getFormattedNumber($energyinflation->IHK) . '.';
            } else {
                $first_sentence = $first_sentence . ' mengalami ' . Utilities::getInfTypeString($energyinflation->INFMOM) .
                    ' sebesar ' . Utilities::getFormattedNumber($energyinflation->INFMOM) .
                    ' persen atau mengalami perubahan indeks dari ' . Utilities::getFormattedNumber($prevenergyinflation->IHK) . ' pada ' . $prevenergyinflation->monthdetail->name . ' ' .
                    $prevenergyinflation->yeardetail->name . ' menjadi ' . Utilities::getFormattedNumber($energyinflation->IHK) . ' pada ' . $energyinflation->monthdetail->name . ' ' .
                    $energyinflation->yeardetail->name . '.';
            }
            $last_sentence = '';
            if ($energyinflation->ANDILMOM == 0) {
                $last_sentence = 'Komponen energi pada ' . $energyinflation->monthdetail->name . ' ' . $energyinflation->yeardetail->name . ' tidak memberikan andil/sumbangan terhadap inflasi nasional.';
            } else {
                $last_sentence = 'Komponen energi pada ' . $energyinflation->monthdetail->name . ' ' . $energyinflation->yeardetail->name . ' memberikan andil/sumbangan terhadap inflasi nasional sebesar ' . Utilities::getFormattedNumber($energyinflation->ANDILMOM, 4) . ' persen.';
            }
            $sentence =
                $first_sentence . ' ' . ucfirst(Utilities::getInfTypeString($energyinflation->INFYTD)) .
                ' komponen energi untuk tahun kalender ' . $energyinflation->monthdetail->name . ' ' . $energyinflation->yeardetail->name . ' sebesar ' . $energyinflation->INFYTD .
                ' persen dan ' . Utilities::getInfTypeString($energyinflation->INFYTD) . ' tahun ke tahun (' . $energyinflation->monthdetail->name . ' ' . $energyinflation->yeardetail->name .
                ' terhadap ' . $energyinflation->monthdetail->name . ' ' . $yearminus1->name . ') sebesar ' . Utilities::getFormattedNumber($energyinflation->INFYOY) . ' persen. ' .
                $last_sentence . ' (lihat Tabel 6)';

            $result['Inflasi Komponen Energi'] = $sentence;
            //Bab 4

            //Bab 5
            $foodinflation = FoodInflationData::where([
                'month_id' => $currentmonth->id,
                'year_id' => $currentyear->id
            ])->first();
            $prevfoodinflation = FoodInflationData::where([
                'month_id' => $prevmonth->id,
                'year_id' => $prevyear->id
            ])->first();
            $first_sentence = 'Bahan makanan pada ' . $foodinflation->monthdetail->name . ' ' . $foodinflation->yeardetail->name;
            if (Utilities::isEnergyFoodInfStill($foodinflation->INFMOM)) {
                $first_sentence = $first_sentence . ' tidak mengalami perubahan indeks dibandingkan dengan bulan sebelumnya, yaitu ' . Utilities::getFormattedNumber($foodinflation->IHK) . '.';
            } else {
                $first_sentence = $first_sentence . ' mengalami ' . Utilities::getInfTypeString($foodinflation->INFMOM) .
                    ' sebesar ' . Utilities::getFormattedNumber($foodinflation->INFMOM) .
                    ' persen atau mengalami perubahan indeks dari ' . Utilities::getFormattedNumber($prevfoodinflation->IHK) . ' pada ' . $prevfoodinflation->monthdetail->name . ' ' .
                    $prevfoodinflation->yeardetail->name . ' menjadi ' . Utilities::getFormattedNumber($foodinflation->IHK) . ' pada ' . $foodinflation->monthdetail->name . ' ' .
                    $foodinflation->yeardetail->name . '.';
            }
            $last_sentence = '';
            if ($foodinflation->ANDILMOM == 0) {
                $last_sentence = 'Bahan makanan pada ' . $foodinflation->monthdetail->name . ' ' . $foodinflation->yeardetail->name . ' tidak memberikan andil/sumbangan terhadap inflasi nasional.';
            } else {
                $last_sentence = 'Bahan makanan pada ' . $foodinflation->monthdetail->name . ' ' . $foodinflation->yeardetail->name . ' memberikan andil/sumbangan terhadap inflasi nasional sebesar ' . Utilities::getFormattedNumber($foodinflation->ANDILMOM, 4) . ' persen.';
            }
            $sentence = $first_sentence . ' ' . ucfirst(Utilities::getInfTypeString($foodinflation->INFYTD)) .
                ' bahan makanan untuk tahun kalender ' . $foodinflation->monthdetail->name . ' ' . $foodinflation->yeardetail->name . ' sebesar ' . $foodinflation->INFYTD .
                ' persen dan ' . Utilities::getInfTypeString($foodinflation->INFYTD) . ' tahun ke tahun (' . $foodinflation->monthdetail->name . ' ' . $foodinflation->yeardetail->name .
                ' terhadap ' . $foodinflation->monthdetail->name . ' ' . $yearminus1->name . ') sebesar ' . Utilities::getFormattedNumber($foodinflation->INFYOY) . ' persen. ' .
                $last_sentence . ' (lihat Tabel 6)';

            $result['Inflasi Bahan Makanan'] = $sentence;
            //Bab 5

            //Excel

            //Tabel 1

            $infcurrent = InflationData::where('month_id', $currentmonth->id)
                ->where('year_id', $currentyear->id)
                ->where(function ($query) {
                    $query->where('flag', '=', 1);
                    $query->orWhere('flag', '=', 0);
                })->get();

            $infprev = InflationData::where('month_id', $prevmonth->id)
                ->where('year_id', $prevyear->id)
                ->where(function ($query) {
                    $query->where('flag', '=', 1);
                    $query->orWhere('flag', '=', 0);
                })->get();

            $infyearminus1 = InflationData::where('month_id', $currentmonth->id)
                ->where('year_id', $yearminus1->id)
                ->where(function ($query) {
                    $query->where('flag', '=', 1);
                    $query->orWhere('flag', '=', 0);
                })->get();

            $spreadsheet = new Spreadsheet();
            $sheet = $spreadsheet->getActiveSheet();
            $sheet->setTitle('Tabel 1');

            $sheet->getCell('A1')
                ->setValue('IHK dan Tingkat Inflasi Kota Probolinggo ' . $currentmonth->name . ' ' . $currentyear->name .
                    ', Tahun Kalender ' . $currentmonth->name . ' ' . $currentyear->name .
                    ', dan Tahun ke Tahun ' . $currentmonth->name . ' ' . $currentyear->name .
                    ' Menurut Kelompok Pengeluaran (2018=100)');

            $sheet->getCell('A3')
                ->setValue('Kelompok Pengeluaran');
            $sheet->getCell('B3')
                ->setValue('IHK ' . $currentmonth->name . ' ' . $yearminus1->name);
            $sheet->getCell('C3')
                ->setValue('IHK ' . $prevmonth->name . ' ' . $prevyear->name);
            $sheet->getCell('D3')
                ->setValue('IHK ' . $currentmonth->name . ' ' . $currentyear->name);
            $sheet->getCell('E3')
                ->setValue('Tingkat Inflasi ' . $currentmonth->name . ' ' . $currentyear->name . ' (%)');
            $sheet->getCell('F3')
                ->setValue('Tingkat Inflasi Tahun Kalender ' . $currentmonth->name . ' ' . $currentyear->name . ' (%)');
            $sheet->getCell('G3')
                ->setValue('Tingkat Inflasi Tahun ke Tahun ' . $currentmonth->name . ' ' . $currentyear->name . ' (%)');
            $sheet->getCell('H3')
                ->setValue('Andil Inflasi ' . $currentmonth->name . ' ' . $currentyear->name . ' (%)');

            $i = 4;
            for ($j = 0; $j < count($infcurrent); $j++) {
                $sheet->getCell('A' . $i)
                    ->setValue(ucwords(strtolower($infcurrent[$j]->item_name)));
                if (count($infyearminus1) == 12)
                    $sheet->getCell('B' . $i)
                        ->setValue($infyearminus1[$j]->IHK);
                else $sheet->getCell('B' . $i)
                    ->setValue('Data Belum Diupload');
                $sheet->getCell('C' . $i)
                    ->setValue(Utilities::getFormattedNumber($infprev[$j]->IHK));
                $sheet->getCell('D' . $i)
                    ->setValue(Utilities::getFormattedNumber($infcurrent[$j]->IHK));
                $sheet->getCell('E' . $i)
                    ->setValue(Utilities::getFormattedNumber($infcurrent[$j]->INFMOM, 2, false));
                $sheet->getCell('F' . $i)
                    ->setValue(Utilities::getFormattedNumber($infcurrent[$j]->INFYTD, 2, false));
                $sheet->getCell('G' . $i)
                    ->setValue(Utilities::getFormattedNumber($infcurrent[$j]->INFYOY, 2, false));
                $sheet->getCell('H' . $i)
                    ->setValue(Utilities::getFormattedNumber($infcurrent[$j]->ANDILMOM, 4, false));

                $i++;
            }

            //Tabel 1

            //Tabel 2

            $infcurrent = InflationData::where('month_id', $currentmonth->id)
                ->where('year_id', $currentyear->id)
                ->where('flag', 0)->first();

            $infyearminus1 = InflationData::where('month_id', $currentmonth->id)
                ->where('year_id', $yearminus1->id)
                ->where('flag', 0)->first();

            $infyearminus2 = InflationData::where('month_id', $currentmonth->id)
                ->where('year_id', $yearminus2->id)
                ->where('flag', 0)->first();

            $infarray = collect();
            $infarray->push($infyearminus2);
            $infarray->push($infyearminus1);
            $infarray->push($infcurrent);

            $spreadsheet->createSheet();
            $spreadsheet->setActiveSheetIndex(1);
            $sheet = $spreadsheet->getActiveSheet();
            $sheet->setTitle('Tabel 2');

            $sheet->getCell('A1')
                ->setValue('Tingkat Inflasi Bulanan, Tahun Kalender, dan Tahun ke Tahun Kota Probolinggo ' . $yearminus2->name . '-' . $currentyear->name . ' (Persen)');

            $sheet->getCell('A3')
                ->setValue('Tingkat Inflasi');
            $sheet->getCell('B3')
                ->setValue($yearminus2->name);
            $sheet->getCell('C3')
                ->setValue($yearminus1->name);
            $sheet->getCell('D3')
                ->setValue($currentyear->name);

            $sheet->getCell('A4')
                ->setValue($currentmonth->name);
            $sheet->getCell('A5')
                ->setValue('Tahun Kalender (Januari-' . $currentmonth->name . ')');
            $sheet->getCell('A6')
                ->setValue('Tahun ke Tahun (' . $currentmonth->name . ' tahun n terhadap ' . $currentmonth->name . ' tahun n-1)');

            $j = 0;
            foreach (range('B', 'D') as $v) {
                $sheet->getCell($v . '4')
                    ->setValue(Utilities::getFormattedNumber($infarray[$j]->INFMOM, 2, false));
                $sheet->getCell($v . '5')
                    ->setValue(Utilities::getFormattedNumber($infarray[$j]->INFYTD, 2, false));
                $sheet->getCell($v . '6')
                    ->setValue(Utilities::getFormattedNumber($infarray[$j]->INFYOY, 2, false));
                $j++;
            }
            //Tabel 2

            //Tabel 3

            $infbyarea = InflationDataByArea::where([
                'month_id' => $currentmonth->id,
                'year_id' => $currentyear->id,
            ])->where('area_code', 'LIKE', '35%')->get();

            $spreadsheet->createSheet();
            $spreadsheet->setActiveSheetIndex(2);
            $sheet = $spreadsheet->getActiveSheet();
            $sheet->setTitle('Tabel 3');

            $sheet->getCell('A1')
                ->setValue('Perbandingan Indeks dan Tingkat Inflasi ' . $currentmonth->name . ' ' . $currentyear->name . ' Kota-Kota di Jawa Timur dengan Jawa Timur (2018=100)');
            $sheet->getCell('A3')
                ->setValue('Kota');
            $sheet->getCell('B3')
                ->setValue('IHK');
            $sheet->getCell('C3')
                ->setValue('Tingkat Inflasi (%)');

            $j = 4;
            foreach ($infbyarea as $inf) {
                $sheet->getCell('A' . $j)
                    ->setValue(ucwords(strtolower($inf->area_name)));
                $sheet->getCell('B' . $j)
                    ->setValue(Utilities::getFormattedNumber($inf->IHK));
                $sheet->getCell('C' . $j)
                    ->setValue(Utilities::getFormattedNumber($inf->INFMOM, 2, false));
                $j++;
            }
            //Tabel 3

            //Tabel 4
            $infbyarea = InflationDataByArea::where([
                'month_id' => $currentmonth->id,
                'year_id' => $currentyear->id,
            ])->where('area_code', 'LIKE', '3%')->get();

            $spreadsheet->createSheet();
            $spreadsheet->setActiveSheetIndex(3);
            $sheet = $spreadsheet->getActiveSheet();
            $sheet->setTitle('Tabel 4');

            $sheet->getCell('A1')
                ->setValue('Perbandingan Indeks dan Tingkat Inflasi ' . $currentmonth->name . ' ' . $currentyear->name . ' Kota-Kota di Pulau Jawa dengan Nasional (2018=100)');
            $sheet->getCell('A3')
                ->setValue('Kota');
            $sheet->getCell('B3')
                ->setValue('IHK');
            $sheet->getCell('C3')
                ->setValue('Tingkat Inflasi (%)');

            $j = 4;
            foreach ($infbyarea as $inf) {
                $sheet->getCell('A' . $j)
                    ->setValue(ucwords(strtolower($inf->area_name)));
                $sheet->getCell('B' . $j)
                    ->setValue(Utilities::getFormattedNumber($inf->IHK));
                $sheet->getCell('C' . $j)
                    ->setValue(Utilities::getFormattedNumber($inf->INFMOM, 2, false));
                $j++;
            }
            //Tabel 4

            //Tabel 5
            $currentenergyinf = EnergyInflationData::where([
                'month_id' => $currentmonth->id,
                'year_id' => $currentyear->id,
            ])->first();
            $prevenergyinf = EnergyInflationData::where([
                'month_id' => $prevmonth->id,
                'year_id' => $prevyear->id,
            ])->first();

            $currentfoodinf = FoodInflationData::where([
                'month_id' => $currentmonth->id,
                'year_id' => $currentyear->id,
            ])->first();
            $prevfoodinf = FoodInflationData::where([
                'month_id' => $prevmonth->id,
                'year_id' => $prevyear->id,
            ])->first();

            $spreadsheet->createSheet();
            $spreadsheet->setActiveSheetIndex(4);
            $sheet = $spreadsheet->getActiveSheet();
            $sheet->setTitle('Tabel 5');

            $sheet->getCell('A1')
                ->setValue('Tingkat Inflasi ' . $currentmonth->name . ' ' . $currentyear->name . ', Tahun Kalender ' . $currentyear->name . ', dan Tahun ke Tahun Kota Probolinggo Menurut Kelompok Komponen Energi dan Bahan Makanan');
            $sheet->getCell('A3')
                ->setValue('Komponen');
            $sheet->getCell('B3')
                ->setValue('IHK ' . $prevmonth->name . ' ' . $prevyear->name);
            $sheet->getCell('C3')
                ->setValue('IHK ' . $currentmonth->name . ' ' . $currentyear->name);
            $sheet->getCell('C3')
                ->setValue('Tingkat Inflasi (%)');
            $sheet->getCell('D3')
                ->setValue('Tingkat Inflasi ' . $currentmonth->name . ' ' . $currentyear->name . ' (%)');
            $sheet->getCell('E3')
                ->setValue('Tingkat Inflasi Tahun Kalender ' . $currentmonth->name . ' ' . $currentyear->name . ' (%)');
            $sheet->getCell('F3')
                ->setValue('Tingkat Inflasi Tahun ke Tahun ' . $currentmonth->name . ' ' . $currentyear->name . ' (%)');
            $sheet->getCell('G3')
                ->setValue('Andil Inflasi ' . $currentmonth->name . ' ' . $currentyear->name . ' (%)');

            $sheet->getCell('A4')
                ->setValue('Energi');
            $sheet->getCell('A5')
                ->setValue('Bahan Makanan');
            $sheet->getCell('B4')
                ->setValue(Utilities::getFormattedNumber($prevenergyinf->IHK));
            $sheet->getCell('B5')
                ->setValue(Utilities::getFormattedNumber($prevfoodinf->IHK));
            $sheet->getCell('C4')
                ->setValue(Utilities::getFormattedNumber($currentenergyinf->IHK));
            $sheet->getCell('C5')
                ->setValue(Utilities::getFormattedNumber($currentfoodinf->IHK));
            $sheet->getCell('D4')
                ->setValue(Utilities::getFormattedNumber($currentenergyinf->INFMOM, 2, false));
            $sheet->getCell('D5')
                ->setValue(Utilities::getFormattedNumber($currentfoodinf->INFMOM, 2, false));
            $sheet->getCell('E4')
                ->setValue(Utilities::getFormattedNumber($currentenergyinf->INFYTD, 2, false));
            $sheet->getCell('E5')
                ->setValue(Utilities::getFormattedNumber($currentfoodinf->INFYTD, 2, false));
            $sheet->getCell('F4')
                ->setValue(Utilities::getFormattedNumber($currentenergyinf->INFYOY, 2, false));
            $sheet->getCell('F5')
                ->setValue(Utilities::getFormattedNumber($currentfoodinf->INFYOY, 2, false));
            $sheet->getCell('G4')
                ->setValue(Utilities::getFormattedNumber($currentenergyinf->ANDILMOM, 4, false));
            $sheet->getCell('G5')
                ->setValue(Utilities::getFormattedNumber($currentfoodinf->ANDILMOM, 4, false));

            //Tabel 5

            header('Content-Type: application/vnd.ms-excel');
            header('Content-Disposition: attachment;filename="Tabel Inflasi ' . $currentmonth->name . ' ' . $currentyear->name . '.xls"');
            header('Cache-Control: max-age=0');

            $writer = \PhpOffice\PhpSpreadsheet\IOFactory::createWriter($spreadsheet, 'Xls');
            $writer->save('php://output');

            return $result;
        } else {
            $error = [];
            if (count($infcurrent) == 0) $error[] = 'Belum Upload Data Inflasi ' . $currentmonth->name . ' ' . $currentyear->name;
            if (count($infprev) == 0) $error[] = 'Belum Upload Data Inflasi ' . $prevmonth->name . ' ' . $prevyear->name;
            if (count($infyearminus1) == 0) $error[] = 'Data Inflasi ' . $currentmonth->name . ' ' . $yearminus1->name . ' belum ada';
            if (count($infyearminus2) == 0) $error[] = 'Data Inflasi ' . $currentmonth->name . ' ' . $yearminus2->name . ' belum ada';

            return redirect('/')->with('error-generate', $error);
        }
    }
}
