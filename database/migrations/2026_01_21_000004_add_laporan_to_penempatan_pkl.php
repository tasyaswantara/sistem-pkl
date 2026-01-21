<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('penempatan_pkl', function (Blueprint $table) {
            $table->text('laporan_industri')->nullable()->after('keterangan');
            $table->string('laporan_status', 20)->default('menunggu')->after('laporan_industri');
            $table->timestamp('laporan_at')->nullable()->after('laporan_status');
        });
    }

    public function down(): void
    {
        Schema::table('penempatan_pkl', function (Blueprint $table) {
            $table->dropColumn(['laporan_industri', 'laporan_status', 'laporan_at']);
        });
    }
};
