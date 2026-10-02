<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Encuestas de Seguimiento -- v2: CUESTIONARIO GENERAL ÚNICO.
 *
 * Ya no se distingue Obra / Programa: toda acción usa la misma ficha, que
 * tiene dos bloques de preguntas (ENCUESTA 1 y ENCUESTA 2, columna
 * tblc_encuestapreguntas.Bloque). Cada encuesta tiene un TIPO (INICIO,
 * PROCESO, CONCLUSION), hasta 5 fotos (posiciones 1..5) y documentos
 * ilimitados. Una encuesta nueva se precarga con la última de la acción.
 */
class EncuestasController extends Controller
{
    private const MAX_FOTOS = 5;
    // Las fotos se guardan DENTRO de public/ (no en storage/), así se ven sin `storage:link`
    // ni permisos de enlaces simbólicos en el hosting.
    private const DIR_FOTOS = 'uploads/encuestas/fotos';
    // Igual con los documentos. Como quedan en una carpeta pública, SOLO se aceptan estas
    // extensiones (nunca .php u otros ejecutables).
    private const DIR_DOCS = 'uploads/encuestas/documentos';
    private const EXT_DOCS = ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx', 'csv', 'txt', 'zip', 'rar', 'jpg', 'jpeg', 'png'];
    private const TIPOS = ['INICIO' => 'Inicio', 'PROCESO' => 'En proceso', 'CONCLUSION' => 'Conclusión'];

    /**
     * Borra el archivo físico de una foto o documento. Solo toca archivos dentro de
     * public/uploads/encuestas/. Si la ruta es de la versión anterior (storage/app/public),
     * lo borra de ahí.
     */
    private function borrarArchivoPublico($ruta)
    {
        if (!$ruta) {
            return;
        }

        if (strpos($ruta, 'uploads/encuestas/') === 0) {
            $abs = public_path($ruta);
            if (File::exists($abs)) {
                File::delete($abs);
            }
        } elseif (strpos($ruta, 'encuestas/documentos/') === 0 || strpos($ruta, 'encuestas/fotos/') === 0) {
            Storage::disk('public')->delete($ruta); // archivos de la versión anterior
        }
    }

    /** Logo del municipio como data-URI (funciona igual en pantalla y en el PDF). */
    private function logoDataUri()
    {
        $abs = public_path('assets/media/logo_chilon_ficha.png');

        return File::exists($abs) ? 'data:image/png;base64,' . base64_encode(File::get($abs)) : null;
    }

    /**
     * Foto lista para reportes: se reduce a $maxLado px y se recomprime (las fotos de
     * celular pesan varios MB y harían lentos o imposibles los reportes con muchas
     * fotos). Corrige la orientación EXIF. Sin la extensión GD manda la original.
     */
    private function imagenParaReporte($ruta, $maxLado = 1100)
    {
        $abs = public_path($ruta);
        if (!$ruta || !File::exists($abs)) {
            return null;
        }

        $original = function () use ($abs) {
            return 'data:' . (File::mimeType($abs) ?: 'image/jpeg') . ';base64,' . base64_encode(File::get($abs));
        };

        if (!function_exists('imagecreatetruecolor')) {
            return $original();
        }

        $info = @getimagesize($abs);
        if (!$info) {
            return null;
        }

        switch ($info[2]) {
            case IMAGETYPE_JPEG: $img = @imagecreatefromjpeg($abs); break;
            case IMAGETYPE_PNG:  $img = @imagecreatefrompng($abs); break;
            case IMAGETYPE_GIF:  $img = @imagecreatefromgif($abs); break;
            case IMAGETYPE_WEBP: $img = function_exists('imagecreatefromwebp') ? @imagecreatefromwebp($abs) : false; break;
            default:             $img = false;
        }
        if (!$img) {
            return $original();
        }

        // Orientación EXIF (fotos tomadas con el celular)
        if ($info[2] === IMAGETYPE_JPEG && function_exists('exif_read_data')) {
            $exif = @exif_read_data($abs);
            $ang = [3 => 180, 6 => -90, 8 => 90][$exif['Orientation'] ?? 1] ?? 0;
            if ($ang) {
                $rot = @imagerotate($img, $ang, 0);
                if ($rot) {
                    imagedestroy($img);
                    $img = $rot;
                }
            }
        }

        $w = imagesx($img);
        $h = imagesy($img);
        $esc = min(1, $maxLado / max($w, $h));
        $nw = max(1, (int) round($w * $esc));
        $nh = max(1, (int) round($h * $esc));

        $dst = imagecreatetruecolor($nw, $nh);
        imagefill($dst, 0, 0, imagecolorallocate($dst, 255, 255, 255)); // fondo blanco (PNG con transparencia)
        imagecopyresampled($dst, $img, 0, 0, 0, 0, $nw, $nh, $w, $h);
        imagedestroy($img);

        ob_start();
        imagejpeg($dst, null, 80);
        $bin = ob_get_clean();
        imagedestroy($dst);

        return 'data:image/jpeg;base64,' . base64_encode($bin);
    }

