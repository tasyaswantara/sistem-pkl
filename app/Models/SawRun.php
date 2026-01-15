<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SawRun extends Model
{
    use HasFactory;

    protected $table = 'saw_runs';

    protected $fillable = [
        'jurusan_id',
        'tahun_ajaran',
        'run_at',
        'created_by',
    ];

    protected $casts = [
        'run_at' => 'datetime',
    ];

    public function jurusan()
    {
        return $this->belongsTo(Jurusan::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function hasilRekomendasi()
    {
        return $this->hasMany(HasilRekomendasi::class);
    }
}
