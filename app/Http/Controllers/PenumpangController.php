<?php

namespace App\Http\Controllers;

use App\Models\JadwalKeberangkatan;
use App\Models\Penumpang;
use App\Models\Tiket;
use App\Models\Invoice;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use PDF;
use GuzzleHttp\Client;
class PenumpangController extends Controller
{
    //
    public function view_ekspedisi($id_tujuan) {
         $response = DB::select("SELECT * FROM `penumpangs` as p LEFT JOIN (SELECT id_penumpang, COUNT(id_barang) as total FROM `barangs` GROUP BY id_penumpang) as b ON p.id_penumpang = b.id_penumpang where p.id_tujuan = 5 AND DATE_FORMAT(p.tanggal , '%Y-%m-%d') = CURDATE() AND p.id_penumpang NOT IN (SELECT id_penumpang FROM `keberangkatans`)");
        
        return response()->json($response, 200);
    }
    
    public function get_master_barang() {
         $response = DB::select("select * from master_barang");
        
        return response()->json($response, 200);
    }
    
    public function add_master_barang(Request $request) {
        $this->validate($request,[
            'nama' => 'required|string',
        ]);
        
        DB::table('master_barang')->insert([
            'nama' => $request->input('nama')
        ]);
        
        $response = [
            'message' => 'Barang created',
        ];
        
        return response()->json($response, 200);
    }
    
    public function edit_master_barang(Request $request, $id) {
        $this->validate($request,[
            'nama' => 'required|string'
        ]);

        DB::table('master_barang')->where('id', $id)
            ->update(
                array(
                    'nama' => $request->input('nama')
                )
            );
        
        $response = [
            'message' => 'Barang edited'
        ];

        return response()->json($response, 200);
    }
    
    public function delete_master_barang($id) {
        DB::table('master_barang')->where('id', $id)
            ->delete();
        
        $response = [
            'message' => 'Barang deleted'
        ];

        return response()->json($response, 200);
    }
    
    public function add_barang(Request $request, $id_penumpang) {
        $this->validate($request,[
            'nama_barang' => 'required|string',
            'berat' => 'required'
        ]);
        
        DB::table('barangs')->insert([
            'nama_barang' => $request->input('nama_barang'),
            'berat' => $request->input('berat'),
            'id_penumpang' => $id_penumpang
        ]);
        
        $response = [
            'message' => 'Penumpang created',
        ];
        
        return response()->json($response, 200);
    }
    
    public function store_barang(Request $request) {
        $this->validate($request,[
            'nama_penumpang' => 'required|string',
            'no_identitas' => 'required',
            'jenis_kelamin' => 'required',
            'nomer_kendaraan' => 'required',
            'tanggal' => 'required',
        ]);
        
        $penumpang = new Penumpang([
            'nama_penumpang' => $request->input('nama_penumpang'),
            'no_identitas' => $request->input('no_identitas'),
            'id_jns_penum' => 1,
            'id_tujuan' => 5,
            'jenis_kelamin' => $request->input('jenis_kelamin'),
            'nomer_kendaraan' => $request->input('nomer_kendaraan'),
            'tanggal' => $request->input('tanggal'),
        ]);
        
        if ($penumpang->save()) {
            $response = [
                'message' => 'Penumpang created',
                'penumpang' => $penumpang
            ];
            
            return response()->json($response, 200);
        }

        return response()->json(['message' => 'Penumpang not created'], 404);
    }
    