    // ══════════════════════════════════════════════════════════════
    // Catálogos
    // ══════════════════════════════════════════════════════════════

    /** Se conserva: lo usa el formulario de Acción en Obras.js */
    public function getTipoOrigen()
    {
        return response()->json(
            DB::table('tblc_tipoorigen')
                ->select('IDTipoOrigen as id', 'Nombre as nombre')
                ->orderBy('IDTipoOrigen')
                ->get()
        );
    }

    /** Situación: Concluida / Proceso */
    public function getSituacionEncontrada()
    {
        return response()->json(
            DB::table('tblc_situacionencontrada')
                ->select('IDSituacion as id', 'Nombre as nombre')
                ->orderBy('IDSituacion')
                ->get()
        );
    }

    public function getSubsituacionEncontrada()
    {
        return response()->json(
            DB::table('tblc_subsituacionencontrada')
                ->where('Activa', 1)
                ->select('IDSubsituacion as id', 'Nombre as nombre')
                ->orderBy('IDSubsituacion')
                ->get()
        );
    }

    public function getSituacionEncuestador()
    {
        return response()->json(
            DB::table('tblc_situacionencontradaencuestador')
                ->where('Activa', 1)
                ->select('IDSituacionEncuestador as id', 'Nombre as nombre')
                ->orderBy('IDSituacionEncuestador')
                ->get()
        );
    }

    public function getMunicipios()
    {
        return response()->json(
            DB::table('tblc_municipios')
                ->select('CveMunicipio as id', 'MNP_Nombre as nombre')
                ->orderBy('MNP_Nombre')
                ->get()
        );
    }

    public function getLocalidades($cveMunicipio)
    {
        return response()->json(
            DB::table('tblc_localidades')
                ->where('CveMunicipio', $cveMunicipio)
                ->select('IDLocalidad as id', 'LCL_Nombre as nombre')
                ->orderBy('LCL_Nombre')
                ->get()
        );
    }

    /**
     * GET /api/Encuestas/getPreguntas
     * Cuestionario general (ambos bloques) en el orden del catálogo.
     */
    public function getPreguntas()
    {
        return response()->json(
            DB::table('tblc_encuestapreguntas')
                ->select(
                    'IDPregunta as id',
                    'Bloque as bloque',
                    'Orden as orden',
                    'Texto as texto',
                    'TipoRespuesta as tipo',
                    'RequiereJustificacion as requiereJustificacion',
                    'EtiquetaJustificacion as etiqueta',
                    'Obligatoria as obligatoria'
                )
                ->where('Activa', 1)
                ->orderBy('Bloque')
                ->orderBy('Orden')
                ->get()
        );
    }

    // ══════════════════════════════════════════════════════════════
    // Datos informativos de la acción (solo lectura en la ficha)
    // ══════════════════════════════════════════════════════════════

