<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Invoice;
use App\Models\BpdServicelog;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Http;
use Mtownsend\XmlToArray\XmlToArray;
use Illuminate\Support\Facades\DB;

class PaymentLast extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'payment:last';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Melihat/menampilkan data transaksi terakhir yang sudah selesai dalam hari ini.';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        // $invoice = Invoice::whereNotNull('no_va')->get()->last();
        // $no_bukti = $invoice->no_va;
        $xmls = <<<XML
        <soapenv:Envelope xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xmlns:xsd="http://www.w3.org/2001/XMLSchema" xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/" xmlns:urn="urn:laporanPaymentDetailSetelahNoBukti">
            <soapenv:Header/>
            <soapenv:Body>
            <urn:ws_laporan_payment_detail_setelah_no_bukti soapenv:encodingStyle="http://schemas.xmlsoap.org/soap/encoding/">
                <username xsi:type="xsd:string">BALI_SANTI</username>
                <password xsi:type="xsd:string">hbd3q2p9b4l1s4nt1bpd8ovr</password>
                <instansi xsi:type="xsd:string">ETIKET_BALI_SANTI</instansi>
                <tanggal xsi:type="xsd:date">2023-10-16</tanggal>
                <nobukti xsi:type="xsd:integer">8547564</nobukti>
            </urn:ws_laporan_payment_detail_setelah_no_bukti>
            </soapenv:Body>
        </soapenv:Envelope>
        XML;
        $response = Http::withHeaders(['Content-Type' => 'text/xml; charset=utf-8',"X-Requested-With" => "XMLHttpRequest"])->send('POST', 'https://maiharta.ddns.net:3100/http://180.242.244.3:7070/ws_bpd_payment/interkoneksi/v1/ws_interkoneksi.php', [
            'body' => $xmls,
        ]);
        $arr = XmlToArray::convert($response);
        $jsonFormatData = json_encode($arr["SOAP-ENV:Body"]["ns1:ws_laporan_payment_detail_setelah_no_buktiResponse"]["return"]["@content"]);
        $result = json_decode($jsonFormatData, true);
        $result2 = json_decode($result, true);
        // dd($result2);
        $status = $result2["status"];
        if($status != false){
            foreach ($result2['data'] as $key => $r) {
                DB::table('invoices')->where('no_va', $r['No Tagihan'])
                ->update(
                    array(
                        'status' => $r['sts_bayar'],
                        'status_reversal' => $r['sts_reversal']
                    )
                );
            }
            $penumpang = new BpdServicelog([
                'code' => $result2['code'],
                'data' => json_encode($result2['data']),
                'message' => $result2['message'],
                'status' => $result2['status'],
            ]);
            $penumpang->save();
        }else{
            $penumpang = new BpdServicelog([
                'code' => $result2['code'],
                'data' => json_encode($result2['data']),
                'message' => $result2['message'],
                'status' => $result2['status'],
            ]);
            $penumpang->save();
        }
    }
}
