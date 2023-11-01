<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Invoice;
use App\Models\RekonVa;
use Illuminate\Support\Facades\DB;

class InvoiceController extends Controller
{
    public function getInvoices(Request $request) {
        $payment = '';
        $orderBy = '';
        if($request->input('orderBy')){
            $orderBy = $request->input('orderBy');
        }else{
            $orderBy = 'id';
        }
        $order = $request->input('order');
        $limit = $request->input('limit');
        if($request->input('payment') == 'cash') {
            $response = Invoice::orderBy($orderBy, $order)
            ->where('payment_method', 'cash')
            ->whereBetween('created_at', [$request->input('fromDate'), $request->input('endDate')])
            ->paginate($limit);
        }else{
            if($request->input('payment') == 'qris') {
                $payment = 'qrValue';
            }else if($request->input('payment') == 'va') {
                $payment = 'no_va';
            }

            $response = Invoice::orderBy('invoices.'.$orderBy, $order)
                ->whereNotNull('invoices.'.$payment)
                ->where('invoices.payment_method', 'transfer')
                ->whereBetween('invoices.created_at', [$request->input('fromDate')." 00:00:00", $request->input('endDate')." 23:59:59"])
                ->join('keberangkatans', 'keberangkatans.id_invoice', 'invoices.id')
                ->join('penumpangs', 'penumpangs.id_penumpang', 'keberangkatans.id_penumpang');

            if($request->input('status') !== "semua"){
                $response->where('invoices.status', $request->input('status'));
            }
            
            if($request->input('reversal') !== "semua"){
                $response->where('invoices.status_reversal', $request->input('reversal'));
            }
        }

       return response()->json($response->paginate($limit), 200);
    }
    public function getRekonVa(Request $request) {
        $orderBy = '';
        if($request->input('orderBy')){
            $orderBy = $request->input('orderBy');
        }else{
            $orderBy = 'id';
        }

        $order = $request->input('order');
        $limit = $request->input('limit');

        $response = RekonVa::orderBy($orderBy, $order)
            ->whereBetween('created_at', [$request->input('fromDate')." 00:00:00", $request->input('endDate')." 23:59:59"]);

        if($request->input('status') !== "semua"){
            $response->where('sts_bayar', $request->input('status'));
        }
        
        if($request->input('reversal') !== "semua"){
            $response->where('sts_reversal', $request->input('reversal'));
        }

        if($request->input('no_tagihan')){
            $response->where('no_tagihan', $request->input('no_tagihan'));
        }
        
        if($request->input('limit')){
            return response()->json($response->paginate($limit), 200);
        }else{
            $resp = $response->get();
            return response()->json($response->paginate(10000), 200);
        }

   }
}