    public function store(Request $request) {
        $this->validate($request,[
            'nama_penumpang' => 'required|string',
            'no_identitas' => 'required|string',
            'id_jns_penum' => 'required',
            'id_tujuan' => 'required',
            'id_tiket' => 'required',
            'jenis_kelamin' => 'required',
            'freepass' => 'required',
            'harga_tiket' => 'required',
            'tanggal' => 'required',
        ]);

        if($request->input('ket_freepass')) {
            $penumpang = new Penumpang([
                'nama_penumpang' => $request->input('nama_penumpang'),
                'no_identitas' => $request->input('no_identitas'),
                'id_jns_penum' => $request->input('id_jns_penum'),
                'id_tujuan' => $request->input('id_tujuan'),
                'jenis_kelamin' => $request->input('jenis_kelamin'),
                'alamat' => $request->input('alamat'),
                'status_verif' => $request->input('status_verif'),
                'freepass' => $request->input('freepass'),
                'ket_freepass' => $request->input('ket_freepass'),
                'harga_tiket' => $request->input('harga_tiket'),
            ]);
        }
        else {
            $penumpang = new Penumpang([
                'nama_penumpang' => $request->input('nama_penumpang'),
                'no_identitas' => $request->input('no_identitas'),
                'id_jns_penum' => $request->input('id_jns_penum'),
                'id_tujuan' => $request->input('id_tujuan'),
                'jenis_kelamin' => $request->input('jenis_kelamin'),
                'alamat' => $request->input('alamat'),
                'status_verif' => $request->input('status_verif'),
                'freepass' => $request->input('freepass'),
                'harga_tiket' => $request->input('harga_tiket'),
            ]);
        }

        if ($penumpang->save()) {
            $jadwal = JadwalKeberangkatan::query()->where('id_jadwal', $request->input('id_jadwal'))->firstOrFail();
            
            $jadwal->jadwalToPenumpang()->attach($penumpang, ['id_tiket' => $request->input('id_tiket')]);
            DB::table('keberangkatans')->where('id_jadwal', $request->input('id_jadwal'))->where('id_penumpang', $penumpang->id)
            ->update([
                'tanggal_keberangkatan' => $request->input('tanggal'),
                'id_tiket' => $request->input('id_tiket'),
            ]);
            $rute = $jadwal->jadwalToRute()->with('tujuan_awals')->with('tujuan_akhirs')->get();
            $kapal = $jadwal->jadwalToKapal()->get('nama_kapal');
            // $tiket = Tiket::where('id_jadwal', $jadwal->id_jadwal)->where('id_jns_penum', $penumpang->id_jns_penum)->where('nama_tiket', $request->input('nama_tiket'))->first('harga');
            $response = [
                'message' => 'Penumpang created',
                'penumpang' => $penumpang,
                'jadwal' => $jadwal,
                'rute' => $rute,
                'nama_kapal' => $kapal,
                'harga' => $penumpang->harga_tiket,
            ];
            $invoice = new Invoice;
            if ($request->input('email')) {
                $invoice->email = $request->input('email');
            }
            if ($request->input('payment_method')) {
                $invoice->payment_method = $request->input('payment_method');
            }
            $invoice->id_armada = $jadwal->id_armada;
            $invoice->grandtotal = $penumpang->harga_tiket;
            $invoice->save();
            return response()->json($response, 200);
        }

        return response()->json(['message' => 'Penumpang not created'], 404);
    }

    public function storeGroup(Request $request) {
        $client = new Client();
        $responses = array();
        $invoice = new Invoice;
        $invoice->save();
        $grandtotal = 0;
        foreach ($request['data'] as $key => $r) {
            $penumpang = new Penumpang([
                'nama_penumpang' => $r['nama_penumpang'],
                'no_identitas' => $r['no_identitas'],
                'id_jns_penum' => $r['id_jns_penum'],
                'id_tujuan' => $r['id_tujuan'],
                'jenis_kelamin' => $r['jenis_kelamin'],
                'alamat' => $r['alamat'],
                'status_verif' => isset($r['status_verif']) ? $r['status_verif']:0,
                'freepass' => $r['freepass'],
                'harga_tiket' => $r['harga_tiket'],
                'catatan'  => $r['catatan']
            ]);
            if (isset($r['email']) && $r['email'] != $invoice->email) {
                $invoice->email = $r['email'];
                $invoice->save();
            }
            if (isset($r['payment_method']) && $r['payment_method'] != $invoice->payment_method) {
                $invoice->payment_method = $r['payment_method'];
                $invoice->save();
            }
    
            if ($penumpang->save()) {
                $jadwal = JadwalKeberangkatan::query()->where('id_jadwal', $r['id_jadwal'])->firstOrFail();
                if ($invoice->id_armada != $jadwal->id_armada) {
                    $invoice->id_armada = $jadwal->id_armada;
                    $invoice->save();
                }
                $jadwal->jadwalToPenumpang()->attach($penumpang, ['id_tiket' => $r['id_tiket']]);
                DB::table('keberangkatans')->where('id_jadwal', $r['id_jadwal'])->where('id_penumpang', $penumpang->id)
                ->update([
                    'tanggal_keberangkatan' => $r['tanggal'],
                    'id_tiket' => $r['id_tiket'],
                    'id_invoice' => $invoice->id
                ]);
                $rute = $jadwal->jadwalToRute()->with('tujuan_awals')->with('tujuan_akhirs')->get();
                $kapal = $jadwal->jadwalToKapal()->get('nama_kapal');
                
                $response = [
                    'message' => 'Penumpang created',
                    'penumpang' => $penumpang,
                    'jadwal' => $jadwal,
                    'rute' => $rute,
                    'nama_kapal' => $kapal,
                    'harga' => $penumpang->harga_tiket,
                ];
                array_push($responses,$response);
                $grandtotal += $penumpang->harga_tiket;
                // $client = new Client();
                // $res = $client->request('POST', 'http://maiharta.ddns.net:3333/api/penjualan', [
                //         'json' => [
                //             'id_agen' => '58',
                //             'id_service' => '',
                //             'tanggal' => $request->,
                //             'penumpangs' => array(
                                
                //             )
                //         ]
                // ]);
            }
            else {
                return response()->json(['message' => 'Penumpang not created'], 404);
            }
        }
        $invoice->grandtotal = $grandtotal;
        $invoice->save();
        $invoice = DB::table('invoices as i')
                    ->selectRaw('CAST(i.id as CHAR) as id, i.id_armada, a.nama_armada, CAST(ROUND(i.grandtotal,0) as UNSIGNED) as grandtotal, i.qrValue, i.no_va, i.email, i.bill_number, i.status, DATE_FORMAT(i.created_at, "%Y-%m-%d") as created_at, i.payment_method')
                    ->join('armadas as a', 'a.id_armada', 'i.id_armada')
                    ->where('i.id', $invoice->id)
                    ->first();
        array_push($responses, ['invoice' => $invoice]);
        return response()->json($responses, 200);
    }

