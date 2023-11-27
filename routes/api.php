<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthManualController;
use App\Http\Controllers\NahkodaController;
use App\Http\Controllers\ArmadaController;
use App\Http\Controllers\KapalController;
use App\Http\Controllers\JenisKapalController;
use App\Http\Controllers\StatusKapalController;
use App\Http\Controllers\DermagaController;
use App\Http\Controllers\RuteController;
use App\Http\Controllers\HistoryKeberangkatanController;
use App\Http\Controllers\JadwalKeberangkatanController;
use App\Http\Controllers\JenisTujuanController;
use App\Http\Controllers\PenumpangController;
use App\Http\Controllers\SopController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\JenisPenumpangController;
use App\Http\Controllers\WisataController;
use App\Http\Controllers\BpdServicelogController;
use App\Http\Controllers\PengumumanController;
use App\Http\Controllers\InvoiceController;
/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::get('/user', function (Request $request) {
    return "Success";
});

Route::post('test', [KapalController::class,'test']);

Route::group(['prefix' => 'auth'], function () {
    Route::post('register_armada', [AuthManualController::class,'storeArmada']);
    Route::post('register_loket', [AuthManualController::class,'storeLoket']);
    Route::post('register_nahkoda', [AuthManualController::class, 'storeNahkoda']);
    Route::post('login', [AuthManualController::class, 'login']);
    Route::post('change-password', [AuthManualController::class, 'changePassword']);
});

Route::group(['middleware' => 'jwt.verify'], function () {
    Route::group(['prefix' => 'nahkoda'], function () {
        Route::get('profile/{id_user}', [NahkodaController::class,'view']);
        Route::get('{id_armada}', [NahkodaController::class,'index']);
        Route::post('profile/{id_user}', [NahkodaController::class,'edit']);
        Route::get('view/kosong/{id_armada}', [NahkodaController::class,'nahkoda_kosong']);
        Route::get('profile/delete/{id_user}', [NahkodaController::class,'delete']);
    });
});

    Route::group(['prefix' => 'armada'], function () {
        Route::get('{id_armada}', [ArmadaController::class,'view']);
        Route::get('', [ArmadaController::class,'index']);
        Route::post('{id_armada}', [ArmadaController::class,'edit'])->middleware('jwt.verify');
        Route::post('', [ArmadaController::class,'store'])->middleware('jwt.verify');
    });

    Route::group(['prefix' => 'loket'], function () {
        Route::get('{id_armada}', [ArmadaController::class,'index_loket']);
        Route::get('welcome/{id_armada}', [ArmadaController::class, 'welcome_loket']);
        Route::post('edit/{id_loket}', [ArmadaController::class,'edit_loket'])->middleware('jwt.verify');
        Route::get('delete/{id_loket}', [ArmadaController::class,'delete_loket'])->middleware('jwt.verify');
    });

    Route::group(['prefix' => 'user'], function () {
        Route::get('', [AuthManualController::class,'index']);
        Route::get('syahbandar', [AuthManualController::class,'index_syahbandar']);
        Route::post('{id_loket}', [ArmadaController::class,'edit_loket'])->middleware('jwt.verify');
    });

    Route::group(['prefix' => 'kapal'], function () {
        Route::get('profile/{id_kapal}', [KapalController::class,'view']);
        Route::get('{id_armada}', [KapalController::class,'index']);
        Route::get('delete/{id_armada}', [KapalController::class,'delete'])->middleware('jwt.verify');
        Route::post('{id_kapal}', [KapalController::class,'edit'])->middleware('jwt.verify');
        Route::post('', [KapalController::class,'store'])->middleware('jwt.verify');
    });

Route::group(['middleware' => 'jwt.verify'], function () {
    Route::group(['prefix' => 'jenis_kapal'], function () {
        Route::get('{id_jenis}', [JenisKapalController::class,'view']);
        Route::get('', [JenisKapalController::class,'index']);
        Route::post('{id_jenis}', [JenisKapalController::class,'edit']);
        Route::post('', [JenisKapalController::class,'store']);
        Route::get('delete/{id_jenis}', [JenisKapalController::class,'delete']);
        Route::get('search/data/kapal', [JenisKapalController::class,'search']);
    });

    Route::group(['prefix' => 'status_kapal'], function () {
        Route::get('{id_status}', [StatusKapalController::class,'view']);
        Route::get('', [StatusKapalController::class,'index']);
        Route::post('{id_status}', [StatusKapalController::class,'edit']);
        Route::post('', [StatusKapalController::class,'store']);
    });

    Route::group(['prefix' => 'dermaga'], function () {
        Route::post('', [DermagaController::class,'store']);
        Route::post('edit/{id_dermaga}', [DermagaController::class,'edit']);
        Route::get('', [DermagaController::class,'view']);
        Route::get('delete/{id_dermaga}', [DermagaController::class,'delete']);
    });
});

