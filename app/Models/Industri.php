<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Industri extends Model
{
    protected $table = 'industri';

    protected $fillable = [
        'user_id',
        'nama_industri',
        'kapasitas',
        'alamat',
        'jurusan_id',
        'grade',
        'status_pengajuan',
        'pengajuan_dikirim_at',
        'pengajuan_dijawab_at',
    ];

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
