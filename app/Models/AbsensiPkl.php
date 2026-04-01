<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AbsensiPkl extends Model
{
    protected $table = 'absensi_pkl';

    protected $fillable = [
        'siswa_id',
        'industri_id',
        'tanggal',
        'check_in_at',
        'latitude',
        'longitude',
        'accuracy_m',
        'distance_to_industri_m',
        'is_within_geofence',
        'status',
        'approval_status',
        'approved_by_industri_user_id',
        'approved_at',
        'approval_note',
        'catatan',
    ];

    protected $casts = [
        'tanggal' => 'date',
        'check_in_at' => 'datetime',
        'latitude' => 'float',
        'longitude' => 'float',
        'accuracy_m' => 'float',
        'distance_to_industri_m' => 'float',
        'is_within_geofence' => 'boolean',
        'approved_at' => 'datetime',
    ];

    public function siswa()
    {
        return $this->belongsTo(Siswa::class);
    }

    public function industri()
    {
        return $this->belongsTo(Industri::class);
    }

    public function approvedByIndustriUser()
    {
        return $this->belongsTo(User::class, 'approved_by_industri_user_id');
    }
}
