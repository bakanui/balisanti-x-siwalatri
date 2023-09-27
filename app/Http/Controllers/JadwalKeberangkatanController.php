<?php

namespace App\Http\Controllers;

use App\Models\JadwalKeberangkatan;
use App\Models\HistoryKeberangkatan;
use App\Models\Kapal;
use App\Models\Rute;
use App\Models\Armada;
use App\Models\Keberangkatan;
use App\Models\Loket;
use App\Models\Tiket;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Validator;

class JadwalKeberangkatanController extends Controller
{
    //
    public function insert_jalur(Request $request ){
        $this->validate($request,[
            'id_jadwal' => 'required',
            'jalur' => 'required',
        ]);
        
        DB::table('jalur_manifest')->insert(
            array(
                'id' => null,
                'id_jadwal' => $request->input('id_jadwal'),
                'tanggal' => Carbon::today(), 
                'jalur' => $request->input('jalur'),
            )
        );
        
        return response()->json(['msg' => 'success'], 200);
        
    }
    
    public function view_dashboard($id_jadwal) {
        $return = DB::select(DB::raw("SELECT n.nama_nahkoda, a.nama_armada, k.*, jk.*, d.nama_dermaga as tujuan_awal, d1.nama_dermaga as tujuan_akhir, hk.*, t.* FROM `jadwal_keberangkatans` as jk
            INNER JOIN kapals as k ON jk.id_kapal = k.id_kapal
            INNER JOIN nahkodas as n ON jk.id_nahkoda = n.id_nahkoda
            INNER JOIN armadas as a ON jk.id_armada = a.id_armada
            INNER JOIN rutes as r ON jk.id_rute = r.id_rute
            INNER JOIN dermagas as d ON r.tujuan_awal = d.id_dermaga
            INNER JOIN dermagas as d1 ON r.tujuan_akhir = d1.id_dermaga
            INNER JOIN history_keberangkatan as hk ON hk.id_jadwal = jk.id_jadwal
            LEFT JOIN (SELECT kb.id_jadwal, COUNT(kb.id) as total FROM `keberangkatans` as kb WHERE DATE_FORMAT(kb.tanggal_keberangkatan , '%Y-%m-%d') = CURDATE() GROUP BY kb.id_jadwal) as t on jk.id_jadwal = t.id_jadwal
            where jk.id_jadwal = '$id_jadwal' and DATE_FORMAT(hk.tanggal_berangkat , '%Y-%m-%d') = CURDATE()"));
            
            return response()->json($return, 200);
    }
    
    public function manifest(Request $request) {
        $this->validate($request,[
            'catatan' => 'required',
            'photo' => 'required',
            'id_history' => 'required',
        ]);
        
        $history = DB::table('history_keberangkatan')->where('id', $request->input('id_history'));
        $id_jadwal = $history->first();
        
        if($id_jadwal != null){
            $file = $request->file('photo');
            $name = time();
            $extension = $file->getClientOriginalExtension();
            $nameFile = $id_jadwal->id_jadwal . '_' . $name . '.' . $extension;
            $path = Storage::putFileAs('photo', $file, $nameFile);
    
            $test = Storage::path($path);
            
            $history->update(
                array(
                    'catatan' => $request->input('catatan'),
                    'photo' => '/storage/app/photo/'.$nameFile
                )
            );
            
            $response = [
                'message' => 'Berhasil'
            ];
    
            return response()->json($response, 200);
        } else {
            $response = [
                'message' => 'id tidak ditemukan'
            ];
    
            return response()->json($response, 404);
        }
    }
    
    public function proses_approval(Request $request) {
        $this->validate($request,[
            'id' => 'required',
        ]);

        DB::table('detail_keberangkatan')->where('id', $request->input('id'))
            ->update(['approval' => 1]);
        
        $response = [
            'message' => 'Approved'
        ];

        return response()->json($response, 200);
    }
    
    public function view_approval($id_user, $approval) {
        $response = DB::select(DB::raw("SELECT dk.id, dk.id_jadwal, jk.jadwal,jk.status, a.nama_armada, d.nama_dermaga as tujuan_awal, ds.nama_dermaga as tujuan_akhir, k.nama_kapal, t.total FROM `detail_keberangkatan` as dk INNER JOIN `jadwal_keberangkatans` as jk ON dk.id_jadwal = jk.id_jadwal INNER JOIN `rutes` as r ON jk.id_rute = r.id_rute INNER JOIN `dermagas` as d ON r.tujuan_awal = d.id_dermaga INNER JOIN `dermagas` as ds ON r.tujuan_akhir = ds.id_dermaga INNER JOIN `armadas` as a ON jk.id_armada = a.id_armada INNER JOIN `kapals` as k ON jk.id_kapal = k.id_kapal LEFT JOIN (SELECT k.id_jadwal, COUNT(k.id) as total FROM `keberangkatans` as k WHERE DATE_FORMAT(k.tanggal_keberangkatan , '%Y-%m-%d') = CURDATE() GROUP BY k.id_jadwal) as t on jk.id_jadwal = t.id_jadwal WHERE d.id_syahbandar = '$id_user' AND dk.approval = $approval AND DATE_FORMAT(dk.tanggal_berangkat , '%Y-%m-%d') = CURDATE();"));

        return response()->json($response, 200);
    }
    
    public function create_jadwal_pelabuhan(Request $request, $id_armada) {
        $this->validate($request,[
            'jadwal' => 'required',
            'id_nahkoda' => 'required',
            'id_kapal' => 'required',
            'id_rute' => 'required',
            'jumlah_penumpang' => 'required'
        ]);
        
        $id_jadwal = uniqid();

        $jadwal = new JadwalKeberangkatan([
            'id_jadwal' => $id_jadwal,
            'jadwal' => $request->input('jadwal'),
            'status' => 'Sandar',
            'harga' => 0,
            'id_armada' => $id_armada,
            'id_nahkoda' => $request->input('id_nahkoda'),
            'id_kapal' => $request->input('id_kapal'),
            'id_rute' => $request->input('id_rute'),
            'ekstra' => 1,
        ]);

        if ($jadwal->save()) {
            $penumpangs = $request->input('id_penumpang');
            if(count($penumpangs) > 0) {
                foreach($penumpangs as $data){
                    DB::table('keberangkatans')->insert(
                        array(
                            'id_jadwal' => $id_jadwal,
                            'id_penumpang' => $data,
                        )
                    );
                }
            }
            
            for ($x = 0; $x < $request->input('jumlah_penumpang'); $x++) {
                DB::table('keberangkatans')->insert(
                    array(
                        'id_jadwal' => $id_jadwal,
                        'id_penumpang' => 0,
                    )
                );
            }
            
            $response = [
                'message' => 'Jadwal created'
            ];

            return response()->json($response, 200);
        }
        
        return response()->json(['message' => 'Jadwal not created'], 404);
    }
    
    public function view_tiket($id_jadwal) {
        $response = DB::select("SELECT * FROM `tikets` as t INNER JOIN `jenis_penumpangs` as jp ON t.id_jns_penum = jp.id_jns_penum where id_jadwal = '$id_jadwal'");

        return response()->json($response, 200);
    }
    
    public function search_tiket($id_jadwal, $id_jns_penum) {
        $response = DB::select("SELECT * FROM `tikets` as t INNER JOIN `jenis_penumpangs` as jp ON t.id_jns_penum = jp.id_jns_penum where t.id_jadwal = '$id_jadwal' AND t.id_jns_penum = '$id_jns_penum'");

        return response()->json($response, 200);
    }
    
    public function delete_tiket($id) {
        DB::table('tikets')->where('id', $id)
            ->delete();
        
        $response = [
            'message' => 'Tiket deleted'
        ];

        return response()->json($response, 200);
    }
    
    public function edit_tiket(Request $request, $id) {
        $this->validate($request,[
            'nama' => 'required|string',
            'harga' => 'required|integer',
            'id_jns_penum' => 'required|integer'
        ]);

        DB::table('tikets')->where('id', $id)
            ->update(
                array(
                    'nama_tiket' => $request->input('nama'), 
                    'harga' => $request->input('harga'),
                    'barang' => $request->input('barang'),
                    'id_jns_penum' => $request->input('id_jns_penum'),
                )
            );
        
        $response = [
            'message' => 'Tiket edited'
        ];

        return response()->json($response, 200);
    }
    
    public function add_tiket(Request $request, $id_jadwal) {
        $this->validate($request,[
            'nama' => 'required|string',
            'harga' => 'required|integer',
            'id_jns_penum' => 'required|integer'
        ]);

        DB::table('tikets')->insert(
            array(
                'id' => null,
                'id_jadwal' => $id_jadwal,
                'nama_tiket' => $request->input('nama'), 
                'harga' => $request->input('harga'),
                'id_jns_penum' => $request->input('id_jns_penum'),
                'barang' => $request->input('barang')
            )
        );
        
        $response = [
            'message' => 'Tiket created'
        ];

        return response()->json($response, 200);
    }
    
    public function store(Request $request, $id_armada) {
        $this->validate($request,[
            'jadwal' => 'required',
            'status' => 'required|string',
            'id_nahkoda' => 'required',
            'id_kapal' => 'required',
            'id_rute' => 'required',
            'ekstra' => 'required',
            'id_loket' => 'required',
        ]);

        $jadwal = new JadwalKeberangkatan([
            'id_jadwal' => uniqid(),
            'jadwal' => $request->input('jadwal'),
            'status' => $request->input('status'),
            'harga' => 0,
            'id_armada' => $id_armada,
            'id_nahkoda' => $request->input('id_nahkoda'),
            'id_kapal' => $request->input('id_kapal'),
            'id_rute' => $request->input('id_rute'),
            'ekstra' => $request->input('ekstra'),
            'id_loket' => $request->input('id_loket'),
        ]);

        if ($jadwal->save()) {
            $response = [
                'message' => 'Jadwal created',
                'jadwal' => $jadwal
            ];

            return response()->json($response, 200);
        }

        return response()->json(['message' => 'Jadwal not created'], 404);
    }

    public function storeBySiwalatri(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'id' => 'required',
            'jadwal' => 'required',
            'status' => 'required|string',
            'id_nahkoda' => 'required',
            'id_kapal' => 'required',
            'id_rute' => 'required',
            'id_armada' => 'required',
            'ekstra' => 'required',
            'id_loket' => 'required',
            'harga_tiket' => 'required|array',
            'tanggal_berangkat' => 'required',
            'tanggal_sampai' => 'required'
        ]);
        if($validator->fails()) {
            return response()->json(['error'=>$validator->errors()], 400);
        }
        
        $data = $validator->validated();
        foreach ($data['harga_tiket'] as $h) {
            $validator2 = Validator::make($h, [
                'id_jns_penum' => 'required|integer',
                'harga' => 'required|decimal:0,10',
                'nama_tiket' => 'required|string'
            ]);
            if($validator2->fails()) {
                return response()->json(['error'=>$validator->errors()], 400);
            }
        }
        $arrHarga = $data['harga_tiket'];
        unset($data['harga_tiket']);
        $jadwal = new JadwalKeberangkatan;
        $jadwal->id_jadwal = $data['id'];
        $jadwal->jadwal = $data['jadwal'];
        $jadwal->status = $data['status'];
        $jadwal->harga = 0;
        $jadwal->id_armada = $data['id_armada'];
        $jadwal->ekstra = $data['ekstra'];
        $jadwal->id_kapal = $data['id_kapal'];
        $jadwal->id_nahkoda = $data['id_nahkoda'];
        $jadwal->id_rute = $data['id_rute'];
        $jadwal->id_loket = $data['id_loket'];
        if ($jadwal->save()) {
            $hargas = array_map(function ($e) use ($jadwal) {
                $e['id_jadwal'] = $jadwal->id_jadwal;
                return $e;
            }, $arrHarga);
            if (Tiket::insert($hargas)) {
                $keberangkatan = new HistoryKeberangkatan([
                    'id_jadwal' => $jadwal->id_jadwal,
                    'id_kapal' => $jadwal->id_kapal,
                    'tanggal_berangkat' => $request->input('tanggal_berangkat'),
                    'tanggal_sampai' => $request->input('tanggal_sampai'),
                    'jml_penumpang' => 0
                ]);
                if ($keberangkatan->save()) {
                    $response = [
                        'message' => 'Jadwal created',
                        'jadwal' => $jadwal
                    ];
                    return response()->json($response, 200);
                }else{
                    return response()->json(['message' => 'Jadwal not created'], 404);
                }
                
            }
        }
        
        return response()->json(['message' => 'Jadwal not created'], 404);
    }

