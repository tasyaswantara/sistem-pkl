<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Jurusan extends Model
{
    protected $table = 'jurusan';

    protected $fillable = ['nama'];

    public function siswa()
    {
        return $this->hasMany(Siswa::class);
    }

    public function bobotKriteria()
    {
        return $this->hasMany(BobotKriteria::class);
    }

    public function sawRuns()
    {
        return $this->hasMany(SawRun::class);
    }
}
