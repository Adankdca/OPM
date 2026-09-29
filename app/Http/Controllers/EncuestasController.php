<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

/**
 * Encuestas de Seguimiento -- ahora a nivel ACCIÓN (antes vivían a nivel
 * Obraproyecto). El tipo de cuestionario (Obra / Programa) NO se guarda en
 * la encuesta: se lee de TblD_Acciones.IDTipoOrigen, la acción dueña de la
 * encuesta -- así nunca se puede desincronizar la ficha con su acción.
 *
 * Rutas esperadas (ver bloque "web.php -- rutas a actualizar" que te
 * mandé aparte): todo bajo prefix('api/Encuestas').
 */
class EncuestasController extends Controller
{
    // ══════════════════════════════════════════════════════════════
    // Catálogos
    // ══════════════════════════════════════════════════════════════

    /** GET /api/Encuestas/getTipoOrigen -- para el <select> en el form de Acción */
    public function getTipoOrigen()
    {
        $lista = DB::table('TBLC_TipoOrigen')
            ->select('IDTipoOrigen as id', 'Nombre as nombre')
            ->orderBy('IDTipoOrigen')
            ->get();

        return response()->json($lista);
    }

    /** GET /api/Encuestas/getEstatusEncuesta */
    public function getEstatusEncuesta()
    {
        $lista = DB::table('tblc_estatusencuesta')
            ->select('IDEstatus as id', 'Nombre as nombre')
            ->get();

        return response()->json($lista);
    }

    /** GET /api/Encuestas/getSituacionEncontrada */
    public function getSituacionEncontrada()
    {
        $lista = DB::table('TBLC_SituacionEncontrada')
            ->select('IDSituacion as id', 'Nombre as nombre')
            ->get();

        return response()->json($lista);
    }

    /**
     * GET /api/Encuestas/getPreguntas/{idTipoOrigen}
     * Trae el cuestionario correspondiente (1 = Obra, 2 = Programa) en el
     * orden definido en catálogo. Como viene de BD, agregar/quitar/reordenar
     * preguntas no requiere tocar ni una línea de código.
     */
    public function getPreguntas($idTipoOrigen)
    {
        $lista = DB::table('TBLC_EncuestaPreguntas')
            ->select(
                'IDPregunta as id',
                'Texto as texto',
                'TipoRespuesta as tipo',
                'RequiereJustificacion as requiereJustificacion'
            )
            ->where('IDTipoOrigen', $idTipoOrigen)
            ->where('Activa', 1)
            ->orderBy('Orden')
            ->get();

        return response()->json($lista);
    }

    // ══════════════════════════════════════════════════════════════
    // Bitácora de encuestas de una Acción
    // ══════════════════════════════════════════════════════════════

    /**
     * GET /api/Encuestas/getEncuestas/{idAccion}
     * OJO: antes recibía idobra; ahora la encuesta vive a nivel Acción.
     */
    public function getEncuestas($idAccion)
    {
        $lista = DB::table('tbld_encuestas as E')
            ->leftJoin('tblc_estatusencuesta as ES', 'E.IDEstatus', '=', 'ES.IDEstatus')
            ->leftJoin('TblD_Acciones as A', 'E.IDAcciones', '=', 'A.IDAcciones')
            ->select(
                'E.IDEncuesta as idEncuesta',
                'E.Folio as folio',
                DB::raw("DATE_FORMAT(E.FechaVisita, '%d/%m/%Y') as fechaVisita"),
                'E.PorcentajeAvance as avance',
                'ES.Nombre as estatus',
                'A.IDTipoOrigen as idTipoOrigen', // 1=Obra, 2=Programa -- el JS pinta el texto/color
                'E.NombreEncuestador as encuestador',
                DB::raw('(SELECT COUNT(*) FROM tbld_encuestafotos F WHERE F.IDEncuesta = E.IDEncuesta) as numFotos'),
                DB::raw('(SELECT COUNT(*) FROM tbld_encuestadocumentos D WHERE D.IDEncuesta = E.IDEncuesta) as numDocumentos')
            )
            ->where('E.IDAcciones', $idAccion)
            ->orderBy('E.FechaVisita', 'desc')
            ->get();

        return response()->json($lista);
    }

    /**
     * GET /api/Encuestas/getEncuestaById/{id}
     * Regresa encabezado + respuestas + fotos/documentos ya subidos, listo
     * para precargar el formulario en modo edición.
     */
    public function getEncuestaById($id)
    {
        $encuesta = DB::table('tbld_encuestas')->where('IDEncuesta', $id)->first();

        if (!$encuesta) {
            return response()->json(null, 404);
        }

        $respuestas = DB::table('TblD_EncuestaRespuestas')
            ->where('IDEncuesta', $id)
            ->get(['IDPregunta', 'Respuesta', 'Justificacion']);

        $accion = DB::table('TblD_Acciones')->where('IDAcciones', $encuesta->IDAcciones)->first();

        $fotos = DB::table('tbld_encuestafotos')
            ->where('IDEncuesta', $id)
            ->select('IDFoto as id', 'RutaArchivo as ruta', 'NombreOriginal as nombre')
            ->get();

        $documentos = DB::table('tbld_encuestadocumentos')
            ->where('IDEncuesta', $id)
            ->select('IDDocumento as id', 'RutaArchivo as ruta', 'NombreOriginal as nombre')
            ->get();

        return response()->json([
            'encuesta' => $encuesta,
            'idTipoOrigen' => $accion->IDTipoOrigen ?? 1,
            'respuestas' => $respuestas,
            'fotos' => $fotos,
            'documentos' => $documentos,
        ]);
    }

