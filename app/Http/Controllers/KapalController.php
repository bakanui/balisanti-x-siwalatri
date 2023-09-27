<?php

namespace App\Http\Controllers;

use App\Models\Kapal;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Storage;

class KapalController extends Controller
{
    //
    public function test(Request $request) {
        $file = $request->file('image');
        $name = time();
        $extension = $file->getClientOriginalExtension();
        $nameFile = '1_' . $name . '.' . $extension;
        $path = Storage::putFileAs('photo', $request->file('image'), $nameFile);

        $test = Storage::path($path);

        dd($test);
        // return '<img src="' . $this->BASE_URL . '' . $test . '" alt="">';
    }


    public function store(Request $request) {
        $this->validate($request,[
            'nama_kapal' => 'required|string',
            'mesin' => 'required|string',
            'panjang' => 'required',
            'lebar' => 'required',
            'dimension' => 'required',
            'kapasitas_penumpang' => 'required|integer',
            'kapasitas_crew' => 'required|integer',
            'id_armada' => 'required',
            'id_jenis' => 'required',
            'id_status' => 'required',
            'kilometer' => 'required',
//            'image' => 'required'
        ]);

        $id = uniqid();

//        $file = $request->file('image');
//        $name = time();
//        $extension = $file->getClientOriginalExtension();
//        $nameFile = $id . '_' . $name . '.' . $extension;
//        $path = Storage::putFileAs('photo', $request->file('image'), $nameFile);
//
//        $fileSystem = Config::get('filesystems.disks.' . env('FILESYSTEM_DRIVER', 'public'));
//        $this->BASE_URL = Arr::get($fileSystem, 'url', '');
//
//        $url = $this->BASE_URL . '/app/' . $path;

        $kapal = new Kapal([
            'id_kapal' => $id,
            'nama_kapal' => $request->input('nama_kapal'),
            'mesin' => $request->input('mesin'),
            'panjang' => $request->input('panjang'),
            'lebar' => $request->input('lebar'),
            'dimension' => $request->input('dimension'),
            'kapasitas_penumpang' => $request->input('kapasitas_penumpang'),
            'kapasitas_crew' => $request->input('kapasitas_crew'),
            'id_armada' => $request->input('id_armada'),
            'id_jenis' => $request->input('id_jenis'),
            'id_status' => $request->input('id_status'),
            'kilometer' => $request->input('kilometer'),
//            'image' => $url
        ]);

        if ($kapal->save()) {
            $response = [
                'message' => 'Kapal created',
                'kapal' => $kapal,
            ];

            return response()->json($response, 201);
        }

        return response()->json(['message' => 'Kapal not created'], 404);
    }

    public function index($id_armada) {
        $kapal = Kapal::query()
            ->with('kapalToArmada')
            ->with('kapalToJenis')
            ->with('kapalToStatus')
            ->where('id_armada', $id_armada)->get();

        return response()->json($kapal, 200);
    }

    public function view($id_kapal) {
        $kapal = Kapal::query()
            ->with('kapalToArmada')
            ->with('kapalToJenis')
            ->with('kapalToStatus')
            ->where('id_kapal', $id_kapal)->get();

        return response()->json($kapal, 200);
    }
    
    public function delete($id_kapal) {
        $kapal = Kapal::query()->where('id_kapal', $id_kapal)->delete();

        return response()->json($kapal, 200);
    }

    public function edit(Request $request, $id_kapal) {
        $this->validate($request,[
            'nama_kapal' => 'required|string',
            'mesin' => 'required|string',
            'panjang' => 'required',
            'lebar' => 'required',
            'dimension' => 'required',
            'kapasitas_penumpang' => 'required|integer',
            'kapasitas_crew' => 'required|integer',
            'id_jenis' => 'required',
            'id_status' => 'required',
            'kilometer' => 'required'
        ]);

        $kapal = [
            'nama_kapal' => $request->input('nama_kapal'),
            'mesin' => $request->input('mesin'),
            'panjang' => $request->input('panjang'),
            'lebar' => $request->input('lebar'),
            'dimension' => $request->input('dimension'),
            'kapasitas_penumpang' => $request->input('kapasitas_penumpang'),
            'kapasitas_crew' => $request->input('kapasitas_crew'),
            'id_jenis' => $request->input('id_jenis'),
            'id_status' => $request->input('id_status'),
            'kilometer' => $request->input('kilometer')
        ];

        $data = Kapal::query()->where('id_kapal', $id_kapal);

        if ($data) {
            if ($data->update($kapal)) {
                $response = [
                    'message' => 'Kapal edited',
                    'kapal' => $data->get()
                ];

                return response()->json($response, 201);
            }

            return response()->json(['message' => 'Kapal not edited'], 404);
        }

        return response()->json(['message' => 'Kapal not found'], 404);
    }

}