    public function storeByEasyBook(Request $request) {
        $validator = Validator::make($request->all(),[ 
            'jadwal' => 'required',
            'id_kapal' => 'required',
            'id_rute' => 'required',
            'id_armada' => 'required',
            'schedule_id' => 'required',
            'ekstra' => 'required'
        ]);
        if($validator->fails()) {          
            return response()->json(['error'=>$validator->errors()], 401);
        }

        $jadwal = new JadwalKeberangkatan([
            'id_jadwal' => uniqid(),
            'jadwal' => $request->input('jadwal'),
            'status' => 'Sandar',
            'harga' => 0,
            'id_armada' => $request->input('id_armada'),
            'id_nahkoda' => '0',
            'id_kapal' => $request->input('id_kapal'),
            'id_rute' => $request->input('id_rute'),
            'ekstra' => $request->input('ekstra'),
            'id_loket' => 0,
            'schedule_id' => $request->input('schedule_id')
        ]);

        if ($jadwal->save()) {
            $response = [
                'message' => 'Jadwal created',
                'jadwal' => $jadwal
            ];

            return response()->json($response, 200);
        }

        return response()->json(['message' => 'Jadwal not created'], 404);
    }

    public function getByEasyBook() {
        return response()->json(['message' => 'ok', 'data' => JadwalKeberangkatan::whereNotNull('schedule_id')->get()]);
    }

