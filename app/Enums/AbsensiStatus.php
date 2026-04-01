<?php

namespace App\Enums;

enum AbsensiStatus: string
{
    case HADIR_VALID_LOKASI = 'hadir_valid_lokasi';
    case MENUNGGU_PERSETUJUAN_LUAR_LOKASI = 'menunggu_persetujuan_luar_lokasi';
    case HADIR_VALID_LUAR_LOKASI = 'hadir_valid_luar_lokasi';
    case ALPHA = 'alpha';

    /**
     * @return array<string>
     */
    public static function validStatuses(): array
    {
        return [
            self::HADIR_VALID_LOKASI->value,
            self::HADIR_VALID_LUAR_LOKASI->value,
        ];
    }
}