    private function infoAccion($idAccion)
    {
        $a = DB::table('tbld_acciones as A')
            ->join('tblp_obraproyecto as O', 'A.IDobraproyecto', '=', 'O.IDobraproyecto')
            ->leftJoin('tblc_programa as P', 'O.IDprogram', '=', 'P.IDprogram')
            ->leftJoin('tblc_area as AR', 'O.IDArea', '=', 'AR.IDArea')
            ->where('A.IDAcciones', $idAccion)
            ->select(
                'A.IDAcciones',
                'A.dependenciaejecutora',
                'A.MNP_Nombre',
                'A.LCL_Nombre',
                'A.OP_Año as ejercicio',
                'A.Beneficiario',
                'A.Tipobeneficiario',
                'A.AC_Descripcionaccion',
                'A.metageneral',
                'A.Meta',
                'A.Latitud',
                'A.Logintud',
                'O.OP_NombreObra',
                'P.claveprograma',
                'AR.Area_Nombre as areaNombre'
            )
            ->first();

        if (!$a) {
            return null;
        }

        $inversion = DB::table('tbld_financiamientoinversion')
            ->where('IDAcciones', $idAccion)
            ->sum('FI_Inversion');

        // IDAcciones -> IDProgramafinanciamiento -> IDFuenteFinanciamiento
        $fuentes = DB::table('tbld_financiamientoinversion as F')
            ->join('tblc_fuentefinanciamiento as FF', 'F.IDProgramafinanciamiento', '=', 'FF.IDFuenteFinanciamiento')
            ->where('F.IDAcciones', $idAccion)
            ->pluck('FF.PRF_Nombre')
            ->filter()
            ->unique()
            ->values()
            ->all();

        $beneficiados = trim(($a->Beneficiario ?? '') . ' ' . ($a->Tipobeneficiario ?? ''));
        $nombreProyecto = trim(implode(' - ', array_filter([
            trim((string) $a->OP_NombreObra),
            trim((string) $a->AC_Descripcionaccion),
        ])));

        return [
            // si la acción aún no tiene dependencia capturada, cae al área de la obra
            'dependenciaEjecutora' => $a->dependenciaejecutora ?: $a->areaNombre,
            'municipio' => $a->MNP_Nombre,
            'localidad' => $a->LCL_Nombre,
            'ejercicio' => $a->ejercicio,
            'beneficiados' => $beneficiados,
            'inversionProgramada' => (float) $inversion,
            'programa' => $a->claveprograma,
            'nombreProyecto' => $nombreProyecto,
            'meta' => $a->metageneral ?: $a->Meta,
            'fuentes' => implode("\n", $fuentes),
            'latitud' => $a->Latitud,
            'longitud' => $a->Logintud,
        ];
    }

    /** GET /api/Encuestas/getDatosAccion/{idAccion} */
    public function getDatosAccion($idAccion)
    {
        $info = $this->infoAccion($idAccion);

        return $info
            ? response()->json($info)
            : response()->json(null, 404);
    }

    // ══════════════════════════════════════════════════════════════
    // Bitácora
    // ══════════════════════════════════════════════════════════════

