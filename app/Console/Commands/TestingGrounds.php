<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Invoice;
use Illuminate\Support\Facades\Http;
use Mtownsend\XmlToArray\XmlToArray;
use Illuminate\Support\Facades\DB;
use App\Mail\ConfirmationPayment;
use Illuminate\Support\Facades\Mail;
use App\Models\Penumpang;
use App\Models\JadwalKeberangkatan;
use App\Models\HistoryKeberangkatan;

class TestingGrounds extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'ws:echotest';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $xmls = <<<XML
        <soapenv:Envelope xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xmlns:xsd="http://www.w3.org/2001/XMLSchema" xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/" xmlns:urn="urn:echoTest">
            <soapenv:Header/>
            <soapenv:Body>
            <urn:ws_echo_test soapenv:encodingStyle="http://schemas.xmlsoap.org/soap/encoding/">
                <username xsi:type="xsd:string">BALI_SANTI</username>
                <password xsi:type="xsd:string">BALISANTI@1234</password>
            </urn:ws_echo_test>
            </soapenv:Body>
        </soapenv:Envelope>
        XML;
        $response = Http::withHeaders(['Content-Type' => 'text/xml; charset=utf-8',"X-Requested-With" => "XMLHttpRequest"])->send('POST', 'https://portal.bpdbali.id/ws_bpd_payment/interkoneksi/v1/ws_interkoneksi.php', [
            'body' => $xmls,
        ]);
        $arr = XmlToArray::convert($response);
        $jsonFormatData = json_encode($arr["SOAP-ENV:Body"]["ns1:ws_echo_testResponse"]["return"]["@content"]);
        $result = json_decode($jsonFormatData, true);
        $result2 = json_decode($result, true);
        print_r($result2);
    }
}
