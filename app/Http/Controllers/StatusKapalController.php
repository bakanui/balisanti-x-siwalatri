<?php

namespace App\Http\Controllers;

use App\Models\StatusKapal;
use Illuminate\Http\Request;

class StatusKapalController extends Controller
{
    //
    public function store(Request $request) {
        $this->validate($request, [
            'nama_status' => 'required|string'
        ]);

        $status = new StatusKapal([
            'nama_status' => $request->input('nama_status')
        ]);

        if ($status->save()) {
            $response = [
                'message' => 'Status kapal created',
                'status' => $status
            ];

            return response()->json($response, 201);
        }

        return response()->json(['message' => 'Status kapal not created'], 404);
    }

    public function view($id_status) {
        $status = StatusKapal::query()->where('id_status', $id_status)->get();

        return response()->json($status, 200);
    }

    public function index() {
        $status = StatusKapal::query()->get();

        return response()->json($status, 200);
    }

    public function edit(Request $request, $id_status) {
        $this->validate($request, [
            'nama_status' => 'required|string'
        ]);

        $status = [
            'nama_status' => $request->input('nama_status')
        ];

        $data = StatusKapal::query()->where('id_status', $id_status);

        if ($data) {
            if ($data->update($status)) {
                $response = [
                    'message' => 'Status Kapal edited',
                    'status' => $data->get()
                ];

                return response()->json($response, 201);
            }

            return response()->json(['message' => 'Status Kapal not edited'], 404);
        }

        return response()->json(['message' => 'Status Kapal not found'], 404);
    }
}
