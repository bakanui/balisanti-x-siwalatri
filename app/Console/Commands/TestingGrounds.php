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
    protected $signature = 'test:all';

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
        function whatGender($jenis_kelamin){
            if ($jenis_kelamin == 0){
                return "L";
            }else {
                return "P";
            }
        }
    
        function whatAge($jenis_penumpang){
            if ($jenis_penumpang == "Dewasa"){
                return rand(18,40);
            }else if ($jenis_penumpang == "Anak - anak"){
                return rand(5,17);
            }else{
                return rand(0,4);
            }
        }
        $this->validate($request,['id_invoice' => 'required']);
        $id_invoice = $request->id_invoice;
        $invoice = Invoice::find($id_invoice);
        $penumpangs = Penumpang::join('keberangkatans', 'keberangkatans.id_penumpang', 'penumpangs.id_penumpang')
                        ->where('id_invoice', $id_invoice);
        if (count($penumpangs->get()) == 0) {
            return response()->json(['message' => 'Penumpang tidak ditemukan'], 404);
        }
        $responses = array();
        $pe = $penumpangs->get();
        array_push($responses, ['penumpangs' => $pe]);
        $jml_penumpang = count($pe);
        $penumpangs->update(['status_verif' => 1]);
        $invoice->status = 1;
        $emailed = $invoice->email;
        $id_jadwal = $pe[0]->id_jadwal;
        $jadwal = JadwalKeberangkatan::query()->where('id_jadwal', $id_jadwal)->firstOrFail();
        $tanggal_berangkat = str_split($pe[0]->tanggal_keberangkatan, 11)[0] . $jadwal->jadwal;
        $rute = $jadwal->jadwalToRute()->with('tujuan_awals')->with('tujuan_akhirs')->get();
        $kapal = $jadwal->jadwalToKapal()->get('nama_kapal');
        $id_kapal = $jadwal->jadwalToKapal()->get('id_kapal');
        $nama_kapal = $jadwal->jadwalToKapal()->get('nama_kapal');
        $jml_real = HistoryKeberangkatan::where('id_jadwal', $id_jadwal)
                    ->where('id_kapal', $id_kapal[0]->id_kapal)
                    ->where('tanggal_berangkat', $tanggal_berangkat)
                    ->pluck('jml_penumpang');
        $jml = $jml_real[0] + $jml_penumpang;
        DB::table('history_keberangkatans')
            ->where('id_jadwal', $id_jadwal)
            ->where('id_kapal', $id_kapal[0]->id_kapal)
            ->update(
                array(
                    'jml_penumpang' => $jml,
                )
            );
        $randTickString = (string) $id_invoice;
        $dit_numpangs = array();
        foreach ($pe as $key => $r) {
            $dit_numpang = array(
                'ticket_id' => $id_invoice,
                'booking_id' => $id_invoice,
                'passenger_name' => $r['nama_penumpang'],
                'passenger_nationality_code' => 'ID',
                'passenger_id_type' => 'KTP',
                'ticket_id' => $randTickString ,
                'booking_id' => $randTickString ,
                'passenger_id_number' => $r['no_identitas'],
                'passenger_gender' => whatGender($r['jenis_kelamin']),
                'passenger_age_value' => whatAge('Dewasa'),
                'passenger_age_status' => 'DEWASA',
                'passenger_address' => $r['alamat'],
                'passenger_seat_number' => 'NON SEAT'
            );
            array_push($dit_numpangs, $dit_numpang);
        }
        $response = Http::withHeaders(['Content-Type' => 'application/json'])->send('POST', 'https://manifest-kapal.dephub.go.id/rest-api/bup/manifest_penumpang/token', [
            'body' => json_encode([
                'api_user' => 'TRIBUANA',
                'api_key' => 'AFQEES'
            ])
        ]);
        if($id_jadwal == "lmuixstd"){
            $insert = Http::withHeaders(['Content-Type' => 'application/json'])->send('POST', 'https://manifest-kapal.dephub.go.id/rest-api/bup/manifest_penumpang/insert_penumpang_batch', [
                'body' => json_encode([
                    'token' => $response['data']['token'],
                    'pelabuhan_id' => '510506',
                    'operator_id' => 'X0109',
                    'ship_name' => 'Gangga Express 18',
                    'ship_sail_number' => 'X105091',
                    'ship_sail_etd' => $tanggal_berangkat . ' 06:30:00',
                    'ship_sail_eta' => $tanggal_berangkat . ' 07:00:00',
                    'ship_sail_from' => 'PELABUHAN TRIBUANA',
                    'ship_sail_destination' => 'PELABUHAN SAMPALAN',
                    'ship_sail_type' => 'keberangkatan',
                    'ship_sail_status' => 'domestik',
                    'data_penumpang' => $dit_numpangs
                ])
            ]);
            array_push($responses, ['ditlala' => $insert->json()]);
        }else if($id_jadwal == "lmyaeiwt"){
            $insert = Http::withHeaders(['Content-Type' => 'application/json'])->send('POST', 'https://manifest-kapal.dephub.go.id/rest-api/bup/manifest_penumpang/insert_penumpang_batch', [
                'body' => json_encode([
                    'token' => $response['data']['token'],
                    'pelabuhan_id' => '510506',
                    'operator_id' => 'X0109',
                    'ship_name' => 'Gangga Express 6',
                    'ship_sail_number' => 'X105192',
                    'ship_sail_etd' => $tanggal_berangkat . ' 06:00:00',
                    'ship_sail_eta' => $tanggal_berangkat . ' 06:30:00',
                    'ship_sail_from' => 'PELABUHAN TRIBUANA',
                    'ship_sail_destination' => 'PELABUHAN SAMPALAN',
                    'ship_sail_type' => 'keberangkatan',
                    'ship_sail_status' => 'domestik',
                    'data_penumpang' => $dit_numpangs
                ])
            ]);
            array_push($responses, ['ditlala' => $insert->json()]);
        }else if($id_jadwal == "lmyae8j6"){
            $insert = Http::withHeaders(['Content-Type' => 'application/json'])->send('POST', 'https://manifest-kapal.dephub.go.id/rest-api/bup/manifest_penumpang/insert_penumpang_batch', [
                'body' => json_encode([
                    'token' => $response['data']['token'],
                    'pelabuhan_id' => '510506',
                    'operator_id' => 'X0109',
                    'ship_name' => 'Gangga Express 2',
                    'ship_sail_number' => 'X105293',
                    'ship_sail_etd' => $tanggal_berangkat . ' 13:30:00',
                    'ship_sail_eta' => $tanggal_berangkat . ' 14:00:00',
                    'ship_sail_from' => 'PELABUHAN TRIBUANA',
                    'ship_sail_destination' => 'PELABUHAN SAMPALAN',
                    'ship_sail_type' => 'keberangkatan',
                    'ship_sail_status' => 'domestik',
                    'data_penumpang' => $dit_numpangs
                ])
            ]);
            array_push($responses, ['ditlala' => $insert->json()]);
        }else if($id_jadwal == "lmyaety9"){
            $insert = Http::withHeaders(['Content-Type' => 'application/json'])->send('POST', 'https://manifest-kapal.dephub.go.id/rest-api/bup/manifest_penumpang/insert_penumpang_batch', [
                'body' => json_encode([
                    'token' => $response['data']['token'],
                    'pelabuhan_id' => '510506',
                    'operator_id' => 'X0109',
                    'ship_name' => 'Gangga Express 6',
                    'ship_sail_number' => 'X105394',
                    'ship_sail_etd' => $tanggal_berangkat . ' 04:00:00',
                    'ship_sail_eta' => $tanggal_berangkat . ' 04:30:00',
                    'ship_sail_from' => 'PELABUHAN TRIBUANA',
                    'ship_sail_destination' => 'PELABUHAN SAMPALAN',
                    'ship_sail_type' => 'keberangkatan',
                    'ship_sail_status' => 'domestik',
                    'data_penumpang' => $dit_numpangs
                ])
            ]);
            array_push($responses, ['ditlala' => $insert->json()]);
        }
        // Mail::to($emailed)->send(new FinishedPayment($invoice));
        $invoice->save();
        print_r($responses);
        // return response()->json($responses, 200);
    }
}
