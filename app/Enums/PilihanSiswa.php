<?php

namespace App\Enums;

enum PilihanSiswa: string
{
    case REKOMENDASI = 'rekomendasi';
    case USULAN_LAIN = 'usulan_lain';
    case LANGSUNG = 'langsung';
}
