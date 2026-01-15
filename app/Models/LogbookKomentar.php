<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LogbookKomentar extends Model
{
    use HasFactory;

    protected $table = 'logbook_komentar';

    protected $fillable = [
        'logbook_id',
        'guru_pembimbing_id',
        'komentar',
    ];

    public function logbook()
    {
        return $this->belongsTo(Logbook::class);
    }

    public function guruPembimbing()
    {
        return $this->belongsTo(GuruPembimbing::class, 'guru_pembimbing_id');
    }
}
