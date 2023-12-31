<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use App\Models\Invoice;
use App\Models\BpdServicelog;
use Illuminate\Database\Eloquent\Collection;
use Mtownsend\XmlToArray\XmlToArray;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class PaymentAll extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'payment:all';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Melihat/menampilkan semua data transaksi Virtual Account BPD yang sudah selesai dalam hari ini.';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $today = Carbon::now()->format('Y-m-d');
        $xmls = <<<XML
        <soapenv:Envelope xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xmlns:xsd="http://www.w3.org/2001/XMLSchema" xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/" xmlns:urn="urn:laporanPaymentDetailSetelahNoBukti">
            <soapenv:Header/>
            <soapenv:Body>
            <urn:ws_laporan_payment_detail_setelah_no_bukti soapenv:encodingStyle="http://schemas.xmlsoap.org/soap/encoding/">
                <username xsi:type="xsd:string">BALI_SANTI</username>
                <password xsi:type="xsd:string">hbd3q2p9b4l1s4nt1bpd8ovr</password>
                <instansi xsi:type="xsd:string">ETIKET_BALI_SANTI</instansi>
                <tanggal xsi:type="xsd:date">$today</tanggal>
                <nobukti xsi:type="xsd:integer">0</nobukti>
            </urn:ws_laporan_payment_detail_setelah_no_bukti>
            </soapenv:Body>
        </soapenv:Envelope>
        XML;
        $response = Http::withHeaders(['Content-Type' => 'text/xml; charset=utf-8',"X-Requested-With" => "XMLHttpRequest"])->send('POST', 'https://maiharta.ddns.net:3100/http://180.242.244.3:7070/ws_bpd_payment/interkoneksi/v1/ws_interkoneksi.php', [
            'body' => $xmls,
        ]);
        if($response->getStatusCode() != 200){
            $penumpang = new BpdServicelog([
                'code' => "500",
                'data' => json_encode(array()),
                'message' => "Terjadi kesalahan pada jaringan.",
                'status' => 0,
            ]);
            $penumpang->save();
        }else{
            $arr = XmlToArray::convert($response);
            $jsonFormatData = $arr["SOAP-ENV:Body"]["ns1:ws_laporan_payment_detail_setelah_no_buktiResponse"]["return"]["@content"];
            $result = json_decode($jsonFormatData, true);
            $status = $result["status"];
            if($status != false){
                foreach ($result['data'] as $key => $r) {
                    DB::table('invoices')->where('no_va', $r['No Tagihan'])
                    ->update(
                        array(
                            'status' => $r['sts_bayar'],
                            'status_reversal' => $r['sts_reversal']
                        )
                    );
                    DB::table('rekon_vas')->where('no_tagihan', $r['No Tagihan'])
                    ->update(
                        array(
                            'jenis_pembayaran' => $r['Jenis Pembayaran'],
                            'jenis_tiket' => $r['Jenis Tiket'],
                            'jumlah_tiket' => $r['Jumlah Tiket'],
                            'operator' => $r['Operator'],
                            'tanggal_keberangkatan' => $r['Tanggal Keberangkatan'],
                            'tanggal_pembelian_tiket' => $r['Tanggal Pembelian Tiket'],
                            'tujuan' => $r['Tujuan'],
                            'instansi' => $r['instansi'],
                            'kd_user' => $r['kd_user'],
                            'nama' => $r['nama'],
                            'tagihan' => $r['tagihan'],
                            'tgl_upd' => $r['tgl_tx'],
                            'sts_bayar' => $r['sts_bayar'],
                            'sts_reversal' => $r['sts_reversal'],
                            'no_bukti' => $r['noBukti']
                        )
                    );
                }
                $penumpang = new BpdServicelog([
                    'code' => $result['code'],
                    'data' => json_encode($result['data']),
                    'message' => $result['message'],
                    'status' => $result['status'],
                ]);
                $penumpang->save();
            }else{
                $penumpang = new BpdServicelog([
                    'code' => $result['code'],
                    'data' => json_encode($result['data']),
                    'message' => $result['message'],
                    'status' => $result['status'],
                ]);
                $penumpang->save();
            }
        }
    }
}
