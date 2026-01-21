<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Notification;
use App\Models\User;
use App\Notifications\PenempatanStatusChanged;

class PenempatanPKL extends Model
{
    use HasFactory;

    protected $table = 'penempatan_pkl';

    protected $fillable = [
        'siswa_id',
        'industri_id',
        'usulan_industri_id',
        'pilihan_siswa',
        'status',
        'jenis_penempatan',
        'alasan_penempatan_langsung',
        'ditetapkan_oleh',
        'ditetapkan_at',
        'tanggal_mulai',
        'tanggal_selesai',
        'guru_pembimbing_id',
        'keterangan',
    ];

    protected $casts = [
        'tanggal_mulai' => 'date',
        'tanggal_selesai' => 'date',
    ];

    public function siswa()
    {
        return $this->belongsTo(Siswa::class);
    }

    public function industri()
    {
        return $this->belongsTo(Industri::class);
    }

    public function usulanIndustri()
    {
        return $this->belongsTo(UsulanIndustri::class);
    }

    public function guruPembimbing()
    {
        return $this->belongsTo(GuruPembimbing::class, 'guru_pembimbing_id');
    }

    protected static function booted()
    {
        static::updating(function (PenempatanPKL $penempatan) {
            if (!$penempatan->isDirty('status')) {
                return;
            }

            $oldStatus = (string) $penempatan->getOriginal('status');
            $newStatus = (string) $penempatan->status;

            $admins = User::role('admin')->get();
            if ($admins->isNotEmpty()) {
                Notification::send($admins, new PenempatanStatusChanged($penempatan, $oldStatus, $newStatus));
            }
        });

        static::updated(function (PenempatanPKL $penempatan) {
            if ($penempatan->wasChanged('status') && $penempatan->status === 'diterima_industri') {
                $penempatan->siswa?->update(['status_pkl' => 'berjalan']);
            }
        });
    }
}
