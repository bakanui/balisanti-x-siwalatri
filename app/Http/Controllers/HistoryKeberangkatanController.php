<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\HistoryKeberangkatan;
use Illuminate\Support\Facades\DB;

class HistoryKeberangkatanController extends Controller
{
    public function create(Request $request) {
        $this->validate($request,[
            'id_jadwal' => 'required|string',
            'id_kapal' => 'required|string',
            'tanggal_berangkat' => 'required',
            'tanggal_sampai' => 'required',
            'jml_penumpang' => 'required|integer'
        ]);

        $keberangkatan = new HistoryKeberangkatan([
            'id_jadwal' => $request->input('id_jadwal'),
            'id_kapal' => $request->input('id_kapal'),
            'tanggal_berangkat' => $request->input('tanggal_berangkat'),
            'tanggal_sampai' => $request->input('tanggal_sampai'),
            'jml_penumpang' => $request->input('jml_penumpang')
        ]);

        if ($keberangkatan->save()) {
            return response()->json(['message' => 'Keberangkatan successfully created'], 200);
        }else{
            return response()->json(['message' => 'Keberangkatan not created'], 404);
        }
    }

    // public function edit(Request $request) {
    //     $this->validate($request,[
    //         'id_jadwal' => 'required|string',
    //         'id_kapal' => 'required|string',
    //         'jml_penumpang' => 'required|integer'
    //     ]);

    //     $id_jadwal = $request->input('$id_jadwal');

    //     $data = HistoryKeberangkatan::where('id_jadwal', $id_jadwal)->first();

    //     $data->jml_penumpang = $request->input('jml_penumpang');

    //     if ($data->update()) {
    //         $response = [
    //             'message' => 'History has been changed.',
    //             'jadwal' => $data->get()
    //         ];

    //         return response()->json($response, 200);
    //     }else{
    //         return response()->json(['message' => 'History has not been changed.'], 404);
    //     }
    // }

    public function edit(Request $request) {
        $this->validate($request,[
            'id_jadwal' => 'required|string',
            'id_kapal' => 'required|string',
            'jml_penumpang' => 'required|integer'
        ]);

        $id_jadwal = $request->input('id_jadwal');
        $id_kapal = $request->input('id_kapal');

        $jml_real = HistoryKeberangkatan::where('id_jadwal', $id_jadwal)->where('id_kapal', $id_kapal)->pluck('jml_penumpang');
        $jml_penumpang = $request->input('jml_penumpang');
        $jml = $jml_real[0] + $jml_penumpang;

        DB::table('history_keberangkatans')->where('id_jadwal', $id_jadwal)->where('id_kapal', $id_kapal)
            ->update(
                array(
                    'jml_penumpang' => $jml,
                )
            );
        
        $response = [
            'message' => 'History has been changed.'
        ];

        return response()->json($response, 200);
    }
}
