<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Rute extends Model
{
    use SoftDeletes;

    protected $dates = ['deleted_at'];
    //
    protected $fillable = [
        'tujuan_awal', 'tujuan_akhir', 'jarak'
    ];

    public function ruteToJadwal()
    {
        return $this->belongsTo('App\Models\JadwalKeberangkatan', 'id_rute', 'id_rute');
    }

    public function tujuan_awals()
    {
        return $this->hasOne('App\Models\Dermaga', 'id_dermaga', 'tujuan_awal')->with('zona');
    }

    public function tujuan_akhirs()
    {
        return $this->hasOne('App\Models\Dermaga', 'id_dermaga', 'tujuan_akhir')->with('zona');
    }

    // public function ruteToArmada()
    // {
    //     return $this->belongsToMany('App\JadwalKeberangkatan', )
    // }
}
