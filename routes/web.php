<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ObrasController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
*/

// Esta es la ruta que trae Laravel por default -- la que mostraba la
// pantalla de bienvenida que ya probamos. La dejamos aqui para no perderla.
Route::get('/', function () {
    return view('welcome');
});

// Vista principal del modulo de Obras
Route::get('/obras', function () {
    return view('obras.principal');
})->name('obras.principal');

// Endpoints tipo API que consume Obras.js via $.get()
// OJO: el prefijo usa "Obras" con mayuscula a proposito, porque tu
// Obras.js ya trae escrito  const API = 'api/Obras/';  -- asi no hay
// que tocar el JS que ya esta probado y funcionando.
Route::prefix('api/Obras')->group(function () {
    Route::get('/getObras', [ObrasController::class, 'getObras']);
    Route::get('/getRubros', [ObrasController::class, 'getRubros']);
    Route::get('/getAnios', [ObrasController::class, 'getAnios']);

    // Autocompletes de los filtros de busqueda
    Route::get('/getContratosAutocomplete', [ObrasController::class, 'getContratosAutocomplete']);
    Route::get('/getNumObrasAutocomplete', [ObrasController::class, 'getNumObrasAutocomplete']);
    Route::get('/getNombresAutocomplete', [ObrasController::class, 'getNombresAutocomplete']);

    // Modal "Nueva Obra"
    Route::get('/getObraById/{id}', [ObrasController::class, 'getObraById']);
    Route::get('/getSubrubros/{idRubro}', [ObrasController::class, 'getSubrubros']);
    Route::get('/getProgramas', [ObrasController::class, 'getProgramas']);
    Route::get('/getAreas', [ObrasController::class, 'getAreas']);
    Route::get('/getMarginacion', [ObrasController::class, 'getMarginacion']);
    Route::get('/getTipoObra', [ObrasController::class, 'getTipoObra']);
    Route::get('/getAreasByRubro/{idRubro}', [ObrasController::class, 'getAreasByRubro']);
    Route::post('/guardarObra', [ObrasController::class, 'guardarObra']);
});
