<?php

namespace App\Http\Controllers;

use App\Models\JenisKapal;
use Illuminate\Http\Request;

class JenisKapalController extends Controller
{
    //
    public function store(Request $request) {
        $this->validate($request,[
            'nama_jenis' => 'required|string',
            'description' => 'required|string'
        ]);

        $jenis = new JenisKapal([
            'nama_jenis' => $request->input('nama_jenis'),
            'description' => $request->input('description')
        ]);

        if ($jenis->save()) {
            $response = [
                'message' => 'Jenis Kapal created',
                'jenis' => $jenis
            ];

            return response()->json($response, 201);
        }

        return response()->json(['message' => 'Jenis Kapal not created'], 404);
    }

    public function view($id_jenis) {
        $jenis = JenisKapal::query()->where('id_jenis', $id_jenis)->get();

        return response()->json($jenis, 200);
    }
    
    public function search(Request $request) {
        $jenis = JenisKapal::query()->where('nama_jenis', 'like', '%' . $request->input('nama_jenis') . '%')->get();

        return response()->json($jenis, 200);
    }
    
    public function delete($id_jenis) {
        $jenis = JenisKapal::query()->where('id_jenis', $id_jenis)->delete();

        return response()->json($jenis, 200);
    }

    public function index() {
        $jenis = JenisKapal::query()->get();

        return response()->json($jenis, 200);
    }

    public function edit(Request $request, $id_jenis) {
        $this->validate($request,[
            'nama_jenis' => 'required|string',
            'description' => 'required|string'
        ]);

        $jenis = [
            'nama_jenis' => $request->input('nama_jenis'),
            'description' => $request->input('description')
        ];

        $data = JenisKapal::query()->where('id_jenis', $id_jenis);

        if ($data) {
            if ($data->update($jenis)) {
                $response = [
                    'message' => 'Jenis Kapal edited',
                    'jenis' => $data->get()
                ];

                return response()->json($response, 201);
            }

            return response()->json(['message' => 'Jenis Kapal not edited'], 404);
        }

        return response()->json(['message' => 'Jenis Kapal not found'], 404);
    }
}
