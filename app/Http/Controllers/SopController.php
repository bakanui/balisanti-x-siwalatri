<?php

namespace App\Http\Controllers;

use App\Models\SOP;
use App\Models\JadwalKeberangkatan;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
class SopController extends Controller
{
    //
    public function add_sop(Request $request) {
        $this->validate($request,[
            'id_jadwal' => 'required',
            'id_sop' => 'required',
        ]);

        $id_jadwal = $request->input('id_jadwal');
        
        $approval = DB::table('detail_keberangkatan')->where('id_jadwal', $id_jadwal)->whereDate('tanggal_berangkat', DB::raw('CURDATE()'))->first();
        
        if($approval) {
            return response()->json(['msg' => 'SOP sudah terapprove'], 404); 
        } else {
            DB::table('detail_keberangkatan')->insert(
                array(
                    'id_jadwal' => $id_jadwal,
                    'approval' => 0,
                    'tanggal_berangkat' => Carbon::now('+08:00'),
                )
            );
            
            $sops = explode("|", $request->input('id_sop'));
            for($i=0; $i < count($sops); $i++){
                DB::table('sop_keberangkatan')->insert(
                    array(
                        'id_jadwal' => $id_jadwal,
                        'id_sop' => $sops[$i],
                        'tanggal' => Carbon::now('+08:00'),
                    )
                );
            }
    
            return response()->json(['msg' => 'SOP siap di approve'], 200);  
        }
    }
    
    public function view_sop($id_jadwal) {
        $sop = DB::select(DB::raw("SELECT * FROM `sop_keberangkatan` as sk INNER JOIN `s_o_p_s` as s ON sk.id_sop = s.id WHERE sk.id_jadwal = '$id_jadwal' AND DATE_FORMAT(tanggal , '%Y-%m-%d') = CURDATE()"));
        
        $jadwal = JadwalKeberangkatan::query()
            ->with('jadwalToArmada')
            ->with('jadwalToNahkoda')
            ->with('jadwalToKapal')
            ->with('jadwalToRute')
            ->where('id_jadwal', $id_jadwal)->first();
            
        $approval = DB::table('detail_keberangkatan')->where('id_jadwal', $id_jadwal)->whereDate('tanggal_berangkat', DB::raw('CURDATE()'))->first();
            
        $response = [
            'sop' => $sop,
            'jadwal' => $jadwal,
            'approval' => $approval
        ];

        return response()->json($response, 200);
    }
    
    public function view() {
        $sop = SOP::query()->get();

        return response()->json($sop, 200);
    }

    public function delete($id) {
        $sop = SOP::query()->where('id', $id)->delete();

        return response()->json($sop, 200);
    }
    
    public function store(Request $request) {
        $this->validate($request, [
            'description' => 'required'
        ]);

        $sop = new SOP([
            'description' => $request->input('description')
        ]);

        if($sop->save()) {
            return response()->json(['msg' => 'SOP created'], 200);
        }

        return response()->json(['msg' => 'SOP not created'], 404);
    }
    
    public function edit(Request $request, $id) {
        $this->validate($request,[
            'description' => 'required'
        ]);

        $sop = [
            'description' => $request->input('description')
        ];

        $data = SOP::query()
            ->where('id', $id);

        if ($data->update($sop)) {
            $response = [
                'message' => 'Jadwal edited',
                'dermaga' => $data->get()
            ];

            return response()->json($response, 201);
        }

        return response()->json(['message' => 'Jadwal not edited'], 404);
    }
}
