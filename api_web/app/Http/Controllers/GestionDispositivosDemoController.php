<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class GestionDispositivosDemoController extends Controller
{
    public function index()
    {
        return view('gestion_dispositivos.demo');
    }
}
