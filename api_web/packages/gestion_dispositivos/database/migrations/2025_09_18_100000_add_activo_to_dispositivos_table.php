<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('dispositivos', function (Blueprint $table) {
            if (!Schema::hasColumn('dispositivos', 'activo')) {
                $table->boolean('activo')->default(true)->after('enlace_mac');
                $table->index('activo');
            }
        });
    }

    public function down(): void
    {
        Schema::table('dispositivos', function (Blueprint $table) {
            if (Schema::hasColumn('dispositivos', 'activo')) {
                $table->dropIndex(['activo']);
                $table->dropColumn('activo');
            }
        });
    }
};
