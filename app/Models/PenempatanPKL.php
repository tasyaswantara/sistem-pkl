<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PenempatanPKL extends Model
{
    use HasFactory;

    protected $table = 'penempatan_pkl';

    protected $fillable = [
        'siswa_id',
        'industri_id',
        'pilihan_siswa',
        'status',
        'tanggal_mulai',
        'tanggal_selesai',
        'guru_pembimbing_id',
        'keterangan',
    ];

    protected $casts = [
        'tanggal_mulai' => 'date',
        'tanggal_selesai' => 'date',
    ];

    public function siswa()
    {
        return $this->belongsTo(Siswa::class);
    }

    public function industri()
    {
        return $this->belongsTo(Industri::class);
    }

    public function guruPembimbing()
    {
        return $this->belongsTo(GuruPembimbing::class, 'guru_pembimbing_id');
    }
}