    public function view($id_jadwal) {
        $jadwal = JadwalKeberangkatan::query()
            ->with('jadwalToArmada')
            ->with('jadwalToNahkoda')
            ->with('jadwalToKapal')
            ->with('jadwalToRute')
            ->with('jadwalToLoket')
            ->where('id_jadwal', $id_jadwal)->first();

        return response()->json($jadwal, 200);
    }
    
    public function delete($id_jadwal) {
        $jadwal = JadwalKeberangkatan::query()->where('id_jadwal', $id_jadwal)->delete();

        return response()->json($jadwal, 200);
    }

    public function list_nahkoda($id_nahkoda) {
        $status_kapal = $_GET['status'];
        $jadwal = DB::table('jadwal_keberangkatans')
            ->join('kapals', 'jadwal_keberangkatans.id_kapal', '=', 'kapals.id_kapal')
            ->join('rutes', 'jadwal_keberangkatans.id_rute', '=', 'rutes.id_rute')
            ->join('dermagas as d', 'rutes.tujuan_awal', '=', 'd.id_dermaga')
            ->join('dermagas as ds', 'rutes.tujuan_akhir', '=', 'ds.id_dermaga')
            ->leftJoin(DB::raw("(SELECT k.id_jadwal, COUNT(k.id) as total FROM `keberangkatans` as k WHERE DATE_FORMAT(k.tanggal_keberangkatan , '%Y-%m-%d') = CURDATE() GROUP BY k.id_jadwal) as t"), 'jadwal_keberangkatans.id_jadwal', '=', 't.id_jadwal')
            ->select('jadwal_keberangkatans.*', 'kapals.nama_kapal', 'kapals.kapasitas_penumpang', 'd.nama_dermaga as tujuan_awal', 'd.lokasi as lokasi_awal', 'ds.nama_dermaga as tujuan_akhir', 'ds.lokasi as lokasi_akhir', 'd.id_syahbandar', 't.total')
            ->where('jadwal_keberangkatans.id_nahkoda', '=', $id_nahkoda)
            ->where('jadwal_keberangkatans.status', '=', $status_kapal)
            ->whereNotIn('jadwal_keberangkatans.id_jadwal', function ($query) {
                $query->select('history_keberangkatan.id_jadwal')
                ->from('history_keberangkatan')
                ->whereDate('history_keberangkatan.tanggal_berangkat', Carbon::today());
            })
            ->paginate(10);
            
        $return = array();
        $return['data'] = $jadwal;
        $return['link'] = (object) array(
            'first_page_url' => $jadwal->url($jadwal->firstItem()),
            'last_page_url' => $jadwal->url($jadwal->lastPage()),
            'next_page_url' => $jadwal->nextPageUrl(),
            'prev_page_url' => $jadwal->previousPageUrl()
        );
        $return['meta'] = (object) array(
            'current_page' => $jadwal->currentPage(),
            'per_page' => $jadwal->perPage(),
            'total' => $jadwal->total(),
            'last_page' => $jadwal->lastPage(),
            'from' => $jadwal->firstItem()
        );

        return response()->json($return, 200);
    }

