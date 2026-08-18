<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\LoginController;
use App\Http\Controllers\ObrasController;
use App\Http\Controllers\EncuestasController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
*/

// Esta es la ruta que trae Laravel por default -- la que mostraba la
// pantalla de bienvenida que ya probamos. La dejamos aqui para no perderla.
Route::redirect('/', '/login');
Route::get('/login', [LoginController::class, 'create'])->name('login');
Route::post('/login', [LoginController::class, 'store'])->name('login.store');
Route::post('/logout', [LoginController::class, 'destroy'])->name('logout');

// Vista principal del modulo de Obras
Route::get('/obras', function () {
    return view('obras.principal');
})->middleware('login.required')->name('obras.principal');

// Endpoints tipo API que consume Obras.js via $.get()
// OJO: el prefijo usa "Obras" con mayuscula a proposito, porque tu
// Obras.js ya trae escrito  const API = 'api/Obras/';  -- asi no hay
// que tocar el JS que ya esta probado y funcionando.
Route::prefix('api/Obras')->middleware('login.required')->group(function () {
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

    // Submodal "Acciones de la Obra"
    Route::get('/getNombreObra/{idobra}', [ObrasController::class, 'getNombreObra']);
    Route::get('/getAcciones', [ObrasController::class, 'getAcciones']);
    Route::get('/getAccionById/{idAccion}', [ObrasController::class, 'getAccionById']);
    Route::post('/guardarAccion', [ObrasController::class, 'guardarAccion']);
    Route::get('/getAniosAccion/{idobra}', [ObrasController::class, 'getAniosAccion']);
    Route::get('/getTipoEjecucion', [ObrasController::class, 'getTipoEjecucion']);
    Route::get('/getTipoAccion', [ObrasController::class, 'getTipoAccion']);
    Route::get('/getLocalidades', [ObrasController::class, 'getLocalidades']);
    Route::get('/getSubrubroEspecifico/{idobra}', [ObrasController::class, 'getSubrubroEspecifico']);

    // Origen de Inversión (submodal de Acciones)
    Route::get('/getOrigenes/{idAccion}', [ObrasController::class, 'getOrigenes']);
    Route::get('/getOrigenById/{idFuente}', [ObrasController::class, 'getOrigenById']);
    Route::post('/guardarOrigen', [ObrasController::class, 'guardarOrigen']);
    Route::delete('/eliminarOrigen/{idFuente}', [ObrasController::class, 'eliminarOrigen']);
    Route::get('/getOrigenFuente', [ObrasController::class, 'getOrigenFuente']);
    Route::get('/getFuenteFinanciamiento', [ObrasController::class, 'getFuenteFinanciamiento']);

    // COCI (submodal de Origenes)
    Route::get('/getCOCI', [ObrasController::class, 'getCOCI']);
    Route::get('/getCOCIById/{idCoci}', [ObrasController::class, 'getCOCIById']);
    Route::post('/guardarCOCI', [ObrasController::class, 'guardarCOCI']);
    Route::delete('/eliminarCOCI/{idCoci}', [ObrasController::class, 'eliminarCOCI']);
});

// ══════════════════════════════════════════════════════════════
// Modulo NUEVO (beta): Encuestas de Seguimiento de Obra
// ══════════════════════════════════════════════════════════════
Route::prefix('api/Encuestas')->middleware('login.required')->group(function () {
    Route::get('/getEncuestas/{idobra}', [EncuestasController::class, 'getEncuestas']);
    Route::get('/getEncuestaById/{id}', [EncuestasController::class, 'getEncuestaById']);
    Route::post('/guardarEncuesta', [EncuestasController::class, 'guardarEncuesta']);
    Route::delete('/eliminarEncuesta/{id}', [EncuestasController::class, 'eliminarEncuesta']);
    Route::delete('/eliminarFoto/{idFoto}', [EncuestasController::class, 'eliminarFoto']);
    Route::delete('/eliminarDocumento/{idDocumento}', [EncuestasController::class, 'eliminarDocumento']);
    Route::get('/getEstatusEncuesta', [EncuestasController::class, 'getEstatusEncuesta']);
});
