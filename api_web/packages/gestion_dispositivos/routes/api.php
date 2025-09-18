<?php

use Illuminate\Support\Facades\Route;
use Ezparking\GestionDispositivos\Http\Controllers\DispositivoController;
use Ezparking\GestionDispositivos\Http\Controllers\Security\ApiKeyController;

// Consulta de relaciones por token de dispositivo (no requiere api_key header)
Route::post('/dispositivos/relaciones/consulta', [DispositivoController::class, 'relacionesPorToken']);

// Rutas de gestión de API keys protegidas por llave admin
Route::middleware(['api_key.auth:admin'])->prefix('keys')->group(function(){
    Route::get('/', [ApiKeyController::class, 'index']);
    Route::post('/', [ApiKeyController::class, 'store']);
    Route::put('/{id}', [ApiKeyController::class, 'update']);
    Route::delete('/{id}', [ApiKeyController::class, 'destroy']);
});

// Rutas de dispositivos protegidas por api_key.auth
Route::middleware(['api_key.auth'])->prefix('dispositivos')->group(function () {
    Route::get('/', [DispositivoController::class, 'index']); // Listar dispositivos
    Route::post('/', [DispositivoController::class, 'registrar']); // Registrar dispositivo
    Route::get('/{mac}', [DispositivoController::class, 'show']); // Obtener un dispositivo por MAC
    Route::get('/{mac}/relaciones', [DispositivoController::class, 'relaciones']); // Dispositivo + enlazado_por
    Route::put('/{mac}', [DispositivoController::class, 'actualizar']); // Actualizar ip/enlace
    Route::delete('/{mac}', [DispositivoController::class, 'eliminar']); // Eliminar dispositivo
    Route::post('/{mac}/enlace', [DispositivoController::class, 'establecerEnlace']); // Establecer enlace
    Route::delete('/{mac}/enlace', [DispositivoController::class, 'eliminarEnlace']); // Eliminar enlace
});
