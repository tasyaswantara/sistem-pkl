<?php

namespace App\Enums;

enum JadwalWawancaraStatus: string
{
    case MENUNGGU = 'menunggu';
    case DIJADWALKAN = 'dijadwalkan';
    case SELESAI = 'selesai';
    case DIBATALKAN = 'dibatalkan';
}
