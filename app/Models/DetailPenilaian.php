<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DetailPenilaian extends Model
{
    use HasFactory;

    protected $table = 'detail_penilaian';

    protected $fillable = [
        'penilaian_id',
        'aspek_penilaian_id',
        'nilai',
    ];

    public function penilaian()
    {
        return $this->belongsTo(Penilaian::class);
    }

    public function aspekPenilaian()
    {
        return $this->belongsTo(AspekPenilaian::class);
    }
}
