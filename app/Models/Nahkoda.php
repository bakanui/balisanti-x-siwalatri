<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Nahkoda extends Model
{
    use SoftDeletes;

    protected $dates = ['deleted_at'];
    //
    protected $fillable = [
        'id_nahkoda', 'nama_nahkoda', 'no_hp', 'id_armada', 'id_kecakapan'
    ];

    public function nahkodaToUser()
    {
        return $this->hasOne('App\Models\User', 'id', 'id_nahkoda');
    }

    public function nahkodaToArmada()
    {
        return $this->belongsTo('App\Models\Armada', 'id_armada', 'id_armada');
    }
    
    public function nahkodaToKecakapan()
    {
        return $this->belongsTo('App\Models\KecakapanNahkoda','id_kecakapan', 'id_kecakapan');
    }

}
