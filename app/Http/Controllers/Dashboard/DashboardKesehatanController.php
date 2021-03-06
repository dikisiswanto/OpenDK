<?php

namespace App\Http\Controllers\Dashboard;

use App\Models\Kecamatan;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;

class DashboardKesehatanController extends Controller
{

    public $nama_kuartal = array('q1'=>'Kuartal 1', 'q2' => 'Kuartal 2', 'q3' => 'Kuartal 3', 'q4' => 'Kuartal 4');

    public function __construct()
    {

    }

    // Dashboiard Kesehatan AKI & AKB
    public function showKesehatan()
    {
        $defaultProfil = config('app.default_profile');
        $page_title = 'Kesehatan';
        $page_description = 'Data Kesehatan Kecamatan ';
        $year_list = years_list();
        $list_desa = DB::table('das_data_desa')->select('*')->where('kecamatan_id', '=', $defaultProfil)->get();
        return view('dashboard.kesehatan.show_kesehatan', compact('page_title', 'page_description', 'defaultProfil', 'year_list', 'list_desa'));
    }

    // Get Data Chart AKI & AKB
    public function getChartAKIAKB()
    {
        $kid = request('kid');
        $did = request('did');
        $year = request('y');
        $data = array();

        // Grafik Data Kesehatan AKI & AKB
        $data_kesehatan = array();
        if($year == 'ALL'){
            foreach (years_list() as $yearl) {
                // SD
                $query_aki = DB::table('das_akib')
                    ->where('tahun', '=', $yearl)
                    ->where('kecamatan_id', '=', $kid);
                if ($did != 'ALL') {
                    $query_aki->where('desa_id', '=', $did);
                }
                $aki = $query_aki->sum('aki');
                $akb = $query_aki->sum('akb');

                $data_kesehatan[] = [
                    'year' => $yearl,
                    'aki' => $aki,
                    'akb' =>  $akb,
                ];
            }
        }else{
            $data_tabel = array();
            // Quartal
            foreach(kuartal_bulan() as $key=>$kuartal){
                $query = DB::table('das_akib')
                    ->whereRaw('bulan in ('.$this->getIdsQuartal($key).')')
                    ->where('tahun', $year);
                if ($did != 'ALL') {
                    $query->where('desa_id', '=', $did);
                }
                $data_tabel[] = array(
                    'year' => $this->nama_kuartal[$key],
                    'aki' => $query->sum('aki'),
                    'akb' => $query->sum('akb')
                );
            }


            $data_kesehatan = $data_tabel;
        }

        // Data Tabel AKI & AKB
        $tabel_kesehatan = array();

        // Kuartal & Detail Per Desa
        if($year!='ALL' && $did=='ALL'){
            $data_tabel = array();
            // Quartal
            foreach(kuartal_bulan() as $key=>$kuartal){
                $query = DB::table('das_akib')
                    ->whereRaw('bulan in ('.$this->getIdsQuartal($key).')')
                    ->where('tahun', $year);
                $data_tabel['quartal'][$key] = array(
                    'aki' => $query->sum('aki'),
                    'akb' => $query->sum('akb')
                );
            }

            // Detail Desa
            foreach(kuartal_bulan() as $key=>$kuartal){
                $query = DB::table('das_akib')
                    ->join('das_data_desa', 'das_akib.desa_id', '=', 'das_data_desa.desa_id')
                    ->selectRaw('das_data_desa.nama, sum(das_akib.aki) as aki, sum(das_akib.akb) as akb')
                    ->whereRaw('das_akib.bulan in ('.$this->getIdsQuartal($key).')')
                    ->where('das_akib.tahun', $year)
                    ->groupBy('das_data_desa.nama')->get();
                $data_tabel['desa'][$key] = $query;
            }


            $tabel_kesehatan = view('dashboard.kesehatan.tabel_akiakb_1', compact('data_tabel'))->render();
            //$tabel_kesehatan = $data_tabel;
        }elseif($year !='ALL' && $did != 'ALL'){
            $data_tabel = array();
            foreach(kuartal_bulan() as $key=>$kuartal){
                $query = DB::table('das_akib')
                    ->whereRaw('bulan in ('.$this->getIdsQuartal($key).')')
                    ->where('tahun', $year)
                    ->where('desa_id', $did);
                $data_tabel['quartal'][$key] = array(
                    'aki' => $query->sum('aki'),
                    'akb' => $query->sum('akb')
                );
            }

            $tabel_kesehatan = view('dashboard.kesehatan.tabel_akiakb_2', compact('data_tabel'))->render();
        }

        return array(
            'grafik' => $data_kesehatan,
            'tabel' => $tabel_kesehatan
        );
    }

