<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

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
        $invoice = Invoice::with('armada')->find($id);
        $keberangkatans = DB::select("SELECT DATE(k.tanggal_keberangkatan) AS tanggal, d1.nama_dermaga AS dermaga_awal, d2.nama_dermaga AS dermaga_akhir, jk.jadwal, kp.nama_kapal, COUNT(k.id) AS jumlah FROM keberangkatans k
                INNER JOIN jadwal_keberangkatans jk ON k.id_jadwal = jk.id_jadwal
                INNER JOIN rutes r ON jk.id_rute = r.id_rute
                INNER JOIN dermagas d1 ON r.tujuan_awal = d1.id_dermaga
                INNER JOIN dermagas d2 ON r.tujuan_akhir = d2.id_dermaga
                INNER JOIN kapals kp ON kp.id_kapal = jk.id_kapal
                WHERE k.id_invoice = $invoice->id
                GROUP BY k.id_invoice
            ");
        $penumpangs = DB::table('penumpangs as p')
                        ->select('p.nama_penumpang', 'p.no_identitas')
                        ->join('keberangkatans as k', 'k.id_penumpang', 'p.id_penumpang')
                        ->where('k.id_invoice', $invoice->id)
                        ->get();
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
                'ship_name' => 'Gangga Express 18',
                'ship_sail_number' => 'X111111',
                'ship_sail_etd' => '2023-10-02 12:30:00',
                'ship_sail_eta' => '2023-10-02 13:00:00',
                'ship_sail_from' => 'PELABUHAN TRIBUANA',
                'ship_sail_destination' => 'PELABUHAN SAMPALAN',
                'ship_sail_type' => 'keberangkatan',
                'ship_sail_status' => 'domestik',
                'data_penumpang' => $dit_numpangs
            ])
        ]);
        $randTick = $invoice->id;
        $randTickString = (string) $randTick;
        $dit_numpangs = array();
        foreach ($request['data'] as $key => $r) {
            $dit_numpang = array(
                'ticket_id' => $invoice->id,
                'booking_id' => $invoice->id,
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
    }
}
