<?php

namespace App\Http\Controllers;

use App\Models\Armada;
use App\Models\JenisPenumpang;
use App\Models\HistoryKeberangkatan;
use App\Models\Keberangkatan;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use PDF;
use DateTime;

class DashboardController extends Controller
{
    //
    public function laporan_manifest(Request $request, $id_armada) 
    {
        $tanggal = $request->input('tanggal');
        
        $jadwal = DB::table('jadwal_keberangkatans')
            ->join('kapals', 'jadwal_keberangkatans.id_kapal', '=', 'kapals.id_kapal')
            ->join('rutes', 'jadwal_keberangkatans.id_rute', '=', 'rutes.id_rute')
            ->join('dermagas as d', 'rutes.tujuan_awal', '=', 'd.id_dermaga')
            ->join('dermagas as ds', 'rutes.tujuan_akhir', '=', 'ds.id_dermaga')
            ->join('history_keberangkatans as hk', 'hk.id_jadwal', '=', 'jadwal_keberangkatans.id_jadwal')
            ->select('jadwal_keberangkatans.*', 'kapals.nama_kapal', 'd.nama_dermaga as tujuan_awal', 'd.lokasi as lokasi_awal', 'ds.nama_dermaga as tujuan_akhir', 'ds.lokasi as lokasi_akhir', 'd.id_syahbandar', 't.total', 'hk.*')
            ->where('jadwal_keberangkatans.id_armada', '=', $id_armada);
            
        if($request->input('tanggal') == null) {
            $return = $jadwal
                    ->leftJoin(DB::raw("(SELECT k.id_jadwal, COUNT(k.id) as total FROM `keberangkatans` as k WHERE DATE_FORMAT(k.tanggal_keberangkatan , '%Y-%m-%d') = CURDATE() GROUP BY k.id_jadwal) as t"), 'jadwal_keberangkatans.id_jadwal', '=', 't.id_jadwal')
                    ->whereDate('hk.tanggal_berangkat', Carbon::today())
                    ->get();  
        } else {
            $return = $jadwal
                    ->leftJoin(DB::raw("(SELECT k.id_jadwal, COUNT(k.id) as total FROM `keberangkatans` as k WHERE DATE_FORMAT(k.tanggal_keberangkatan , '%Y-%m-%d') = '$tanggal' GROUP BY k.id_jadwal) as t"), 'jadwal_keberangkatans.id_jadwal', '=', 't.id_jadwal')
                    ->whereDate('hk.tanggal_berangkat', Carbon::parse($tanggal))
                    ->get();
        }
        
        return response()->json($return, 200);
    }
    
