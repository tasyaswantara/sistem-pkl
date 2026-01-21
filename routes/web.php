<?php

use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Admin\RoleController;
use App\Http\Controllers\Admin\PermissionController;
use App\Http\Controllers\Admin\PenempatanController;
use App\Http\Controllers\Admin\LogbookController;
use App\Http\Controllers\Admin\PerizinanController;
use App\Http\Controllers\Admin\PenilaianController;
use App\Http\Controllers\Admin\NotificationController;
use App\Http\Controllers\Guru\SiswaController as GuruSiswaController;
use App\Http\Controllers\Guru\LogbookController as GuruLogbookController;
use App\Http\Controllers\Guru\PerizinanController as GuruPerizinanController;
use App\Http\Controllers\Guru\PenilaianController as GuruPenilaianController;
use App\Http\Controllers\Siswa\PenempatanController as SiswaPenempatanController;
use App\Http\Controllers\Siswa\LogbookController as SiswaLogbookController;
use App\Http\Controllers\Siswa\PerizinanController as SiswaPerizinanController;
use App\Http\Controllers\Siswa\PenilaianController as SiswaPenilaianController;
use App\Http\Controllers\Industri\PengajuanController as IndustriPengajuanController;
use App\Http\Controllers\Industri\DataSiswaController as IndustriDataSiswaController;
use App\Http\Controllers\Industri\LogbookController as IndustriLogbookController;
use App\Http\Controllers\Industri\PerizinanController as IndustriPerizinanController;
use App\Http\Controllers\Industri\PenilaianController as IndustriPenilaianController;

Route::get('/', function () {
    if (!auth()->check()) {
        return redirect()->route('login');
    }

    $user = auth()->user();
    if ($user->hasRole('guru pembimbing')) {
        return redirect()->route('guru.siswa');
    }
    if ($user->hasRole('siswa')) {
        return redirect()->route('siswa.penempatan');
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
});

Route::group(['middleware' => ['role:admin']], function () {
    // Route::get('/admin', function () {
    //     return view('admin.dashboard');
    // })->name('admin.dashboard');  

    //ROUTE ADMIN DATA PENGGUNA
    Route::get('/data-pengguna', [UserController::class, 'index'])
        ->middleware(['auth', 'role:admin'])
        ->name('admin.data-pengguna');
    Route::post('/data-pengguna/industri/{industri}/pengajuan', [UserController::class, 'kirimPengajuanIndustri'])
        ->middleware(['auth', 'role:admin'])
        ->name('admin.industri.pengajuan');
    Route::get('/penempatan', [PenempatanController::class, 'index'])->name('admin.penempatan');
    Route::post('/penempatan/bobot', [PenempatanController::class, 'storeBobot'])->name('admin.penempatan.bobot');
    Route::post('/penempatan/run-saw', [PenempatanController::class, 'runSaw'])->name('admin.penempatan.run-saw');
    Route::post('/penempatan/usulan/{usulan}/approve', [PenempatanController::class, 'approveUsulanIndustri'])
        ->name('admin.penempatan.usulan.approve');
    Route::post('/penempatan/usulan/{usulan}/reject', [PenempatanController::class, 'rejectUsulanIndustri'])
        ->name('admin.penempatan.usulan.reject');
    Route::post('/penempatan/{penempatan}/guru', [PenempatanController::class, 'setGuruPembimbing'])
        ->name('admin.penempatan.guru');
    Route::get('/elogbook', [LogbookController::class, 'index'])->name('admin.elogbook');
    Route::get('/perizinan', [PerizinanController::class, 'index'])->name('admin.perizinan');
    Route::get('/penilaian', [PenilaianController::class, 'index'])->name('admin.penilaian');
    Route::post('/penilaian/rubrik', [PenilaianController::class, 'updateRubrik'])->name('admin.penilaian.rubrik');
    Route::post('/penilaian/aspek', [PenilaianController::class, 'storeAspek'])->name('admin.penilaian.aspek.store');
    Route::delete('/penilaian/aspek/{aspek}', [PenilaianController::class, 'destroyAspek'])->name('admin.penilaian.aspek.destroy');
    Route::get('/notifications', [NotificationController::class, 'index'])->name('admin.notifications');

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
    Route::get('/elogbook', [GuruLogbookController::class, 'index'])->name('elogbook');
    Route::post('/elogbook/{logbook}/komentar', [GuruLogbookController::class, 'storeKomentar'])->name('elogbook.komentar');
    Route::get('/perizinan', [GuruPerizinanController::class, 'index'])->name('perizinan');
    Route::get('/penilaian', [GuruPenilaianController::class, 'index'])->name('penilaian');
});

Route::middleware(['auth', 'role:siswa'])->prefix('siswa')->name('siswa.')->group(function () {
    Route::get('/penempatan', [SiswaPenempatanController::class, 'index'])->name('penempatan');
    Route::post('/penempatan/pilih', [SiswaPenempatanController::class, 'pilihRekomendasi'])->name('penempatan.pilih');
    Route::post('/penempatan/usulan', [SiswaPenempatanController::class, 'usulkanIndustri'])->name('penempatan.usulan');
    Route::get('/elogbook', [SiswaLogbookController::class, 'index'])->name('elogbook');
    Route::post('/elogbook', [SiswaLogbookController::class, 'store'])->name('elogbook.store');
    Route::put('/elogbook/{logbook}', [SiswaLogbookController::class, 'update'])->name('elogbook.update');
    Route::delete('/elogbook/{logbook}', [SiswaLogbookController::class, 'destroy'])->name('elogbook.destroy');
    Route::get('/perizinan', [SiswaPerizinanController::class, 'index'])->name('perizinan');
    Route::get('/penilaian', [SiswaPenilaianController::class, 'index'])->name('penilaian');
});

Route::middleware(['auth', 'role:perwakilan industri'])->prefix('industri')->name('industri.')->group(function () {
    Route::get('/pengajuan', [IndustriPengajuanController::class, 'index'])->name('pengajuan');
    Route::post('/pengajuan', [IndustriPengajuanController::class, 'konfirmasi'])->name('pengajuan.konfirmasi');
});

Route::middleware(['auth', 'role:perwakilan industri', 'industri.approved'])->prefix('industri')->name('industri.')->group(function () {
    Route::get('/siswa', [IndustriDataSiswaController::class, 'index'])->name('siswa');
    Route::post('/siswa/{penempatan}/status', [IndustriDataSiswaController::class, 'setStatus'])->name('siswa.status');
    Route::post('/siswa/{penempatan}/jadwal', [IndustriDataSiswaController::class, 'storeJadwal'])->name('siswa.jadwal');
    Route::get('/elogbook', [IndustriLogbookController::class, 'index'])->name('elogbook');
    Route::post('/elogbook/{logbook}', [IndustriLogbookController::class, 'update'])->name('elogbook.update');
    Route::get('/perizinan', [IndustriPerizinanController::class, 'index'])->name('perizinan');
    Route::post('/perizinan/{perizinan}', [IndustriPerizinanController::class, 'update'])->name('perizinan.update');
    Route::get('/penilaian', [IndustriPenilaianController::class, 'index'])->name('penilaian');
    Route::post('/penilaian/{penempatan}', [IndustriPenilaianController::class, 'store'])->name('penilaian.store');
});

// Group routes that need admin role and authentication
Route::middleware(['auth', 'role:admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::resource('users', UserController::class);
    Route::resource('roles', RoleController::class);
    Route::resource('permissions', PermissionController::class);
});

require __DIR__ . '/auth.php';
