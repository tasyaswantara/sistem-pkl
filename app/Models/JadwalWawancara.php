<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class JadwalWawancara extends Model
{
    use HasFactory;

    protected $table = 'jadwal_wawancara';

    protected $fillable = [
        'siswa_id',
        'industri_id',
        'tanggal',
        'waktu',
        'lokasi',
        'status',
        'catatan',
    ];

    protected $casts = [
        'tanggal' => 'date',
        'waktu' => 'datetime:H:i',
    ];

    public function siswa()
    {
        return $this->belongsTo(Siswa::class);
    }

    public function industri()
    {
        return $this->belongsTo(Industri::class);
    }
}
