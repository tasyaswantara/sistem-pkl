<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureIndustriApproved
{
    public function handle(Request $request, Closure $next): Response
    {
        $industri = $request->user()?->industri;

        if (!$industri || $industri->status_pengajuan !== 'disetujui') {
            return redirect()
                ->route('industri.pengajuan')
                ->with('warning', 'Pengajuan industri harus disetujui sebelum mengakses fitur lain.');
        }

        return $next($request);
    }
}