    public function index($id_armada) {
        //->with('jadwalToPenumpang')
        $jadwal = JadwalKeberangkatan::query()
            ->with('jadwalToArmada')
            ->with('jadwalToNahkoda')
            ->with('jadwalToKapal')
            ->with('jadwalToRute')
            ->where('id_armada', $id_armada)
            ->where(function ($query) {
                $query->where('ekstra', '=', 0)
                ->orWhere(function ($query1) {
                    $query1->where('ekstra', '=', 1)
                    ->whereDate('created_at', Carbon::today());
                });
            })->orderBy('jadwal')->get();
        $lokets = Loket::where('id_armada',$id_armada)->get(['id_loket','nama_loket','lokasi_loket']);
        $response = ['jadwal'=>$jadwal, 'lokets'=>$lokets];

        return response()->json($response, 200);
    }

    public function keberangkatan(Request $request, $id_armada) {
        $tanggal = $request->query('tanggal');
        $jadwal = DB::table('keberangkatans as k')
            ->select('k.id_jadwal','jk.*', 'kp.nama_kapal', 'd.nama_dermaga as tujuan_awal', 'dt.nama_dermaga as tujuan_akhir', DB::raw('COUNT(k.id_penumpang) as total'))
            ->join('jadwal_keberangkatans as jk', 'jk.id_jadwal', '=', 'k.id_jadwal')
            ->join('penumpangs as p', 'k.id_penumpang', '=', 'p.id')
            ->join('rutes as r', 'jk.id_rute', '=', 'r.id_rute')
            ->join('dermagas as d', 'r.tujuan_awal', '=', 'd.id_dermaga')
            ->join('dermagas as dt', 'r.tujuan_akhir', '=', 'dt.id_dermaga')
            ->join('kapals as kp', 'kp.id_kapal', '=', 'jk.id_kapal')
            ->where('jk.id_armada','=', $id_armada)
            ->whereDate('k.tanggal_keberangkatan', '=', $tanggal)
            ->groupBy('k.id_jadwal')
            ->get();

        return response()->json($jadwal, 200);
    }

