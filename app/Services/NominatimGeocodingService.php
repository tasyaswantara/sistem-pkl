<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class NominatimGeocodingService
{
    /**
     * Geocode alamat ke koordinat menggunakan endpoint publik Nominatim.
     * Return null jika request gagal / alamat tidak ditemukan, agar caller
     * bisa menampilkan pesan validasi yang konsisten.
     *
     * @return array{lat:float,lng:float,display_name:string}|null
     */
    public function geocode(string $address): ?array
    {
        $address = trim($address);
        if ($address === '') {
            return null;
        }

        $baseUrl = rtrim((string) config('services.nominatim.base_url'), '/');
        $userAgent = (string) config('services.nominatim.user_agent');
        $email = (string) config('services.nominatim.email');

        $query = [
            'q' => $address,
            'format' => 'jsonv2',
            // Ambil kandidat pertama agar deterministik dan cepat untuk use case admin.
            'limit' => 1,
            'addressdetails' => 1,
        ];

        if ($email !== '') {
            $query['email'] = $email;
        }

        try {
            $response = Http::acceptJson()
                ->withHeaders([
                    'User-Agent' => $userAgent,
                ])
                ->timeout(12)
                ->get($baseUrl . '/search', $query);
        } catch (\Throwable $e) {
            // Network error / timeout tetap diperlakukan sebagai "tidak ditemukan"
            // agar alur UI tetap sederhana.
            report($e);

            return null;
        }

        if (!$response->ok()) {
            return null;
        }

        $first = $response->json('0');
        if (!is_array($first) || !isset($first['lat'], $first['lon'])) {
            return null;
        }

        return [
            'lat' => round((float) $first['lat'], 7),
            'lng' => round((float) $first['lon'], 7),
            'display_name' => (string) ($first['display_name'] ?? ''),
        ];
    }
}
