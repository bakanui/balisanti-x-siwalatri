<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\HistoryKeberangkatan;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use GuzzleHttp\Client;
use Illuminate\Support\Facades\Http;

class DitlalaInsert extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'ditlala:insert';

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
        $tanggal = '2023-11-15';
        $keberangkatans = DB::select(
            "SELECT
            k.id_jadwal, 
            jk.jadwal, 
            d1.nama_dermaga AS tujuan_awal,
            d2.nama_dermaga AS tujuan_akhir,
            kp.nama_kapal
            FROM
                keberangkatans AS k
                INNER JOIN
                jadwal_keberangkatans AS jk
                ON 
                    k.id_jadwal = jk.id_jadwal
                INNER JOIN
                rutes AS r
                ON 
                    jk.id_rute = r.id_rute
                INNER JOIN
                dermagas AS d1
                ON 
                    r.tujuan_awal = d1.id_dermaga
                INNER JOIN
                dermagas AS d2
                ON 
                    r.tujuan_akhir = d2.id_dermaga
                INNER JOIN
                kapals AS kp
                ON 
                    kp.id_kapal = jk.id_kapal
            WHERE
                tanggal_keberangkatan LIKE '%$tanggal%'AND
	            d1.nama_dermaga = 'Tribuana'
            GROUP BY
                k.id_jadwal");
        foreach ($keberangkatans as $key => $r) {
            $t_keberangkatan = $tanggal . " 00:00:00";
            $penumpangs = DB::select(
                "SELECT
                *
                FROM
                    penumpangs AS p
                    INNER JOIN
                    keberangkatans AS k
                    ON 
                        p.id_penumpang = k.id_penumpang
                WHERE
                    p.flag_ditlala = 0 AND
                    k.id_jadwal = '$r->id_jadwal' AND
                    k.tanggal_keberangkatan = '$t_keberangkatan'
            ");
            $history = HistoryKeberangkatan::where('id_jadwal', $r->id_jadwal)
                        ->where('tanggal_berangkat', $tanggal . " " . $r->jadwal)->first();
            $dit_numpangs = array();
            foreach ($penumpangs as $key => $s) {
                $dit_numpang = array(
                    'ticket_id' => $s->id_invoice,
                    'booking_id' => $s->id_invoice,
                    'passenger_name' => $s->nama_penumpang,
                    'passenger_nationality_code' => 'ID',
                    'passenger_id_type' => 'KTP',
                    'ticket_id' => (string) $s->id_invoice,
                    'booking_id' => (string) $s->id_invoice,
                    'passenger_id_number' => $s->no_identitas,
                    'passenger_gender' => whatGender($s->jenis_kelamin),
                    'passenger_age_value' => whatAge('Dewasa'),
                    'passenger_age_status' => 'DEWASA',
                    'passenger_address' => $s->alamat,
                    'passenger_seat_number' => 'NON SEAT'
                );
                array_push($dit_numpangs, $dit_numpang);
            }
            $t_depart = $tanggal . " " . $r->jadwal;
            $t_adder = Carbon::createFromFormat('Y-m-d H:i:s', $t_depart);
            $sail_number = sprintf("%06d", mt_rand(1, 999999));
            $response = Http::withHeaders(['Content-Type' => 'application/json'])->send('POST', 'https://manifest-kapal.dephub.go.id/rest-api/bup/manifest_penumpang/token', [
                'body' => json_encode([
                    'api_user' => 'TRIBUANA',
                    'api_key' => 'AFQEES'
                ])
            ]);
            $insert = Http::withHeaders(['Content-Type' => 'application/json'])->send('POST', 'https://manifest-kapal.dephub.go.id/rest-api/bup/manifest_penumpang/insert_penumpang_batch', [
                'body' => json_encode([
                    'token' => $response['data']['token'],
                    'pelabuhan_id' => '510506',
                    'operator_id' => 'X0109',
                    'ship_name' => $r->nama_kapal,
                    'ship_sail_number' => 'X' . $sail_number,
                    'ship_sail_etd' => $t_depart,
                    'ship_sail_eta' => $t_adder->addHour()->format('Y-m-d H:i:s'),
                    'ship_sail_from' => 'PELABUHAN TRIBUANA',
                    'ship_sail_destination' => 'PELABUHAN SAMPALAN',
                    'ship_sail_type' => 'keberangkatan',
                    'ship_sail_status' => 'domestik',
                    'data_penumpang' => $dit_numpangs
                ])
            ]);
            print_r($insert);
        }
    }
}
