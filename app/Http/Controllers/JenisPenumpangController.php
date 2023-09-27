<?php

namespace App\Http\Controllers;

use App\Models\JenisPenumpang;
use Illuminate\Http\Request;

class JenisPenumpangController extends Controller
{
    //
    public function store(Request $request) {
        $this->validate($request,[
            'nama_jns_penum' => 'required|string'
        ]);

        $jenis_penumpang = new JenisPenumpang([
            'nama_jns_penum' => $request->input('nama_jns_penum')
        ]);

        if ($jenis_penumpang->save()) {
            $response = [
                'message' => 'Jenis Penumpang created',
                'jenis_penumpang' => $jenis_penumpang
            ];

            return response()->json($response, 201);
        }

        return response()->json(['message' => 'Jenis Penumpang not created'], 404);
    }

    public function view($id_jns_penumpang) {
        $jns_penumpang = JenisPenumpang::query()->where('id_jns_penum', $id_jns_penumpang)->get();

        return response()->json($jns_penumpang, 200);
    }
    
    public function delete($id_jns_penumpang) {
        $jns_penumpang = JenisPenumpang::query()->where('id_jns_penum', $id_jns_penumpang)->delete();

        return response()->json($jns_penumpang, 200);
    }

    public function index() {
        $jns_penumpang = JenisPenumpang::query()->orderBy('id_jns_penum', 'desc')->get();

        return response()->json($jns_penumpang, 200);
    }

    public function edit(Request $request, $id_jns_penumpang) {
        $this->validate($request,[
            'nama_jns_penum' => 'required|string'
        ]);

        $jenis_penumpang = [
            'nama_jns_penum' => $request->input('nama_jns_penum')
        ];

        $data = JenisPenumpang::query()->where('id_jns_penum', $id_jns_penumpang);

        if ($data) {
            if ($data->update($jenis_penumpang)) {
                $response = [
                    'message' => 'Jenis Penumpang created',
                    'jns_penumpang' => $data->get()
                ];

                return response()->json($response, 201);
            }

            return response()->json(['message' => 'Jenis Penumpang not created'], 404);
        }

        return response()->json(['message' => 'Jenis Penumpang not found'], 404);
    }
}