    /** GET /api/Encuestas/getEncuestas/{idAccion} -- en orden de captura (la más antigua primero) */
    public function getEncuestas($idAccion)
    {
        $lista = DB::table('tbld_encuestas as E')
            ->leftJoin('tblc_situacionencontrada as S', 'E.IDSituacionEncontrada', '=', 'S.IDSituacion')
            ->leftJoin('tblc_subsituacionencontrada as SS', 'E.IDSubsituacionencontrada', '=', 'SS.IDSubsituacion')
            ->select(
                'E.IDEncuesta as idEncuesta',
                'E.Folio as folio',
                'E.TipoEncuesta as tipo',
                DB::raw("DATE_FORMAT(E.FechaCreacion, '%d/%m/%Y %H:%i') as fechaCaptura"),
                DB::raw("DATE_FORMAT(E.FechaVisita, '%d/%m/%Y') as fechaVisita"),
                'E.avanceencontrado as avance',
                'S.Nombre as situacion',
                'SS.Nombre as subsituacion',
                'E.nombreencuestador as encuestador',
                DB::raw('(SELECT COUNT(*) FROM tbld_encuestafotos F WHERE F.IDEncuesta = E.IDEncuesta) as numFotos'),
                DB::raw('(SELECT COUNT(*) FROM tbld_encuestadocumentos D WHERE D.IDEncuesta = E.IDEncuesta) as numDocumentos')
            )
            ->where('E.IDAcciones', $idAccion)
            ->orderBy('E.FechaCreacion', 'asc')
            ->orderBy('E.IDEncuesta', 'asc')
            ->get();

        return response()->json($lista);
    }

    /**
     * Arma el paquete encabezado + respuestas (+ fotos/documentos) de una encuesta.
     * $conEvidencia = false cuando se usa solo para precargar una encuesta nueva.
     */
    private function datosEncuesta($id, $conEvidencia = true)
    {
        $encuesta = DB::table('tbld_encuestas')->where('IDEncuesta', $id)->first();
        if (!$encuesta) {
            return null;
        }

        $respuestas = DB::table('tbld_encuestarespuestas')
            ->where('IDEncuesta', $id)
            ->get(['IDPregunta', 'Respuesta', 'Justificacion', 'RespuestaHombres', 'RespuestaMujeres']);

        $fotos = [];
        $documentos = [];
        if ($conEvidencia) {
            $fotos = DB::table('tbld_encuestafotos')
                ->where('IDEncuesta', $id)
                ->orderBy('Posicion')
                ->select('IDFoto as id', 'Posicion as posicion', 'RutaArchivo as ruta', 'NombreOriginal as nombre')
                ->get();

            $documentos = DB::table('tbld_encuestadocumentos')
                ->where('IDEncuesta', $id)
                ->select('IDDocumento as id', 'RutaArchivo as ruta', 'NombreOriginal as nombre')
                ->get();
        }

        return [
            'encuesta' => $encuesta,
            'respuestas' => $respuestas,
            'fotos' => $fotos,
            'documentos' => $documentos,
        ];
    }

    /** GET /api/Encuestas/getEncuestaById/{id} */
    public function getEncuestaById($id)
    {
        $datos = $this->datosEncuesta($id);

        return $datos ? response()->json($datos) : response()->json(null, 404);
    }

    /**
     * GET /api/Encuestas/getUltimaEncuesta/{idAccion}
     * Última encuesta capturada de la acción (sin fotos ni documentos) para
     * precargar una encuesta nueva. Regresa null si es la primera.
     */
    public function getUltimaEncuesta($idAccion)
    {
        $id = DB::table('tbld_encuestas')
            ->where('IDAcciones', $idAccion)
            ->orderByDesc('FechaCreacion')
            ->orderByDesc('IDEncuesta')
            ->value('IDEncuesta');

        return response()->json($id ? $this->datosEncuesta($id, false) : null);
    }

    // ══════════════════════════════════════════════════════════════
    // Guardar
    // ══════════════════════════════════════════════════════════════