Route::group(['prefix' => 'rute'], function () {
    Route::get('{id_rute}', [RuteController::class,'view']);
    Route::get('list/now', [RuteController::class,'list_now']);
    Route::get('', [RuteController::class,'index']);
    Route::post('{id_rute}', [RuteController::class,'edit'])->middleware('jwt.verify');
    Route::get('delete/data/{id_rute}', [RuteController::class,'delete'])->middleware('jwt.verify');
    Route::post('', [RuteController::class,'store'])->middleware('jwt.verify');
});

    Route::group(['prefix' => 'jadwal_pelabuhan'], function () {
        Route::post('create/{id_armada}', [JadwalKeberangkatanController::class,'create_jadwal_pelabuhan'])->middleware('jwt.verify');
    });

    Route::group(['prefix' => 'manifest'], function () {
        Route::post('add', [JadwalKeberangkatanController::class,'manifest']);
    });

    Route::group(['prefix' => 'jadwal_keberangkatan'], function () {
        Route::get('detail-jadwal/{id_jadwal}', [DashboardController::class, 'detail_jadwal']);
        Route::get('', [JadwalKeberangkatanController::class, 'get_all']);
        Route::get('view/approval/{id_user}/{approval}', [JadwalKeberangkatanController::class,'view_approval'])->middleware('jwt.verify');
        Route::post('proses/approval', [JadwalKeberangkatanController::class,'proses_approval']);
        Route::get('view/detail/approval/{id_jadwal}', [SopController::class,'view_sop'])->middleware('jwt.verify');
        Route::get('view/{id_jadwal}', [JadwalKeberangkatanController::class,'view']);
        Route::get('index/{id_armada}', [JadwalKeberangkatanController::class,'index']);
        Route::post('edit/{id_jadwal}', [JadwalKeberangkatanController::class,'edit'])->middleware('jwt.verify');
        Route::get('delete/{id_jadwal}', [JadwalKeberangkatanController::class,'delete'])->middleware('jwt.verify');
        Route::post('{id_armada}', [JadwalKeberangkatanController::class,'store'])->middleware('jwt.verify');
        Route::post('start/{id_jadwal}', [JadwalKeberangkatanController::class,'start']);
        Route::post('stop/{id_jadwal}/{id_hs}', [JadwalKeberangkatanController::class,'stop']);
        Route::post('reset/now', [JadwalKeberangkatanController::class,'reset'])->middleware('jwt.verify');
        Route::get('view/now/{id_armada}', [JadwalKeberangkatanController::class,'view_now']);
        Route::get('view/penumpang/{id_jadwal}', [JadwalKeberangkatanController::class,'view_penumpang'])->middleware('jwt.verify');
        Route::get('view/rute/{id_rute}', [JadwalKeberangkatanController::class,'view_rute'])->middleware('jwt.verify');
        Route::get('list/{id_nahkoda}', [JadwalKeberangkatanController::class,'list_nahkoda'])->middleware('jwt.verify');
        Route::get('keberangkatan/{id_armada}', [JadwalKeberangkatanController::class,'keberangkatan'])->middleware('jwt.verify');
        Route::get('view_keberangkatan/{id_jadwal}', [JadwalKeberangkatanController::class,'view_keberangkatan'])->middleware('jwt.verify');
        Route::get('rute/{id_rute}', [JadwalKeberangkatanController::class,'view_rute']);
        Route::get('kapal/{id_kapal}', [JadwalKeberangkatanController::class,'view_kapal']);
        Route::get('search/tiket/{id_jadwal}/{id_jns_penum}', [JadwalKeberangkatanController::class,'search_tiket']);
        Route::get('view/tiket/{id_jadwal}', [JadwalKeberangkatanController::class,'view_tiket']);
        Route::post('add/tiket/{id_jadwal}', [JadwalKeberangkatanController::class,'add_tiket'])->middleware('jwt.verify');
        Route::post('edit/tiket/{id}', [JadwalKeberangkatanController::class,'edit_tiket'])->middleware('jwt.verify');
        Route::get('delete/tiket/{id}', [JadwalKeberangkatanController::class,'delete_tiket'])->middleware('jwt.verify');
        Route::get('detail/dashboard/jadwal/{id}', [JadwalKeberangkatanController::class,'view_dashboard'])->middleware('jwt.verify');
        Route::get('public', [JadwalKeberangkatanController::class,'jadwal_public']);
        Route::get('get-list-jadwalrute', [JadwalKeberangkatanController::class,'listJadwalDariRute']);
        Route::get('get-zona-akhir', [JadwalKeberangkatanController::class,'cariZonaTujuan']);
        Route::get('get-zona', [JadwalKeberangkatanController::class,'view_zona']);
    });