    public function detail(Request $request, $id_jadwal) 
    {
        $tanggal = $request->input('tanggal');
        $penumpang = DB::select(
            "SELECT jk.*, hk.*, n.nama_nahkoda, a.nama_armada, d.nama_dermaga as tujuan_awal, ds.nama_dermaga as tujuan_akhir, k.nama_kapal, 
            t.total FROM `jadwal_keberangkatans` as jk 
            INNER JOIN `armadas` as a ON jk.id_armada = a.id_armada 
            INNER JOIN `nahkodas` as n ON jk.id_nahkoda = n.id_nahkoda 
            INNER JOIN `rutes` as r ON jk.id_rute = r.id_rute 
            INNER JOIN `dermagas` as d ON r.tujuan_awal = d.id_dermaga 
            INNER JOIN `dermagas` as ds ON r.tujuan_akhir = ds.id_dermaga 
            INNER JOIN `kapals` as k ON jk.id_kapal = k.id_kapal 
            INNER JOIN `history_keberangkatans` as hk ON jk.id_jadwal = hk.id_jadwal 
            LEFT JOIN (SELECT k.id_jadwal, COUNT(k.id) as total FROM `keberangkatans` as k
                INNER JOIN penumpangs p ON p.id_penumpang=k.id_penumpang 
                WHERE DATE_FORMAT(k.tanggal_keberangkatan , '%Y-%m-%d') = '$tanggal'
                AND p.status_verif=1
                GROUP BY k.id_jadwal) as t on jk.id_jadwal = t.id_jadwal 
            WHERE jk.id_jadwal = '$id_jadwal'
            AND DATE_FORMAT(hk.tanggal_berangkat , '%Y-%m-%d') = '$tanggal'");
        $penumpang_unverif = DB::select(
            "SELECT k.id_jadwal as id_jadwal, p.*, jp.nama_jns_penum,jt.nama_tujuan, t.id as id_tiket, k.tanggal_keberangkatan 
            from penumpangs p 
            inner join jenis_penumpangs jp on p.id_jns_penum=jp.id_jns_penum
            inner join jenis_tujuans jt on p.id_tujuan = jt.id_tujuan 
            inner join keberangkatans k on k.id_penumpang = p.id_penumpang
            inner join tikets t on t.id = k.id_tiket
            where k.id_jadwal='$id_jadwal' and p.status_verif = 0 and date(k.tanggal_keberangkatan) = date('$tanggal')");
        $datas = DB::select(
            "SELECT k.id_jadwal as id_jadwal, p.*, jp.nama_jns_penum, jt.nama_tujuan, t.id as id_tiket 
            FROM `penumpangs` as p 
            INNER JOIN `keberangkatans` as k ON p.id_penumpang = k.id_penumpang 
            INNER JOIN `jenis_penumpangs` as jp ON p.id_jns_penum = jp.id_jns_penum 
            INNER JOIN `jenis_tujuans` as jt ON p.id_tujuan = jt.id_tujuan 
            inner join tikets t on t.id = k.id_tiket
            WHERE k.id_jadwal = '$id_jadwal' 
            AND DATE_FORMAT(k.tanggal_keberangkatan , '%Y-%m-%d') = '$tanggal' and p.status_verif=1");
        
        $tujuan = DB::select("SELECT jt.id_tujuan, jt.nama_tujuan, kp.total FROM `jenis_tujuans` as jt LEFT JOIN ( SELECT p.id_tujuan, COUNT(p.id_tujuan) as total FROM `penumpangs` as p INNER JOIN `keberangkatans` as k ON p.id_penumpang = k.id_penumpang WHERE DATE_FORMAT(k.tanggal_keberangkatan , '%Y-%m-%d') = '$tanggal' AND k.id_jadwal = '$id_jadwal' and p.status_verif=1 GROUP BY p.id_tujuan ) as kp ON jt.id_tujuan = kp.id_tujuan");
        
        $jenis = DB::select("SELECT jp.id_jns_penum, jp.nama_jns_penum, kp.total FROM `jenis_penumpangs` as jp LEFT JOIN ( SELECT p.id_jns_penum, COUNT(p.id_jns_penum) as total FROM `penumpangs` as p INNER JOIN `keberangkatans` as k ON p.id_penumpang = k.id_penumpang WHERE DATE_FORMAT(k.tanggal_keberangkatan , '%Y-%m-%d') = '$tanggal' AND k.id_jadwal = '$id_jadwal' and p.status_verif=1 GROUP BY p.id_jns_penum ) as kp ON jp.id_jns_penum = kp.id_jns_penum order by jp.id_jns_penum desc");
        
        $response = [
            'detail' => $penumpang,
            'detail2' => $datas,
            'datas' => $penumpang_unverif,
            'tujuans' => $tujuan,
            'jenis' => $jenis,
            'tanggal' => $id_jadwal
        ];

        return response()->json($response, 200);
    }

    public function detailTanpaHistory(Request $request, $id_jadwal) 
    {
        $tanggal = $request->input('tanggal');
        $detail = DB::select(
            "SELECT a.nama_armada,n.nama_nahkoda, kp.nama_kapal, d.nama_dermaga as tujuan_akhir, d.lokasi, count(k.id) as jml_penum, jk.jadwal, jk.ekstra, '$tanggal' as tanggal_berangkat
            from keberangkatans k 
            INNER JOIN jadwal_keberangkatans jk on k.id_jadwal=jk.id_jadwal 
            inner join armadas a on jk.id_armada=a.id_armada 
            inner join nahkodas n on n.id_nahkoda=jk.id_nahkoda 
            inner join kapals kp on kp.id_kapal=jk.id_kapal 
            inner join rutes r on r.id_rute=jk.id_rute 
            inner join dermagas d on d.id_dermaga=r.tujuan_akhir 
            inner join penumpangs p on p.id_penumpang=k.id_penumpang
            where k.id_jadwal='$id_jadwal' 
            and date(k.tanggal_keberangkatan)='$tanggal' 
            and p.status_verif=1
            group by k.id_jadwal");
        if (!isset($detail[0])) {
            return response()->json(["message"=>"no data"]);
        }
        $data['detail'] = $detail[0];
        $data['detail2'] = DB::select(
            "SELECT p.nama_penumpang,p.no_identitas,p.jenis_kelamin,p.alamat,jt.nama_tujuan, p.harga_tiket from keberangkatans k
            inner join penumpangs p on p.id_penumpang=k.id_penumpang
            inner join jenis_tujuans jt on jt.id_tujuan=p.id_tujuan
            where k.id_jadwal='$id_jadwal' and date(k.tanggal_keberangkatan)='$tanggal' and p.status_verif=1");
        
        $tujuan = DB::select("SELECT jt.id_tujuan, jt.nama_tujuan, kp.total FROM `jenis_tujuans` as jt LEFT JOIN ( SELECT p.id_tujuan, COUNT(p.id_tujuan) as total FROM `penumpangs` as p INNER JOIN `keberangkatans` as k ON p.id_penumpang = k.id_penumpang WHERE DATE_FORMAT(k.tanggal_keberangkatan , '%Y-%m-%d') = '$tanggal' AND k.id_jadwal = '$id_jadwal' GROUP BY p.id_tujuan ) as kp ON jt.id_tujuan = kp.id_tujuan");
        
        $data['jenis'] = DB::select("SELECT jp.id_jns_penum, jp.nama_jns_penum, kp.total FROM `jenis_penumpangs` as jp LEFT JOIN ( SELECT p.id_jns_penum, COUNT(p.id_jns_penum) as total FROM `penumpangs` as p INNER JOIN `keberangkatans` as k ON p.id_penumpang = k.id_penumpang WHERE DATE_FORMAT(k.tanggal_keberangkatan , '%Y-%m-%d') = '$tanggal' AND k.id_jadwal = '$id_jadwal' GROUP BY p.id_jns_penum ) as kp ON jp.id_jns_penum = kp.id_jns_penum");
        $data['id_jadwal'] = $id_jadwal;
        
        // $response = [
        //     'detail' => $header,
        //     'detail2' => $penumpangs,
        //     'jenis' => $jenis,
        //     'id_jadwal' => $id_jadwal
        // ];

        // return response()->json($response, 200);
        // dd($data['detail']);
        $pdf = PDF::loadView('manifest',$data);
        return $pdf->stream('manifest.pdf');
    }
    
    public function detailTanpaHistory2(Request $request, $id_jadwal) 
    {
        $tanggal = $request->input('tanggal');
        $detail = DB::select(
            "SELECT a.nama_armada,n.nama_nahkoda, kp.nama_kapal, d.nama_dermaga as tujuan_akhir, d.lokasi, count(k.id) as jml_penum, jk.jadwal, jk.ekstra, '$tanggal' as tanggal_berangkat
            from keberangkatans k 
            INNER JOIN jadwal_keberangkatans jk on k.id_jadwal=jk.id_jadwal 
            inner join armadas a on jk.id_armada=a.id_armada 
            inner join nahkodas n on n.id_nahkoda=jk.id_nahkoda 
            inner join kapals kp on kp.id_kapal=jk.id_kapal 
            inner join rutes r on r.id_rute=jk.id_rute 
            inner join dermagas d on d.id_dermaga=r.tujuan_akhir 
            inner join penumpangs p on p.id_penumpang=k.id_penumpang
            where k.id_jadwal='$id_jadwal' 
            and date(k.tanggal_keberangkatan)='$tanggal' 
            and p.status_verif=1
            group by k.id_jadwal");
        if (!isset($detail[0])) {
            return response()->json(["message"=>"no data"]);
        }
        $data['detail'] = $detail[0];
        $data['detail2'] = DB::select(
            "SELECT p.nama_penumpang,p.no_identitas,p.jenis_kelamin,p.alamat,jt.nama_tujuan, p.harga_tiket from keberangkatans k
            inner join penumpangs p on p.id_penumpang=k.id_penumpang
            inner join jenis_tujuans jt on jt.id_tujuan=p.id_tujuan
            where k.id_jadwal='$id_jadwal' and date(k.tanggal_keberangkatan)='$tanggal' and p.status_verif=1");
        
        $tujuan = DB::select("SELECT jt.id_tujuan, jt.nama_tujuan, kp.total FROM `jenis_tujuans` as jt LEFT JOIN ( SELECT p.id_tujuan, COUNT(p.id_tujuan) as total FROM `penumpangs` as p INNER JOIN `keberangkatans` as k ON p.id_penumpang = k.id_penumpang WHERE DATE_FORMAT(k.tanggal_keberangkatan , '%Y-%m-%d') = '$tanggal' AND k.id_jadwal = '$id_jadwal' GROUP BY p.id_tujuan ) as kp ON jt.id_tujuan = kp.id_tujuan");
        
        $data['jenis'] = DB::select("SELECT jp.id_jns_penum, jp.nama_jns_penum, kp.total FROM `jenis_penumpangs` as jp LEFT JOIN ( SELECT p.id_jns_penum, COUNT(p.id_jns_penum) as total FROM `penumpangs` as p INNER JOIN `keberangkatans` as k ON p.id_penumpang = k.id_penumpang WHERE DATE_FORMAT(k.tanggal_keberangkatan , '%Y-%m-%d') = '$tanggal' AND k.id_jadwal = '$id_jadwal' GROUP BY p.id_jns_penum ) as kp ON jp.id_jns_penum = kp.id_jns_penum");
        $data['id_jadwal'] = $id_jadwal;
        
        // $response = [
        //     'detail' => $header,
        //     'detail2' => $penumpangs,
        //     'jenis' => $jenis,
        //     'id_jadwal' => $id_jadwal
        // ];

        // return response()->json($response, 200);
        // dd($data['detail']);
        $pdf = PDF::loadView('manifest2',$data);
        return $pdf->stream('manifest2.pdf');
    }

    public function data_bulanan(Request $request) 
    {
        if($request->input('tanggal') == null) {
            $this_month = Carbon::now()->format('Y-m');
            $penumpang = DB::select("SELECT jk.*, hk.*, n.nama_nahkoda, a.nama_armada, d.nama_dermaga as tujuan_awal, ds.nama_dermaga as tujuan_akhir, k.nama_kapal, t.total FROM `jadwal_keberangkatans` as jk INNER JOIN `armadas` as a ON jk.id_armada = a.id_armada INNER JOIN `nahkodas` as n ON jk.id_nahkoda = n.id_nahkoda INNER JOIN `rutes` as r ON jk.id_rute = r.id_rute INNER JOIN `dermagas` as d ON r.tujuan_awal = d.id_dermaga INNER JOIN `dermagas` as ds ON r.tujuan_akhir = ds.id_dermaga INNER JOIN `kapals` as k ON jk.id_kapal = k.id_kapal INNER JOIN `history_keberangkatans` as hk ON jk.id_jadwal = hk.id_jadwal LEFT JOIN (SELECT k.id_jadwal, COUNT(k.id) as total FROM `keberangkatans` as k WHERE DATE_FORMAT(k.tanggal_keberangkatan , '%Y-%m') = CURDATE() GROUP BY k.id_jadwal) as t on jk.id_jadwal = t.id_jadwal WHERE DATE_FORMAT(hk.tanggal_berangkat , '%Y-%m') = ". $this_month . " AND jk.deleted_at is null ORDER BY hk.id DESC");
            
            $total = DB::select("SELECT jt.id_tujuan, jt.nama_tujuan, kp.total FROM `jenis_tujuans` as jt LEFT JOIN ( SELECT p.id_tujuan, COUNT(p.id_tujuan) as total FROM `penumpangs` as p INNER JOIN `keberangkatans` as k ON p.id_penumpang = k.id_penumpang WHERE DATE_FORMAT(k.tanggal_keberangkatan , '%Y-%m') = ". $this_month . " GROUP BY p.id_tujuan ) as kp ON jt.id_tujuan = kp.id_tujuan");
        } else {
            $tanggal = $request->input('tanggal');
            $penumpang = DB::select("SELECT jk.*, hk.*, n.nama_nahkoda, a.nama_armada, d.nama_dermaga as tujuan_awal, ds.nama_dermaga as tujuan_akhir, k.nama_kapal, t.total FROM `jadwal_keberangkatans` as jk INNER JOIN `armadas` as a ON jk.id_armada = a.id_armada INNER JOIN `nahkodas` as n ON jk.id_nahkoda = n.id_nahkoda INNER JOIN `rutes` as r ON jk.id_rute = r.id_rute INNER JOIN `dermagas` as d ON r.tujuan_awal = d.id_dermaga INNER JOIN `dermagas` as ds ON r.tujuan_akhir = ds.id_dermaga INNER JOIN `kapals` as k ON jk.id_kapal = k.id_kapal INNER JOIN `history_keberangkatans` as hk ON jk.id_jadwal = hk.id_jadwal LEFT JOIN (SELECT k.id_jadwal, COUNT(k.id) as total FROM `keberangkatans` as k WHERE DATE_FORMAT(k.tanggal_keberangkatan , '%Y-%m') = '$tanggal' GROUP BY k.id_jadwal) as t on jk.id_jadwal = t.id_jadwal WHERE DATE_FORMAT(hk.tanggal_berangkat , '%Y-%m') = '$tanggal' AND jk.deleted_at is null ORDER BY hk.id DESC");
            
            $total = DB::select("SELECT jt.id_tujuan, jt.nama_tujuan, kp.total FROM `jenis_tujuans` as jt LEFT JOIN ( SELECT p.id_tujuan, COUNT(p.id_tujuan) as total FROM `penumpangs` as p INNER JOIN `keberangkatans` as k ON p.id_penumpang = k.id_penumpang WHERE DATE_FORMAT(k.tanggal_keberangkatan , '%Y-%m') = '$tanggal' GROUP BY p.id_tujuan ) as kp ON jt.id_tujuan = kp.id_tujuan");
        }
        
        $response = [
            'penumpang' => $penumpang,
            'total' => $total
        ];

        return response()->json($response, 200);
    }
    
    public function data_harian(Request $request) 
    {
        if($request->input('tanggal') == null) {
            $penumpang = DB::select("SELECT jk.*, hk.*, n.nama_nahkoda, a.nama_armada, d.nama_dermaga as tujuan_awal, ds.nama_dermaga as tujuan_akhir, k.nama_kapal, t.total FROM `jadwal_keberangkatans` as jk INNER JOIN `armadas` as a ON jk.id_armada = a.id_armada INNER JOIN `nahkodas` as n ON jk.id_nahkoda = n.id_nahkoda INNER JOIN `rutes` as r ON jk.id_rute = r.id_rute INNER JOIN `dermagas` as d ON r.tujuan_awal = d.id_dermaga INNER JOIN `dermagas` as ds ON r.tujuan_akhir = ds.id_dermaga INNER JOIN `kapals` as k ON jk.id_kapal = k.id_kapal INNER JOIN `history_keberangkatans` as hk ON jk.id_jadwal = hk.id_jadwal LEFT JOIN (SELECT k.id_jadwal, COUNT(k.id) as total FROM `keberangkatans` as k WHERE DATE_FORMAT(k.tanggal_keberangkatan , '%Y-%m-%d') = CURDATE() GROUP BY k.id_jadwal) as t on jk.id_jadwal = t.id_jadwal WHERE DATE_FORMAT(hk.tanggal_berangkat , '%Y-%m-%d') = CURDATE() AND jk.deleted_at is null ORDER BY hk.id DESC");
            
            $total = DB::select("SELECT jt.id_tujuan, jt.nama_tujuan, kp.total FROM `jenis_tujuans` as jt LEFT JOIN ( SELECT p.id_tujuan, COUNT(p.id_tujuan) as total FROM `penumpangs` as p INNER JOIN `keberangkatans` as k ON p.id_penumpang = k.id_penumpang WHERE DATE_FORMAT(k.tanggal_keberangkatan , '%Y-%m-%d') = CURDATE() GROUP BY p.id_tujuan ) as kp ON jt.id_tujuan = kp.id_tujuan");
            
             $jenis = DB::select("SELECT jp.id_jns_penum, jp.nama_jns_penum, kp.total FROM `jenis_penumpangs` as jp LEFT JOIN ( SELECT p.id_jns_penum, COUNT(p.id_jns_penum) as total FROM `penumpangs` as p INNER JOIN `keberangkatans` as k ON p.id_penumpang = k.id_penumpang WHERE DATE_FORMAT(k.tanggal_keberangkatan , '%Y-%m-%d') = CURDATE() and p.status_verif=1 GROUP BY p.id_jns_penum ) as kp ON jp.id_jns_penum = kp.id_jns_penum order by jp.id_jns_penum desc");
             
        } else {
            $tanggal = $request->input('tanggal');
            $penumpang = DB::select("SELECT jk.*, hk.*, n.nama_nahkoda, a.nama_armada, d.nama_dermaga as tujuan_awal, ds.nama_dermaga as tujuan_akhir, k.nama_kapal, t.total FROM `jadwal_keberangkatans` as jk INNER JOIN `armadas` as a ON jk.id_armada = a.id_armada INNER JOIN `nahkodas` as n ON jk.id_nahkoda = n.id_nahkoda INNER JOIN `rutes` as r ON jk.id_rute = r.id_rute INNER JOIN `dermagas` as d ON r.tujuan_awal = d.id_dermaga INNER JOIN `dermagas` as ds ON r.tujuan_akhir = ds.id_dermaga INNER JOIN `kapals` as k ON jk.id_kapal = k.id_kapal INNER JOIN `history_keberangkatans` as hk ON jk.id_jadwal = hk.id_jadwal LEFT JOIN (SELECT k.id_jadwal, COUNT(k.id) as total FROM `keberangkatans` as k WHERE DATE_FORMAT(k.tanggal_keberangkatan , '%Y-%m-%d') = '$tanggal' GROUP BY k.id_jadwal) as t on jk.id_jadwal = t.id_jadwal WHERE DATE_FORMAT(hk.tanggal_berangkat , '%Y-%m-%d') = '$tanggal' AND jk.deleted_at is null ORDER BY hk.id DESC");
            
            $total = DB::select("SELECT jt.id_tujuan, jt.nama_tujuan, kp.total FROM `jenis_tujuans` as jt LEFT JOIN ( SELECT p.id_tujuan, COUNT(p.id_tujuan) as total FROM `penumpangs` as p INNER JOIN `keberangkatans` as k ON p.id_penumpang = k.id_penumpang WHERE DATE_FORMAT(k.tanggal_keberangkatan , '%Y-%m-%d') = '$tanggal' GROUP BY p.id_tujuan ) as kp ON jt.id_tujuan = kp.id_tujuan");
            
             $jenis = DB::select("SELECT jp.id_jns_penum, jp.nama_jns_penum, kp.total FROM `jenis_penumpangs` as jp LEFT JOIN ( SELECT p.id_jns_penum, COUNT(p.id_jns_penum) as total FROM `penumpangs` as p INNER JOIN `keberangkatans` as k ON p.id_penumpang = k.id_penumpang WHERE DATE_FORMAT(k.tanggal_keberangkatan , '%Y-%m-%d') = '$tanggal' and p.status_verif=1 GROUP BY p.id_jns_penum ) as kp ON jp.id_jns_penum = kp.id_jns_penum order by jp.id_jns_penum desc");
        }
        
        $response = [
            'penumpang' => $penumpang,
            'total' => $total,
            'jenis' => $jenis
        ];

        return response()->json($response, 200);
    }
    
    public function grafikPenumpang($query) 
    {
        $armadas = Armada::all();

        if($query == 3) {
            $data = array(
                'time' => Carbon::now('+08:00')->format('Y'),
                'labels' => ['1','2','3','4','5','6','7','8','9','10','11','12'],
                'datasets' => array()
            );
            foreach ($armadas as $armada) {
                $temp = [0,0,0,0,0,0,0,0,0,0,0,0];

                $jadwals = DB::table('keberangkatans as k')
                    ->select(DB::raw('month(tanggal_keberangkatan) as bulan'), DB::raw('COUNT(k.id_penumpang) as total'))
                    ->join('jadwal_keberangkatans as jk', 'jk.id_jadwal', '=', 'k.id_jadwal')
                    ->where('jk.id_armada','=', $armada['id_armada'])
                    ->whereYear('k.tanggal_keberangkatan', Carbon::now('+08:00'))
                    ->groupBy('bulan')
                    ->get();
                foreach ($jadwals as $jadwal) {
                    $temp[$jadwal->bulan-1] = $jadwal->total;
                }
                $data['datasets'][] = [
                    'nama_armada' => $armada['nama_armada'],
                    'data' => $temp
                ];
            }

            return response()->json($data, 200);
        }
        else if ($query == 2) {
            $data = array(
                'time' => Carbon::now('+08:00')->format('M Y'),
                'labels' => ['1','2','3','4','5','6','7','8','9','10','11','12','13','14','15','16','17','18','19','20','21','22','23','24','25','26','27','28','29','30','31'],
                'datasets' => array()
            );
            foreach ($armadas as $armada) {
                $temp = [0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0];
                $jadwals = DB::table('keberangkatans as k')
                    ->select(DB::raw('day(tanggal_keberangkatan) as tanggal'), DB::raw('COUNT(k.id_penumpang) as total'))
                    ->join('jadwal_keberangkatans as jk', 'jk.id_jadwal', '=', 'k.id_jadwal')
                    ->where('jk.id_armada','=', $armada['id_armada'])
                    ->whereYear('k.tanggal_keberangkatan', Carbon::now('+08:00'))
                    ->whereMonth('k.tanggal_keberangkatan', Carbon::now('+08:00'))
                    ->groupBy('tanggal')
                    ->get();
                foreach ($jadwals as $jadwal) {
                    $temp[$jadwal->tanggal-1] = $jadwal->total;
                }

                $data['datasets'][] = [
                    'nama_armada' => $armada['nama_armada'],
                    'data' => $temp
                ];
            }

            return response()->json($data, 200);
        }
        else {
            $data = array(
                'time' => Carbon::now('+08:00')->format('d, M Y'),
                'labels' => ['01:00','02:00','03:00','04:00','05:00','06:00','07:00','08:00','09:00','10:00','11:00','12:00','13:00','14:00','15:00','16:00','17:00','18:00','19:00','20:00','21:00','22:00','23:00','24:00'],
                'datasets' => array()
            );
            foreach ($armadas as $armada) {
                $temp = [0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0];
                $jadwals = DB::table('keberangkatans as k')
                    ->select(DB::raw('hour(tanggal_keberangkatan) as hari'), DB::raw('COUNT(k.id_penumpang) as total'))
                    ->join('jadwal_keberangkatans as jk', 'jk.id_jadwal', '=', 'k.id_jadwal')
                    ->where('jk.id_armada','=', $armada['id_armada'])
                    ->whereYear('k.tanggal_keberangkatan', Carbon::now('+08:00'))
                    ->whereMonth('k.tanggal_keberangkatan', Carbon::now('+08:00'))
                    ->whereDay('k.tanggal_keberangkatan', Carbon::now('+08:00'))
                    ->groupBy('hari')
                    ->get();
                foreach ($jadwals as $jadwal) {
                    $temp[$jadwal->hari-1] = $jadwal->total;
                }

                $data['datasets'][] = [
                    'nama_armada' => $armada['nama_armada'],
                    'data' => $temp
                ];
            }

            return response()->json($data, 200);
        }
    }

    public function grafikKeberangkatan($query) 
    {
        if($query == 3) {
            $data = array(
                'time' => Carbon::now('+08:00')->format('Y'),
            );
            $temp = DB::table('keberangkatans as k')
            ->select(DB::raw('count(k.tanggal_keberangkatan) as total'), 'a.nama_armada')
            ->join('jadwal_keberangkatans as jk', 'jk.id_jadwal', '=', 'k.id_jadwal')
            ->join('armadas as a', 'jk.id_armada', '=', 'a.id_armada')
            ->whereYear('k.tanggal_keberangkatan', Carbon::now('+08:00'))
            ->groupBy('a.id_armada')
            ->get();

            foreach ($temp as $value) {
                $data['total'][] = $value->total;
                $data['labels'][] = $value->nama_armada;
            }

            return response()->json($data, 200);
        }
        else if($query == 2) {
            $data = array(
                'time' => Carbon::now('+08:00')->format('M Y'),
            );
            $temp = DB::table('keberangkatans as k')
            ->select(DB::raw('count(k.tanggal_keberangkatan) as total'), 'a.nama_armada')
            ->join('jadwal_keberangkatans as jk', 'jk.id_jadwal', '=', 'k.id_jadwal')
            ->join('armadas as a', 'jk.id_armada', '=', 'a.id_armada')
            ->whereYear('k.tanggal_keberangkatan', Carbon::now('+08:00'))
            ->whereMonth('k.tanggal_keberangkatan', Carbon::now('+08:00'))
            ->groupBy('a.id_armada')
            ->get();

            foreach ($temp as $value) {
                $data['total'][] = $value->total;
                $data['labels'][] = $value->nama_armada;
            }

            return response()->json($data, 200);
        }
        else {
            $data = array(
                'time' => Carbon::now('+08:00')->format('d, M Y'),
            );
            $temp = DB::table('keberangkatans as k')
            ->select(DB::raw('count(k.tanggal_keberangkatan) as total'), 'a.nama_armada')
            ->join('jadwal_keberangkatans as jk', 'jk.id_jadwal', '=', 'k.id_jadwal')
            ->join('armadas as a', 'jk.id_armada', '=', 'a.id_armada')
            ->whereYear('k.tanggal_keberangkatan', Carbon::now('+08:00'))
            ->whereMonth('k.tanggal_keberangkatan', Carbon::now('+08:00'))
            ->whereDay('k.tanggal_keberangkatan', Carbon::now('+08:00'))
            ->groupBy('a.id_armada')
            ->get();

            foreach ($temp as $value) {
                $data['total'][] = $value->total;
                $data['labels'][] = $value->nama_armada;
            }

            return response()->json($data, 200);
        }
    }

    public function grafikKapal() 
    {
        $data = array(
            'time' => Carbon::now('+08:00')->format('d, M Y'),
        );
        $temp = DB::table('keberangkatans as k')
        ->select(DB::raw('count(jk.id_kapal) as total'), 'a.nama_armada')
        ->join('jadwal_keberangkatans as jk', 'jk.id_jadwal', '=', 'k.id_jadwal')
        ->join('armadas as a', 'jk.id_armada', '=', 'a.id_armada')
        ->join('history_keberangkatans as hk', 'jk.id_jadwal', '=', 'hk.id_jadwal')
        ->whereYear('k.tanggal_keberangkatan', Carbon::now('+08:00'))
        ->whereMonth('k.tanggal_keberangkatan', Carbon::now('+08:00'))
        ->whereDay('k.tanggal_keberangkatan', Carbon::now('+08:00'))
        ->groupBy('a.id_armada')
        ->get();

        foreach ($temp as $value) {
            $data['total'][] = $value->total;
            $data['labels'][] = $value->nama_armada;
        }

        return response()->json($data, 200);
    }

    public function grafikJenis($query) 
    {
        $jenis = JenisPenumpang::all();

        if($query == 3) {
            $data = array(
                'time' => Carbon::now('+08:00')->format('Y'),
                'labels' => ['1','2','3','4','5','6','7','8','9','10','11','12'],
                'datasets' => array()
            );
            foreach ($jenis as $value) {
                $temp = [0,0,0,0,0,0,0,0,0,0,0,0];

                $jadwals = DB::table('keberangkatans as k')
                    ->select(DB::raw('month(tanggal_keberangkatan) as bulan'), DB::raw('COUNT(k.id_penumpang) as total'))
                    ->join('penumpangs as p', 'p.id', '=', 'k.id_penumpang')
                    ->where('p.id_jns_penum','=', $value['id_jns_penum'])
                    ->whereYear('k.tanggal_keberangkatan', Carbon::now('+08:00'))
                    ->groupBy('bulan')
                    ->get();

                foreach ($jadwals as $jadwal) {
                    $temp[$jadwal->bulan-1] = $jadwal->total;
                }

                $data['datasets'][] = [
                    'nama_armada' => $value['nama_jns_penum'],
                    'data' => $temp
                ];
            }

            return response()->json($data, 200);
        }
        else if ($query == 2) {
            $data = array(
                'time' => Carbon::now('+08:00')->format('M Y'),
                'labels' => ['1','2','3','4','5','6','7','8','9','10','11','12','13','14','15','16','17','18','19','20','21','22','23','24','25','26','27','28','29','30','31'],
                'datasets' => array()
            );
            foreach ($jenis as $value) {
                $temp = [0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0];

                $jadwals = DB::table('keberangkatans as k')
                    ->select(DB::raw('day(tanggal_keberangkatan) as hari'), DB::raw('COUNT(k.id_penumpang) as total'))
                    ->join('penumpangs as p', 'p.id', '=', 'k.id_penumpang')
                    ->where('p.id_jns_penum','=', $value['id_jns_penum'])
                    ->whereYear('k.tanggal_keberangkatan', Carbon::now('+08:00'))
                    ->whereMonth('k.tanggal_keberangkatan', Carbon::now('+08:00'))
                    ->groupBy('hari')
                    ->get();

                foreach ($jadwals as $jadwal) {
                    $temp[$jadwal->hari-1] = $jadwal->total;
                }

                $data['datasets'][] = [
                    'nama_armada' => $value['nama_jns_penum'],
                    'data' => $temp
                ];
            }

            return response()->json($data, 200);
        }
        else {
            $data = array(
                'time' => Carbon::now('+08:00')->format('d, M Y'),
                'labels' => ['01:00','02:00','03:00','04:00','05:00','06:00','07:00','08:00','09:00','10:00','11:00','12:00','13:00','14:00','15:00','16:00','17:00','18:00','19:00','20:00','21:00','22:00','23:00','24:00'],
                'datasets' => array()
            );
            foreach ($jenis as $value) {
                $temp = [0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0];

                $jadwals = DB::table('keberangkatans as k')
                    ->select(DB::raw('hour(tanggal_keberangkatan) as hari'), DB::raw('COUNT(k.id_penumpang) as total'))
                    ->join('penumpangs as p', 'p.id', '=', 'k.id_penumpang')
                    ->where('p.id_jns_penum','=', $value['id_jns_penum'])
                    ->whereYear('k.tanggal_keberangkatan', Carbon::now('+08:00'))
                    ->whereMonth('k.tanggal_keberangkatan', Carbon::now('+08:00'))
                    ->whereDay('k.tanggal_keberangkatan', Carbon::now('+08:00'))
                    ->groupBy('hari')
                    ->get();

                foreach ($jadwals as $jadwal) {
                    $temp[$jadwal->hari-1] = $jadwal->total;
                }

                $data['datasets'][] = [
                    'nama_armada' => $value['nama_jns_penum'],
                    'data' => $temp
                ];
            }

            return response()->json($data, 200);
        }
    }

    public function detailHarian(Request $request)
    {
        if($request->input('tanggal')) {
            $tanggal = $request->input('tanggal');
        }
        else {
            $tanggal = date('Y-m-d');
        }
        $details = DB::select("SELECT kp.grt, kp.panjang, kp.dwt,hk.id_kapal,hk.id_jadwal,r.tujuan_awal,r.tujuan_akhir,kp.*, a.nama_armada, 
            count(hk.id) as count_trip,t.total as jml_penumpang FROM history_keberangkatans hk
            INNER JOIN jadwal_keberangkatans jk ON hk.`id_jadwal`=jk.`id_jadwal`
            inner join rutes r on r.id_rute=jk.id_rute
            INNER JOIN kapals kp ON kp.`id_kapal`=hk.`id_kapal`
            INNER JOIN armadas a ON a.`id_armada`=kp.`id_armada` 
            LEFT JOIN (SELECT k.id_jadwal, COUNT(p.id_penumpang) AS total FROM keberangkatans k
                INNER JOIN penumpangs p ON p.id_penumpang=k.id_penumpang
                WHERE DATE(k.tanggal_keberangkatan)='$tanggal' 
                AND p.status_verif=1 
                GROUP BY k.id_jadwal) t ON t.id_jadwal=hk.id_jadwal
            where date(hk.tanggal_berangkat) = date('$tanggal') 
            GROUP BY jk.id_rute,hk.id_kapal");
        $dermagas = DB::select(DB::raw("SELECT id_dermaga,nama_dermaga,lokasi,id_syahbandar FROM dermagas"));        

        return response()->json(['details'=>$details,'dermagas'=>$dermagas], 200);
    }

    public function detailHarianPDF(Request $request)
    {
        if($request->input('tanggal')) {
            $tanggal = $request->input('tanggal');
        }
        else {
            $tanggal = date('Y-m-d');
        }
        $tanggal2 = date('d-m-Y', strtotime($tanggal));
        $data['details'] = DB::select(
            "SELECT kp.grt, kp.panjang, kp.dwt,hk.id_kapal,hk.id_jadwal,r.tujuan_awal,r.tujuan_akhir,
            kp.*, a.nama_armada, count(hk.id) as count_trip,sum(hk.jml_penumpang) as jml_penumpang, 
            d1.nama_dermaga as tujuan_awal, d1.lokasi as lokasi_dermaga, d2.nama_dermaga as tujuan_akhir,
            '$tanggal2' as bulan
            FROM history_keberangkatans hk
            INNER JOIN jadwal_keberangkatans jk ON hk.`id_jadwal`=jk.`id_jadwal`
            inner join rutes r on r.id_rute=jk.id_rute
            left join (select id_dermaga,nama_dermaga,lokasi,id_syahbandar from dermagas) d1 ON d1.id_dermaga = r.tujuan_awal
            left join (select id_dermaga,nama_dermaga,lokasi,id_syahbandar from dermagas) d2 ON d2.id_dermaga = r.tujuan_akhir
            INNER JOIN kapals kp ON kp.`id_kapal`=hk.`id_kapal`
            INNER JOIN armadas a ON a.`id_armada`=kp.`id_armada` 
            where date(hk.tanggal_berangkat) = date('$tanggal') GROUP BY jk.id_rute,hk.id_kapal");
        $data['hari_bulan_tahun'] = date('d F Y', strtotime($tanggal));
        if(!isset($data['details'][0])) {
            return response()->json(['message' => 'no data']);
        }
        $pdf = PDF::loadView('laporan_harian',$data)->setPaper('a4', 'landscape');
        return $pdf->stream('laporan harian.pdf');
    }

    public function detailBulanan(Request $request)
    {
        if($request->input('tanggal')) {
            $tanggal = $request->input('tanggal');
        }
        else {
            $tanggal = date('Y-m-d');
        }
        $details = DB::select("SELECT kp.grt, kp.panjang, kp.dwt,hk.id_kapal,hk.`id_jadwal`,r.tujuan_awal,r.tujuan_akhir,kp.nama_kapal, a.nama_armada, 
            count(hk.id) as count_trip,sum(hk.jml_penumpang) as jml_penumpang FROM history_keberangkatans hk
            INNER JOIN jadwal_keberangkatans jk ON hk.`id_jadwal`=jk.`id_jadwal`
            inner join rutes r on r.id_rute=jk.id_rute
            INNER JOIN kapals kp ON kp.`id_kapal`=hk.`id_kapal`
            INNER JOIN armadas a ON a.`id_armada`=kp.`id_armada` where date_format(hk.tanggal_berangkat,'%Y%m') = date_format('$tanggal','%Y%m') GROUP BY jk.id_rute,hk.id_kapal");
        $dermagas = DB::select("SELECT id_dermaga,nama_dermaga,lokasi,id_syahbandar FROM dermagas");

        return response()->json(['details'=>$details,'dermagas'=>$dermagas], 200);
    }

    public function detailBulananPDF(Request $request)
    {
        if($request->input('tanggal')) {
            $tanggal = $request->input('tanggal');
        }
        else {
            $tanggal = date('Y-m-d');
        }
        $tanggal2 = date('m-Y', strtotime($tanggal));
        $data['details'] = DB::select("SELECT kp.grt, kp.panjang, kp.dwt,hk.id_kapal,hk.`id_jadwal`,
            kp.nama_kapal, a.nama_armada, count(hk.id) as count_trip,
            sum(hk.jml_penumpang) as jml_penumpang, d1.lokasi as lokasi_dermaga, 
            d1.nama_dermaga as tujuan_awal, d2.nama_dermaga as tujuan_akhir, '$tanggal2' as bulan
            FROM history_keberangkatans hk
            INNER JOIN jadwal_keberangkatans jk ON hk.`id_jadwal`=jk.`id_jadwal`
            inner join rutes r on r.id_rute=jk.id_rute
            INNER JOIN kapals kp ON kp.`id_kapal`=hk.`id_kapal`
            INNER JOIN armadas a ON a.`id_armada`=kp.`id_armada` 
            left join (select id_dermaga,nama_dermaga,lokasi,id_syahbandar from dermagas) d1 ON d1.id_dermaga = r.tujuan_awal
            left join (select id_dermaga,nama_dermaga,lokasi,id_syahbandar from dermagas) d2 ON d2.id_dermaga = r.tujuan_akhir
            where date_format(hk.tanggal_berangkat,'%Y%m') = date_format('$tanggal','%Y%m') 
            GROUP BY jk.id_rute,hk.id_kapal");
        if(!isset($data['details'][0])) {
            return response()->json(['message' => 'no data']);
        }
        // $dermagas = DB::select(DB::raw("SELECT id_dermaga,nama_dermaga,lokasi,id_syahbandar FROM dermagas"));
        $data['bulan_tahun'] = date('F Y', strtotime($tanggal));

        // return response()->json(['details'=>$details,'dermagas'=>$dermagas], 200);
        $pdf = PDF::loadView('laporan_bulanan',$data)->setPaper('a4', 'landscape');
        return $pdf->stream('laporan bulanan.pdf');
    }

    public function detailTahunan(Request $request)
    {
        if($request->input('tahun')) {
            $tahun = $request->input('tahun');
        }
        else {
            $tanggal = date('Y');
        }
        $details = DB::select("SELECT MONTH(hk.tanggal_berangkat) as bulan,a.id_armada,a.nama_armada,r.tujuan_awal,r.tujuan_akhir,
            count(hk.id) as count_trip,sum(hk.jml_penumpang) as jml_penumpang FROM history_keberangkatans hk
            INNER JOIN jadwal_keberangkatans jk ON hk.`id_jadwal`=jk.`id_jadwal`
            inner join rutes r on r.id_rute=jk.id_rute
            INNER JOIN kapals kp ON kp.`id_kapal`=hk.`id_kapal`
            INNER JOIN armadas a ON a.`id_armada`=kp.`id_armada` where date_format(hk.tanggal_berangkat,'%Y') = '$tahun' GROUP BY MONTH(hk.tanggal_berangkat),jk.id_rute,a.id_armada");
        
        $dermagas = DB::select("SELECT id_dermaga,nama_dermaga,lokasi,id_syahbandar FROM dermagas");

        return response()->json(['details'=>$details,'dermagas'=>$dermagas], 200);
    }

    public function detailJadwal($id_jadwal)
    {
        $jadwal = DB::select("SELECT jk.*, a.nama_armada, n.nama_nahkoda, kp.nama_kapal, kp.id_kapal, d1.lokasi as lokasi_awal, d1.nama_dermaga as dermaga_awal, d1.lokasi as lokasi_akhir, d2.nama_dermaga as dermaga_akhir,
            l.nama_loket, l.lokasi_loket, t.total as total_penumpang from jadwal_keberangkatans jk 
            inner join armadas a on jk.id_armada = a.id_armada
            inner join nahkodas n on jk.id_nahkoda = n.id_nahkoda
            inner join kapals kp on jk.id_kapal = kp.id_kapal
            inner join rutes r on jk.id_rute = r.id_rute
            left join (select id_dermaga, lokasi, nama_dermaga from dermagas) as d1 on d1.id_dermaga = r.tujuan_awal
            left join (select id_dermaga, lokasi, nama_dermaga from dermagas) as d2 on d2.id_dermaga = r.tujuan_akhir
            left join (select k.id_jadwal, count(p.id_penumpang) as total from keberangkatans k
                inner join penumpangs p on p.id_penumpang=k.id_penumpang
                where p.status_verif=1 and k.id_jadwal='$id_jadwal'
                and date(k.tanggal_keberangkatan)=curdate()) t ON t.id_jadwal=jk.id_jadwal
            inner join lokets l on jk.id_loket = l.id_loket
            where jk.id_jadwal = '$id_jadwal'");
        
        return response()->json(['jadwal'=>$jadwal], 200);
    }

    public function export_pdf_backend($id_armada)
    {
        $jadwal = DB::table('jadwal_keberangkatans')
            ->join('kapals', 'jadwal_keberangkatans.id_kapal', '=', 'kapals.id_kapal')
            ->join('rutes', 'jadwal_keberangkatans.id_rute', '=', 'rutes.id_rute')
            ->join('dermagas as d', 'rutes.tujuan_awal', '=', 'd.id_dermaga')
            ->join('dermagas as ds', 'rutes.tujuan_akhir', '=', 'ds.id_dermaga')
            ->join('history_keberangkatans as hk', 'hk.id_jadwal', '=', 'jadwal_keberangkatans.id_jadwal')
            ->join('armadas as a', 'jadwal_keberangkatans.id_armada', '=', 'a.id_armada')
            ->join('nahkodas as n', 'n.id_nahkoda', '=', 'jadwal_keberangkatans.id_nahkoda')
            ->select('jadwal_keberangkatans.*', 'kapals.nama_kapal', 'd.nama_dermaga as tujuan_awal', 'd.lokasi as lokasi_awal', 'ds.nama_dermaga as tujuan_akhir', 'ds.lokasi as lokasi_akhir', 'd.id_syahbandar', 't.total', 'hk.*','a.nama_armada','n.nama_nahkoda')
            ->where('jadwal_keberangkatans.id_armada', '=', $id_armada);
            
        if(!isset($_GET['tanggal'])) {
            $return = $jadwal
                    ->leftJoin(DB::raw("(SELECT k.id_jadwal, COUNT(k.id) as total FROM `keberangkatans` as k WHERE DATE_FORMAT(k.tanggal_keberangkatan , '%Y-%m-%d') = CURDATE() GROUP BY k.id_jadwal) as t"), 'jadwal_keberangkatans.id_jadwal', '=', 't.id_jadwal')
                    ->whereDate('hk.tanggal_berangkat', Carbon::today())
                    ->get();
            $penumpangs = DB::table('keberangkatans as k')
                    ->join('jadwal_keberangkatans as jk', 'jk.id_jadwal', '=', 'k.id_jadwal')
                    ->join('history_keberangkatans as hk', 'hk.id_jadwal', '=', 'jk.id_jadwal')
                    ->join('penumpangs as p', 'p.id_penumpang', '=', 'k.id_penumpang')
                    ->join('jenis_tujuans as jt', 'jt.id_tujuan', '=', 'p.id_tujuan')
                    ->select('p.nama_penumpang', 'p.no_identitas','p.jenis_kelamin', 'p.alamat', 'jt.nama_tujuan')
                    ->where('jk.id_armada', '=', $id_armada)
                    ->whereDate('k.tanggal_keberangkatan', '=', Carbon::today())
                    ->whereDate('hk.tanggal_berangkat', '=', Carbon::today())
                    ->get();
        } else {
            $tanggal = $_GET['tanggal'];
            $return = $jadwal
                    ->leftJoin(DB::raw("(SELECT k.id_jadwal, COUNT(k.id) as total FROM `keberangkatans` as k WHERE DATE_FORMAT(k.tanggal_keberangkatan , '%Y-%m-%d') = '$tanggal' GROUP BY k.id_jadwal) as t"), 'jadwal_keberangkatans.id_jadwal', '=', 't.id_jadwal')
                    ->whereDate('hk.tanggal_berangkat', Carbon::parse($tanggal))
                    ->get();
            $penumpangs = DB::table('keberangkatans as k')
                    ->join('jadwal_keberangkatans as jk', 'jk.id_jadwal', '=', 'k.id_jadwal')
                    ->join('history_keberangkatans as hk', 'hk.id_jadwal', '=', 'jk.id_jadwal')
                    ->join('penumpangs as p', 'p.id_penumpang', '=', 'k.id_penumpang')
                    ->join('jenis_tujuans as jt', 'jt.id_tujuan', '=', 'p.id_tujuan')
                    ->select('p.nama_penumpang', 'p.no_identitas','p.jenis_kelamin', 'p.alamat', 'jt.nama_tujuan')
                    ->where('jk.id_armada', '=', $id_armada)
                    ->whereDate('k.tanggal_keberangkatan', '=', $tanggal)
                    ->whereDate('hk.tanggal_berangkat', '=', $tanggal)
                    ->get();
        }
        $temp = $return->toArray();
        if (!isset($temp[0])) {
            return response()->json(['message'=>'no data'], 200);
        }
        $data['data'] = $temp[0];
        $dt = new DateTime($data['data']->tanggal_berangkat);
        $data['date'] = $dt->format('d F Y');
        $data['time'] = $dt->format('h:i');
        $data['penumpangs'] = $penumpangs->toArray();
        $data['message'] = 'data_loaded';

        $pdf = PDF::loadView('pdfmanifest',$data);
        return $pdf->stream('manifest.pdf');
    }
}
