<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\NotificationController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin\AdminUserController;
use App\Http\Controllers\Admin\AdminRoleController;
use App\Http\Controllers\Admin\AdminPermissionController;
use App\Http\Controllers\Admin\AdminPenempatanController;
use App\Http\Controllers\Admin\AdminLogbookController;
use App\Http\Controllers\Admin\AdminPerizinanController;
use App\Http\Controllers\Admin\AdminPenilaianController;
use App\Http\Controllers\Admin\AdminPeringatanDiniController;
use App\Http\Controllers\Admin\AdminPresensiController;
use App\Http\Controllers\Guru\GuruPresensiController;
use App\Http\Controllers\Guru\GuruSiswaController;
use App\Http\Controllers\Guru\GuruLogbookController;
use App\Http\Controllers\Guru\GuruPerizinanController;
use App\Http\Controllers\Guru\GuruPenilaianController;
use App\Http\Controllers\Guru\GuruPeringatanDiniController;
use App\Http\Controllers\Siswa\SiswaPresensiController;
use App\Http\Controllers\Siswa\SiswaDashboardController;
use App\Http\Controllers\Siswa\SiswaPenempatanController;
use App\Http\Controllers\Siswa\SiswaLogbookController;
use App\Http\Controllers\Siswa\SiswaPerizinanController;
use App\Http\Controllers\Siswa\SiswaPenilaianController;
use App\Http\Controllers\Siswa\SiswaBerkasController;
use App\Http\Controllers\Industri\IndustriPresensiController;
use App\Http\Controllers\Industri\IndustriPengajuanController;
use App\Http\Controllers\Industri\IndustriDataSiswaController;
use App\Http\Controllers\Industri\IndustriLogbookController;
use App\Http\Controllers\Industri\IndustriPerizinanController;
use App\Http\Controllers\Industri\IndustriPenilaianController;

Route::get('/', function () {
    if (!auth()->check()) {
        return redirect()->route('login');
    }

    $user = auth()->user();
    if ($user->hasRole('admin')) {
        return redirect()->route('admin.data-pengguna');
    }
    if ($user->hasRole('guru pembimbing')) {
        return redirect()->route('guru.siswa');
    }
    if ($user->hasRole('siswa')) {
        return redirect()->route('siswa.dashboard');
    }
    if ($user->hasRole('perwakilan industri')) {
        return redirect()->route('industri.pengajuan');
    }

    return redirect()->route('dashboard');
});

