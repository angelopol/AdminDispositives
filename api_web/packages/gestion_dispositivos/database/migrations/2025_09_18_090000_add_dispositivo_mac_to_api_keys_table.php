<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('api_keys', function (Blueprint $table) {
            if (!Schema::hasColumn('api_keys', 'dispositivo_mac')) {
                $table->string('dispositivo_mac')->nullable()->after('is_admin');
                $table->index('dispositivo_mac');
                // FK a dispositivos.mac (puede no estar disponible si app aún no migró esa tabla)
                try {
                    $table->foreign('dispositivo_mac')
                        ->references('mac')->on('dispositivos')
                        ->nullOnDelete();
                } catch (\Throwable $e) {
                    // En algunos motores/órdenes de migración puede fallar; se puede añadir manualmente luego.
                }
            }
        });
    }

    public function down(): void
    {
        Schema::table('api_keys', function (Blueprint $table) {
            if (Schema::hasColumn('api_keys', 'dispositivo_mac')) {
                // Eliminar FK si existe
                try { $table->dropForeign(['dispositivo_mac']); } catch (\Throwable $e) {}
                $table->dropIndex(['dispositivo_mac']);
                $table->dropColumn('dispositivo_mac');
            }
        });
    }
};