    public function view_keberangkatan(Request $request, $id_jadwal) {
        $tanggal = $request->query('tanggal');
        $jadwal = DB::table('keberangkatans as k')
            ->select('*')
            ->join('jadwal_keberangkatans as jk', 'jk.id_jadwal', '=', 'k.id_jadwal')
            ->join('penumpangs as p', 'k.id_penumpang', '=', 'p.id')
            ->join('rutes as r', 'jk.id_rute', '=', 'r.id_rute')
            ->join('dermagas as d', 'r.tujuan_awal', '=', 'd.id_dermaga')
            ->join('dermagas as dt', 'r.tujuan_akhir', '=', 'dt.id_dermaga')
            ->join('kapals as kp', 'kp.id_kapal', '=', 'jk.id_kapal')
            ->join('jenis_penumpangs as jp', 'jp.id_jns_penum', '=', 'p.id_jns_penum')
            ->join('jenis_tujuans as jt', 'jt.id_tujuan', '=', 'p.id_tujuan')
            ->where('jk.id_jadwal','=', $id_jadwal)
            ->whereDate('k.tanggal_keberangkatan', '=', $tanggal)
            ->get();

        return response()->json($jadwal, 200);
    }

    public function edit(Request $request, $id_jadwal) {
        $this->validate($request,[
            'jadwal' => 'required',
            'status' => 'required|string',
            'id_nahkoda' => 'required',
            'id_kapal' => 'required',
            'id_rute' => 'required',
            'ekstra' => 'required',
            'id_loket' => 'required',
        ]);

        $jadwal = [
            'jadwal' => $request->input('jadwal'),
            'status' => $request->input('status'),
            'id_nahkoda' => $request->input('id_nahkoda'),
            'id_kapal' => $request->input('id_kapal'),
            'id_rute' => $request->input('id_rute'),
            'ekstra' => $request->input('ekstra'),
            'id_loket' => $request->input('id_loket'),
            'status_kirim_mitra' => $request->input('status_kirim_mitra'),
        ];

        $data = JadwalKeberangkatan::query()
            ->with('jadwalToArmada')
            ->with('jadwalToNahkoda')
            ->with('jadwalToKapal')
            ->with('jadwalToRute')
            ->where('id_jadwal', $id_jadwal);

        if ($data) {
            if ($data->update($jadwal)) {
                $response = [
                    'message' => 'Jadwal edited',
                    'jadwal' => $data->get()
                ];

                return response()->json($response, 200);
            }

            return response()->json(['message' => 'Jadwal not edited'], 404);
        }

        return response()->json(['message' => 'Jadwal not found'], 404);
    }

    public function start($id_jadwal) {
        $tmp = array(
            'status' => 'Start'
        );

        $sql = JadwalKeberangkatan::query()->where('id_jadwal', $id_jadwal);
        if($sql->update($tmp)){
            $id_kapal = JadwalKeberangkatan::query()->select('id_kapal','id_jadwal')->where('id_jadwal', $id_jadwal)->first();
            $count_penumpang = count(Keberangkatan::where('id_jadwal', $id_kapal->id_jadwal)->whereDate('tanggal_keberangkatan', '=', date('Y-m-d'))->get());
            $id = DB::table('history_keberangkatan')->insertGetId([
                'id_jadwal' => $id_jadwal,
                'tanggal_berangkat' => Carbon::now('+08:00'),
                'id_kapal' => $id_kapal['id_kapal'],
                'jml_penumpang' => $count_penumpang,
            ]);
            
            $response = [
                'message' => 'Start now',
                'jadwal' => DB::table('history_keberangkatan')->where('id', $id)->first(),
                'status' => 'Start'
            ];
            
            return response()->json($response, 200);
        }
        
        return response()->json(['message' => 'Start failed'], 404);
    }

    public function stop(Request $request, $id_jadwal, $id) {
        $kilometer = $request->query('kilometer');

        $tmp = array(
            'status' => 'Off'
        );

        $sql = JadwalKeberangkatan::query()->where('id_jadwal', $id_jadwal);
        if($sql->update($tmp)){
            DB::table('history_keberangkatan')->where('id', $id)
            ->update([
                'tanggal_sampai' => Carbon::now('+08:00')
            ]);
            
            $id_kapal = JadwalKeberangkatan::query()->select('id_kapal')->where('id_jadwal', $id_jadwal)->first();
            
            $kapal = Kapal::query()->where('id_kapal', $id_kapal['id_kapal']);
            
            $kapal->update([
                'kilometer' => $kilometer
            ]);
            
            $response = [
                'message' => 'Stop now',
                'jadwal' => DB::table('history_keberangkatan')->where('id', $id)->first(),
                'status' => 'Sandar'
            ];
            
            return response()->json($response, 200);
        }

        return response()->json(['message' => 'Stop  failed'], 404);
    }

