<?php

namespace App\Enums;

enum LogbookStatus: string
{
    case PENDING = 'pending';
    case DISETUJUI = 'disetujui';
    case DITOLAK = 'ditolak';
}
