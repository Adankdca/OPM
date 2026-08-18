<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

/**
 * Modulo NUEVO (beta): Encuestas de Seguimiento de Obra.
 * No existe en el sistema C# original -- se diseño desde cero.
 *
 * DIFERENCIA IMPORTANTE frente a todo lo que hemos hecho hasta ahora:
 * este es el primer endpoint que maneja ARCHIVOS (fotos/documentos).
 * Hasta ahora todo viajaba como JSON (contentType: 'application/json').
 * Los archivos NO pueden ir en JSON -- el navegador los manda como
 * "multipart/form-data" usando el objeto FormData de JavaScript.
 * Por eso Encuestas.js (el JS nuevo que acompaña esto) NO usa
 * JSON.stringify() como Obras.js, usa FormData en su lugar.
 */
class EncuestasController extends Controller
{
    /**
     * GET /api/encuestas/getEncuestas/{idobra}
     * Bitacora completa de una obra (mas reciente primero).
     */
    public function getEncuestas($idobra)
    {
        $lista = DB::table('TblD_Encuestas as E')
            ->leftJoin('TblC_EstatusEncuesta as S', 'E.IDEstatus', '=', 'S.IDEstatus')
            ->select(
                'E.IDEncuesta as idEncuesta',
                'E.FechaVisita as fechaVisita',
                'E.PorcentajeAvance as porcentajeAvance',
                'E.IDEstatus as idEstatus',
                'S.Nombre as estatus',
                'E.NombreEncuestador as nombreEncuestador',
                'E.Observaciones as observaciones'
            )
            ->where('E.IDobraproyecto', $idobra)
            ->orderBy('E.FechaVisita', 'desc')
            ->get();

        // Se le agrega a cada encuesta cuantas fotos/documentos tiene,
        // para mostrar el conteo en el listado sin pedir el detalle completo.
        foreach ($lista as $enc) {
            $enc->numFotos = DB::table('TblD_EncuestaFotos')->where('IDEncuesta', $enc->idEncuesta)->count();
            $enc->numDocumentos = DB::table('TblD_EncuestaDocumentos')->where('IDEncuesta', $enc->idEncuesta)->count();
        }

        return response()->json($lista);
    }

    /**
     * GET /api/encuestas/getEncuestaById/{id}
     * Detalle completo, incluidas las fotos y documentos ya subidos.
     */
    public function getEncuestaById($id)
    {
        $encuesta = DB::table('TblD_Encuestas')->where('IDEncuesta', $id)->first();
        if (!$encuesta) {
            return response()->json(null, 404);
        }

        $encuesta->fotos = DB::table('TblD_EncuestaFotos')
            ->select('IDFoto as idFoto', 'RutaArchivo as ruta', 'NombreOriginal as nombre')
            ->where('IDEncuesta', $id)->get();

        $encuesta->documentos = DB::table('TblD_EncuestaDocumentos')
            ->select('IDDocumento as idDocumento', 'RutaArchivo as ruta', 'NombreOriginal as nombre')
            ->where('IDEncuesta', $id)->get();

        return response()->json($encuesta);
    }

