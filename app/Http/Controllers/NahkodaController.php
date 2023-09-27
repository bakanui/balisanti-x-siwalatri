<?php

namespace App\Http\Controllers;

use App\Models\Nahkoda;
use App\Models\User;
use App\Models\KecakapanNahkoda;


use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class NahkodaController extends Controller
{
    //
    public function view($id_user) {
        $user = User::query()->where('id', $id_user)->get();
        $nahkoda = Nahkoda::query()->where('id_nahkoda', $id_user)->get();

        $response = [
            'user' => $user,
            'nahkoda' => $nahkoda
        ];

        return response()->json($response, 200);
    }

    public function index($id_armada) {
        $nahkoda = Nahkoda::query()->with('nahkodaToArmada')->join('kecakapan_nahkoda', 'kecakapan_nahkoda.id_kecakapan', '=', 'nahkodas.id_kecakapan')->where('id_armada', $id_armada)->get();

        return response()->json($nahkoda, 200);
    }
    
    public function delete($id_user) {
        $nahkoda = Nahkoda::query()->where('id_nahkoda', $id_user)->delete();

        return response()->json($nahkoda, 200);
    }

    public function edit(Request $request, $id_user) {
        $this->validate($request, [
            'nama_nahkoda' => 'required|string',
            'no_hp' => 'required|string',
            'id_kecakapan' => 'required'
        ]);

        $nahkoda = [
            'nama_nahkoda' => $request->input('nama_nahkoda'),
            'no_hp' => $request->input('no_hp'),
            'id_kecakapan' => $request->input('id_kecakapan')
        ];

        $data = Nahkoda::query()->where('id_nahkoda', $id_user);

        if ($data->update($nahkoda)) {
            $response = [
                'message' => 'User Edited',
                'user' => User::query()->where('id', $id_user)->get(),
                'nahkoda' => $data->get()
            ];

            return response()->json($response, 201);
        }

        return response()->json(['User not Edited'], 404);
    }
    
    public function getKecakapanMaster(){
        // $response = KecakapanNahkoda::query()->get();
        $response = DB::select(DB::raw("select * from kecakapan_nahkoda"));
        
        return response()->json($response, 200);
    }
    
    public function nahkoda_kosong($id_armada){
        $response = DB::select(DB::raw("SELECT * FROM nahkodas as n WHERE n.id_nahkoda NOT IN (SELECT jk.id_nahkoda FROM jadwal_keberangkatans as jk) AND n.id_nahkoda = '$id_armada' AND n.deleted_at is null"));
        
        return response()->json($response, 200);
    }
}
