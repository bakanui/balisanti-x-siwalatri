<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Pengumuman;

class PengumumanController extends Controller
{
    public function index()
    {
        if(isset($_GET['id'])) {
            $data['pengumumans'] = Pengumuman::where('id', $_GET['id'])->get();
        }
        else {
            $data['pengumumans'] = Pengumuman::get();
        }
        return response()->json(['data' => $data]);
    }

    public function store(Request $request)
    {
        $this->validate($request,[
            'judul' => 'required',
            'deskripsi' => 'required',
        ]);
        $data = new Pengumuman;
        $data->judul = $request->input('judul');
        $data->deskripsi = $request->input('deskripsi');
        try {
            $data->save();
            return response()->json(['message' => 'Berhasil.'], 200);
        } catch (\Throwable $th) {
            //throw $th;
            return response()->json(['message' => 'Gagal.'], 400);
        }
    }

    public function edit($id)
    {
        $data = Pengumuman::find($id);
        if($data) {
            return response()->json(['data'=> $data], 200);
        }
        else {
            return response()->json(['message'=> 'data not found'], 400);
        }
    }

    public function update(Request $request, $id)
    {
        $data = Pengumuman::find($id);
        $data->judul = $request->input('judul');
        $data->deskripsi = $request->input('deskripsi');
        try {
            $data->save();
            return response()->json(['message' => 'Berhasil.'], 200);
        } catch (\Throwable $th) {
            //throw $th;
            return response()->json(['message' => 'Gagal.'], 400);
        }
    }

    public function delete($id)
    {
        Pengumuman::find($id)->delete();
        return response()->json(['message' => 'Berhasil.'], 200);
    }
}