    public function storeByAtix(Request $request) {
        $responses = array();
        $invoice = new Invoice;
        $invoice->save();
        $grandtotal = 0;
        foreach ($request['data'] as $key => $r) {
            $detailTiket = Tiket::find($r['idtiket_siwalatri']);
            if($detailTiket){
                if(JadwalKeberangkatan::where('id_jadwal',$detailTiket->id_jadwal)->first()) {
                    $penumpang = new Penumpang([
                        'nama_penumpang' => $r['nama_penumpang'],
                        'no_identitas' => $r['no_identitas'],
                        'id_jns_penum' => $detailTiket->id_jns_penum,
                        'id_tujuan' => 1,
                        'jenis_kelamin' => ($r['jenis_kelamin'] == 1) ? 0 : 1,
                        'alamat' => $r['alamat'],
                        'status_verif' => 1,
                        'freepass' => 0,
                        'harga_tiket' => $detailTiket->harga,
                        'catatan' => (isset($r['catatan'])) ? $r['catatan'] : null
                    ]);
                    if (isset($r['email']) && $r['email'] != $invoice->email) {
                        $invoice->email = $r['email'];
                        $invoice->save();
                    }
                    if (isset($r['payment_method']) && $r['payment_method'] != $invoice->payment_method) {
                        $invoice->payment_method = $r['payment_method'];
                        $invoice->save();
                    }
            
                    if ($penumpang->save()) {
                        $jadwal = JadwalKeberangkatan::query()->where('id_jadwal', $r['idjadwal_siwalatri'])->firstOrFail();
                        if ($invoice->id_armada != $jadwal->id_armada) {
                            $invoice->id_armada = $jadwal->id_armada;
                            $invoice->save();
                        }
                        $jadwal->jadwalToPenumpang()->attach($penumpang, ['id_tiket' => $r['idtiket_siwalatri']]);
                        DB::table('keberangkatans')->where('id_jadwal', $r['idjadwal_siwalatri'])->where('id_penumpang', $penumpang->id)
                        ->update([
                            'tanggal_keberangkatan' => date('Y-m-d'),
                            'id_tiket' => $r['idtiket_siwalatri'],
                            'idtiket_atix' => $r['idtiket_atix'],
                            'id_invoice' => $invoice->id
                        ]);
                        $rute = $jadwal->jadwalToRute()->with('tujuan_awals')->with('tujuan_akhirs')->get();
                        $kapal = $jadwal->jadwalToKapal()->get('nama_kapal');
                        $response = [
                            'message' => 'Penumpang created',
                            'penumpang' => $penumpang,
                            'jadwal' => $jadwal,
                            'rute' => $rute,
                            'nama_kapal' => $kapal,
                            'harga' => $penumpang->harga_tiket,
                            'idtiket_atix' => $r['idtiket_atix'],
                        ];
                        array_push($responses, $response);
                        $grandtotal += $penumpang->harga_tiket;
                    }
                    else {
                        return response()->json(['message' => 'Penumpang not created'], 404);
                    }
                }
                else {
                    return response()->json(['message' => 'Data jadwal not found'], 404);
                }
            }else{
                return response()->json(['message' => 'Data tiket not found'], 404);
            }
        }
        $invoice->grandtotal = $grandtotal;
        $invoice->status = 1;
        $invoice->save();
        $invoice = DB::table('invoices as i')
                    ->selectRaw('CAST(i.id as CHAR) as id, i.id_armada, a.nama_armada, CAST(ROUND(i.grandtotal,0) as UNSIGNED) as grandtotal, i.qrValue, i.email, i.bill_number, i.status, DATE_FORMAT(i.created_at, "%Y-%m-%d") as created_at, i.payment_method')
                    ->join('armadas as a', 'a.id_armada', 'i.id_armada')
                    ->where('i.id', $invoice->id)
                    ->first();
        array_push($responses, ['invoice' => $invoice]);
        return response()->json($responses, 200);
    }

