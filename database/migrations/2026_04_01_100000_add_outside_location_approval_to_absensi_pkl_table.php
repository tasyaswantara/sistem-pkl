<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('absensi_pkl', function (Blueprint $table) {
            $table->string('approval_status', 32)->nullable()->after('status');
            $table->foreignId('approved_by_industri_user_id')->nullable()->after('approval_status')
                ->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable()->after('approved_by_industri_user_id');
            $table->text('approval_note')->nullable()->after('approved_at');
            $table->index(['status', 'approval_status'], 'absensi_pkl_status_approval_index');
        });

        DB::table('absensi_pkl')
            ->where('status', 'hadir_valid')
            ->update([
                'status' => 'hadir_valid_lokasi',
                'approval_status' => null,
            ]);

        DB::table('absensi_pkl')
            ->where('status', 'di_luar_area')
            ->update([
                'status' => 'hadir_valid_luar_lokasi',
                'approval_status' => 'disetujui',
            ]);
    }

    public function down(): void
    {
        DB::table('absensi_pkl')
            ->where('status', 'hadir_valid_lokasi')
            ->update([
                'status' => 'hadir_valid',
            ]);

        DB::table('absensi_pkl')
            ->whereIn('status', [
                'menunggu_persetujuan_luar_lokasi',
                'hadir_valid_luar_lokasi',
                'alpha',
            ])
            ->update([
                'status' => 'di_luar_area',
            ]);

        Schema::table('absensi_pkl', function (Blueprint $table) {
            $table->dropForeign(['approved_by_industri_user_id']);
            $table->dropIndex('absensi_pkl_status_approval_index');
            $table->dropColumn([
                'approval_status',
                'approved_by_industri_user_id',
                'approved_at',
                'approval_note',
            ]);
        });
    }
};
