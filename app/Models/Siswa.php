<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\User;

class Siswa extends Model
{
    use HasFactory;

    protected $table = 'siswa';

    protected $fillable = [
        'user_id',
        'nis',
        'jurusan_id',
        'kelas',
        'nilai_akademik',
        'perangkat',
        'status_pkl',
        'tahun_ajaran',
    ];

    /**
     * Relasi ke User
     * 1 siswa dimiliki oleh 1 user
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }
    public function jurusan()
    {
        return $this->belongsTo(Jurusan::class);
    }

    public function penempatanPkl()
    {
        return $this->hasMany(PenempatanPKL::class);
    }

    public function hasilRekomendasi()
    {
        return $this->hasMany(HasilRekomendasi::class);
    }

    public function penilaian()
    {
        return $this->hasMany(Penilaian::class);
    }

    public function logbook()
    {
        return $this->hasMany(Logbook::class);
    }

    public function perizinan()
    {
        return $this->hasMany(Perizinan::class);
    }

    public function jadwalWawancara()
    {
        return $this->hasMany(JadwalWawancara::class);
    }
}