    public function view($id_penumpang) {
        $penumpang = Penumpang::query()
            ->with('penumpangToTujuan')
            ->with('penumpangToJenis')
            ->with('penumpangToJadwal')
            ->with('penumpangToKeberangkatan')
            ->where('id', $id_penumpang)->first();

        $jadwal = $penumpang['penumpangToJadwal'];

        $return_jadwal = array();
//        foreach ($jadwal as $item) {
//            $jdwl = JadwalKeberangkatan::query()->with('')
//        }

        return response()->json($penumpang, 200);
    }
    
    public function view_penumpang($id_armada) {
        $penumpang = DB::select("SELECT jk.jadwal, jk.harga, p.nama_penumpang, p.no_identitas, d.nama_dermaga as tujuan_awal , d.lokasi as lokasi_awal, d1.nama_dermaga as tujuan_akhir, d1.lokasi as lokasi_akhir, kp.nama_kapal FROM `jadwal_keberangkatans` as jk INNER JOIN `keberangkatans` as k ON jk.id_jadwal = k.id_jadwal INNER JOIN `kapals` as kp ON kp.id_kapal = jk.id_kapal INNER JOIN `penumpangs` as p ON k.id_penumpang = p.id_penumpang INNER JOIN `rutes` as r ON jk.id_rute = r.id_rute INNER JOIN `dermagas` as d ON r.tujuan_awal = d.id_dermaga INNER JOIN `dermagas` as d1 ON r.tujuan_akhir = d1.id_dermaga INNER JOIN `armadas` as a ON jk.id_armada = a.id_armada WHERE jk.id_armada = '$id_armada' AND DATE_FORMAT(k.tanggal_keberangkatan , '%Y-%m-%d') = CURDATE() AND jk.deleted_at IS NULL");

        return response()->json($penumpang, 200);
    }
    
