<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GuruPembimbing extends Model
{
    protected $table = 'guru_pembimbing';

    protected $fillable = [
        'user_id',
        'nip',
        'jurusan_id',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
    public function jurusan()
    {
        return $this->belongsTo(Jurusan::class);
    }
}
