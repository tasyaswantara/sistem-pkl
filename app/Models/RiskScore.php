<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RiskScore extends Model
{
    protected $table = 'risk_scores';

    protected $fillable = [
        'siswa_id',
        'score',
        'category',
        'week_start',
        'week_end',
    ];

    protected $casts = [
        'score' => 'decimal:4',
        'week_start' => 'date',
        'week_end' => 'date',
    ];

    public function siswa()
    {
        return $this->belongsTo(Siswa::class);
    }
}
