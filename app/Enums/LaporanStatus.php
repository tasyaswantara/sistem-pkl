<?php

namespace App\Enums;

enum LaporanStatus: string
{
    case MENUNGGU = 'menunggu';
    case DITINDAK = 'ditindak';
    case SELESAI = 'selesai';
}
