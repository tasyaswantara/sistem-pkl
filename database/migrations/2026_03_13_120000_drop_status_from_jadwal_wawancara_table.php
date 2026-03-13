<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('jadwal_wawancara', function (Blueprint $table) {
            $table->dropColumn('status');
        });
    }

    public function down(): void
    {
        Schema::table('jadwal_wawancara', function (Blueprint $table) {
            $table->enum('status', ['menunggu', 'dijadwalkan', 'selesai', 'dibatalkan'])
                ->default('menunggu')
                ->after('lokasi');
        });
    }
};
