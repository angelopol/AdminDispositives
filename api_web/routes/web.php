<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\GestionDispositivosDemoController;

// Página raíz muestra directamente la demo de gestión de dispositivos
Route::get('/', [GestionDispositivosDemoController::class, 'index'])
    ->name('gestion.dispositivos.demo');

// Ruta anterior (si alguien la tenía) redirige a la raíz
Route::redirect('/gestion-dispositivos-demo', '/');

// Ruta directa a la vista Blade estática demo de dispositivos (sin pasar por controlador)
Route::view('/gestion-dispositivos/demo', 'gestion_dispositivos.demo')
    ->name('gestion_dispositivos.demo.view');

// Ruta demo para gestión de API Keys (sólo en entorno debug idealmente)
Route::view('/gestion-dispositivos/keys-demo', 'gestion_dispositivos.keys_demo')
    ->name('gestion_dispositivos.keys_demo.view');
