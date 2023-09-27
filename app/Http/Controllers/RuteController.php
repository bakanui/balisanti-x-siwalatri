<?php

namespace App\Http\Controllers;

use App\Models\JadwalKeberangkatan;
use App\Models\Rute;
use Carbon\Carbon;
use Illuminate\Http\Request;

class RuteController extends Controller
{
    //
    public function store(Request $request) {
        $this->validate($request,[
            'tujuan_awal' => 'required|integer',
            'tujuan_akhir' => 'required|integer',
            'jarak' => 'required|integer'
        ]);

        $rute = new Rute([
            'tujuan_awal' => $request->input('tujuan_awal'),
            'tujuan_akhir' => $request->input('tujuan_akhir'),
            'jarak' => $request->input('jarak'),
        ]);

        if($rute->save()) {
            $response = [
                'message' => 'Rute created',
                'rute' => $rute
            ];

            return response()->json($response, 201);
        }

        return response()->json(['message' => 'Rute not created'], 404);
    }

    public function index() {
        $rute = Rute::query()
            ->with('tujuan_awals')
            ->with('tujuan_akhirs')
            ->get();

        return response()->json($rute, 200);
    }

    public function view($id_rute) {
        $rute = Rute::query()->where('id_rute', $id_rute)
            ->with('tujuan_awals')
            ->with('tujuan_akhirs')
            ->get();

        return response()->json($rute, 200);
    }
    
    public function delete($id_rute) {
        $rute = Rute::query()->where('id_rute', $id_rute)
            ->with('tujuan_awals')
            ->with('tujuan_akhirs')
            ->delete();

        return response()->json($rute, 200);
    }

    public function list_now() {
        $rute = Rute::query()
            ->select('rutes.id_rute', 'rutes.tujuan_awal', 'rutes.tujuan_akhir', 'rutes.jarak', 'rutes.created_at', 'rutes.updated_at')
            ->join('jadwal_keberangkatans', 'rutes.id_rute', '=', 'jadwal_keberangkatans.id_rute')
            ->whereDate('jadwal_keberangkatans.jadwal', Carbon::today())
            ->groupBy('rutes.id_rute')
            ->with('tujuan_awals')
            ->with('tujuan_akhirs')
            ->get();

        return response()->json($rute, 200);
    }

    public function edit(Request $request, $id_rute) {
        $this->validate($request,[
            'tujuan_awal' => 'required|integer',
            'tujuan_akhir' => 'required|integer',
            'jarak' => 'required|integer'
        ]);

        $rute = [
            'tujuan_awal' => $request->input('tujuan_awal'),
            'tujuan_akhir' => $request->input('tujuan_akhir'),
            'jarak' => $request->input('jarak')
        ];

        $data = Rute::query()
            ->with('tujuan_awals')
            ->with('tujuan_akhirs')
            ->where('id_rute', $id_rute);

        if ($data) {
            if ($data->update($rute)) {
                $response = [
                    'message' => 'Rute created',
                    'rute' => $data->get()
                ];

                return response()->json($response, 201);
            }

            return response()->json(['message' => 'Rute not create'], 404);
        }

        return response()->json(['message' => 'Rute not found'], 404);
    }
}