    /**
     * POST /api/Encuestas/guardarEncuesta
     * multipart/form-data (igual que ya lo tenías, para poder recibir
     * fotos[]/documentos[] en el mismo request).
     * Guarda encabezado + respuestas del cuestionario en una sola
     * transacción, y sube evidencia nueva sin tocar la que ya existía.
     */
    public function guardarEncuesta(Request $request)
    {
        $idAccion = (int) $request->input('idAccion');

        $campos = [
            'IDAcciones' => $idAccion,
            'Folio' => $request->input('folio'),
            'FechaVisita' => $request->input('fechaVisita'),
            'PorcentajeAvance' => $request->input('avance', 0),
            'IDEstatus' => $request->input('idEstatus') ?: null,
            'IDSituacionEncontrada' => $request->input('idSituacionEncontrada') ?: null,
            'DetalleSituacionEncontrada' => $request->input('detalleSituacionEncontrada'),
            'Recomendacion' => $request->input('recomendacion'),
            'Direccion' => $request->input('direccion'),
            'OrganismoPublico' => $request->input('organismoPublico'),
            'NombreEncuestador' => $request->input('nombreEncuestador'),
            'Observaciones' => $request->input('observaciones'),
            'InformanteNombre' => $request->input('informanteNombre'),
            'InformanteDomicilio' => $request->input('informanteDomicilio'),
            'InformanteSexo' => $request->input('informanteSexo') ?: null,
            'InformanteEdad' => $request->input('informanteEdad') ?: null,
            'InversionProgramada' => $request->input('inversionProgramada', 0),
            'Beneficiarios' => $request->input('beneficiarios', 0),
            'TipoBeneficiario' => $request->input('tipoBeneficiario'),
        ];

        // El JS manda las respuestas del cuestionario como JSON dentro de
        // un campo de texto normal (necesario porque el request es
        // multipart, no application/json, por los archivos adjuntos):
        // respuestasJson = '[{"idPregunta":1,"respuesta":"SI","justificacion":"..."}]'
        $respuestas = json_decode($request->input('respuestasJson', '[]'), true) ?: [];

        $idEncuesta = DB::transaction(function () use ($request, $campos, $respuestas) {
            if ($request->input('accion') === 'add') {
                $id = DB::table('tbld_encuestas')->insertGetId(array_merge($campos, [
                    'FechaCreacion' => now(),
                ]));
            } else {
                $id = (int) $request->input('idEncuesta');
                DB::table('tbld_encuestas')->where('IDEncuesta', $id)->update($campos);

                // Se reemplazan las respuestas anteriores -- más simple y
                // seguro que hacer upsert pregunta por pregunta.
                DB::table('TblD_EncuestaRespuestas')->where('IDEncuesta', $id)->delete();
            }

            foreach ($respuestas as $r) {
                DB::table('TblD_EncuestaRespuestas')->insert([
                    'IDEncuesta' => $id,
                    'IDPregunta' => $r['idPregunta'],
                    'Respuesta' => $r['respuesta'] ?? null,
                    'Justificacion' => $r['justificacion'] ?? null,
                ]);
            }

            return $id;
        });

        // ── Evidencia nueva (se ANEXA, nunca se borra la que ya existía) ──
        if ($request->hasFile('fotos')) {
            foreach ($request->file('fotos') as $foto) {
                $ruta = $foto->store('encuestas/fotos', 'public');
                DB::table('tbld_encuestafotos')->insert([
                    'IDEncuesta' => $idEncuesta,
                    'RutaArchivo' => $ruta,
                    'NombreOriginal' => $foto->getClientOriginalName(),
                    'FechaSubida' => now(),
                ]);
            }
        }

        if ($request->hasFile('documentos')) {
            foreach ($request->file('documentos') as $doc) {
                $ruta = $doc->store('encuestas/documentos', 'public');
                DB::table('tbld_encuestadocumentos')->insert([
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
     * DELETE /api/Encuestas/eliminarEncuesta/{id}
     * Las respuestas se borran en cascada (FK ON DELETE CASCADE). Las
     * fotos/documentos NO se tocan aquí a propósito -- si de verdad quieres
     * borrar también la evidencia, hazlo aparte con eliminarFoto/
     * eliminarDocumento, para que nunca se pierda "sin querer" al borrar
     * una ficha.
     */
    public function eliminarEncuesta($id)
    {
        DB::table('tbld_encuestas')->where('IDEncuesta', $id)->delete();

        return response()->json(['success' => true]);
    }

    /** DELETE /api/Encuestas/eliminarFoto/{idFoto} -- sin cambios de lógica */
    public function eliminarFoto($idFoto)
    {
        $foto = DB::table('tbld_encuestafotos')->where('IDFoto', $idFoto)->first();
        if ($foto) {
            Storage::disk('public')->delete($foto->RutaArchivo);
            DB::table('tbld_encuestafotos')->where('IDFoto', $idFoto)->delete();
        }

        return response()->json(['success' => true]);
    }

    /** DELETE /api/Encuestas/eliminarDocumento/{idDocumento} -- sin cambios de lógica */
    public function eliminarDocumento($idDocumento)
    {
        $doc = DB::table('tbld_encuestadocumentos')->where('IDDocumento', $idDocumento)->first();
        if ($doc) {
            Storage::disk('public')->delete($doc->RutaArchivo);
            DB::table('tbld_encuestadocumentos')->where('IDDocumento', $idDocumento)->delete();
        }

        return response()->json(['success' => true]);
    }

    // ══════════════════════════════════════════════════════════════
    // Reporte / Ficha imprimible (HTML para pantalla + descarga PDF)
    // ══════════════════════════════════════════════════════════════

    /**
     * Arma TODOS los datos que hacían falta para poder imprimir la ficha
     * igual que tus 2 PDF de ejemplo (Obra o Programa, según
     * TblD_Acciones.IDTipoOrigen de la acción dueña de la encuesta).
     */
    private function datosFicha($idEncuesta)
    {
        $encuesta = DB::table('tbld_encuestas')->where('IDEncuesta', $idEncuesta)->first();
        abort_if(!$encuesta, 404, 'Encuesta no encontrada.');

        $accion = DB::table('TblD_Acciones as A')
                ->join('TBLP_Obraproyecto as O', 'A.IDobraproyecto', '=', 'O.IDobraproyecto')
                ->leftJoin('TBLC_Subrubro as S', 'O.IDPrograma', '=', 'S.IDPrograma')
                ->leftJoin('TBLC_Area as AR', 'O.IDArea', '=', 'AR.IDArea')
                ->leftJoin('TBLD_Contrato as C', 'A.IDContrato', '=', 'C.IDContrato')
                ->select(
                    'A.*',
                    'O.OP_NombreObra as nombreObra',
                    'O.OP_Num_obra as numObra',
                    'S.PRO_Nombre as programa',
                    'AR.Area_Nombre as dependenciaEjecutora',
                    'C.CON_Contrato as noContrato'
                )
                ->where('A.IDAcciones', $encuesta->IDAcciones)
                ->first();

        abort_if(!$accion, 404, 'La acción de esta encuesta ya no existe.');

        $tipoOrigen = DB::table('TBLC_TipoOrigen')->where('IDTipoOrigen', $accion->IDTipoOrigen)->value('Nombre');
        $situacion = $encuesta->IDSituacionEncontrada
            ? DB::table('TBLC_SituacionEncontrada')->where('IDSituacion', $encuesta->IDSituacionEncontrada)->value('Nombre')
            : null;
        $estatus = $encuesta->IDEstatus
            ? DB::table('tblc_estatusencuesta')->where('IDEstatus', $encuesta->IDEstatus)->value('Nombre')
            : null;

        $preguntas = DB::table('TBLC_EncuestaPreguntas')
            ->where('IDTipoOrigen', $accion->IDTipoOrigen)
            ->where('Activa', 1)
            ->orderBy('Orden')
            ->get();

        $respuestas = DB::table('TblD_EncuestaRespuestas')
            ->where('IDEncuesta', $idEncuesta)
            ->get()
            ->keyBy('IDPregunta');

        return [
            'encuesta' => $encuesta,
            'accion' => $accion,
            'tipoOrigen' => $tipoOrigen, // 'Obra' o 'Programa'
            'situacion' => $situacion,
            'estatus' => $estatus,
            'preguntas' => $preguntas,
            'respuestas' => $respuestas,
            // Ver nota de negocio: el municipio de la ficha impresa es
            // siempre Chilón, no se pide en el formulario.
            'municipio' => 'Chilón',
        ];
    }

    /**
     * GET /api/Encuestas/fichaHtml/{idEncuesta}
     * Vista para pantalla / impresión directa del navegador (Ctrl+P). El
     * botón "Imprimir" del JS abre esta ruta en una pestaña nueva.
     */
    public function fichaHtml($idEncuesta)
    {
        return view('encuestas.ficha', $this->datosFicha($idEncuesta));
    }

    /**
     * GET /api/Encuestas/fichaPdf/{idEncuesta}
     * Mismo view, convertido a PDF descargable.
     * Requiere: composer require barryvdh/laravel-dompdf
     * (agrega el Facade 'PDF' => Barryvdh\DomPDF\Facade\Pdf::class si tu
     * Laravel no usa auto-discovery de paquetes).
     */
    public function fichaPdf($idEncuesta)
    {
        $data = $this->datosFicha($idEncuesta);

        $pdf = \PDF::loadView('encuestas.ficha', $data)->setPaper('letter');

        return $pdf->download('ficha_verificacion_' . $idEncuesta . '.pdf');
    }
}