    public function view_now($id_armada) {
        $jadwal = JadwalKeberangkatan::query()
            ->with('jadwalToArmada')
            ->with('jadwalToNahkoda')
            ->with('jadwalToKapal')
            ->with('jadwalToRute')
            ->where('id_armada', $id_armada)
            ->whereDate('jadwal', Carbon::now('+08:00'))->get();

        return response()->json($jadwal, 200);
    }

    public function view_rute($id_rute) {
        $jadwal = JadwalKeberangkatan::query()
            ->with('jadwalToArmada')
            ->with('jadwalToKapal')
            ->with('jadwalToRute')
            ->where('id_rute', $id_rute)
            ->whereDate('jadwal', Carbon::today())->get();

        return response()->json($jadwal, 200);
    }

    public function view_penumpang($id_jadwal) {
        $jadwal = JadwalKeberangkatan::query()
            ->with('jadwalToPenumpang')
            ->where('id_jadwal', $id_jadwal)->get();

        return response()->json($jadwal, 200);
    }

    public function view_tujuan($id_rute) {
        $jadwal = JadwalKeberangkatan::query()
            ->with('jadwalToPenumpang')
            ->where('id_rute', $id_rute)->get();

        return response()->json($jadwal, 200);
    }

    public function view_kapal($id_kapal) {
        $jadwal = JadwalKeberangkatan::query()
            ->with('jadwalToArmada')
            ->with('jadwalToKapal')
            ->with('jadwalToRute')
            ->with('jadwalToPenumpang')
            ->where('id_kapal', $id_kapal)->get();

        return response()->json($jadwal, 200);
    }

