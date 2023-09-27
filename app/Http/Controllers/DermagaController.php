<?php

namespace App\Http\Controllers;

use App\Models\Dermaga;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DermagaController extends Controller
{
    //
    public function store(Request $request) {
        $this->validate($request,[
            'nama_dermaga' => 'required|string',
            'lokasi' => 'required|string',
            'id_syahbandar' => 'required'
        ]);

        $dermaga = new Dermaga([
            'nama_dermaga' => $request->input('nama_dermaga'),
            'lokasi' => $request->input('lokasi'),
            'id_syahbandar' => $request->input('id_syahbandar')
        ]);

        if($dermaga->save()) {
            $response = [
                'message' => 'Dermaga created',
                'dermaga' => $dermaga
            ];

            return response()->json($response, 201);
        }

        return response()->json(['message' => 'Dermaga not created'], 404);
    }
    
    public function view() {
        $dermaga = DB::select("SELECT * FROM `dermagas` as d INNER JOIN `users` as u ON d.id_syahbandar = u.id WHERE d.deleted_at is null");

        return response()->json($dermaga, 200);
    }
    
    public function delete($id_dermaga) {
        $dermaga = Dermaga::query()->where('id_dermaga', $id_dermaga)->delete();

        return response()->json($dermaga, 200);
    }
    
    public function edit(Request $request, $id_dermaga) {
        $this->validate($request,[
            'nama_dermaga' => 'required|string',
            'lokasi' => 'required|string',
            'id_syahbandar' => 'required'
        ]);

        $dermaga = [
            'nama_dermaga' => $request->input('nama_dermaga'),
            'lokasi' => $request->input('lokasi'),
            'id_syahbandar' => $request->input('id_syahbandar')
        ];
        
        $data = Dermaga::query()
            ->where('id_dermaga', $id_dermaga);

        if ($data->update($dermaga)) {
            $response = [
                'message' => 'Jadwal edited',
                'dermaga' => $data->get()
            ];

            return response()->json($response, 201);
        }

        return response()->json(['message' => 'Jadwal not edited'], 404);
    }
    
}