    // Get Data Chart Cakupan Imunisasi
    public function getChartImunisasi()
    {
        $kid = request('kid');
        $did = request('did');
        $year = request('y');
        $data = array();

        // Grafik Data Kesehatan Cakupan Imunisasi
        $data_kesehatan = array();
        if($year == 'ALL'){
            foreach (years_list() as $yearl) {
                // SD
                $query = DB::table('das_imunisasi')
                    ->where('tahun', '=', $yearl)
                    ->where('kecamatan_id', '=', $kid);
                if ($did != 'ALL') {
                    $query->where('desa_id', '=', $did);
                }

                $data_kesehatan[] = [
                    'year' => $yearl,
                    'cakupan_imunisasi' => $query->sum('cakupan_imunisasi'),
                ];
            }
        }else{
            foreach(kuartal_bulan() as $key=>$kuartal){
                $query = DB::table('das_imunisasi')
                    ->whereRaw('bulan in ('.$this->getIdsQuartal($key).')')
                    ->where('tahun', $year);
                if ($did != 'ALL') {
                    $query->where('desa_id', '=', $did);
                }
                $data_tabel[]= array(
                    'year' => $this->nama_kuartal[$key],
                    'cakupan_imunisasi' => $query->sum('cakupan_imunisasi'),
                );
            }

            $data_kesehatan = $data_tabel;
        }

        // Data Tabel Cakupan Imunisasi
        $tabel_kesehatan = array();

        // Kuartal & Detail Per Desa
        if($year!='ALL' && $did=='ALL'){
            $data_tabel = array();
            // Quartal
            foreach(kuartal_bulan() as $key=>$kuartal){
                $query = DB::table('das_imunisasi')
                    ->whereRaw('bulan in ('.$this->getIdsQuartal($key).')')
                    ->where('tahun', $year);
                $data_tabel['quartal'][$key] = array(
                    'cakupan_imunisasi' => $query->sum('cakupan_imunisasi'),
                );
            }

            // Detail Desa
            foreach(kuartal_bulan() as $key=>$kuartal){
                $query = DB::table('das_imunisasi')
                    ->join('das_data_desa', 'das_imunisasi.desa_id', '=', 'das_data_desa.desa_id')
                    ->selectRaw('das_data_desa.nama, sum(das_imunisasi.cakupan_imunisasi) as cakupan_imunisasi')
                    ->whereRaw('das_imunisasi.bulan in ('.$this->getIdsQuartal($key).')')
                    ->where('das_imunisasi.tahun', $year)
                    ->groupBy('das_data_desa.nama')->get();
                $data_tabel['desa'][$key] = $query;
            }

            $tabel_kesehatan = view('dashboard.kesehatan.tabel_imunisasi_1', compact('data_tabel'))->render();
            //$tabel_kesehatan = $data_tabel;

        }elseif($year !='ALL' && $did != 'ALL'){
            $data_tabel = array();
            foreach(kuartal_bulan() as $key=>$kuartal){
                $query = DB::table('das_imunisasi')
                    ->whereRaw('bulan in ('.$this->getIdsQuartal($key).')')
                    ->where('tahun', $year)
                    ->where('desa_id', $did);
                $data_tabel['quartal'][$key] = array(
                    'cakupan_imunisasi' => $query->sum('cakupan_imunisasi'),
                );
            }

            //$tabel_kesehatan = $data_tabel;
            $tabel_kesehatan = view('dashboard.kesehatan.tabel_imunisasi_2', compact('data_tabel'))->render();
        }

        return array(
            'grafik' => $data_kesehatan,
            'tabel' => $tabel_kesehatan
        );
    }

