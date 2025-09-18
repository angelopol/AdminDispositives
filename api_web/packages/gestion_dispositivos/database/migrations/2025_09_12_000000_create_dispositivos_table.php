<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('dispositivos', function (Blueprint $table) {
            // La MAC pasa a ser la clave primaria
            $table->string('mac')->primary();
            $table->string('nombre');
            $table->string('ip')->nullable();
            // Nueva columna para enlace por MAC (auto-referencia)
            $table->string('enlace_mac')->nullable();
            $table->timestamps();

            $table->foreign('enlace_mac')
                ->references('mac')->on('dispositivos')
                ->nullOnDelete(); // Si se elimina el destino, se limpia el enlace
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('dispositivos');
    }
};