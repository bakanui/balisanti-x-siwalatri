<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\BpdServicelog;
use App\Models\RekonVa;

class BpdServicelogController extends Controller
{
    public function storeVALogs(Request $request){
        $this->validate($request,[
            'code' => 'required|string',
            'data' => 'required',
            'message' => 'required|string',
            'status' => 'required',
        ]);
        $data_dec = json_decode($request->input('data'), true);
        $invoice_id = $request->input('invoice_id');

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
            if($data_dec !== null) {
                foreach($data_dec as $i => $v){
                    $rekon = new RekonVa([
                        'jenis_pembayaran' => $v['Jenis Pembayaran'],
                        'jenis_tiket' => $v['Jenis Tiket'],
                        'jumlah_tiket' => $v['Jumlah Tiket'],
                        'no_tagihan' => $v['No Tagihan'],
                        'operator' => $v['Operator'],
                        'tanggal_keberangkatan' => $v['Tanggal Keberangkatan'],
                        'tanggal_pembelian_tiket' => $v['Tanggal Pembelian Tiket'],
                        'tujuan' => $v['Tujuan'],
                        'instansi' => $v['instansi'],
                        'kd_user' => $v['kd_user'],
                        'nama' => $v['nama'],
                        'recordId' => $v['recordId'],
                        'sts_bayar' => $v['sts_bayar'],
                        'tagihan' => $v['tagihan'],
                        'tgl_upd' => $v['tgl_upd'],
                        'invoice_id' => $invoice_id,
                    ]);
                    $rekon->save();
                }
            }
            
            return response()->json($response, 200);
        }

        return response()->json(['message' => 'BPD VA Log not created'], 404);
    }
}