    /**
     * POST /api/Encuestas/guardarEncuesta  (multipart/form-data)
     * Campos: los de tbld_encuestas + respuestasJson + foto_1..foto_5
     * (opcionales; una foto nueva en una posición REEMPLAZA a la anterior) + documentos[].
     */
    public function guardarEncuesta(Request $request)
    {
        $request->validate([
            'idAccion' => 'required|integer',
            'fechaVisita' => 'required|date',
            'direccion' => 'required|string',
            'avanceencontrado' => 'required|numeric|min:0|max:100',
            'idSituacionEncontrada' => 'required|integer',
            'idSubsituacionencontrada' => 'required|integer',
            'tipoEncuesta' => 'required|in:INICIO,PROCESO,CONCLUSION',
            'foto_1' => 'nullable|image|max:10240',
            'foto_2' => 'nullable|image|max:10240',
            'foto_3' => 'nullable|image|max:10240',
            'foto_4' => 'nullable|image|max:10240',
            'foto_5' => 'nullable|image|max:10240',
            'documentos' => 'nullable|array',
            'documentos.*' => 'file|max:20480|mimes:' . implode(',', self::EXT_DOCS),
        ]);

        // Regla: la primera encuesta de una acción SIEMPRE es de tipo INICIO
        // (las demás pueden ser INICIO, PROCESO o CONCLUSION en cualquier orden).
        $primeraId = DB::table('tbld_encuestas')
            ->where('IDAcciones', (int) $request->input('idAccion'))
            ->orderBy('FechaCreacion')->orderBy('IDEncuesta')
            ->value('IDEncuesta');
        $esPrimera = $request->input('accion') === 'add'
            ? !$primeraId
            : ((int) $request->input('idEncuesta') === (int) $primeraId);

        if ($esPrimera && $request->input('tipoEncuesta') !== 'INICIO') {
            $msg = 'La primera encuesta de la acción debe ser de tipo Inicio.';
            return response()->json(['message' => $msg, 'errors' => ['tipoEncuesta' => [$msg]]], 422);
        }

        $vacio = function ($campo) use ($request) {
            $v = $request->input($campo);
            return ($v === null || $v === '') ? null : $v;
        };

        $campos = [
            'IDAcciones' => (int) $request->input('idAccion'),
            'Folio' => $vacio('folio'),
            'TipoEncuesta' => $request->input('tipoEncuesta'),
            'FechaVisita' => $request->input('fechaVisita'),
            'situacionreportada' => $vacio('situacionreportada'),
            'avancefisico' => $vacio('avancefisico'),
            'foliosrelacionados' => $vacio('foliosrelacionados'),
            'Direccion' => $vacio('direccion'),
            'avanceencontrado' => $vacio('avanceencontrado'),
            'IDSituacionEncontrada' => $vacio('idSituacionEncontrada'),
            'IDSubsituacionencontrada' => $vacio('idSubsituacionencontrada'),
            'observacionessituacion' => $vacio('observacionessituacion'),
            // persona que proporcionó la información
            'identificacionpersonal' => $vacio('identificacionpersonal'),
            'nombrecompletopersonal' => $vacio('nombrecompletopersonal'),
            'cargopersonal' => $vacio('cargopersonal'),
            'domiciliopersonal' => $vacio('domiciliopersonal'),
            'clavemunicipiopersonal' => $vacio('clavemunicipiopersonal'),
            'idlocalidadpersonal' => $vacio('idlocalidadpersonal'),
            // encuestador / verificador (después de ENCUESTA 1)
            'nombreencuestador' => $vacio('nombreencuestador'),
            'recomendacionesencuestador' => $vacio('recomendacionesencuestador'),
            'situacionencontradaencuestador' => $vacio('situacionencontradaencuestador'),
            'observacionesencuestador' => $vacio('observacionesencuestador'),
            // encuestado
            'identificacionperencuestado' => $vacio('identificacionperencuestado'),
            'nombreencuestado' => $vacio('nombreencuestado'),
            'sexoencuestado' => $vacio('sexoencuestado'),
            'edadencuestado' => $vacio('edadencuestado'),
            'parentescoencuestado' => $vacio('parentescoencuestado'),
            'domicilioencuestado' => $vacio('domicilioencuestado'),
            'clavemunicipioencuestador' => $vacio('clavemunicipioencuestador'),
            'idlocalidadencuestador' => $vacio('idlocalidadencuestador'),
            // cierre (después de ENCUESTA 2)
            'nombreencuestadorfinal' => $vacio('nombreencuestadorfinal'),
            'recomendacionesencuestadorfinal' => $vacio('recomendacionesencuestadorfinal'),
            'comentariofinal' => $vacio('comentariofinal'),
        ];

        // [{"idPregunta":1,"respuesta":"SI","justificacion":"...","hombres":2,"mujeres":3}]
        $respuestas = json_decode($request->input('respuestasJson', '[]'), true) ?: [];

        $idEncuesta = DB::transaction(function () use ($request, $campos, $respuestas) {
            if ($request->input('accion') === 'add') {
                $id = DB::table('tbld_encuestas')->insertGetId(array_merge($campos, [
                    'FechaCreacion' => now(),
                ]));
            } else {
                $id = (int) $request->input('idEncuesta');
                DB::table('tbld_encuestas')->where('IDEncuesta', $id)->update($campos);
                DB::table('tbld_encuestarespuestas')->where('IDEncuesta', $id)->delete();
            }

            foreach ($respuestas as $r) {
                $hom = $r['hombres'] ?? null;
                $muj = $r['mujeres'] ?? null;
                DB::table('tbld_encuestarespuestas')->insert([
                    'IDEncuesta' => $id,
                    'IDPregunta' => $r['idPregunta'],
                    'Respuesta' => ($r['respuesta'] ?? '') !== '' ? $r['respuesta'] : null,
                    'Justificacion' => ($r['justificacion'] ?? '') !== '' ? $r['justificacion'] : null,
                    'RespuestaHombres' => ($hom === '' || $hom === null) ? null : (int) $hom,
                    'RespuestaMujeres' => ($muj === '' || $muj === null) ? null : (int) $muj,
                ]);
            }

            return $id;
        });

        // ── Fotos: máximo 5 por encuesta (posiciones 1..5). Una foto nueva en una
        //    posición ocupada REEMPLAZA a la anterior (se borra también el archivo viejo). ──
        for ($pos = 1; $pos <= self::MAX_FOTOS; $pos++) {
            $input = 'foto_' . $pos;
            if (!$request->hasFile($input)) {
                continue;
            }

            $previa = DB::table('tbld_encuestafotos')
                ->where('IDEncuesta', $idEncuesta)
                ->where('Posicion', $pos)
                ->first();
            if ($previa) {
                $this->borrarArchivoPublico($previa->RutaArchivo);
                DB::table('tbld_encuestafotos')->where('IDFoto', $previa->IDFoto)->delete();
            }

            $foto = $request->file($input);
            $nombreArchivo = Str::random(40) . '.' . ($foto->guessExtension() ?: 'jpg');
            $foto->move(public_path(self::DIR_FOTOS), $nombreArchivo); // crea la carpeta si no existe
            DB::table('tbld_encuestafotos')->insert([
                'IDEncuesta' => $idEncuesta,
                'Posicion' => $pos,
                'RutaArchivo' => self::DIR_FOTOS . '/' . $nombreArchivo,
                'NombreOriginal' => $foto->getClientOriginalName(),
                'FechaSubida' => now(),
            ]);
        }

        // ── Documentos: varios, se ANEXAN a los que ya existían. Van a public/uploads/encuestas/documentos ──
        if ($request->hasFile('documentos')) {
            foreach ($request->file('documentos') as $doc) {
                $ext = strtolower($doc->getClientOriginalExtension());
                if (!in_array($ext, self::EXT_DOCS, true)) {
                    $ext = strtolower($doc->guessExtension() ?: 'bin');
                }
                $nombreArchivo = Str::random(40) . '.' . $ext;
                $doc->move(public_path(self::DIR_DOCS), $nombreArchivo);
                $ruta = self::DIR_DOCS . '/' . $nombreArchivo;
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

    public function eliminarEncuesta($id)
    {
        // Las respuestas caen en cascada. Fotos/documentos no se tocan (igual que antes).
        DB::table('tbld_encuestas')->where('IDEncuesta', $id)->delete();

        return response()->json(['success' => true]);
    }

    public function eliminarFoto($idFoto)
    {
        $foto = DB::table('tbld_encuestafotos')->where('IDFoto', $idFoto)->first();
        if ($foto) {
            $this->borrarArchivoPublico($foto->RutaArchivo);
            DB::table('tbld_encuestafotos')->where('IDFoto', $idFoto)->delete();
        }

        return response()->json(['success' => true]);
    }

    public function eliminarDocumento($idDocumento)
    {
        $doc = DB::table('tbld_encuestadocumentos')->where('IDDocumento', $idDocumento)->first();
        if ($doc) {
            $this->borrarArchivoPublico($doc->RutaArchivo);
            DB::table('tbld_encuestadocumentos')->where('IDDocumento', $idDocumento)->delete();
        }

        return response()->json(['success' => true]);
    }

    // ══════════════════════════════════════════════════════════════
    // Ficha imprimible / PDF (mismo layout que el formulario)
    // ══════════════════════════════════════════════════════════════

    private function datosFicha($idEncuesta)
    {
        $encuesta = DB::table('tbld_encuestas')->where('IDEncuesta', $idEncuesta)->first();
        abort_if(!$encuesta, 404, 'Encuesta no encontrada.');

        $info = $this->infoAccion($encuesta->IDAcciones);
        abort_if(!$info, 404, 'La acción de esta encuesta ya no existe.');

        $nombre = function ($tabla, $campoId, $valor, $campoNombre) {
            return $valor ? DB::table($tabla)->where($campoId, $valor)->value($campoNombre) : null;
        };

        $preguntas = DB::table('tblc_encuestapreguntas')
            ->where('Activa', 1)
            ->orderBy('Bloque')->orderBy('Orden')
            ->get()
            ->groupBy('Bloque');

        $respuestas = DB::table('tbld_encuestarespuestas')
            ->where('IDEncuesta', $idEncuesta)
            ->get()
            ->keyBy('IDPregunta');

        // Fotos livianas como data-URI, en orden de posición
        $fotos = [];
        $filas = DB::table('tbld_encuestafotos')->where('IDEncuesta', $idEncuesta)->orderBy('Posicion')->get();
        foreach ($filas as $f) {
            $src = $this->imagenParaReporte($f->RutaArchivo);
            if ($src) {
                $fotos[] = ['pos' => $f->Posicion, 'src' => $src];
            }
        }

        return [
            'encuesta' => $encuesta,
            'tipoEncuesta' => self::TIPOS[$encuesta->TipoEncuesta] ?? $encuesta->TipoEncuesta,
            'logo' => $this->logoDataUri(),
            'info' => $info,
            'situacion' => $nombre('tblc_situacionencontrada', 'IDSituacion', $encuesta->IDSituacionEncontrada, 'Nombre'),
            'subsituacion' => $nombre('tblc_subsituacionencontrada', 'IDSubsituacion', $encuesta->IDSubsituacionencontrada, 'Nombre'),
            'situacionEncuestador' => $nombre('tblc_situacionencontradaencuestador', 'IDSituacionEncuestador', $encuesta->situacionencontradaencuestador, 'Nombre'),
            'municipioPersonal' => $nombre('tblc_municipios', 'CveMunicipio', $encuesta->clavemunicipiopersonal, 'MNP_Nombre'),
            'localidadPersonal' => $nombre('tblc_localidades', 'IDLocalidad', $encuesta->idlocalidadpersonal, 'LCL_Nombre'),
            'municipioEncuestado' => $nombre('tblc_municipios', 'CveMunicipio', $encuesta->clavemunicipioencuestador, 'MNP_Nombre'),
            'localidadEncuestado' => $nombre('tblc_localidades', 'IDLocalidad', $encuesta->idlocalidadencuestador, 'LCL_Nombre'),
            'preguntas1' => $preguntas->get(1, collect()),
            'preguntas2' => $preguntas->get(2, collect()),
            'respuestas' => $respuestas,
            'fotos' => $fotos,
        ];
    }

    /** GET /api/Encuestas/fichaHtml/{idEncuesta} -- vista previa / impresión */
    public function fichaHtml($idEncuesta)
    {
        return view('encuestas.ficha', $this->datosFicha($idEncuesta));
    }

    /** GET /api/Encuestas/fichaPdf/{idEncuesta} -- requiere barryvdh/laravel-dompdf (igual que antes) */
    public function fichaPdf($idEncuesta)
    {
        $pdf = \PDF::loadView('encuestas.ficha', $this->datosFicha($idEncuesta))->setPaper('letter');

        return $pdf->download('ficha_verificacion_' . $idEncuesta . '.pdf');
    }

    // ══════════════════════════════════════════════════════════════
    // Reporte general de encuestas de una acción
    // ══════════════════════════════════════════════════════════════

    private function datosReporteGeneral($idAccion)
    {
        @set_time_limit(180);
        @ini_set('memory_limit', '512M');

        $info = $this->infoAccion($idAccion);
        abort_if(!$info, 404, 'La acción no existe.');

        $encuestas = DB::table('tbld_encuestas')
            ->where('IDAcciones', $idAccion)
            ->orderBy('FechaVisita')->orderBy('FechaCreacion')->orderBy('IDEncuesta')
            ->get(['IDEncuesta', 'Folio', 'TipoEncuesta', 'FechaVisita', 'avanceencontrado', 'foliosrelacionados']);

        // Siempre en este orden; dentro de cada grupo, por fecha de visita (la más antigua primero)
        $grupos = ['INICIO' => [], 'PROCESO' => [], 'CONCLUSION' => []];
        $relacionados = [];
        $totalFotos = 0;

        foreach ($encuestas as $e) {
            $fotos = [];
            $filas = DB::table('tbld_encuestafotos')->where('IDEncuesta', $e->IDEncuesta)->orderBy('Posicion')->get();
            foreach ($filas as $f) {
                $src = $this->imagenParaReporte($f->RutaArchivo);
                if ($src) {
                    $fotos[] = ['pos' => $f->Posicion, 'src' => $src];
                }
            }
            $totalFotos += count($fotos);

            $rel = array_values(array_filter(array_map('trim', explode(',', (string) $e->foliosrelacionados))));
            foreach ($rel as $r) {
                $relacionados[$r] = true;
            }

            $grupos[$e->TipoEncuesta][] = [
                'folio' => $e->Folio ?: ('#' . $e->IDEncuesta),
                'fecha' => $e->FechaVisita ? \Carbon\Carbon::parse($e->FechaVisita)->format('d/m/Y') : '-',
                'avance' => $e->avanceencontrado !== null ? (int) $e->avanceencontrado . '%' : '-',
                'relacionados' => $rel ? implode(', ', $rel) : '-',
                'fotos' => $fotos,
            ];
        }

        return [
            'info' => $info,
            'grupos' => $grupos,
            'etiquetas' => self::TIPOS,
            'totalEncuestas' => count($encuestas),
            'totalFotos' => $totalFotos,
            'foliosRelacionados' => $relacionados ? implode(', ', array_keys($relacionados)) : '-',
            'logo' => $this->logoDataUri(),
            'emitido' => now()->format('d/m/Y H:i'),
        ];
    }

    /** GET /api/Encuestas/reporteGeneral/{idAccion} -- vista previa / impresión */
    public function reporteGeneralHtml($idAccion)
    {
        return view('encuestas.reporte_general', $this->datosReporteGeneral($idAccion));
    }

    /** GET /api/Encuestas/reporteGeneralPdf/{idAccion} */
    public function reporteGeneralPdf($idAccion)
    {
        $pdf = \PDF::loadView('encuestas.reporte_general', $this->datosReporteGeneral($idAccion))->setPaper('letter');

        return $pdf->download('reporte_general_encuestas_accion_' . $idAccion . '.pdf');
    }
}
