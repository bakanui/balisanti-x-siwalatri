<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\DB;

class Dermaga extends Model
{
    use SoftDeletes;

    protected $dates = ['deleted_at'];
    //
    protected $fillable = [
        'nama_dermaga', 'lokasi', 'id_syahbandar'
    ];

    public function zona()
    {
        return $this->belongsTo('App\Models\Zona', 'id_zona', 'id_zona');
    }
}
