<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Penilaian extends Model
{
    use HasFactory;

    protected $table = 'penilaian';

    protected $fillable = [
        'siswa_id',
        'industri_id',
        'tanggal_penilaian',
        'total_nilai',
        'catatan',
    ];

    protected $casts = [
        'tanggal_penilaian' => 'date',
    ];

    public function siswa()
    {
        return $this->belongsTo(Siswa::class);
    }

    public function industri()
    {
        return $this->belongsTo(Industri::class);
    }

    public function detailPenilaian()
    {
        return $this->hasMany(DetailPenilaian::class);
    }
}
