<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UsulanIndustri extends Model
{
    use HasFactory;

    protected $table = 'usulan_industri';

    protected $fillable = [
        'siswa_id',
        'jurusan_id',
        'nama_industri',
        'email',
        'kapasitas',
        'alamat',
        'kontak',
        'keterangan',
        'status',
    ];

    public function siswa()
    {
        return $this->belongsTo(Siswa::class);
    }

    public function jurusan()
    {
        return $this->belongsTo(Jurusan::class);
    }
}