Route::get('/dashboard', function () {
    return view('dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
    Route::get('/notifications', [NotificationController::class, 'index'])->name('notifications.index');
    Route::post('/notifications/read-all', [NotificationController::class, 'readAll'])->name('notifications.read-all');
});

Route::middleware(['auth', 'role:admin'])->group(function () {
    //ROUTE ADMIN DATA PENGGUNA
    Route::get('/data-pengguna', [AdminUserController::class, 'index'])
        ->middleware(['auth', 'role:admin'])
        ->name('admin.data-pengguna');
    Route::post('/data-pengguna/industri/{industri}/pengajuan', [AdminUserController::class, 'kirimPengajuanIndustri'])
        ->middleware(['auth', 'role:admin'])
        ->name('admin.industri.pengajuan');
    Route::get('/penempatan', [AdminPenempatanController::class, 'index'])->name('admin.penempatan');
    Route::post('/penempatan/bobot', [AdminPenempatanController::class, 'storeBobot'])->name('admin.penempatan.bobot');
    Route::post('/penempatan/run-saw', [AdminPenempatanController::class, 'runSaw'])->name('admin.penempatan.run-saw');
    Route::post('/penempatan/usulan/{usulan}/approve', [AdminPenempatanController::class, 'approveUsulanIndustri'])
        ->name('admin.penempatan.usulan.approve');
    Route::post('/penempatan/usulan/{usulan}/reject', [AdminPenempatanController::class, 'rejectUsulanIndustri'])
        ->name('admin.penempatan.usulan.reject');
    Route::post('/penempatan/{penempatan}/confirm', [AdminPenempatanController::class, 'confirmPilihan'])
        ->name('admin.penempatan.confirm');
    Route::post('/penempatan/{penempatan}/reject', [AdminPenempatanController::class, 'rejectPilihan'])
        ->name('admin.penempatan.reject');
    Route::post('/penempatan/{penempatan}/guru', [AdminPenempatanController::class, 'setGuruPembimbing'])
        ->name('admin.penempatan.guru');
    Route::post('/penempatan/{penempatan}/laporan', [AdminPenempatanController::class, 'updateLaporanStatus'])
        ->name('admin.penempatan.laporan');
    Route::post('/penempatan/langsung', [AdminPenempatanController::class, 'penempatanLangsung'])
        ->name('admin.penempatan.langsung');
    Route::get('/elogbook', [AdminLogbookController::class, 'index'])->name('admin.elogbook');
    Route::get('/perizinan', [AdminPerizinanController::class, 'index'])->name('admin.perizinan');
    Route::post('/perizinan', [AdminPerizinanController::class, 'store'])->name('admin.perizinan.store');
    Route::put('/perizinan/{perizinan}', [AdminPerizinanController::class, 'update'])->name('admin.perizinan.update');
    Route::delete('/perizinan/{perizinan}', [AdminPerizinanController::class, 'destroy'])->name('admin.perizinan.destroy');
    Route::get('/penilaian', [AdminPenilaianController::class, 'index'])->name('admin.penilaian');
    Route::post('/penilaian/rubrik', [AdminPenilaianController::class, 'updateRubrik'])->name('admin.penilaian.rubrik');
    Route::post('/penilaian/aspek', [AdminPenilaianController::class, 'storeAspek'])->name('admin.penilaian.aspek.store');
    Route::delete('/penilaian/aspek/{aspek}', [AdminPenilaianController::class, 'destroyAspek'])->name('admin.penilaian.aspek.destroy');
    Route::get('/peringatan-dini', [AdminPeringatanDiniController::class, 'index'])->name('admin.peringatan-dini');
    Route::post('/peringatan-dini/run', [AdminPeringatanDiniController::class, 'runRisk'])->name('admin.peringatan-dini.run');
    Route::get('/presensi', [AdminPresensiController::class, 'index'])->name('admin.presensi');
    Route::put('/presensi/geofence/radius-global', [AdminPresensiController::class, 'updateGlobalRadius'])->name('admin.presensi.geofence.radius-global');

    Route::get('/forms', function () {
        return view('admin.forms');
    })->name('admin.forms');
    Route::get('/tables', function () {
        return view('admin.tables');
    })->name('admin.tables');
    Route::get('/ui-elements', function () {
        return view('admin.ui-elements');
    })->name('admin.ui-elements');
});

Route::group(['middleware' => ['permission:publish articles']], function () {});

Route::middleware(['auth', 'role:guru pembimbing'])->prefix('guru')->name('guru.')->group(function () {
    Route::get('/siswa', [GuruSiswaController::class, 'index'])->name('siswa');
    Route::get('/presensi', [GuruPresensiController::class, 'index'])->name('presensi');
    Route::get('/elogbook', [GuruLogbookController::class, 'index'])->name('elogbook');
    Route::post('/elogbook/{logbook}/komentar', [GuruLogbookController::class, 'storeKomentar'])->name('elogbook.komentar');
    Route::get('/perizinan', [GuruPerizinanController::class, 'index'])->name('perizinan');
    Route::get('/penilaian', [GuruPenilaianController::class, 'index'])->name('penilaian');
    Route::get('/peringatan-dini', [GuruPeringatanDiniController::class, 'index'])->name('peringatan-dini');
});

Route::middleware(['auth', 'role:siswa'])->prefix('siswa')->name('siswa.')->group(function () {
    Route::get('/dashboard', [SiswaDashboardController::class, 'index'])->name('dashboard');
    Route::post('/penempatan/pilih', [SiswaPenempatanController::class, 'pilihRekomendasi'])->name('penempatan.pilih');
    Route::post('/penempatan/usulan', [SiswaPenempatanController::class, 'usulkanIndustri'])->name('penempatan.usulan');
    Route::post('/elogbook', [SiswaLogbookController::class, 'store'])->name('elogbook.store');
    Route::put('/elogbook/{logbook}', [SiswaLogbookController::class, 'update'])->name('elogbook.update');
    Route::delete('/elogbook/{logbook}', [SiswaLogbookController::class, 'destroy'])->name('elogbook.destroy');
    Route::get('/presensi', [SiswaPresensiController::class, 'index'])->name('presensi');
    Route::post('/presensi', [SiswaPresensiController::class, 'store'])->name('presensi.store');
    Route::post('/perizinan', [SiswaPerizinanController::class, 'store'])->name('perizinan.store');
    Route::put('/berkas', [SiswaBerkasController::class, 'update'])->name('berkas.update');
});

Route::middleware(['auth', 'role:perwakilan industri'])->prefix('industri')->name('industri.')->group(function () {
    Route::get('/pengajuan', [IndustriPengajuanController::class, 'index'])->name('pengajuan');
    Route::post('/pengajuan', [IndustriPengajuanController::class, 'konfirmasi'])->name('pengajuan.konfirmasi');
});

Route::middleware(['auth', 'role:perwakilan industri', 'industri.approved'])->prefix('industri')->name('industri.')->group(function () {
    Route::get('/siswa', [IndustriDataSiswaController::class, 'index'])->name('siswa');
    Route::post('/siswa/{penempatan}/status', [IndustriDataSiswaController::class, 'setStatus'])->name('siswa.status');
    Route::post('/siswa/{penempatan}/jadwal', [IndustriDataSiswaController::class, 'storeJadwal'])->name('siswa.jadwal');
    Route::post('/siswa/{penempatan}/laporan', [IndustriDataSiswaController::class, 'storeLaporan'])->name('siswa.laporan');
    Route::get('/presensi', [IndustriPresensiController::class, 'index'])->name('presensi');
    Route::post('/presensi/{absensi}/review', [IndustriPresensiController::class, 'review'])->name('presensi.review');
    Route::get('/elogbook', [IndustriLogbookController::class, 'index'])->name('elogbook');
    Route::post('/elogbook/{logbook}', [IndustriLogbookController::class, 'update'])->name('elogbook.update');
    Route::get('/perizinan', [IndustriPerizinanController::class, 'index'])->name('perizinan');
    Route::post('/perizinan/{perizinan}', [IndustriPerizinanController::class, 'update'])->name('perizinan.update');
    Route::get('/penilaian', [IndustriPenilaianController::class, 'index'])->name('penilaian');
    Route::post('/penilaian/{penempatan}', [IndustriPenilaianController::class, 'store'])->name('penilaian.store');
});

// Group routes that need admin role and authentication
Route::middleware(['auth', 'role:admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::resource('users', AdminUserController::class);
    Route::resource('roles', AdminRoleController::class);
    Route::resource('permissions', AdminPermissionController::class);
});

require __DIR__ . '/auth.php';