    public function get_all(Request $request)
    {
        if (isset($request->nama_armada)) {
            $search_nama_armada = $request->nama_armada;
            $jadwal = DB::select("SELECT a.nama_armada,jk.id_jadwal,jk.jadwal,kp.nama_kapal, kp.kapasitas_penumpang, nk.nama_nahkoda, jk.status,d.nama_dermaga as tujuan_awal, 
            d.lokasi as lokasi_awal, d1.nama_dermaga as tujuan_akhir, d1.lokasi as lokasi_akhir, IF(t.total_penumpang IS NOT NULL,t.total_penumpang,0) AS total_penumpang from jadwal_keberangkatans jk
            inner join armadas a on a.id_armada=jk.id_armada inner join kapals kp on kp.id_kapal=jk.id_kapal 
            INNER JOIN rutes as r ON jk.id_rute = r.id_rute INNER JOIN nahkodas as nk ON jk.id_nahkoda = nk.id_nahkoda 
            INNER JOIN dermagas as d ON r.tujuan_awal = d.id_dermaga INNER JOIN dermagas as d1 ON r.tujuan_akhir = d1.id_dermaga 
            LEFT JOIN (SELECT k.id_jadwal, COUNT(IF(p.status_verif=1,k.id,NULL)) as total_penumpang FROM keberangkatans k  INNER JOIN penumpangs p ON k.id_penumpang = p.id_penumpang WHERE DATE(k.tanggal_keberangkatan)=DATE(NOW()) GROUP BY k.id_jadwal) as t ON t.id_jadwal = jk.id_jadwal
            where jk.ekstra = 0 AND a.nama_armada LIKE '%$search_nama_armada%' order by jk.jadwal");
        }
        else {
            $jadwal = DB::select("SELECT a.nama_armada,jk.id_jadwal,jk.jadwal,kp.nama_kapal, kp.kapasitas_penumpang, nk.nama_nahkoda, jk.status,d.nama_dermaga as tujuan_awal, 
            d.lokasi as lokasi_awal, d1.nama_dermaga as tujuan_akhir, d1.lokasi as lokasi_akhir, IF(t.total_penumpang IS NOT NULL,t.total_penumpang,0) AS total_penumpang from jadwal_keberangkatans jk
            inner join armadas a on a.id_armada=jk.id_armada inner join kapals kp on kp.id_kapal=jk.id_kapal 
            INNER JOIN rutes as r ON jk.id_rute = r.id_rute INNER JOIN nahkodas as nk ON jk.id_nahkoda = nk.id_nahkoda 
            INNER JOIN dermagas as d ON r.tujuan_awal = d.id_dermaga INNER JOIN dermagas as d1 ON r.tujuan_akhir = d1.id_dermaga 
            LEFT JOIN (SELECT k.id_jadwal, COUNT(IF(p.status_verif=1,k.id,NULL)) as total_penumpang FROM keberangkatans k  INNER JOIN penumpangs p ON k.id_penumpang = p.id_penumpang WHERE DATE(k.tanggal_keberangkatan)=DATE(NOW()) GROUP BY k.id_jadwal) as t ON t.id_jadwal = jk.id_jadwal
            where jk.ekstra = 0 order by jk.jadwal");
        }
        

         $response = array();
         foreach($jadwal as $data){
             if($data->status !== 'Off'){
                 
                    $timestamp = strtotime($data->jadwal) - 60*60;
                    $time = date('H:i', $timestamp);
        
                    $timestamp_now = strtotime(Carbon::now('+07:00')) + 60*60;
                    $time_now =  date('H:i', $timestamp_now);
        
                    if($time_now >= $time && $time_now <= $data->jadwal && $data->status != 'Start') {
                        $data->status = 'Persiapan';
                    }
                    
                     if($data->status == 'Nyandar')
                    {
                        $data->status = 'Sandar';
                    }
        
        
                    if($data->status == 'Start')
                    {
                        $data->status = 'Berlayar';
                    }
                    
                    
                 $response[] = [
                    'nama_armada' => $data->nama_armada,
                    'id_jadwal' => $data->id_jadwal,
                    'jadwal' => $data->jadwal,
                    'nama_kapal' => $data->nama_kapal,
                    'kapasitas_penumpang' => $data->kapasitas_penumpang,
                    'nama_nahkoda' => $data->nama_nahkoda,
                    'status' => $data->status,
                    'tujuan_awal' => $data->tujuan_awal,
                    'lokasi_awal' => $data->lokasi_awal,
                    'tujuan_akhir' => $data->tujuan_akhir,
                    'lokasi_akhir' => $data->lokasi_akhir,
                    'total_penumpang' => $data->total_penumpang
                ];
             }
                
         }

        return response()->json($response, 200);
    }

    public function jadwal_public()
    {
        $id_armada = $_GET['id_armada'];
        $id_loket = $_GET['id_loket'];
        $jadwal = JadwalKeberangkatan::where('id_armada', $id_armada)
            ->where('id_loket', $id_loket)
            ->where('status', 'Sandar')
            ->with('jadwalToArmada')
            ->with('jadwalToNahkoda')
            ->with('jadwalToKapal')
            ->with('jadwalToRute')
            ->where(function ($query) {
                $query->where('ekstra', '=', 0)
                ->orWhere(function ($query1) {
                    $query1->where('ekstra', '=', 1)
                    ->whereDate('created_at', Carbon::today());
                });
            })
            ->orderBy('jadwal')
            ->get();
        // $jadwal = JadwalKeberangkatan::where('id_loket', $id_loket)->get();

        return response()->json($jadwal, 200);
    }

    public function total_penumpang_jadwal()
    {
        $tanggal = $_GET['tanggal'];
        $jadwal = DB::select(
            "SELECT a.nama_armada,jk.id_jadwal,jk.jadwal,kp.nama_kapal, kp.kapasitas_penumpang, nk.nama_nahkoda, jk.status,d.nama_dermaga as tujuan_awal, 
            d.lokasi as lokasi_awal, d1.nama_dermaga as tujuan_akhir, d1.lokasi as lokasi_akhir, t.total_penumpang
            from jadwal_keberangkatans jk 
            inner join armadas a on a.id_armada=jk.id_armada 
            inner join kapals kp on kp.id_kapal=jk.id_kapal 
            INNER JOIN rutes as r ON jk.id_rute = r.id_rute 
            INNER JOIN nahkodas as nk ON jk.id_nahkoda = nk.id_nahkoda 
            INNER JOIN dermagas as d ON r.tujuan_awal = d.id_dermaga 
            INNER JOIN dermagas as d1 ON r.tujuan_akhir = d1.id_dermaga 
            LEFT JOIN (SELECT k.id_jadwal, COUNT(k.id_penumpang) as total_penumpang FROM keberangkatans k WHERE DATE(k.tanggal_keberangkatan) = '$tanggal' GROUP BY k.id_jadwal) as t ON t.id_jadwal = jk.id_jadwal
            where jk.ekstra = 0 order by jk.jadwal");

        return response()->json($jadwal, 200);
    }

    public function xml_to_json()
    {
        $xml = simplexml_load_file('https://data.bmkg.go.id/datamkg/MEWS/DigitalForecast/DigitalForecast-Bali.xml');
        $json = json_encode($xml);
        $array = json_decode($json,TRUE);
        $array = $array['forecast']['area'][6];
        $data = array();
        foreach ($array['parameter'] as $key => $a) {
            $data[$a['@attributes']['description']] = array();
            foreach($a['timerange'] as $key2 => $b) {
                $temp = $b['@attributes']['datetime'];
                $temp = date('d/m/Y H:i', strtotime($temp));
                array_push($data[$a['@attributes']['description']], ['datetime'=>$temp, 'value'=>$b['value']]);
            }
        }

        return response()->json($data, 200);
    }

    public function cariDermagaTujuan()
    {
        if (isset($_GET['awal'])) {
            $id_awal = $_GET['awal'];
            $data = DB::select("SELECT DISTINCT(r.tujuan_akhir), d.nama_dermaga FROM rutes r 
                INNER JOIN dermagas d ON r.tujuan_akhir=d.id_dermaga WHERE tujuan_awal = $id_awal AND id_rute IN 
                (SELECT id_rute FROM `jadwal_keberangkatans`)");
            if(count($data) > 0 ) {
                return response()->json($data, 200);
            }
            return response()->json(['message'=>'tidak ada data'], 201);
        }
        return response()->json(['message'=>'gagal'], 201);
    }

    public function listJadwalDariRute(Request $request)
    {
        $this->validate($request,[
            'awal' => 'required',
            'akhir' => 'required',
            'tanggal' => 'required'
        ]);
        if ($request->tanggal > date('Y-m-d')) {
            if (isset($_GET['id_armada'])) {
                $id_armada = $_GET['id_armada'];
                $data = DB::select("SELECT jk.id_jadwal,jk.jadwal, jk.id_armada, a.nama_armada, n.nama_nahkoda, k.nama_kapal, k.kapasitas_penumpang, l.nama_loket, IFNULL(x.total_penumpang,0) as total_penumpang, d.nama_dermaga as dermaga_awal, d2.nama_dermaga as dermaga_akhir, d.lokasi as lok_dermaga_awal, d2.lokasi as lok_dermaga_akhir FROM jadwal_keberangkatans jk 
                INNER JOIN rutes r ON jk.id_rute=r.id_rute
                INNER JOIN armadas a ON a.id_armada=jk.id_armada
                INNER JOIN nahkodas n ON n.id_nahkoda=jk.id_nahkoda
                INNER JOIN kapals k ON k.id_kapal=jk.id_kapal
                INNER JOIN lokets l ON l.id_loket=jk.id_loket
                LEFT JOIN dermagas d ON d.id_dermaga=r.tujuan_awal
                LEFT JOIN dermagas d2 ON d2.id_dermaga=r.tujuan_akhir
                LEFT JOIN (SELECT k.id_jadwal, COUNT(k.id) AS total_penumpang FROM keberangkatans k
                    INNER JOIN penumpangs p ON k.id_penumpang=p.id_penumpang
                     WHERE DATE(k.tanggal_keberangkatan) = '$request->tanggal' AND p.status_verif=1 GROUP BY k.id_jadwal) x ON x.id_jadwal=jk.id_jadwal 
                WHERE jk.id_armada='$id_armada' AND d.id_zona=$request->awal AND d2.id_zona=$request->akhir
                ORDER BY jk.jadwal");
            }
            else {
                $data = DB::select("SELECT jk.id_jadwal,jk.jadwal, jk.id_armada, a.nama_armada, n.nama_nahkoda, k.nama_kapal, k.kapasitas_penumpang, l.nama_loket, IFNULL(x.total_penumpang,0) as total_penumpang, d.nama_dermaga as dermaga_awal, d2.nama_dermaga as dermaga_akhir, d.lokasi as lok_dermaga_awal, d2.lokasi as lok_dermaga_akhir FROM jadwal_keberangkatans jk 
                INNER JOIN rutes r ON jk.id_rute=r.id_rute
                INNER JOIN armadas a ON a.id_armada=jk.id_armada
                INNER JOIN nahkodas n ON n.id_nahkoda=jk.id_nahkoda
                INNER JOIN kapals k ON k.id_kapal=jk.id_kapal
                INNER JOIN lokets l ON l.id_loket=jk.id_loket
                LEFT JOIN dermagas d ON d.id_dermaga=r.tujuan_awal
                LEFT JOIN dermagas d2 ON d2.id_dermaga=r.tujuan_akhir
                LEFT JOIN (SELECT k.id_jadwal, COUNT(k.id) AS total_penumpang FROM keberangkatans k
                    INNER JOIN penumpangs p ON k.id_penumpang=p.id_penumpang
                     WHERE DATE(k.tanggal_keberangkatan) = '$request->tanggal' AND p.status_verif=1 GROUP BY k.id_jadwal) x ON x.id_jadwal=jk.id_jadwal 
                WHERE d.id_zona=$request->awal AND d2.id_zona=$request->akhir
                ORDER BY jk.jadwal");
            }
            return response()->json($data, 200);
        }
        return response()->json(['message'=>'harus pilih tanggal setelah tanggal sekarang.'], 201);
    }

    public function cariZonaTujuan()
    {
        if (isset($_GET['zona_awal'])) {
            $id_awal = $_GET['zona_awal'];
            $data = DB::select("SELECT z.id_zona, z.lokasi FROM rutes r INNER JOIN dermagas d ON r.tujuan_awal = d.id_dermaga LEFT JOIN dermagas d2 ON d2.id_dermaga = r.tujuan_akhir INNER JOIN zona z ON z.id_zona = d2.id_zona WHERE d.id_zona = $id_awal GROUP BY d2.id_zona");
            return response()->json($data, 200);
        }
    }
}
