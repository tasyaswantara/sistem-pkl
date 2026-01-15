<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class HasilRekomendasi extends Model
{
    use HasFactory;

    protected $table = 'hasil_rekomendasi';

    protected $fillable = [
        'saw_run_id',
        'siswa_id',
        'industri_id',
        'nilai_preferensi',
        'peringkat',
    ];

    public function sawRun()
    {
        return $this->belongsTo(SawRun::class);
    }

    public function siswa()
    {
        return $this->belongsTo(Siswa::class);
    }

    public function industri()
    {
        return $this->belongsTo(Industri::class);
    }
}