    public function view_harian(Request $request, $id_armada) {
        if($request->input('tanggal') == null) {
            $penumpang = DB::select("SELECT jk.id_jadwal, jk.jadwal, jk.harga, jk.status, jk.ekstra, d.nama_dermaga as tujuan_awal , 
                    d.lokasi as lokasi_awal, d1.nama_dermaga as tujuan_akhir, d1.lokasi as lokasi_akhir, 
                    kp.nama_kapal, t.*, kp.kapasitas_penumpang FROM `jadwal_keberangkatans` as jk INNER JOIN `kapals` as kp 
                    ON kp.id_kapal = jk.id_kapal INNER JOIN `rutes` as r ON jk.id_rute = r.id_rute INNER JOIN `dermagas` as d 
                    ON r.tujuan_awal = d.id_dermaga INNER JOIN `dermagas` as d1 ON r.tujuan_akhir = d1.id_dermaga 
                    INNER JOIN `armadas` as a ON jk.id_armada = a.id_armada LEFT JOIN 
                    (SELECT k.id_jadwal, COUNT(if(p.status_verif=1,k.id,null)) as total, SUM(if(p.status_verif=1,p.harga_tiket,0)) as harga_total 
                    FROM `keberangkatans` as k INNER JOIN `tikets` as tk on k.id_tiket = tk.id inner join penumpangs p 
                    on k.id_penumpang = p.id_penumpang WHERE DATE_FORMAT(k.tanggal_keberangkatan , '%Y-%m-%d') = CURDATE()
                    GROUP BY k.id_jadwal) as t on jk.id_jadwal = t.id_jadwal WHERE jk.id_armada = '$id_armada' AND 
                    (jk.ekstra = 1 AND DATE_FORMAT(jk.created_at , '%Y-%m-%d') = CURDATE() OR jk.ekstra = 0) AND 
                    jk.deleted_at IS NULL");
        } else {
            $tanggal = $request->input('tanggal');
            $penumpang = DB::select(
                "SELECT jk.id_jadwal, jk.jadwal, jk.harga, jk.status, jk.ekstra, d.nama_dermaga AS tujuan_awal , 
                d.lokasi AS lokasi_awal, d1.nama_dermaga AS tujuan_akhir, d1.lokasi AS lokasi_akhir, 
                kp.nama_kapal, t.*, kp.kapasitas_penumpang FROM `jadwal_keberangkatans` AS jk 
                INNER JOIN `kapals` AS kp ON kp.id_kapal = jk.id_kapal 
                INNER JOIN `rutes` AS r ON jk.id_rute = r.id_rute 
                INNER JOIN `dermagas` AS d ON r.tujuan_awal = d.id_dermaga 
                INNER JOIN `dermagas` AS d1 ON r.tujuan_akhir = d1.id_dermaga 
                INNER JOIN `armadas` AS a ON jk.id_armada = a.id_armada 
                LEFT JOIN (SELECT k.id_jadwal, COUNT(IF(p.status_verif=1,k.id,NULL)) AS total, SUM(IF(p.status_verif=1,tk.harga,0)) AS harga_total 
                FROM `keberangkatans` AS k 
                    INNER JOIN `tikets` AS tk ON k.id_tiket = tk.id 
                    INNER JOIN penumpangs p ON k.id_penumpang = p.id_penumpang 
                    WHERE DATE_FORMAT(k.tanggal_keberangkatan , '%Y-%m-%d') = '$tanggal' 
                    GROUP BY k.id_jadwal) AS t ON jk.id_jadwal = t.id_jadwal 
                WHERE jk.id_armada = '$id_armada' AND (jk.ekstra = 1 AND DATE_FORMAT(jk.created_at , '%Y-%m-%d') = '$tanggal' OR jk.ekstra = 0)
                AND jk.deleted_at IS NULL");
        }

        return response()->json($penumpang, 200);
    }
    
    public function search(Request $request, $id_armada) {
        $nama_penumpang = $request->input('nama_penumpang');
        $penumpang = DB::select("SELECT jk.jadwal, jk.harga, p.nama_penumpang, p.no_identitas, d.nama_dermaga as tujuan_awal , d.lokasi as lokasi_awal, d1.nama_dermaga as tujuan_akhir, d1.lokasi as lokasi_akhir, kp.nama_kapal FROM `jadwal_keberangkatans` as jk INNER JOIN `keberangkatans` as k ON jk.id_jadwal = k.id_jadwal INNER JOIN `kapals` as kp ON kp.id_kapal = jk.id_kapal INNER JOIN `penumpangs` as p ON k.id_penumpang = p.id_penumpang INNER JOIN `rutes` as r ON jk.id_rute = r.id_rute INNER JOIN `dermagas` as d ON r.tujuan_awal = d.id_dermaga INNER JOIN `dermagas` as d1 ON r.tujuan_akhir = d1.id_dermaga INNER JOIN `armadas` as a ON jk.id_armada = a.id_armada WHERE jk.id_armada = '$id_armada' AND DATE_FORMAT(k.tanggal_keberangkatan , '%Y-%m-%d') = CURDATE() AND p.nama_penumpang LIKE '%$nama_penumpang%'");

        return response()->json($penumpang, 200);
    }
    
    public function total($id_jadwal, Request $request) {
        if(isset($request->tanggal) && isset($request->jml_penumpang_register)) {
            $penumpang = DB::select(
                "SELECT COUNT(k.id) as total, kp.kapasitas_penumpang FROM `keberangkatans` as k 
                INNER JOIN jadwal_keberangkatans as jk ON k.id_jadwal = jk.id_jadwal 
                INNER JOIN penumpangs p ON p.id_penumpang = k.id_penumpang
                LEFT JOIN kapals as kp ON jk.id_kapal = kp.id_kapal 
                WHERE k.id_jadwal = '$id_jadwal' 
                AND p.status_verif = 1
                AND DATE_FORMAT(tanggal_keberangkatan , '%Y-%m-%d') = '$request->tanggal'");
            if ($request->jml_penumpang_register == $penumpang[0]->kapasitas_penumpang) {
                $penumpang['message'] = "penumpang sudah penuh";
            }
        }
        else {
            $penumpang = DB::select(
                "SELECT COUNT(k.id) as total, kp.kapasitas_penumpang FROM `keberangkatans` as k 
                INNER JOIN jadwal_keberangkatans as jk ON k.id_jadwal = jk.id_jadwal 
                INNER JOIN penumpangs p ON p.id_penumpang = k.id_penumpang
                LEFT JOIN kapals as kp ON jk.id_kapal = kp.id_kapal 
                WHERE k.id_jadwal = '$id_jadwal' 
                AND p.status_verif = 1
                AND DATE_FORMAT(tanggal_keberangkatan , '%Y-%m-%d') = CURDATE()");
        }
        
        

        return response()->json($penumpang, 200);
    }
    
    public function detail_jenis_penumpang(Request $request, $id_jadwal) {
        $tanggal = $request->input('tanggal');
        $penumpang = DB::select("SELECT jp.nama_jns_penum ,COUNT(p.id_jns_penum) as total FROM `jenis_penumpangs` as jp INNER JOIN penumpangs as p ON p.id_jns_penum = jp.id_jns_penum INNER JOIN (SELECT * FROM `keberangkatans` WHERE id_jadwal = '$id_jadwal' AND DATE_FORMAT(tanggal_keberangkatan , '%Y-%m-%d') = '$tanggal') as k ON p.id_penumpang = k.id_penumpang GROUP BY jp.nama_jns_penum");

        return response()->json($penumpang, 200);
    }
    
    public function detail_tujuan_penumpang(Request $request, $id_jadwal) {
        $tanggal = $request->input('tanggal');
        $penumpang = DB::select("SELECT jt.nama_tujuan ,COUNT(p.id_tujuan) as total FROM `jenis_tujuans` as jt INNER JOIN penumpangs as p ON p.id_tujuan = jt.id_tujuan INNER JOIN (SELECT * FROM `keberangkatans` WHERE id_jadwal = '$id_jadwal' AND DATE_FORMAT(tanggal_keberangkatan , '%Y-%m-%d') = '$tanggal') as k ON p.id_penumpang = k.id_penumpang GROUP BY jt.nama_tujuan");

        return response()->json($penumpang, 200);
    }

    public function index() {
        $penumpang = Penumpang::query()->with('penumpangToTujuan')->with('penumpangToJenis')->get();

        return response()->json($penumpang, 200);
    }

    public function edit(Request $request, $id_penumpang) {
        $this->validate($request,[
            'nama_penumpang' => 'required|string',
            'no_identitas' => 'required|string',
            // 'id_jns_penum' => 'required',
            // 'id_tujuan' => 'required',
            // 'id_jadwal' => 'required',
            'status_verif' => 'required',
            'freepass' => 'required',
            'tanggal' => 'required',
        ]);

        if($request->input('ket_freepass')) {
            $penumpang = [
                'nama_penumpang' => $request->input('nama_penumpang'),
                'no_identitas' => $request->input('no_identitas'),
                'id_jns_penum' => $request->input('id_jns_penum'),
                // 'id_tujuan' => $request->input('id_tujuan'),
                // 'id_jadwal' => $request->input('id_jadwal'),
                'alamat' => $request->input('alamat'),
                'status_verif' => $request->input('status_verif'),
                'freepass' => $request->input('freepass'),
                'ket_freepass' => $request->input('ket_freepass'),
                'harga_tiket' => $request->input('harga_tiket'),
            ];
        }
        else {
            $penumpang = [
                'nama_penumpang' => $request->input('nama_penumpang'),
                'no_identitas' => $request->input('no_identitas'),
                'id_jns_penum' => $request->input('id_jns_penum'),
                // 'id_tujuan' => $request->input('id_tujuan'),
                // 'id_jadwal' => $request->input('id_jadwal'),
                'alamat' => $request->input('alamat'),
                'status_verif' => $request->input('status_verif'),
                'freepass' => $request->input('freepass'),
                'harga_tiket' => $request->input('harga_tiket'),
            ];
        }
        

        $data = Penumpang::where('id_penumpang',$id_penumpang)->with('penumpangToTujuan')->with('penumpangToJenis');
        if($request->input('id_jadwal')) {
            if ($request->input('id_tiket')) {
                DB::table('keberangkatans')->where('id_penumpang', $id_penumpang)
                ->update(
                    array(
                        'tanggal_keberangkatan' => $request->input('tanggal'),
                        'id_jadwal' => $request->input('id_jadwal'),
                        'id_tiket' => $request->input('id_tiket'),
                    )
                );
            }
            else {
                DB::table('keberangkatans')->where('id_penumpang', $id_penumpang)
                ->update(
                    array(
                        'tanggal_keberangkatan' => $request->input('tanggal'),
                        'id_jadwal' => $request->input('id_jadwal'),
                    )
                );
            }
        }
        else {
            if ($request->input('id_tiket')) {
                DB::table('keberangkatans')->where('id_penumpang', $id_penumpang)
                ->update(
                    array(
                        'tanggal_keberangkatan' => $request->input('tanggal'),
                        'id_tiket' => $request->input('id_tiket'),
                    )
                );
            }
            else {
                DB::table('keberangkatans')->where('id_penumpang', $id_penumpang)
                ->update(
                    array(
                        'tanggal_keberangkatan' => $request->input('tanggal'),
                    )
                );
            }
        }
        
        if ($data) {
            if ($data->update($penumpang)) {
                $response = [
                    'message' => 'Penumpang updated',
                    'penumpang' => $data->get()
                ];

                return response()->json($response, 200);
            }

            return response()->json(['message' => 'Penumpang not created'], 404);
        }

        return response()->json(['message' => 'Penumpang not found'], 404);
    }

    public function penumpangByJadwalTanggal($id_jadwal, $tanggal)
    {
        $penumpangs = DB::select("SELECT p.*, jp.nama_jns_penum, d.nama_dermaga as tujuan from keberangkatans k
            inner join penumpangs p on k.id_penumpang = p.id_penumpang
            inner join jenis_penumpangs jp on p.id_jns_penum = jp.id_jns_penum
            inner join dermagas d on p.id_tujuan = d.id_dermaga
            where k.id_jadwal = '$id_jadwal' and date(k.tanggal_keberangkatan) = '$tanggal'");

        return response()->json(['penumpangs'=>$penumpangs], 200);
    }
    
    public function penumpangByTanggal($tanggal)
    {
        $penumpangs = DB::select(DB::raw("SELECT p.*, jp.nama_jns_penum, d.nama_dermaga from keberangkatans k
            inner join penumpangs p on k.id_penumpang = p.id_penumpang
            inner join jenis_penumpangs jp on p.id_jns_penum = jp.id_jns_penum
            inner join jadwal_keberangkatans jk on k.id_jadwal = jk.id_jadwal
            INNER JOIN `rutes` as r ON jk.id_rute = r.id_rute
            INNER JOIN `dermagas` as d ON r.tujuan_awal = d.id_dermaga
            where date(k.tanggal_keberangkatan) = '$tanggal'"));

        return response()->json(['penumpangs'=>$penumpangs], 200);
    }

    public function updateStatusInvoice(Request $request) {
        $this->validate($request,['id_invoice' => 'required']);
        $id_invoice = $request->id_invoice;
        $invoice = Invoice::find($id_invoice);
        $penumpangs = DB::table('penumpangs as p')
                        ->select('p.*')
                        ->join('keberangkatans as k', 'k.id_penumpang', 'p.id_penumpang')
                        ->where('id_invoice', $id_invoice);
        if (count($penumpangs->get()) == 0) {
            return response()->json(['message' => 'Penumpang tidak ditemukan'], 404);
        }
        $penumpangs->update(['status_verif' => 1]);
        $invoice->status = 1;
        $invoice->save();

        return response()->json(['penumpangs'=>$penumpangs->get()], 200);
    }
    
    public function getInvoice(Request $request)
    {
        $this->validate($request,['id_invoice' => 'required']);
        $id = $request->id_invoice;
        $invoice = Invoice::with('armada')->find($id);
        $keberangkatans = DB::select("SELECT DATE(k.tanggal_keberangkatan) AS tanggal, d1.nama_dermaga AS dermaga_awal, d2.nama_dermaga AS dermaga_akhir, jk.jadwal, kp.nama_kapal, COUNT(k.id) AS jumlah FROM keberangkatans k
                INNER JOIN jadwal_keberangkatans jk ON k.id_jadwal = jk.id_jadwal
                INNER JOIN rutes r ON jk.id_rute = r.id_rute
                INNER JOIN dermagas d1 ON r.tujuan_awal = d1.id_dermaga
                INNER JOIN dermagas d2 ON r.tujuan_akhir = d2.id_dermaga
                INNER JOIN kapals kp ON kp.id_kapal = jk.id_kapal
                WHERE k.id_invoice = $invoice->id
                GROUP BY k.id_invoice
            ");
        $penumpangs = DB::table('penumpangs as p')
                        ->select('p.nama_penumpang', 'p.no_identitas')
                        ->join('keberangkatans as k', 'k.id_penumpang', 'p.id_penumpang')
                        ->where('k.id_invoice', $invoice->id)
                        ->get();
        
        return response()->json(['invoice' => $invoice, 'keberangkatans' => $keberangkatans[0], 'penumpangs' => $penumpangs], 200);
    }
    
    public function updateBillNumberInvoice(Request $request)
    {
        $this->validate($request,['id_invoice' => 'required']);
        $invoice = Invoice::where('id', $request->id_invoice)->update(['bill_number' => $request->bill_number, 'qrValue' => $request->qrvalue]);
        return response()->json(Invoice::with('armada')->find($request->id_invoice), 200);
    }

    public function cetakPenjualan(Request $request)
    {
        $data = DB::table('keberangkatans as k')
                    ->selectRaw('DATE(k.tanggal_keberangkatan) as tanggal, p.id_penumpang as nomor_tiket, "-" AS agent, p.nama_penumpang, jk.jadwal as jam, "-" as kewarganegaraan, t.nama_tiket, t.harga, i.payment_method')
                    ->join('penumpangs as p', 'p.id_penumpang', 'k.id_penumpang')
                    ->join('tikets as t', 't.id', 'k.id_tiket')
                    ->join('jadwal_keberangkatans as jk', 'k.id_jadwal', 'jk.id_jadwal')
                    ->join('invoices as i', 'k.id_invoice', 'i.id')
                    ->where('p.status_verif', 1);
                if (isset($request->tanggal)) {
                    $data = $data->whereDate('k.tanggal_keberangkatan', $request->tanggal);
                }
                if (isset($request->id_jadwal)) {
                    $data = $data->where('k.id_jadwal', $request->id_jadwal);
                }
                $data = $data->get();
        $response['data'] = $data;

        // return response()->json($data, 200);
        $customPaper = array(0,0,500.00,1200.00);
        $pdf = PDF::loadView('pdfpenjualan',$response)->setPaper($customPaper, 'landscape');
        return $pdf->stream('penjualan.pdf');
    }
    
    public function updateInvoice(Request $request){
        $this->validate($request,['id_invoice' => 'required']);
        $id_invoice = $request->id_invoice;
        $invoice = Invoice::find($id_invoice);
        $penumpangs = DB::table('penumpangs as p')
                        ->select('p.*')
                        ->join('keberangkatans as k', 'k.id_penumpang', 'p.id_penumpang')
                        ->where('id_invoice', $id_invoice);
        if (count($penumpangs->get()) == 0) {
            return response()->json(['message' => 'Penumpang tidak ditemukan'], 404);
        }
        if ($request->expireddate) {
            $invoice->expiredDate = $request->expiredDate;
        }
        if ($request->nns) {
            $invoice->nns = $request->nns;
        }
        if ($request->nmid) {
            $invoice->nmid = $request->nmid;
        }
        if($request->bill_number){
            $invoice->bill_number = $request->bill_number;
        }
        if($request->qrvalue){
            $invoice->qrValue =  $request->qrvalue;
        }
        if($request->no_va){
            $invoice->no_va = $request->no_va;
        }
        if($request->status == 0) {
            $penumpangs->update(['status_verif' => 0]);
            $invoice->status = 0;
            $invoice->save();
            return response()->json(['invoice'=>$invoice, 'message' => 'Invoice berhasil diupdate dan belum terbayarkan'], 200);
        } else {
            $penumpangs->update(['status_verif' => 1]);
            $invoice->status = 1;
            $invoice->save();
            return response()->json(['invoice'=>$invoice, 'message'=>'Invoice berhasil diupdate dan terbayarkan'], 200);
        }
    }

    public function testApi(Request $request){
        $client = new Client();
        $data = new stdClass();
        $penumpang = new stdClass();
        $data->id_agen = '58';
        $data->id_service = '';
        $data->tanggal = '2023-08-08';
        $penumpang->email = 'test@example.com';
        $penumpang->id_jenis_tiket = 1;
        $penumpang->jenis_kelamin = "1";
        $penumpang->nama_penumpang = "Gede Dragon";
        $penumpang->no_identitas = "0";
        $data->penumpangs = [$penumpang];
            // 'penumpangs':[
            //     {
            //         'email': 'test@example.com',
            //         'id_jenis_tiket': 1
            //         'jenis_kelamin': "1"
            //         'nama_penumpang': "Gede Dragon"
            //         'no_identitas': "0"
            //     }
            // ]
        
        $url = "http://maiharta.ddns.net:3333/api/penjualan";
        $r = $client->request('POST', $url, [
        'body' => $data
        ]);

        echo $r->getBody();
        return response()->json($r->getBody(), 200);
    }
}
