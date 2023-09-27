<?php

namespace App\Http\Controllers;
use App\Models\Wisata;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class WisataController extends Controller
{
    public function index()
    {
        if(isset($_GET['id'])) {
            $data['wisatas'] = Wisata::where('id', $_GET['id'])->get();
        }
        else {
            $data['wisatas'] = Wisata::get();
        }
        // return view('temp', $data);
        return response()->json(['data' => $data]);
    }

    public function store(Request $request)
    {
        $this->validate($request,[
            'path' => 'required',
            'judul' => 'required',
            'deskripsi' => 'required',
        ]);

        try {
            $data = new Wisata;
            $data->path = $request->input('path');
            $data->judul = $request->input('judul');
            $data->deskripsi = $request->input('deskripsi');
            $data->save();

            $response = [
                'message' => 'Berhasil'
            ];
            return response()->json($response, 200);
        } catch (\Throwable $th) {
            throw $th;
            return response()->json(['message' => 'error'], 400);
        }
    }

    public function edit($id)
    {
        $data = Wisata::find($id);
        
        return response()->json(['data'=>$data], 200);
    }

    public function update(Request $request, $id)
    {
        $data = Wisata::find($id);
        $updates = [
            'path' => $request->input('path'),
            'judul' => $request->input('judul'),
            'deskripsi' => $request->input('deskripsi')
        ];
        if ($data) {
            if ($data->update($updates)) {
                $response = [
                    'message' => 'Wisata edited',
                ];

                return response()->json($response, 200);
            }

            return response()->json(['message' => 'Wisata not edited'], 404);
        }

        return response()->json(['message' => 'Wisata not found'], 404);
    }

    public function delete($id)
    {
        Wisata::find($id)->delete();
        return response()->json(['message' => 'Berhasil.'], 200);
    }
}
