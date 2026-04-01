<?php

return [
    'errors' => [
        'akun' => 'Akun siswa belum terhubung.',
        'terima' => 'Presensi hanya bisa dilakukan setelah penempatan diterima industri.',
        'duplikat' => 'Anda sudah melakukan check-in hari ini.',
        'geofence' => 'Geofence industri belum diatur admin. Silakan hubungi admin.',
        'alamat_kosong' => 'Alamat industri kosong, geocoding tidak bisa dijalankan.',
        'geocode_gagal' => 'Geocoding gagal atau alamat tidak ditemukan oleh Nominatim.',
        'alasan_luar_area' => 'Jika check-in di luar area industri, alasan wajib diisi.',
        'lokasi' => 'Lokasi tidak valid. Silakan ambil lokasi ulang.',
    ],
    'success' => [
        'checkin' => 'Check-in berhasil direkam.',
        'checkin_pending' => 'Check-in luar lokasi berhasil direkam dan menunggu persetujuan industri.',
        'geofence' => 'Pengaturan geofence industri berhasil diperbarui.',
        'geocode_ok' => 'Geocoding berhasil untuk :industri (:lat, :lng).',
        'global_radius' => 'Radius geofence global berhasil diterapkan untuk semua industri.',
    ],
    'status' => [
        'all' => 'Semua Status',
        'hadir_valid_lokasi' => 'Hadir Valid di Lokasi',
        'menunggu_persetujuan_luar_lokasi' => 'Menunggu Persetujuan Luar Lokasi',
        'hadir_valid_luar_lokasi' => 'Hadir Valid di Luar Lokasi',
        'alpha' => 'Alpha',
    ],
];
