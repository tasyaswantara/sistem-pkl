<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BobotKriteria extends Model
{
    use HasFactory;

    protected $table = 'bobot_kriteria';

    protected $fillable = [
        'jurusan_id',
        'kriteria_id',
        'bobot',
    ];

    public function jurusan()
    {
        return $this->belongsTo(Jurusan::class);
    }

    public function kriteria()
    {
        return $this->belongsTo(Kriteria::class);
    }
}
