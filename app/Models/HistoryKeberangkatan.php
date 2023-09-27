<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class HistoryKeberangkatan extends Model
{
    use HasFactory;

    protected $fillable = [
        'id', 'id_jadwal', 'id_kapal', 'tanggal_berangkat', 'tanggal_sampai', 'jml_penumpang'
    ];

}
