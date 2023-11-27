<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RekonQris extends Model
{
    use HasFactory;
    protected $fillable = [
        'productCode', 'qrValue', 'nmid', 'merchantName', 'expiredDate', 'amount', 'totalAmount', 'billNumber'
    ];
}
