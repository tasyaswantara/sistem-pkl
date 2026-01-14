<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Industri extends Model
{
    protected $table = 'industri';

    protected $fillable = [
        'user_id',
        'nama_industri',
        'kapasitas',
        'alamat',
        'reputasi',
        'jurusan'
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
