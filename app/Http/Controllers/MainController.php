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
use DateInterval;
use DatePeriod;
use DateTime;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use Intervention\Image\Facades\Image;
use ZipArchive;

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

    public function indexTable()
    {
        $currentmonth = Month::where(['code' => date('m')])->first()->id;
        $currentyear = Year::where(['code' => date('Y')])->first()->id;
        $months = Month::all();
        $years = Year::all();

        return view('brs-table-index', [
            'months' => $months,
            'years' => $years,
            'currentmonth' => $currentmonth,
            'currentyear' => $currentyear
        ]);
    }

    public function indexInfographic()
    {
        $currentmonth = Month::where(['code' => date('m')])->first()->id;
        $currentyear = Year::where(['code' => date('Y')])->first()->id;
        $months = Month::all();
        $years = Year::all();

        return view('brs-info-index', [
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
            'file-inf' => 'required_without_all:file-inf-area,file-inf-food-energy',
            'file-inf-area' => 'required_without_all:file-inf,file-inf-food-energy',
            'file-inf-food-energy' => 'required_without_all:file-inf,file-inf-area'
        ]);

        try {
            if ($request->file('file-inf') != null) {
                Excel::import(new InflationDataImport, $request->file('file-inf'));
            }
            if ($request->file('file-inf-area') != null) {
                Excel::import(new InflationDataByAreaImport, $request->file('file-inf-area'));
            }
            if ($request->file('file-inf-food-energy') != null) {
                Excel::import(new FoodAndEnergyInflationDataImport, $request->file('file-inf-food-energy'));
            }

            return redirect('/upload')->with('success-upload', 'Upload Berhasil');
        } catch (Exception $e) {
            return redirect('/upload')->with('error-upload', 'Upload Gagal. Cek apakah file yang diupload sudah sesuai');
        }
    }

    public function generateText(Request $request)
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
                $i = 0;
                foreach ($jatimarea[$key] as $jatimareaitem) {
                    if ($i > 0) {
                        $area_sentence[$key][] = Utilities::getAreaType($jatimareaitem->area_code) . ' ' . ucfirst(strtolower($jatimareaitem->area_name)) .
                            ' sebesar ' . Utilities::getFormattedNumber($jatimareaitem->INFMOM) . ' persen';
                    }
                    $i++;
                }
            }

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
                ' komponen energi untuk tahun kalender ' . $energyinflation->monthdetail->name . ' ' . $energyinflation->yeardetail->name . ' sebesar ' . Utilities::getFormattedNumber($energyinflation->INFYTD) .
                ' persen dan ' . Utilities::getInfTypeString($energyinflation->INFYTD) . ' tahun ke tahun (' . $energyinflation->monthdetail->name . ' ' . $energyinflation->yeardetail->name .
                ' terhadap ' . $energyinflation->monthdetail->name . ' ' . $yearminus1->name . ') sebesar ' . Utilities::getFormattedNumber($energyinflation->INFYOY) . ' persen. ' .
                $last_sentence . ' (Lihat Tabel 6)';

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
                ' bahan makanan untuk tahun kalender ' . $foodinflation->monthdetail->name . ' ' . $foodinflation->yeardetail->name . ' sebesar ' . Utilities::getFormattedNumber($foodinflation->INFYTD) .
                ' persen dan ' . Utilities::getInfTypeString($foodinflation->INFYTD) . ' tahun ke tahun (' . $foodinflation->monthdetail->name . ' ' . $foodinflation->yeardetail->name .
                ' terhadap ' . $foodinflation->monthdetail->name . ' ' . $yearminus1->name . ') sebesar ' . Utilities::getFormattedNumber($foodinflation->INFYOY) . ' persen. ' .
                $last_sentence . ' (Lihat Tabel 6)';

            $result['Inflasi Bahan Makanan'] = $sentence;
            //Bab 5

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

    public function generateTable(Request $request)
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
            ->setValue('Tingkat Inflasi Tahun Kalender ' . $currentyear->name . ' (%)');
        $sheet->getCell('G3')
            ->setValue('Tingkat Inflasi Tahun ke Tahun ' . $currentyear->name . ' (%)');
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
    }

    public function generateInfographic(Request $request)
    {
        $request->validate([
            'brsno' => 'required',
            'month' => 'required',
            'year' => 'required',
        ]);

        $date = Year::find($request->year)->code . '-' . Month::find($request->month)->code . '-01';
        // $date = Year::find('4')->code . '-' . Month::find('7')->code . '-01';
        $currentyear = date('Y', strtotime($date));
        $currentmonth = date('m', strtotime($date));

        $yoyyear = date('Y', strtotime($date . ' -12 months'));
        $yoymonth = date('m', strtotime($date . ' -12 months'));

        $currentmonth = Month::where(['code' => $currentmonth])->first();
        $currentyear = Year::where(['code' => $currentyear])->first();

        $yoymonth = Month::where(['code' => $yoymonth])->first();
        $yoyyear = Year::where(['code' => $yoyyear])->first();

        $infcurrent = InflationData::where([
            'month_id' => $currentmonth->id,
            'year_id' => $currentyear->id,
            'flag' => 0
        ])->first();

        $infcurrentbygroup = InflationData::where([
            'month_id' => $currentmonth->id,
            'year_id' => $currentyear->id,
            'flag' => 1
        ])->get();

        $infbyarea = InflationDataByArea::where([
            'month_id' => $currentmonth->id,
            'year_id' => $currentyear->id
        ])->get();

        if ($infcurrent != null && count($infcurrentbygroup) > 0 && count($infbyarea) > 0) {

            $info = [
                'title_pd' => ['x' => 1310, 'y' => 440, 'size' => 110],
                'no' => ['x' => 660, 'y' => 510, 'size' => 45],
                'inf_period' => ['x' => 132, 'y' => 660, 'size' => 40],
                'inf_value' => ['x' => 720, 'y' => 765, 'size' => 90],
                'inf_ytd_value' => ['x' => 1485, 'y' => 765, 'size' => 90],
                'inf_yoy_value' => ['x' => 2285, 'y' => 765, 'size' => 90],
                'percent_1' => ['x' => 742, 'y' => 720, 'size' => 70],
                'percent_2' => ['x' => 1510, 'y' => 720, 'size' => 70],
                'percent_3' => ['x' => 2315, 'y' => 720, 'size' => 70],
                'rect_1' => ['x1' => 123, 'y1' => 684, 'x2' => 444, 'y2' => 765],
                'rect_2' => ['x1' => 867, 'y1' => 684, 'x2' => 1206, 'y2' => 765],
                'rect_3' => ['x1' => 1650, 'y1' => 684, 'x2' => 1980, 'y2' => 765],
                'inf_text_1' => ['x' => 130, 'y' => 750, 'size' => 80],
                'inf_text_2' => ['x' => 877, 'y' => 750, 'size' => 80],
                'inf_text_3' => ['x' => 1660, 'y' => 750, 'size' => 80],
                'maxyline' => 900,
                'minyline' => 1100,
                'startxline' => 200,
                'intervalxline' => 174,
                'offsetxline' => 50,
                'separator' => ['start' => 1230, 'end' => 1135],
                'line_atr' =>
                [
                    'line_thickness' => 25,
                    'circle_size' => 30,
                    'zero_line_thickness' => 1,
                    'value_size' => 50,
                    'pd_size' => 30,
                    'pd_offset' => 70,
                    'year_pd_size' => 25,
                    'year_pd_offset' => 130,
                ],
                'bar_atr' =>
                [
                    'value_size' => 35,
                    'value_offset' => 30
                ],
                'maxybar' => 1750,
                'minybar' => 2330,
                'startxbar' => 290,
                'intervalxbar' => 196,
                'bar_width_offset' => 20,
                'map_atr' => [
                    'circle_size' => 175,
                    'area_name_y_offset' => 15,
                    'area_name_size' => 24,
                    'value_y_offset' => 40,
                    'value_size' => 40,
                ],
                '3571' => ['x' => 1065, 'y' => 2961],
                '3574' => ['x' => 1613, 'y' => 2732],
                '3509' => ['x' => 1722, 'y' => 3192],
                '3510' => ['x' => 2016, 'y' => 3028],
                '3529' => ['x' => 1908, 'y' => 2739],
                '3573' => ['x' => 1494, 'y' => 3161],
                '3577' => ['x' => 1227, 'y' => 2663],
                '3578' => ['x' => 1500, 'y' => 2605],
                'count_inf' => ['x' => 276, 'y' => 2465, 'size' => 35],
                'count_def' => ['x' => 276, 'y' => 2538, 'size' => 35],
                'sentence' => ['x' => 130, 'y_start' => 2673, 'size' => 33, 'offset' => 50],
            ];

            $social = [
                'title_pd' => ['x' => 2321, 'y' => 687, 'size' => 168],
                'no' => ['x' => 1255, 'y' => 810, 'size' => 77],
                'inf_period' => ['x' => 310, 'y' => 1042, 'size' => 70],
                'inf_value' => ['x' => 1220, 'y' => 1220, 'size' => 160],
                'inf_ytd_value' => ['x' => 2575, 'y' => 1220, 'size' => 160],
                'inf_yoy_value' => ['x' => 3975, 'y' => 1220, 'size' => 160],
                'percent_1' => ['x' => 1265, 'y' => 1150, 'size' => 120],
                'percent_2' => ['x' => 2625, 'y' => 1150, 'size' => 120],
                'percent_3' => ['x' => 4015, 'y' => 1150, 'size' => 120],
                'rect_1' => ['x1' => 286, 'y1' => 1100, 'x2' => 825, 'y2' => 1212],
                'rect_2' => ['x1' => 1614, 'y1' => 1100, 'x2' => 2145, 'y2' => 1212],
                'rect_3' => ['x1' => 2961, 'y1' => 1100, 'x2' => 3495, 'y2' => 1212],
                'inf_text_1' => ['x' => 324, 'y' => 1190, 'size' => 120],
                'inf_text_2' => ['x' => 1635, 'y' => 1190, 'size' => 120],
                'inf_text_3' => ['x' => 2991, 'y' => 1190, 'size' => 120],
                'maxyline' => 1420,
                'minyline' => 1780,
                'startxline' => 408,
                'intervalxline' => 300,
                'offsetxline' => 80,
                'separator' => ['start' => 1900, 'end' => 1800],
                'line_atr' =>
                [
                    'line_thickness' => 35,
                    'circle_size' => 40,
                    'zero_line_thickness' => 2,
                    'value_size' => 80,
                    'pd_size' => 60,
                    'pd_offset' => 100,
                    'year_pd_size' => 50,
                    'year_pd_offset' => 200,
                ],
                'bar_atr' =>
                [
                    'value_size' => 60,
                    'value_offset' => 60
                ],
                'maxybar' => 2770,
                'minybar' => 3720,
                'startxbar' => 688,
                'intervalxbar' => 313,
                'bar_width_offset' => 50,
                'map_atr' => [
                    'circle_size' => 280,
                    'area_name_y_offset' => 25,
                    'area_name_size' => 40,
                    'value_y_offset' => 60,
                    'value_size' => 70,
                ],
                '3571' => ['x' => 2015, 'y' => 4758],
                '3574' => ['x' => 2898, 'y' => 4396],
                '3509' => ['x' => 3070, 'y' => 5125],
                '3510' => ['x' => 3550, 'y' => 4865],
                '3529' => ['x' => 3366, 'y' => 4395],
                '3573' => ['x' => 2704, 'y' => 5084],
                '3577' => ['x' => 2275, 'y' => 4282],
                '3578' => ['x' => 2719, 'y' => 4179],
                'count_inf' => ['x' => 640, 'y' => 3975, 'size' => 65],
                'count_def' => ['x' => 640, 'y' => 4090, 'size' => 65],
                'sentence' => ['x' => 250, 'y_start' => 4300, 'size' => 65, 'offset' => 95],
            ];

            $this->generateInfographicByPosition($request, 'template/brs.png', 'template/brs_result.png', $info);
            $this->generateInfographicByPosition($request, 'template/medsos.png', 'template/medsos_result.png', $social);


            $zip = new ZipArchive;
            $file = 'template/' . $infcurrent->monthdetail->name . ' ' . $infcurrent->yeardetail->name . '.zip';
            Storage::disk('public')->delete($file);
            if ($zip->open($file, ZipArchive::CREATE) === TRUE) {
                $zip->addFile('template/brs_result.png', 'brs_result_' . $infcurrent->monthdetail->name . '_' . $infcurrent->yeardetail->name . '.png');
                $zip->addFile('template/medsos_result.png', 'medsos_result_' . $infcurrent->monthdetail->name . '_' . $infcurrent->yeardetail->name . '.png');
                $zip->close();
            }

            // Storage::download('/template/brs_result.png', 'a.png', ['Content-Type: image/png']);

            $filePath = public_path($file);
            $headers = ['Content-Type: application/zip'];
            $fileName = 'Infografis ' . $infcurrent->monthdetail->name . ' ' . $infcurrent->yeardetail->name . '.zip';

            return response()->download($filePath, $fileName, $headers);
        } else {
            $error = [];
            if ($infcurrent == null) $error[] = 'Belum Upload Data Inflasi ' . $currentmonth->name . ' ' . $currentyear->name;
            if (count($infbyarea) == 0) $error[] = 'Belum Upload Data Inflasi Per Kota ' . $currentmonth->name . ' ' . $currentyear->name;

            return redirect('/generate-info')->with('error-generate', $error);;
        }
    }

    public function generateInfographicByPosition(Request $request, $source, $target, $pos)
    {
        $date = Year::find($request->year)->code . '-' . Month::find($request->month)->code . '-01';
        // $date = Year::find('4')->code . '-' . Month::find('7')->code . '-01';
        $currentyear = date('Y', strtotime($date));
        $currentmonth = date('m', strtotime($date));

        $yoyyear = date('Y', strtotime($date . ' -12 months'));
        $yoymonth = date('m', strtotime($date . ' -12 months'));

        $currentmonth = Month::where(['code' => $currentmonth])->first();
        $currentyear = Year::where(['code' => $currentyear])->first();

        $yoymonth = Month::where(['code' => $yoymonth])->first();
        $yoyyear = Year::where(['code' => $yoyyear])->first();

        $infcurrent = InflationData::where([
            'month_id' => $currentmonth->id,
            'year_id' => $currentyear->id,
            'flag' => 0
        ])->first();

        $infcurrentbygroup = InflationData::where([
            'month_id' => $currentmonth->id,
            'year_id' => $currentyear->id,
            'flag' => 1
        ])->get();

        $infbyarea = InflationDataByArea::where([
            'month_id' => $currentmonth->id,
            'year_id' => $currentyear->id
        ])->get();

        $color = [
            'blue' => '#0c588f',
            'green' => '#8cc63f',
            'yellow' => '#fcdf05',
            'black' => '#000000',
            'red' => '#FF0000',
            'white' => '#FFFFFF'
        ];

        $img = Image::make($source);

        $img->text(strtoupper($infcurrent->monthdetail->name) . ' ' . $infcurrent->yeardetail->name, $pos['title_pd']['x'], $pos['title_pd']['y'], function ($font) use ($color, $pos) {
            $font->file(public_path('assets/fonts/metropolis/Metropolis-Bold.otf'));
            $font->size($pos['title_pd']['size']);
            $font->color($color['blue']);
        });

        // $img->text('dslajdksajd', $pos['no']['x'], $pos['no']['y'], function ($font) use ($color, $pos) {
        //     $font->file(public_path('assets/fonts/metropolis/Metropolis-Regular.otf'));
        //     $font->size($pos['no']['size']);
        //     $font->color($color['black']);
        // });

        $img->text($request->brsno, $pos['no']['x'], $pos['no']['y'], function ($font) use ($color, $pos) {
            $font->file(public_path('assets/fonts/metropolis/Metropolis-Regular.otf'));
            $font->size($pos['no']['size']);
            $font->color($color['black']);
        });

        $img->text(strtoupper($infcurrent->monthdetail->name) . ' ' . $infcurrent->yeardetail->name, $pos['inf_period']['x'], $pos['inf_period']['y'], function ($font) use ($color, $pos) {
            $font->file(public_path('assets/fonts/metropolis/Metropolis-Regular.otf'));
            $font->size($pos['inf_period']['size']);
            $font->color($color['yellow']);
        });

        $img->text(Utilities::getFormattedNumber($infcurrent->INFMOM), $pos['inf_value']['x'], $pos['inf_value']['y'], function ($font) use ($color, $pos) {
            $font->file(public_path('assets/fonts/metropolis/Metropolis-Bold.otf'));
            $font->size($pos['inf_value']['size']);
            $font->color($color['yellow']);
            $font->align('right');
        });

        $img->text(Utilities::getFormattedNumber($infcurrent->INFYTD), $pos['inf_ytd_value']['x'], $pos['inf_ytd_value']['y'], function ($font) use ($color, $pos) {
            $font->file(public_path('assets/fonts/metropolis/Metropolis-Bold.otf'));
            $font->size($pos['inf_ytd_value']['size']);
            $font->color($color['yellow']);
            $font->align('right');
        });

        $img->text(Utilities::getFormattedNumber($infcurrent->INFYOY), $pos['inf_yoy_value']['x'], $pos['inf_yoy_value']['y'], function ($font) use ($color, $pos) {
            $font->file(public_path('assets/fonts/metropolis/Metropolis-Bold.otf'));
            $font->size($pos['inf_yoy_value']['size']);
            $font->color($color['yellow']);
            $font->align('right');
        });

        $img->text('%', $pos['percent_1']['x'], $pos['percent_1']['y'], function ($font) use ($color, $pos) {
            $font->file(public_path('assets/fonts/metropolis/Metropolis-Regular.otf'));
            $font->size($pos['percent_1']['size']);
            $font->color($color['yellow']);
        });
        $img->text('%', $pos['percent_2']['x'], $pos['percent_2']['y'], function ($font) use ($color, $pos) {
            $font->file(public_path('assets/fonts/metropolis/Metropolis-Regular.otf'));
            $font->size($pos['percent_2']['size']);
            $font->color($color['yellow']);
        });
        $img->text('%', $pos['percent_3']['x'], $pos['percent_3']['y'], function ($font) use ($color, $pos) {
            $font->file(public_path('assets/fonts/metropolis/Metropolis-Regular.otf'));
            $font->size($pos['percent_3']['size']);
            $font->color($color['yellow']);
        });

        $img->rectangle(
            $pos['rect_1']['x1'],
            $pos['rect_1']['y1'],
            $pos['rect_1']['x2'],
            $pos['rect_1']['y2'],
            function ($draw) use ($color) {
                $draw->background($color['blue']);
            }
        );

        $img->rectangle(
            $pos['rect_2']['x1'],
            $pos['rect_2']['y1'],
            $pos['rect_2']['x2'],
            $pos['rect_2']['y2'],
            function ($draw) use ($color) {
                $draw->background($color['blue']);
            }
        );

        $img->rectangle(
            $pos['rect_3']['x1'],
            $pos['rect_3']['y1'],
            $pos['rect_3']['x2'],
            $pos['rect_3']['y2'],
            function ($draw) use ($color) {
                $draw->background($color['blue']);
            }
        );

        $img->text(
            strtoupper(Utilities::getInfTypeString($infcurrent->INFMOM)),
            $pos['inf_text_1']['x'],
            $pos['inf_text_1']['y'],
            function ($font) use ($color, $pos) {
                $font->file(public_path('assets/fonts/metropolis/Metropolis-Bold.otf'));
                $font->size($pos['inf_text_1']['size']);
                $font->color($color['yellow']);
            }
        );

        $img->text(
            strtoupper(Utilities::getInfTypeString($infcurrent->INFYTD)),
            $pos['inf_text_2']['x'],
            $pos['inf_text_2']['y'],
            function ($font) use ($color, $pos) {
                $font->file(public_path('assets/fonts/metropolis/Metropolis-Bold.otf'));
                $font->size($pos['inf_text_2']['size']);
                $font->color($color['yellow']);
            }
        );

        $img->text(
            strtoupper(Utilities::getInfTypeString($infcurrent->INFYOY)),
            $pos['inf_text_3']['x'],
            $pos['inf_text_3']['y'],
            function ($font) use ($color, $pos) {
                $font->file(public_path('assets/fonts/metropolis/Metropolis-Bold.otf'));
                $font->size($pos['inf_text_3']['size']);
                $font->color($color['yellow']);
            }
        );

        $end = new DateTime($currentyear->code . '-' . $currentmonth->code . '-15');
        $begin = new DateTime($yoyyear->code . '-' . $yoymonth->code . '-01');
        $interval = DateInterval::createFromDateString('1 month');

        $period = new DatePeriod($begin, $interval, $end);

        $coordinates = [];
        $yearscount = [];

        $infarray = collect();
        foreach ($period as $dt) {
            $month = Month::where(['code' => $dt->format("m")])->first();
            $year = Year::where(['code' => $dt->format("Y")])->first();
            $inf = InflationData::where([
                'month_id' => $month->id,
                'year_id' => $year->id,
                'flag' => 0
            ])->first();
            $infarray->push($inf);

            $yearscount[$dt->format("Y")] = 0;
        }

        foreach ($period as $dt) {
            $yearscount[$dt->format("Y")]++;
        }

        $max = $infarray->max('INFMOM');
        $min = $infarray->min('INFMOM');

        $maxycoordinate = $pos['maxyline'];
        $minycoordinate = $pos['minyline'];

        $grad = ($minycoordinate - $maxycoordinate) / ((float)$min - (float)$max);
        $const = $minycoordinate - $grad * (float)$min;

        $startxcoordinate = $pos['startxline'];
        $intervalxcoordinate = $pos['intervalxline'];
        $offsetxcoordinate = $pos['offsetxline'];

        $tempstartxcoordinate = $startxcoordinate;

        foreach ($infarray as $inf) {
            $coordinate = [];

            $coordinate['y'] = (int)($grad * ($inf != null ? $inf->INFMOM : 0) + $const);
            $coordinate['x'] = (int)($tempstartxcoordinate);
            $coordinate['isnull'] = $inf == null;
            $coordinate['value'] = $inf;

            $tempstartxcoordinate += $intervalxcoordinate;

            $coordinates[] = $coordinate;
        }

        $ycoordinatezero = (int)($const);

        $img->save($target);

        $imggd = imagecreatefrompng($target);
        $green = imagecolorallocate($imggd, 140, 198, 63);
        $blue = imagecolorallocate($imggd, 12, 88, 143);

        // Set the thickness of the line
        imagesetthickness($imggd, $pos['line_atr']['zero_line_thickness']);

        imageline(
            $imggd,
            $startxcoordinate - $offsetxcoordinate,
            $ycoordinatezero,
            ($intervalxcoordinate * 13) + $offsetxcoordinate * 2,
            $ycoordinatezero,
            $blue
        );

        imagesetthickness($imggd, $pos['line_atr']['line_thickness']);

        for ($i = 0; $i < count($coordinates); $i++) {
            if ($i > 0) {
                imageline(
                    $imggd,
                    $coordinates[$i - 1]['x'],
                    $coordinates[$i - 1]['y'],
                    $coordinates[$i]['x'],
                    $coordinates[$i]['y'],
                    ($i % 2) == 0 ? $blue : $green
                );
            }
            if ($coordinates[$i]['value'] != null) {
                if ($coordinates[$i]['value']->monthdetail->id == 12 && $i != (count($coordinates) - 1)) {
                    imagesetthickness($imggd, 5);
                    imageline(
                        $imggd,
                        $coordinates[$i]['x'] + $intervalxcoordinate / 2,
                        $pos['separator']['start'],
                        $coordinates[$i]['x'] + $intervalxcoordinate / 2,
                        $pos['separator']['end'],
                        $blue
                    );
                    imagesetthickness($imggd, $pos['line_atr']['line_thickness']);
                }
            }
        }

        imagepng($imggd, $target);

        $img = Image::make($target);

        for ($i = 0; $i < count($coordinates); $i++) {
            $img->text(
                $coordinates[$i]['value'] != null ?
                    Utilities::getFormattedNumber($coordinates[$i]['value']->INFMOM, 2, false) : 0,
                $coordinates[$i]['x'],
                $coordinates[$i]['y'] - 50,
                function ($font) use ($color, $coordinates, $i, $pos) {
                    $font->file(public_path('assets/fonts/metropolis/Metropolis-Bold.otf'));
                    $font->size($pos['line_atr']['value_size']);
                    $font->color($coordinates[$i]['value'] != null ? $color['blue'] : $color['red']);
                    $font->align('center');
                }
            );
            $c = ($i % 2) == 0 ? $color['blue'] : $color['green'];
            $img->circle(
                $pos['line_atr']['circle_size'],
                $coordinates[$i]['x'],
                $coordinates[$i]['y'],
                function ($draw) use ($c, $i, $color) {
                    $draw->background($i != 0 ? $c : $color['green']);
                }
            );
            $img->text(
                $coordinates[$i]['value'] != null ?
                    substr($coordinates[$i]['value']->monthdetail->name, 0, 3) . ' ' . substr($coordinates[$i]['value']->yeardetail->name, 2, 2) : 'Na',
                $coordinates[$i]['x'],
                $minycoordinate + $pos['line_atr']['pd_offset'],
                function ($font) use ($color, $pos) {
                    $font->file(public_path('assets/fonts/metropolis/Metropolis-Regular.otf'));
                    $font->size($pos['line_atr']['pd_size']);
                    $font->color($color['blue']);
                    $font->align('center');
                    $font->valign('bottom');
                }
            );
        }

        $tempstartxcoordinate = $startxcoordinate;
        foreach ($yearscount as $year => $value) {
            $tempstartxcoordinate = $tempstartxcoordinate + ($intervalxcoordinate * $value);
            $img->text(
                $year . ' (2018=100)',
                $tempstartxcoordinate - ($intervalxcoordinate * $value) / 2,
                $minycoordinate + $pos['line_atr']['year_pd_offset'],
                function ($font) use ($color, $pos) {
                    $font->file(public_path('assets/fonts/metropolis/Metropolis-Regular.otf'));
                    $font->size($pos['line_atr']['year_pd_size']);
                    $font->color($color['blue']);
                    $font->align('center');
                    $font->valign('bottom');
                }
            );
        }

        $graphbarcoordinates = [];

        $max = $infcurrentbygroup->max('ANDILMOM');
        $min = $infcurrentbygroup->min('ANDILMOM');

        $maxycoordinate = $pos['maxybar'];
        $minycoordinate = $pos['minybar'];

        $grad = ($minycoordinate - $maxycoordinate) / ((float)$min - (float)$max);
        $const = $minycoordinate - $grad * (float)$min;

        $startxcoordinate = $pos['startxbar'];
        $intervalxcoordinate = $pos['intervalxbar'];
        $graphbarwidth = $intervalxcoordinate - $pos['bar_width_offset'];

        $tempstartxcoordinate = $startxcoordinate;

        foreach ($infcurrentbygroup as $inf) {
            $coordinate = [];
            $coordinate['y'] = (int)($grad * ($inf != null ? $inf->ANDILMOM : 0) + $const);
            $coordinate['x'] = (int)($tempstartxcoordinate);
            $coordinate['value'] = $inf;
            $tempstartxcoordinate += $intervalxcoordinate;
            $graphbarcoordinates[] = $coordinate;
        }

        $ycoordinatezero = (int)($const);

        for ($i = 0; $i < count($graphbarcoordinates); $i++) {
            $img->rectangle(
                $graphbarcoordinates[$i]['x'] - $graphbarwidth / 2,
                $graphbarcoordinates[$i]['y'],
                $graphbarcoordinates[$i]['x'] + $graphbarwidth / 2,
                $ycoordinatezero,
                function ($draw) use ($color, $graphbarcoordinates, $i) {
                    $draw->background($graphbarcoordinates[$i]['value']->ANDILMOM >= 0 ? $color['blue'] : $color['green']);
                }
            );

            $img->text(
                Utilities::getFormattedNumber($graphbarcoordinates[$i]['value']->ANDILMOM, 4, false) . '%',
                $graphbarcoordinates[$i]['x'],
                $graphbarcoordinates[$i]['y'] - $pos['bar_atr']['value_offset'],
                function ($font) use ($color, $pos) {
                    $font->file(public_path('assets/fonts/metropolis/Metropolis-Bold.otf'));
                    $font->size($pos['bar_atr']['value_size']);
                    $font->color($color['blue']);
                    $font->align('center');
                    $font->valign('bottom');
                }
            );
        }

        $jatimarea = [
            'inf' => collect(),
            'def' => collect()
        ];

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

        $jatimbyareawithcoordinate = collect();

        foreach ($infbyarea as $area) {
            if (substr($area->area_code, 0, 2) == '35') {
                if ($area->INFMOM > 0)
                    $jatimarea['inf']->push($area);
                else if ($area->INFMOM < 0)
                    $jatimarea['def']->push($area);

                $area->xcoordinate = $pos[$area->area_code]['x'];
                $area->ycoordinate = $pos[$area->area_code]['y'];
                $jatimbyareawithcoordinate->put($area->area_code, $area);
            }
        }

        $img->text(
            count($jatimarea['inf']) > 0 ? count($jatimarea['inf']) . ' kota mengalami inflasi' : 'Tidak ada kota mengalami inflasi',
            $pos['count_inf']['x'],
            $pos['count_inf']['y'],
            function ($font) use ($color, $pos) {
                $font->file(public_path('assets/fonts/metropolis/Metropolis-Regular.otf'));
                $font->size($pos['count_inf']['size']);
                $font->color($color['white']);
                $font->align('left');
            }
        );

        $img->text(
            count($jatimarea['def']) > 0 ? count($jatimarea['def']) . ' kota mengalami deflasi' : 'Tidak ada kota mengalami deflasi',
            $pos['count_def']['x'],
            $pos['count_def']['y'],
            function ($font) use ($color, $pos) {
                $font->file(public_path('assets/fonts/metropolis/Metropolis-Regular.otf'));
                $font->size($pos['count_def']['size']);
                $font->color($color['black']);
                $font->align('left');
            }
        );

        foreach ($jatimbyareawithcoordinate as $key => $area) {
            $img->circle($pos['map_atr']['circle_size'], $area->xcoordinate, $area->ycoordinate, function ($draw) use ($color, $area) {
                $draw->background(Utilities::isInflation($area->INFMOM) ? $color['blue'] : $color['green']);
            });

            $img->text(
                ucwords(strtolower($area->area_name)),
                $area->xcoordinate,
                $area->ycoordinate - $pos['map_atr']['area_name_y_offset'],
                function ($font) use ($color, $area, $pos) {
                    $font->file(public_path('assets/fonts/metropolis/Metropolis-Regular.otf'));
                    $font->size($pos['map_atr']['area_name_size']);
                    $font->color(Utilities::isInflation($area->INFMOM) ? $color['yellow'] : $color['black']);
                    $font->align('center');
                }
            );

            $img->text(
                Utilities::getFormattedNumber($area->INFMOM) . '%',
                $area->xcoordinate,
                $area->ycoordinate + $pos['map_atr']['value_y_offset'],
                function ($font) use ($color, $area, $pos) {
                    $font->file(public_path('assets/fonts/metropolis/Metropolis-Bold.otf'));
                    $font->size($pos['map_atr']['value_size']);
                    $font->color(Utilities::isInflation($area->INFMOM) ? $color['yellow'] : $color['black']);
                    $font->align('center');
                }
            );
        }

        $sentence =
            explode('\n', 'Dari ' . (count($jatimarea['inf']) + count($jatimarea['def'])) . ' kota IHK di Jawa Timur, \n' .
                (count($jatimarea['inf']) > 0 ? ((count($jatimarea['inf']) == (count($jatimarea['inf']) + count($jatimarea['def'])) ? 'seluruh' : (count($jatimarea['inf']))) . ' kota mengalami inflasi') : '') .
                (count($jatimarea['inf']) > 0 && count($jatimarea['def']) > 0 ? ' dan ' : '') .
                (count($jatimarea['def']) > 0 ? ((count($jatimarea['def']) == (count($jatimarea['def']) + count($jatimarea['def'])) ? 'seluruh' : (count($jatimarea['def']))) . ' kota mengalami deflasi') : '') .
                '.\n' .

                (count($jatimarea['inf']) > 0 ? (count($jatimarea['inf']) == 1 ?
                    ('Inflasi terjadi di ' . Utilities::getAreaType($jatimarea['inf']->first()->area_code) . ' ' . ucfirst(strtolower($jatimarea['inf']->first()->area_name)) . '\nsebesar ' . Utilities::getFormattedNumber($jatimarea['inf']->first()->INFMOM) . ' persen dengan IHK sebesar ' . Utilities::getFormattedNumber($jatimarea['inf']->first()->IHK) . '. ')
                    : ('Inflasi tertinggi terjadi di ' . Utilities::getAreaType($jatimarea['inf']->first()->area_code) . ' ' .
                        ucfirst(strtolower($jatimarea['inf']->first()->area_name)) . '\nsebesar ' . Utilities::getFormattedNumber($jatimarea['inf']->first()->INFMOM) .
                        ' persen dengan IHK sebesar ' . Utilities::getFormattedNumber($jatimarea['inf']->first()->IHK) .

                        '\ndan inflasi terendah terjadi di ' . Utilities::getAreaType($jatimarea['inf']->last()->area_code) . ' ' .
                        ucfirst(strtolower($jatimarea['inf']->last()->area_name)) . '\nsebesar ' . Utilities::getFormattedNumber($jatimarea['inf']->last()->INFMOM) .
                        ' persen dengan IHK sebesar ' . Utilities::getFormattedNumber($jatimarea['inf']->last()->IHK) . '.\n')) : '') .

                (count($jatimarea['def']) > 0 ? (count($jatimarea['def']) == 1 ?
                    ('Deflasi terjadi di ' . Utilities::getAreaType($jatimarea['def']->first()->area_code) . ' ' . ucfirst(strtolower($jatimarea['def']->first()->area_name)) . '\nsebesar ' . Utilities::getFormattedNumber($jatimarea['def']->first()->INFMOM) . ' persen dengan IHK sebesar ' . Utilities::getFormattedNumber($jatimarea['def']->first()->IHK) . '. ')
                    : ('Deflasi tertinggi terjadi di ' . Utilities::getAreaType($jatimarea['def']->first()->area_code) . ' ' .
                        ucfirst(strtolower($jatimarea['def']->first()->area_name)) . '\nsebesar ' . Utilities::getFormattedNumber($jatimarea['def']->first()->INFMOM) .
                        ' persen dengan IHK sebesar ' . Utilities::getFormattedNumber($jatimarea['def']->first()->IHK) .

                        '\ndan deflasi terendah terjadi di ' . Utilities::getAreaType($jatimarea['def']->last()->area_code) . ' ' .
                        ucfirst(strtolower($jatimarea['def']->last()->area_name)) . '\nsebesar ' . Utilities::getFormattedNumber($jatimarea['def']->last()->INFMOM) .
                        ' persen dengan IHK sebesar ' . Utilities::getFormattedNumber($jatimarea['def']->last()->IHK) . '.')) : ''));


        for ($i = 0; $i < count($sentence); $i++) {
            $offset = $pos['sentence']['y_start'] + ($i * $pos['sentence']['offset']);
            $img->text($sentence[$i], $pos['sentence']['x'], $offset, function ($font) use ($color, $pos) {
                $font->file(public_path('assets/fonts/metropolis/Metropolis-Regular.otf'));
                $font->size($pos['sentence']['size']);
                $font->color($color['blue']);
                $font->align('left');
            });
        }

        $img->save($target);

        // Storage::download('/template/brs_result.png', 'a.png', ['Content-Type: image/png']);

        // $filePath = public_path("template/brs_result.png");
        // $headers = ['Content-Type: image/png'];
        // $fileName = 'Infografis ' . $infcurrent->monthdetail->name . ' ' . $infcurrent->yeardetail->name . '.png';

        // return response()->download($filePath, $fileName, $headers);

        // return redirect('/generate-info')->with('success-upload', 'Infografis Berhasil di Generate');

        // return 'done';
    }
}
