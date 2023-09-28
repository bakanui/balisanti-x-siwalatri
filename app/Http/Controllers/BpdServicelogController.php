<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\BpdServicelog;

class BpdServicelogController extends Controller
{
    public function storeVALogs(Request $request){
        $this->validate($request,[
            'code' => 'required|string',
            'data' => 'required',
            'message' => 'required|string',
            'status' => 'required',
        ]);
        $data_dec = json_decode($request->input('data'));

        $penumpang = new BpdServicelog([
            'code' => $request->input('code'),
            'data' => $request->input('data'),
            'message' => $request->input('message'),
            'status' => $request->input('status'),
        ]);

        $penumpang_return = new BpdServicelog([
            'code' => $request->input('code'),
            'data' => $data_dec,
            'message' => $request->input('message'),
            'status' => $request->input('status'),
        ]);
        
        if ($penumpang->save()) {
            $response = [
                'message' => 'BPD VA Log created',
                'response' => $penumpang_return
            ];
            
            return response()->json($response, 200);
        }

        return response()->json(['message' => 'BPD VA Log not created'], 404);
    }
}