Route::get('jenis_tujuan', [JenisTujuanController::class,'index']);
Route::get('jenis_penumpang', [JenisPenumpangController::class,'index']);
Route::get('penumpang', [PenumpangController::class,'index']);
Route::post('penumpang', [PenumpangController::class,'store']);
Route::post('penumpang-group', [PenumpangController::class,'storeGroup']);
Route::post('penumpang-by-atix', [PenumpangController::class,'storeByAtix']);
Route::get('get-penumpang-tanggal/{tanggal}', [PenumpangController::class, 'penumpangByTanggal']);
Route::get('penumpang/total/{id_jadwal}', [PenumpangController::class,'total']);
Route::post('penumpang/update-status-invoice', [PenumpangController::class, 'updateStatusInvoice']);
Route::get('penumpang/get-invoice', [PenumpangController::class, 'getInvoice']);
Route::post('penumpang/update-billnumber-invoice', [PenumpangController::class, 'updateBillNumberInvoice']);
Route::post('penumpang/update-invoice', [PenumpangController::class, 'updateInvoice']);
Route::post('penumpang/update-invoice-callback', [PenumpangController::class, 'updateInvoiceCallback']);
Route::get('laporan/harian_armada/detail', [DashboardController::class,'detailHarian']);
Route::get('laporan/bulanan_armada/detail', [DashboardController::class,'detailBulanan']);
Route::get('laporan/harian_armada/detail/pdf', [DashboardController::class,'detailHarianPDF']);
Route::get('laporan/bulanan_armada/detail/pdf', [DashboardController::class,'detailBulananPDF']);
Route::get('laporan/tahunan_armada/detail', [DashboardController::class,'detailTahunan']);
Route::get('laporan/harian_armada/detail/{id_jadwal}', [DashboardController::class,'detail']);
Route::get('laporan/harian_armada/detail-non-history/{id_jadwal}', [DashboardController::class,'detailTanpaHistory']);
Route::get('laporan/harian_armada/detail-non-history2/{id_jadwal}', [DashboardController::class,'detailTanpaHistory2']);
Route::get('laporan/penjualan', [PenumpangController::class,'cetakPenjualan']);

