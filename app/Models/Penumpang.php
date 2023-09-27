<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Penumpang extends Model
{
    use SoftDeletes;

    protected $dates = ['deleted_at'];
    //
    // protected $fillable = [
    //     'nama_penumpang', 'no_identitas', 'id_jns_penum', 'id_tujuan', 'jenis_kelamin', 'alamat', 'nomer_kendaraan', 'tanggal','status_verif','freepass','harga_tiket'
    // ];
    protected $guarded = [];

    public function penumpangToTujuan()
    {
        return $this->belongsTo('App\Models\JenisTujuan', 'id_tujuan', 'id_tujuan');
    }

    public function penumpangToJenis()
    {
        return $this->belongsTo('App\Models\JenisPenumpang', 'id_jns_penum', 'id_jns_penum');
    }

    public function penumpangToKeberangkatan()
    {
        return $this->belongsTo('App\Models\Keberangkatan', 'id_penumpang', 'id_penumpang')->select('keberangkatans.id_tiket');
    }

    public function penumpangToJadwal()
    {
        return $this->belongsToMany(
            JadwalKeberangkatan::class,
            'keberangkatans',
            'id_penumpang',
            'id_jadwal',
            'id',
            'id_jadwal'
        )
            ->select('jadwal_keberangkatans.*', 'keberangkatans.tanggal_keberangkatan')
            ->with('jadwalToRute')
            ->with('jadwalToNahkoda')
            ->with('jadwalToKapal');
    }
}
