<?php

namespace App\Enums;

enum PenempatanStatus: string
{
    case BELUM_MEMILIH = 'belum_memilih';
    case MENUNGGU_KONFIRMASI = 'menunggu_konfirmasi';
    case DITOLAK_SEKOLAH = 'ditolak_sekolah';
    case PROSES_PENGAJUAN = 'proses_pengajuan';
    case PENGAJUAN_DITOLAK_INDUSTRI = 'pengajuan_ditolak_industri';
    case PROSES_WAWANCARA = 'proses_wawancara';
    case DITERIMA_INDUSTRI = 'diterima_industri';
    case TIDAK_LOLOS_INDUSTRI = 'tidak_lolos_industri';
}