    // Get Chart Epidemi Penyakit
    public function getChartEpidemiPenyakit()
    {
        $kid = request('kid');
        $did = request('did');
        $year = request('y');
        $data = array();

        // Grafik Data Kesehatan Cakupan Imunisasi
        $data_kesehatan = array();
        if($year == 'ALL'){
            foreach (years_list() as $yearl) {
                // SD
                $query = DB::table('das_epidemi_penyakit')
                    ->where('tahun', '=', $yearl)
                    ->where('kecamatan_id', '=', $kid);
                if ($did != 'ALL') {
                    $query->where('desa_id', '=', $did);
                }

                $data_kesehatan[] = [
                    'year' => $yearl,
                    'jumlah' => $query->sum('jumlah_penderita'),
                ];
            }
        }else{
            $datas = array();

            foreach(semester() as $key=>$val)
            {
                $penyakit = DB::table('ref_penyakit')->get();
                $temp = array();
                foreach ($penyakit as $value) {
                    $query_total = DB::table('das_epidemi_penyakit')
                        //->join('ref_penyakit', 'das_epidemi_penyakit.penyakit_id', '=', 'ref_penyakit.id')
                        ->where('das_epidemi_penyakit.kecamatan_id', '=', $kid)
                        ->whereRaw('das_epidemi_penyakit.bulan in ('.$this->getIdsSemester($key).')')
                        ->where('das_epidemi_penyakit.tahun', $year)
                        ->where('das_epidemi_penyakit.penyakit_id', $value->id);

                    if ($did != 'ALL') {
                        $query_total->where('das_epidemi_penyakit.desa_id', '=', $did);
                    }
                    $total = $query_total->sum('das_epidemi_penyakit.jumlah_penderita');
                    $temp = array_add($temp, 'penyakit'.$value->id, $total);
                }
                $datas[] = array_add($temp, 'year','Semester '.$key);
            }

            $data_kesehatan = $datas;
        }

        // Data Tabel Cakupan Imunisasi
        $tabel_kesehatan = array();

        // Kuartal & Detail Per Desa
        /*if($year!='ALL' && $did=='ALL'){
            $data_tabel = array();
            // Semester

            foreach(semester() as $key=>$semester){
                $query = DB::table('das_epidemi_penyakit')
                    ->selectRaw('ref_penyakit.nama as penyakit, sum(das_epidemi_penyakit.jumlah_penderita)')
                    ->join('ref_penyakit', 'das_epidemi_penyakit.penyakit_id', '=', 'ref_penyakit.id')
                    ->whereRaw('das_epidemi_penyakit.bulan in ('.$this->getIdsSemester($key).')')
                    ->where('das_epidemi_penyakit.tahun', $year);
                $data_tabel['quartal'][$key] = array(
                    'penyakit'  => '',
                    'jumlah_penderita' =>'' ,
                );
            }

            // Detail Desa
            foreach(kuartal_bulan() as $key=>$semester){
                $query = DB::table('das_imunisasi')
                    ->join('das_data_desa', 'das_imunisasi.desa_id', '=', 'das_data_desa.desa_id')
                    ->selectRaw('das_data_desa.nama, sum(das_imunisasi.cakupan_imunisasi) as cakupan_imunisasi')
                    ->whereRaw('das_imunisasi.bulan in ('.$this->getIdsQuartal($key).')')
                    ->where('das_imunisasi.tahun', $year)
                    ->groupBy('das_data_desa.nama')->get();
                $data_tabel['desa'][$key] = $query;
            }

            $tabel_kesehatan = view('dashboard.kesehatan.tabel_penyakit_1', compact('data_tabel'))->render();
            //$tabel_kesehatan = $data_tabel;

        }elseif($year !='ALL' && $did != 'ALL'){
            $data_tabel = array();
            foreach(kuartal_bulan() as $key=>$semester){
                $query = DB::table('das_imunisasi')
                    ->whereRaw('bulan in ('.$this->getIdsQuartal($key).')')
                    ->where('tahun', $year)
                    ->where('desa_id', $did);
                $data_tabel['quartal'][$key] = array(
                    'cakupan_imunisasi' => $query->sum('cakupan_imunisasi'),
                );
            }

            //$tabel_kesehatan = $data_tabel;
            $tabel_kesehatan = view('dashboard.kesehatan.tabel_penyakit_2', compact('data_tabel'))->render();
        }*/

        return array(
            'grafik' => $data_kesehatan,
            'tabel' => $tabel_kesehatan
        );
    }

