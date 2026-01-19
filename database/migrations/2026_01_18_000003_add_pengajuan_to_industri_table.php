<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('industri', function (Blueprint $table) {
            $table->enum('status_pengajuan', ['menunggu', 'disetujui', 'ditolak'])->nullable()->after('grade');
            $table->timestamp('pengajuan_dikirim_at')->nullable()->after('status_pengajuan');
            $table->timestamp('pengajuan_dijawab_at')->nullable()->after('pengajuan_dikirim_at');
        });
    }

    public function down(): void
    {
        Schema::table('industri', function (Blueprint $table) {
            $table->dropColumn(['status_pengajuan', 'pengajuan_dikirim_at', 'pengajuan_dijawab_at']);
        });
    }
};
