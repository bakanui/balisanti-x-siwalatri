<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RekonVa extends Model
{
    use HasFactory;
    protected $fillable = [
        'jenis_pembayaran', 'jenis_tiket', 'jumlah_tiket','no_tagihan','operator','tanggal_keberangkatan','tanggal_pembelian_tiket','tujuan','instansi','kd_user','nama','recordId','sts_bayar','tagihan','tgl_upd', 'invoice_id'
    ];
}