    /**
     * POST /api/encuestas/guardarEncuesta
     * Recibe FormData (no JSON), porque puede traer archivos.
     * request->input() sigue funcionando igual para los campos de texto;
     * request->file() es lo nuevo, para las fotos/documentos.
     */
    public function guardarEncuesta(Request $request)
    {
        $request->validate([
            'idobra' => 'required|integer',
            'fechaVisita' => 'required|date',
            'porcentajeAvance' => 'required|numeric|min:0|max:100',
            'fotos.*' => 'nullable|image|max:8192',       // 8 MB por foto
            'documentos.*' => 'nullable|file|max:15360',   // 15 MB por documento
        ]);

        $idEncuesta = (int) $request->input('idEncuesta', 0);

        $datos = [
            'IDobraproyecto' => $request->input('idobra'),
            'FechaVisita' => $request->input('fechaVisita'),
            'PorcentajeAvance' => $request->input('porcentajeAvance'),
            'IDEstatus' => $request->input('idEstatus'),
            'NombreEncuestador' => $request->input('nombreEncuestador'),
            'Observaciones' => $request->input('observaciones'),
        ];

        if ($idEncuesta === 0) {
            $datos['FechaCreacion'] = now();
            $idEncuesta = DB::table('TblD_Encuestas')->insertGetId($datos, 'IDEncuesta');
        } else {
            DB::table('TblD_Encuestas')->where('IDEncuesta', $idEncuesta)->update($datos);
        }

        // ── Guardar fotos (puede venir 0, 1 o varias) ──
        if ($request->hasFile('fotos')) {
            foreach ($request->file('fotos') as $foto) {
                // Storage::disk('public') guarda en storage/app/public/...
                // y con "php artisan storage:link" (ver instrucciones aparte)
                // queda accesible via URL en public/storage/...
                $ruta = $foto->store('encuestas/fotos', 'public');
                DB::table('TblD_EncuestaFotos')->insert([
                    'IDEncuesta' => $idEncuesta,
                    'RutaArchivo' => $ruta,
                    'NombreOriginal' => $foto->getClientOriginalName(),
                    'FechaSubida' => now(),
                ]);
            }
        }

        // ── Guardar documentos (puede venir 0, 1 o varios) ──
        if ($request->hasFile('documentos')) {
            foreach ($request->file('documentos') as $doc) {
                $ruta = $doc->store('encuestas/documentos', 'public');
                DB::table('TblD_EncuestaDocumentos')->insert([
                    'IDEncuesta' => $idEncuesta,
                    'RutaArchivo' => $ruta,
                    'NombreOriginal' => $doc->getClientOriginalName(),
                    'FechaSubida' => now(),
                ]);
            }
        }

        return response()->json(['success' => true, 'idEncuesta' => $idEncuesta]);
    }

    /**
     * DELETE /api/encuestas/eliminarFoto/{idFoto}
     */
    public function eliminarFoto($idFoto)
    {
        $foto = DB::table('TblD_EncuestaFotos')->where('IDFoto', $idFoto)->first();
        if ($foto) {
            Storage::disk('public')->delete($foto->RutaArchivo);
            DB::table('TblD_EncuestaFotos')->where('IDFoto', $idFoto)->delete();
        }
        return response()->json(['success' => true]);
    }

    /**
     * DELETE /api/encuestas/eliminarDocumento/{idDocumento}
     */
    public function eliminarDocumento($idDocumento)
    {
        $doc = DB::table('TblD_EncuestaDocumentos')->where('IDDocumento', $idDocumento)->first();
        if ($doc) {
            Storage::disk('public')->delete($doc->RutaArchivo);
            DB::table('TblD_EncuestaDocumentos')->where('IDDocumento', $idDocumento)->delete();
        }
        return response()->json(['success' => true]);
    }

    /**
     * DELETE /api/encuestas/eliminarEncuesta/{id}
     * Borra la encuesta y, en cascada, sus fotos/documentos (fisicos + BD).
     */
    public function eliminarEncuesta($id)
    {
        $fotos = DB::table('TblD_EncuestaFotos')->where('IDEncuesta', $id)->get();
        foreach ($fotos as $f) {
            Storage::disk('public')->delete($f->RutaArchivo);
        }
        $docs = DB::table('TblD_EncuestaDocumentos')->where('IDEncuesta', $id)->get();
        foreach ($docs as $d) {
            Storage::disk('public')->delete($d->RutaArchivo);
        }

        DB::table('TblD_EncuestaFotos')->where('IDEncuesta', $id)->delete();
        DB::table('TblD_EncuestaDocumentos')->where('IDEncuesta', $id)->delete();
        DB::table('TblD_Encuestas')->where('IDEncuesta', $id)->delete();

        return response()->json(['success' => true]);
    }

    /**
     * GET /api/encuestas/getEstatusEncuesta
     */
    public function getEstatusEncuesta()
    {
        $lista = DB::table('TblC_EstatusEncuesta')
            ->select('IDEstatus as id', 'Nombre as nombre')
            ->orderBy('IDEstatus')
            ->get();

        return response()->json($lista);
    }
}
