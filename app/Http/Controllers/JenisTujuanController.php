<?php

namespace App\Http\Controllers;

use App\Models\JenisTujuan;
use Illuminate\Http\Request;

class JenisTujuanController extends Controller
{
    //
    public function store(Request $request) {
        $this->validate($request,[
            'nama_tujuan' => 'required|string'
        ]);

        $tujuan = new JenisTujuan([
            'nama_tujuan' => $request->input('nama_tujuan')
        ]);

        if ($tujuan->save()) {
            $response = [
                'message' => 'Jenis Tujuan created',
                'tujuan' => $tujuan
            ];

            return response()->json($response, 201);
        }

        return response()->json(['message' => 'Jenis Tujuan not created'], 404);
    }

    public function view($id_tujuan) {
        $tujuan = JenisTujuan::query()->where('id_tujuan', $id_tujuan)->get();

        return response()->json($tujuan, 200);
    }
    
    public function delete($id_tujuan) {
        $tujuan = JenisTujuan::query()->where('id_tujuan', $id_tujuan)->delete();

        return response()->json($tujuan, 200);
    }

    public function index() {
        $tujuan = JenisTujuan::query()->get();

        return response()->json($tujuan, 200);
    }

    public function edit(Request $request, $id_tujuan) {
        $this->validate($request,[
            'nama_tujuan' => 'required|string'
        ]);

        $tujuan = [
            'nama_tujuan' => $request->input('nama_tujuan')
        ];

        $data = JenisTujuan::query()->where('id_tujuan', $id_tujuan);

        if ($data) {
            if ($data->update($tujuan)) {
                $response = [
                    'message' => 'Jenis Tujuan created',
                    'tujuan' => $data->get()
                ];

                return response()->json($response, 201);
            }

            return response()->json(['message' => 'Jenis Tujuan not created'], 404);
        }

        return response()->json(['message' => 'Jenis Tujuan not found'], 404);
    }
}