Route::group([], function () {
    Route::group(['prefix' => 'jenis_tujuan'], function () {
        Route::get('{id_tujuan}', [JenisTujuanController::class,'view']);
        Route::post('{id_tujuan}', [JenisTujuanController::class,'edit']);
        Route::post('', [JenisTujuanController::class,'store']);
        Route::get('delete/{id_tujuan}', [JenisTujuanController::class,'delete']);
    });

    Route::group(['prefix' => 'jenis_penumpang'], function () {
        Route::get('{id_jns_penumpang}', [JenisPenumpangController::class,'view']);
        Route::post('{id_jns_penumpang}', [JenisPenumpangController::class,'edit']);
        Route::post('', [JenisPenumpangController::class,'store']);
        Route::get('/delete/{id_jns_penumpang}', [JenisPenumpangController::class,'delete']);
    });

    Route::group(['prefix' => 'barang'], function () {
        Route::get('', [PenumpangController::class,'get_master_barang']);
        Route::post('', [PenumpangController::class,'add_master_barang']);
        Route::post('/{id}', [PenumpangController::class,'edit_master_barang']);
        Route::get('/{id}', [PenumpangController::class,'delete_master_barang']);
    });

    Route::group(['prefix' => 'penumpang'], function () {
        Route::get('{penumpang}', [PenumpangController::class,'view']);
        Route::get('detail/jenis/{id_jadwal}', [PenumpangController::class,'detail_jenis_penumpang']);
        Route::get('detail/tujuan/{id_jadwal}', [PenumpangController::class,'detail_tujuan_penumpang']);
        Route::get('search/{id_armada}', [PenumpangController::class,'search']);
        Route::get('view/{id_armada}', [PenumpangController::class,'view_penumpang']);
        Route::get('view_harian/{id_armada}', [PenumpangController::class,'view_harian']);
        Route::post('{penumpang}', [PenumpangController::class,'edit']);
        Route::post('add/penumpang_barang', [PenumpangController::class,'store_barang']);
        Route::post('add/barang/{id_penumpang}', [PenumpangController::class,'add_barang']);
        Route::get('view/ekspedisi/{id_tujuan}', [PenumpangController::class,'view_ekspedisi']);
    });

    Route::group(['prefix' => 'booking'], function () {
        Route::get('{id_armada}', [PenumpangController::class,'view']);
        Route::post('', [PenumpangController::class,'store']);
    });

    Route::group(['prefix' => 'sop'], function () {
        Route::get('', [SopController::class,'view']);
        Route::post('{id}', [SopController::class,'edit']);
        Route::post('add/approve', [SopController::class,'add_sop']);
        Route::post('', [SopController::class,'store']);
        Route::get('delete/{id}', [SopController::class,'delete']);
    });

    Route::group(['prefix' => 'dashboard'], function () {
        Route::get('/penumpang/{query}', [DashboardController::class,'grafikPenumpang']);
        Route::get('/keberangkatan/{query}', [DashboardController::class,'grafikKeberangkatan']);
        Route::get('/kapal', [DashboardController::class,'grafikKapal']);
        Route::get('/jenis_penumpang/{query}', [DashboardController::class,'grafikJenis']);
    });

    Route::group(['prefix' => 'laporan'], function () {
        Route::get('/harian_armada', [DashboardController::class,'data_harian']);
        Route::get('/bulanan_armada', [DashboardController::class,'data_bulanan']);
        Route::get('/manifest/armada/{id_armada}', [DashboardController::class,'laporan_manifest']);
        Route::get('/manifest/armada/{id_armada}/pdf', [DashboardController::class,'export_pdf_backend']);
    });

    Route::group(['prefix' => 'jalur'], function () {
        Route::post('/manifest', [JadwalKeberangkatanController::class,'insert_jalur']);
    });

    Route::group(['prefix' => 'kecakapan'], function () {
        Route::get('', [NahkodaController::class,'getKecakapanMaster']);
    });
});

Route::group(['prefix' => 'wisata'], function () {
    Route::get('', [WisataController::class,'index']);
    Route::post('', [WisataController::class,'store']);
    Route::post('{id}/edit', [WisataController::class,'edit']);
    Route::post('{id}/update', [WisataController::class,'update']);
    Route::post('{id}/delete', [WisataController::class,'delete']);
 });

Route::group(['prefix' => 'pengumuman'], function () {
    Route::get('', [PengumumanController::class,'index']);
    Route::post('', [PengumumanController::class,'store']);
    Route::post('{id}/edit', [PengumumanController::class,'edit']);
    Route::post('{id}/update', [PengumumanController::class,'update']);
    Route::post('{id}/delete', [PengumumanController::class,'delete']);
});

Route::get('total-penumpang-jadwal', [JadwalKeberangkatanController::class, 'total_penumpang_jadwal']);
Route::get('xml-to-json', [JadwalKeberangkatanController::class, 'xml_to_json']);
Route::get('detail-jadwal/{id_jadwal}', [DashboardController::class, 'detailJadwal']);

// Route::middleware(['blockIP'])->group(function () {
//     Route::get('/testApi', [PenumpangController::class, 'testApi']);
// });

Route::middleware(['block-ip', 'throttle:block-ip'])->group(function () {
    Route::get('/testApi', [PenumpangController::class, 'testApi']);
});

Route::group(['prefix' => 'jadwal_keberangkatan'], function () {
    Route::post('{id_armada}', [JadwalKeberangkatanController::class,'store']);
    Route::post('', [JadwalKeberangkatanController::class,'storeBySiwalatri']);
});

Route::group(['prefix' => 'history_keberangkatan'], function () {
    Route::post('', [HistoryKeberangkatanController::class,'create']);
    Route::post('/edit', [HistoryKeberangkatanController::class,'edit']);
});

Route::group(['prefix' => 'logs'], function () {
    Route::post('/va-bpd', [BpdServicelogController::class,'storeVALogs']);
    Route::post('/delete', [PenumpangController::class,'deletionChecker']);
    Route::post('/deletion', [PenumpangController::class,'delete']);
});

Route::group(['prefix' => 'invoice'], function () {
    Route::get('', [InvoiceController::class,'getInvoices']);
    Route::get('/va', [InvoiceController::class,'getRekonVa']);
    Route::get('/qris', [InvoiceController::class,'getRekonQris']);
});
