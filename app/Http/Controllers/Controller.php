<?php

namespace App\Http\Controllers;

use App\Models\PenempatanPKL;
use App\Services\PenempatanStatusService;

abstract class Controller
{
    protected function handlePenempatanStatusChange(PenempatanPKL $penempatan, ?string $oldStatus): void
    {
        app(PenempatanStatusService::class)->handleStatusChange($penempatan, $oldStatus);
    }
}
