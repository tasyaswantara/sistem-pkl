<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('penempatan_pkl', function (Blueprint $table) {
            $table->string('jenis_penempatan', 20)->default('normal')->after('status');
            $table->text('alasan_penempatan_langsung')->nullable()->after('jenis_penempatan');
            $table->unsignedBigInteger('ditetapkan_oleh')->nullable()->after('alasan_penempatan_langsung');
            $table->timestamp('ditetapkan_at')->nullable()->after('ditetapkan_oleh');

            $table->foreign('ditetapkan_oleh')
                ->references('id')
                ->on('users')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('penempatan_pkl', function (Blueprint $table) {
            $table->dropForeign(['ditetapkan_oleh']);
            $table->dropColumn([
                'jenis_penempatan',
                'alasan_penempatan_langsung',
                'ditetapkan_oleh',
                'ditetapkan_at',
            ]);
        });
    }
};
