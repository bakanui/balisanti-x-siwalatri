<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Invoice extends Model
{
    use HasFactory;
    
    public function armada()
    {
        return $this->belongsTo('App\Models\Armada', 'id_armada', 'id_armada')->select(['id_armada','nama_armada']);
    }
}
