<?php

namespace App\Http\Controllers;

use App\Models\Armada;
use App\Models\Loket;
use App\Models\User;
use App\Models\JadwalKeberangkatan;
use Illuminate\Http\Request;

class ArmadaController extends Controller
{
    //
    public function store(Request $request) {
        $this->validate($request,[
            'id_user' => 'required',
            'nama_armada' => 'required|string',
            'kontak' => 'required|string',
            'alamat' => 'required|string',
            'description' => 'required|string',
        ]);

        $armada = new Armada([
            'id_armada' => uniqid(),
            'id_user' => $request->input('id_user'),
            'nama_armada' => $request->input('nama_armada'),
            'kontak' => $request->input('kontak'),
            'alamat' => $request->input('alamat'),
            'description' => $request->input('description')
        ]);

        if ($armada->save()) {
            $response = [
                'msg' => 'Armada Created',
                'user' => User::query()->where('id', $armada['id_user'])->get(),
                'armada' => $armada
            ];

            return response()->json($response, 201);
        }

        return response()->json(['User not Created'], 404);
    }

    public function view($id_armada) {
        $armada = Armada::query()->with('armadaToUser')->where('id_armada', $id_armada)->get();

        return response()->json($armada, 200);
    }

    public function index() {
        $armada = Armada::query()->with('armadaToUser')->get();

        return response()->json($armada, 200);
    }
    
    public function index_loket($id_armada) {
        $armada = Loket::query()->with('loketToUser')->where('id_armada', $id_armada)->get();

        return response()->json($armada, 200);
    }

    public function welcome_loket($id_armada,$id_loket)
    {
        $armada = Armada::where('id_armada', $id_armada)->with('armadaToJadwal',function($query) {
            return $query->where('id_loket', '=', $id_loket);
        })->get();
        return response()->json($armada, 200);
    }
    
    public function edit_loket(Request $request,$id_loket) {
        $this->validate($request,[
            'nama_loket' => 'required',
            'lokasi_loket' => 'required|string'
        ]);
        
        $loket = [
            'nama_loket' => $request->input('nama_loket'),
            'lokasi_loket' => $request->input('lokasi_loket')
        ];
        
        $data = Loket::query()->where('id_loket', $id_loket);
        
        if($data) {
            if ($data->update($loket)) {
                $response = [
                    'msg' => 'Loket edited',
                    'armada' => $data->get()
                ];

                return response()->json($response, 201);
            }

            return response()->json(['msg' => 'Loket not edited'], 404);
        }

        return response()->json(['msg' => 'Loket not found'],404);
    }
    
    public function delete_loket($id_loket) {
        $kapal = User::query()->where('id', $id_loket)->delete();
        $loket = Loket::query()->where('id_loket', $id_loket)->delete();
        
        return response()->json($kapal, 200);
    }

    public function edit(Request $request, $id_armada) {
        $this->validate($request,[
            'id_user' => 'required',
            'nama_armada' => 'required|string',
            'kontak' => 'required|string',
            'alamat' => 'required|string',
            'description' => 'required|string',
        ]);

        $armada = [
            'id_user' => $request->input('id_user'),
            'nama_armada' => $request->input('nama_armada'),
            'kontak' => $request->input('kontak'),
            'alamat' => $request->input('alamat'),
            'description' => $request->input('description')
        ];

        $data = Armada::query()->with('armadaToUser')->where('id_armada', $id_armada);

        if($data) {
            if ($data->update($armada)) {
                $response = [
                    'msg' => 'Armada edited',
                    'armada' => $data->get()
                ];

                return response()->json($response, 201);
            }

            return response()->json(['msg' => 'Armada not edited'], 404);
        }

        return response()->json(['msg' => 'Armada not found'],404);
    }
}