    // Get Chart Toilet & Sanitasi
    public function getChartToiletSanitasi()
    {
        $kid = request('kid');
        $did = request('did');
        $year = request('y');
        $data = array();

        // Grafik Data Toilet & Sanitasi
        $data_kesehatan = array();
        if($year == 'ALL'){
            foreach (years_list() as $yearl) {
                $query = DB::table('das_toilet_sanitasi')
                    ->where('tahun', '=', $yearl)
                    ->where('kecamatan_id', '=', $kid);
                if ($did != 'ALL') {
                    $query->where('desa_id', '=', $did);
                }

                $data_kesehatan[] = [
                    'year' => $yearl,
                    'toilet' =>  $query->sum('toilet'),
                    'sanitasi' =>   $query->sum('sanitasi'),
                ];
            }
        }else{
            $data_tabel = array();
            // Quartal
            foreach(kuartal_bulan() as $key=>$kuartal){
                $query = DB::table('das_toilet_sanitasi')
                    ->whereRaw('bulan in ('.$this->getIdsQuartal($key).')')
                    ->where('tahun', $year);
                if ($did != 'ALL') {
                    $query->where('desa_id', '=', $did);
                }
                $data_tabel[]= array(
                    'year' => $this->nama_kuartal[$key],
                    'toilet' =>  $query->sum('toilet'),
                    'sanitasi' =>   $query->sum('sanitasi'),
                );
            }

            $data_kesehatan = $data_tabel;
        }

        // Data Tabel AKI & AKB
        $tabel_kesehatan = array();

        // Kuartal & Detail Per Desa
        if($year!='ALL' && $did=='ALL'){
            $data_tabel = array();
            // Quartal
            foreach(kuartal_bulan() as $key=>$kuartal){
                $query = DB::table('das_toilet_sanitasi')
                    ->whereRaw('bulan in ('.$this->getIdsQuartal($key).')')
                    ->where('tahun', $year);
                $data_tabel['quartal'][$key] = array(
                    'toilet' =>  $query->sum('toilet'),
                    'sanitasi' =>   $query->sum('sanitasi'),
                );
            }

            // Detail Desa
            foreach(kuartal_bulan() as $key=>$kuartal){
                $query = DB::table('das_toilet_sanitasi')
                    ->join('das_data_desa', 'das_toilet_sanitasi.desa_id', '=', 'das_data_desa.desa_id')
                    ->selectRaw('das_data_desa.nama, sum(das_toilet_sanitasi.toilet) as toilet, sum(das_toilet_sanitasi.sanitasi) as sanitasi')
                    ->whereRaw('das_toilet_sanitasi.bulan in ('.$this->getIdsQuartal($key).')')
                    ->where('das_toilet_sanitasi.tahun', $year)
                    ->groupBy('das_data_desa.nama')->get();
                $data_tabel['desa'][$key] = $query;
            }


            $tabel_kesehatan = view('dashboard.kesehatan.tabel_sanitasi_1', compact('data_tabel'))->render();
            //$tabel_kesehatan = $data_tabel;
        }elseif($year !='ALL' && $did != 'ALL'){
            $data_tabel = array();
            foreach(kuartal_bulan() as $key=>$kuartal){
                $query = DB::table('das_toilet_sanitasi')
                    ->whereRaw('bulan in ('.$this->getIdsQuartal($key).')')
                    ->where('tahun', $year)
                    ->where('desa_id', $did);
                $data_tabel['quartal'][$key] = array(
                    'toilet' => $query->sum('toilet'),
                    'sanitasi' => $query->sum('sanitasi')
                );
            }

            $tabel_kesehatan = view('dashboard.kesehatan.tabel_sanitasi_2', compact('data_tabel'))->render();
        }

        return array(
            'grafik' => $data_kesehatan,
            'tabel' => $tabel_kesehatan
        );
    }

    private function getIdsQuartal($q)
    {
        $quartal = kuartal_bulan()[$q];
        $ids = '';
        foreach($quartal as $key=>$val){
            $ids.=$key.',';
        }
        return rtrim($ids,',');
    }

    public function getIdsSemester($sm)
    {
        $semester = semester()[$sm];
        $ids = '';
        foreach($semester as $key=>$val)
        {
            $ids.=$key.',';
        }
        return rtrim($ids,',');
    }
}
