<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Invoice;
use Illuminate\Database\Eloquent\Collection;

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
        $invoice = Invoice::get()->last();
        $no_bukti = $invoice->no_va;
        $xmls = <<<XML
        <soapenv:Envelope xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xmlns:xsd="http://www.w3.org/2001/XMLSchema" xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/" xmlns:urn="urn:laporanPaymentDetailSetelahNoBukti">
            <soapenv:Header/>
            <soapenv:Body>
            <urn:ws_laporan_payment_detail_setelah_no_bukti soapenv:encodingStyle="http://schemas.xmlsoap.org/soap/encoding/">
                <username xsi:type="xsd:string">BALI_SANTI</username>
                <password xsi:type="xsd:string">hbd3q2p9b4l1s4nt1bpd8ovr</password>
                <instansi xsi:type="xsd:string">ETIKET_BALI_SANTI</instansi>
                <tanggal xsi:type="xsd:date">2023-09-26</tanggal>
                <nobukti xsi:type="xsd:integer">$no_bukti</nobukti>
            </urn:ws_laporan_payment_detail_setelah_no_bukti>
            </soapenv:Body>
        </soapenv:Envelope>
        XML;
        $response = Http::withHeaders(['Content-Type' => 'text/xml; charset=utf-8',"X-Requested-With" => "XMLHttpRequest"])->send('POST', 'https://maiharta.ddns.net:3100/http://180.242.244.3:7070/ws_bpd_payment/interkoneksi/v1/ws_interkoneksi.php', [
            'body' => $xmls,
        ]);
        $arr = XmlToArray::convert($response);
        $jsonFormatData = json_encode($arr["SOAP-ENV:Body"]["ns1:ws_inquiry_tagihanResponse"]["return"]["@content"]);
        $result = json_decode($jsonFormatData, true);
        print_r($invoice->no_va);
    }
}
