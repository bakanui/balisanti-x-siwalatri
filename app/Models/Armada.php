<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Armada extends Model
{
    use SoftDeletes;

    protected $dates = ['deleted_at'];
    //
    protected $fillable = [
        'id_armada', 'id_user', 'nama_armada', 'kontak', 'alamat', 'description'
    ];

    public function armadaToUser()
    {
        return $this->hasMany('App\Models\User', 'id', 'id_user');
    }

    public function armadaToJadwal()
    {
        return $this->hasMany('App\Models\JadwalKeberangkatan', 'id_armada', 'id_armada')
            ->with('jadwalToRute')
            ->with('jadwalToKapal');
    }
    
    public function armadaToLoket()
    {
        return $this->hasMany('App\Models\Loket', 'id_armada', 'id_armada');
    }
}
