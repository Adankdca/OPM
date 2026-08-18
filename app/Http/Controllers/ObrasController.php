<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Equivalente a Ceduver.Controllers.ObrasController (ApiController de .NET).
 *
 * DIFERENCIAS CLAVE respecto a tu controller original en C#:
 *
 * 1. En C# abrías la conexión a mano con "new SqlConnection(...)" y Dapper.
 *    En Laravel, la conexión ya está configurada UNA VEZ en config/database.php
 *    (leyendo tu .env), así que aquí solo se usa DB::select() / DB::table()
 *    directo, sin abrir/cerrar conexión manualmente.
 *
 * 2. Dapper mapeaba el resultado del SQL a una clase C# (ObraDTO).
 *    Aquí DB::select() ya regresa un array de objetos con las mismas
 *    propiedades que vienen del alias del SELECT (idobra, noObra, etc.),
 *    así que ni siquiera necesitamos una clase DTO aparte: el propio
 *    resultado del query ya trae los nombres correctos.
 *
 * 3. [HttpGet]/[Route("getObras")] de .NET Web API se convierte en una
 *    ruta normal registrada en routes/web.php (lo verás en ese archivo).
 *
 * Por ahora solo se migran los 3 endpoints de la pantalla de listado
 * (getObras, getRubros, getAnios) -- el resto de los 36 endpoints del
 * controller original (guardarObra, getAcciones, COCI, etc.) se van
 * agregando uno por uno conforme migres cada pantalla, tal como acordamos.
 */
class ObrasController extends Controller
{
    /**
     * GET /api/obras/getObras
     * Equivalente a GetObras() en el controller original.
     */
    public function getObras(Request $request)
    {
        $rubro    = $request->query('rubro', '');
        $anio     = $request->query('anio', '');
        $contrato = $request->query('contrato', '');
        $nombre   = $request->query('nombre', '');
        $numObra  = $request->query('numObra', '');

        // NOTA: igual que en el original, se usa SQL "crudo" (DB::select)
        // en vez de Eloquent puro, porque el query mezcla funciones
        // personalizadas (FinanciamientoObraproyecto, localidadObraproyecto,
        // ConteoAcciones), SUM() con GROUP BY, y un subquery con GROUP_CONCAT.
        // Ese tipo de SQL "a la medida" es exactamente para lo que sirve
        // DB::select() -- Eloquent es mejor para CRUD simple, no para esto.
        $sql = "
            SELECT
                O.IDobraproyecto AS idobra,
                O.OP_Num_obra AS noObra,
                O.OP_NombreObra AS nombre,
                S.PRO_Nombre AS subrubro,

                IFNULL((
                    SELECT GROUP_CONCAT(DISTINCT AP2.OP_Año ORDER BY AP2.OP_Año SEPARATOR ', ')
                    FROM TBLD_añoobraproyecto AP2
                    WHERE AP2.IDObraproyecto = O.IDobraproyecto
                ), '') AS anio,

                IFNULL(SUM(AINV.OP_InversioMunicipal), 0) AS municipal,
                IFNULL(SUM(AINV.OP_InversionEstatal), 0) AS estatal,
                IFNULL(SUM(AINV.OP_InversionFederal), 0) AS federal,

                IFNULL(SUM(
                    IFNULL(AINV.OP_InversioMunicipal, 0) +
                    IFNULL(AINV.OP_InversionEstatal, 0) +
                    IFNULL(AINV.OP_InversionFederal, 0)
                ), 0) AS total,

                FinanciamientoObraproyecto(O.IDobraproyecto, 0) AS finanto,
                localidadObraproyecto(O.IDobraproyecto) AS localidad,
                ConteoAcciones(O.IDobraproyecto) AS numAcciones

            FROM TBLP_Obraproyecto O
            LEFT JOIN TblD_Acciones AC ON O.IDobraproyecto = AC.IDobraproyecto
            LEFT JOIN TBLD_Contrato C ON AC.IDContrato = C.IDContrato
            INNER JOIN TBLC_Subrubro S ON O.IDPrograma = S.IDPrograma
            LEFT JOIN TBLD_añoobraproyecto AINV ON O.IDobraproyecto = AINV.IDObraproyecto

            WHERE IFNULL(O.OP_Eliminado, 0) = 0
              AND (:rubro1 = '' OR O.IDRubro = :rubro2)
              AND (:anio1 = '' OR EXISTS (
                    SELECT 1 FROM TBLD_añoobraproyecto AP
                    WHERE AP.IDObraproyecto = O.IDobraproyecto AND AP.OP_Año = :anio2
                  ))
              AND (:contrato1 = '' OR (C.CON_Contrato IS NOT NULL AND C.CON_Contrato LIKE CONCAT('%', :contrato2, '%')))
              AND (:nombre1 = '' OR O.OP_NombreObra LIKE CONCAT('%', :nombre2, '%'))
              AND (:numObra1 = '' OR O.OP_Num_obra LIKE CONCAT('%', :numObra2, '%'))

            GROUP BY O.IDobraproyecto, O.OP_Num_obra, O.OP_NombreObra, S.PRO_Nombre
            ORDER BY O.IDobraproyecto DESC
        ";

        $lista = DB::select($sql, [
            'rubro1' => $rubro, 'rubro2' => $rubro,
            'anio1' => $anio, 'anio2' => $anio,
            'contrato1' => $contrato, 'contrato2' => $contrato,
            'nombre1' => $nombre, 'nombre2' => $nombre,
            'numObra1' => $numObra, 'numObra2' => $numObra,
        ]);

        return response()->json($lista);
    }

    /**
     * GET /api/obras/getRubros
     * Equivalente a GetRubros(). Catalogo simple -> se puede usar DB::table()
     * (query builder) en vez de SQL crudo, es mas legible para algo tan directo.
     */
    public function getRubros()
    {
        $lista = DB::table('TBLC_Rubros')
            ->select('IDRubro as Id', 'Rub_Nombre as Nombre')
            ->where('Rub_Nombre', 'NOT LIKE', '%(SELECCIONE)%')
            ->orderBy('Rub_Nombre', 'asc')
            ->get();

        return response()->json($lista);
    }

    /**
     * GET /api/obras/getAnios
     * Equivalente a GetAnios().
     */
    public function getAnios()
    {
        $lista = DB::table('TBLC_Año')
            ->select('Año as Id', 'Año as Nombre')
            ->where('Año', 'NOT LIKE', '%(SELECCIONE)%')
            ->orderBy('Año', 'desc')
            ->get();

        return response()->json($lista);
    }

    // ══════════════════════════════════════════════════════════════
    // AUTOCOMPLETE (3 endpoints)
    //
    // Los 3 metodos siguen exactamente el mismo patron del original en
    // C#, así que se explica una sola vez aquí:
    //
    // - $request->query('term', '')  ==  string term = "" del parametro
    //   de la firma del metodo en C# (mismo concepto, sintaxis distinta).
    // - ->distinct()  ==  SELECT DISTINCT
    // - ->limit(20)   ==  TOP 20  (en MySQL el LIMIT va al final, no al
    //   inicio del SELECT como en SQL Server)
    // - ->pluck('columna')  es la pieza clave: en vez de traer objetos
    //   completos como en getRubros/getAnios, pluck() regresa SOLO los
    //   valores de esa columna, como una lista plana de strings.
    //   Esto es exactamente lo que hacia con.Query<string>(sql,...) en tu
    //   Dapper original -- y es indispensable aqui porque tu Obras.js
    //   (funcion crearAutocomplete, linea ~96) espera un arreglo de
    //   strings tal cual: ["OT001","OT002",...], NO objetos {valor:"..."}.
    // ══════════════════════════════════════════════════════════════

    /**
     * GET /api/obras/getContratosAutocomplete?term=xxx
     */
    public function getContratosAutocomplete(Request $request)
    {
        $term = $request->query('term', '');

        $lista = DB::table('TBLD_Contrato')
            ->select('CON_Contrato')
            ->where('CON_Contrato', 'like', '%' . $term . '%')
            ->whereNotNull('CON_Contrato')
            ->where('CON_Contrato', '<>', '')
            ->distinct()
            ->orderBy('CON_Contrato', 'asc')
            ->limit(20)
            ->pluck('CON_Contrato');

        return response()->json($lista);
    }

    /**
     * GET /api/obras/getNumObrasAutocomplete?term=xxx
     */
    public function getNumObrasAutocomplete(Request $request)
    {
        $term = $request->query('term', '');

        $lista = DB::table('TBLP_Obraproyecto')
            ->select('OP_Num_obra')
            ->where('OP_Num_obra', 'like', '%' . $term . '%')
            ->whereNotNull('OP_Num_obra')
            ->where('OP_Num_obra', '<>', '')
            ->where('OP_Eliminado', 0)
            ->distinct()
            ->orderBy('OP_Num_obra', 'asc')
            ->limit(20)
            ->pluck('OP_Num_obra');

        return response()->json($lista);
    }

    /**
     * GET /api/obras/getNombresAutocomplete?term=xxx
     */
    public function getNombresAutocomplete(Request $request)
    {
        $term = $request->query('term', '');

        $lista = DB::table('TBLP_Obraproyecto')
            ->select('OP_NombreObra')
            ->where('OP_NombreObra', 'like', '%' . $term . '%')
            ->whereNotNull('OP_NombreObra')
            ->where('OP_NombreObra', '<>', '')
            ->where('OP_Eliminado', 0)
            ->distinct()
            ->orderBy('OP_NombreObra', 'asc')
            ->limit(20)
            ->pluck('OP_NombreObra');

        return response()->json($lista);
    }

    // ══════════════════════════════════════════════════════════════
    // MODAL "NUEVA OBRA" -- catalogos + guardar
    //
    // Los 6 catalogos (getSubrubros, getProgramas, getAreas,
    // getMarginacion, getTipoObra, getAreasByRubro) son todos el mismo
    // patron: SELECT Id, Nombre FROM catalogo. Se dejan con DB::table()
    // porque es exactamente para esto que sirve el Query Builder --
    // mucho mas legible que escribir el SQL a mano para algo tan simple.
    // ══════════════════════════════════════════════════════════════

    /**
     * GET /api/obras/getObraById/{id}
     * Trae una obra para precargar el modal en modo edicion.
     */
    public function getObraById($id)
    {
        $data = DB::table('TBLP_Obraproyecto')
            ->select(
                'IDobraproyecto as idobra',
                'OP_Num_obra as noObra',
                'OP_NombreObra as nombre',
                'IDRubro as idRubro',
                'IDPrograma as idSubrubro',
                'IDprogram as idPrograma',
                'IDArea as idArea',
                'IdNivelmarginacion as idMarginacion',
                'IDaprima as idTipoObra',
                'OP_Antecendentes as antecedentes',
                'OP_Observaciones as observaciones',
                'OP_Acciones as acciones',
                'Op_Franjafronteriza as franja'
            )
            ->where('IDobraproyecto', $id)
            ->first();

        return response()->json($data);
    }

    /**
     * GET /api/obras/getSubrubros/{idRubro}
     */
    public function getSubrubros($idRubro)
    {
        $lista = DB::table('TBLC_Subrubro')
            ->select('IDPrograma as Id', 'PRO_Nombre as Nombre')
            ->where('IDRubro', $idRubro)
            ->where('PRO_Nombre', 'NOT LIKE', '%(SELECCIONE)%')
            ->orderBy('PRO_Nombre')
            ->get();

        return response()->json($lista);
    }

    /**
     * GET /api/obras/getProgramas
     */
    public function getProgramas()
    {
        $lista = DB::table('TblC_Programa')
            ->select('IDprogram as Id', 'claveprograma as Nombre')
            ->where('claveprograma', 'NOT LIKE', '%(SELECCIONE)%')
            ->orderBy('claveprograma')
            ->get();

        return response()->json($lista);
    }

    /**
     * GET /api/obras/getAreas
     */
    public function getAreas()
    {
        $lista = DB::table('TblC_Liderproyecto')
            ->select('IDliderproyecto as Id', 'NombreLider as Nombre')
            ->where('NombreLider', 'NOT LIKE', '%(SELECCIONE)%')
            ->orderBy('NombreLider')
            ->get();

        return response()->json($lista);
    }

    /**
     * GET /api/obras/getMarginacion
     */
    public function getMarginacion()
    {
        $lista = DB::table('TblC_Nivelmarginacion')
            ->select('IdNivelmarginacion as Id', 'NM_Descripcion as Nombre')
            ->where('NM_Descripcion', 'NOT LIKE', '%(SELECCIONE)%')
            ->orderBy('NM_Descripcion')
            ->get();

        return response()->json($lista);
    }

    /**
     * GET /api/obras/getTipoObra
     */
    public function getTipoObra()
    {
        $lista = DB::table('TblC_Normalesnocontratadas')
            ->select('IDaprima as Id', 'AprimaNombre as Nombre')
            ->where('AprimaNombre', 'NOT LIKE', '%(SELECCIONE)%')
            ->orderBy('AprimaNombre')
            ->get();

        return response()->json($lista);
    }

    /**
     * GET /api/obras/getAreasByRubro/{idRubro}
     */
    public function getAreasByRubro($idRubro)
    {
        $lista = DB::table('TblC_Liderproyecto')
            ->select('IDliderproyecto as Id', 'NombreLider as Nombre')
            ->where(function ($q) use ($idRubro) {
                $q->where('IDRubro', $idRubro)->orWhere('IDRubro', 0);
            })
            ->orderBy('NombreLider')
            ->get();

        return response()->json($lista);
    }

    /**
     * POST /api/obras/guardarObra
     * Equivalente a GuardarObra(ModelObra.ObraSaveDTO m).
     *
     * DIFERENCIA IMPORTANTE con el C# original: aqui NO se usa el Modelo
     * Eloquent (Obraproyecto::create/update) sino DB::table()->insert()/
     * update(), por la misma razon que en getObras -- el INSERT trae
     * columnas fijas (IDtipoejecucion=1, etc.) que no queremos exponer
     * como $fillable del modelo por descuido en otras pantallas.
     * Cuando migremos una pantalla que sea CRUD simple de verdad, ahi
     * si conviene usar el Modelo de lleno.
     *
     * Tu Obras.js manda el body como JSON (contentType: 'application/json'),
     * Laravel lo parsea automaticamente -- por eso $request->input('idobra')
     * funciona igual que si fuera un form normal, sin configuracion extra.
     */
    public function guardarObra(Request $request)
    {
        $idobra = (int) $request->input('idobra', 0);

        // .ToUpper() del C# original -> mb_strtoupper() en PHP (la version
        // "mb_" -- multibyte -- es importante para que respete acentos y ñ)
        $nombre = $request->input('nombre') ? mb_strtoupper($request->input('nombre')) : null;
        $antecedentes = $request->input('antecedentes') ? mb_strtoupper($request->input('antecedentes')) : null;
        $observaciones = $request->input('observaciones') ? mb_strtoupper($request->input('observaciones')) : null;

        if ($idobra === 0) {
            // ───── INSERT (nueva obra) ─────
            $id = DB::table('TBLP_Obraproyecto')->insertGetId([
                'IDRubro'             => $request->input('idRubro'),
                'OP_Num_obra'         => $request->input('noObra'),
                'OP_NombreObra'       => $nombre,
                'IDPrograma'          => $request->input('idSubrubro'),
                'IDprogram'           => $request->input('idPrograma'),
                'IDArea'              => $request->input('idArea'),
                'OP_Antecendentes'    => $antecedentes,
                'OP_Observaciones'    => $observaciones,
                'OP_Acciones'         => $request->input('acciones'),
                'IDtipoejecucion'     => 1,
                'OP_ProyectoEstrategico' => 0,
                'OP_Letrero'          => 0,
                'IDNivel'             => 15,
                'OP_Beneficiados'     => 0,
                'OP_Autorizada'       => 0,
                'OP_Eliminado'        => 0,
                'Op_Franjafronteriza' => $request->boolean('franja'),
                'IdNivelmarginacion'  => $request->input('idNivelMarginacion'),
                'IDaprima'            => $request->input('idAprima'),
                'OP_FechaCreacion'    => now(),
            ], 'IDobraproyecto');

            return response()->json(['success' => true, 'id' => $id]);
        }

        // ───── UPDATE (obra existente) ─────
        DB::table('TBLP_Obraproyecto')
            ->where('IDobraproyecto', $idobra)
            ->update([
                'OP_Num_obra'         => $request->input('noObra'),
                'OP_NombreObra'       => $nombre,
                'IDRubro'             => $request->input('idRubro'),
                'IDPrograma'          => $request->input('idSubrubro'),
                'IDprogram'           => $request->input('idPrograma'),
                'IDArea'              => $request->input('idArea'),
                'OP_Antecendentes'    => $antecedentes,
                'OP_Observaciones'    => $observaciones,
                'OP_Acciones'         => $request->input('acciones'),
                'Op_Franjafronteriza' => $request->boolean('franja'),
                'IdNivelmarginacion'  => $request->input('idNivelMarginacion'),
                'IDaprima'            => $request->input('idAprima'),
            ]);

        return response()->json(['success' => true]);
    }

    // ══════════════════════════════════════════════════════════════
    // ACCIONES (submodal "Acciones de la Obra")
    // ══════════════════════════════════════════════════════════════

    /**
     * GET /api/obras/getNombreObra/{idobra}
     * Trae solo el nombre, para el encabezado del modal.
     */
    public function getNombreObra($idobra)
    {
        $nombre = DB::table('TBLP_Obraproyecto')
            ->where('IDobraproyecto', $idobra)
            ->value('OP_NombreObra');

        return response()->json($nombre);
    }

    /**
     * GET /api/obras/getAcciones?idobra=..&anio=..
     * Con anio='' (que es lo que SIEMPRE manda el JS ahora, porque el
     * filtro visual quedo oculto) regresa TODAS las acciones de la obra.
     */
    public function getAcciones(Request $request)
    {
        $idobra = $request->query('idobra');
        $anio = $request->query('anio', '');

        $sql = "
            SELECT DISTINCT
                A.IDAcciones       AS idAccion,
                A.OP_Año           AS anio,
                A.AC_Accion        AS accion,
                TE.TPE_Nombre      AS tipoEjecucion,
                A.IDtipoejecucion  AS idTipoEjecucion,
                TA.Descripcion     AS tipoAccion,
                A.IDTipoaccion     AS idTipoAccion,
                A.LCL_Nombre       AS localidad,
                A.IDLocalidad      AS idLocalidad,
                C.CON_Contrato     AS contrato,
                A.IDContrato       AS idContrato
            FROM TblD_Acciones A
            LEFT JOIN TBLC_Tipoejecucion TE ON A.IDtipoejecucion = TE.IDtipoejecucion
            LEFT JOIN TblC_TipoAccion    TA ON A.IDTipoaccion    = TA.IDTipoaccion
            LEFT JOIN TBLD_Contrato       C ON A.IDContrato      = C.IDContrato
            WHERE A.IDobraproyecto = :idobra1
              AND (:anio1 = '' OR A.OP_Año = :anio2)
            ORDER BY A.IDAcciones DESC
        ";

        $lista = DB::select($sql, [
            'idobra1' => $idobra,
            'anio1' => $anio,
            'anio2' => $anio,
        ]);

        return response()->json($lista);
    }

    /**
     * GET /api/obras/getAccionById/{idAccion}
     * Precarga el formulario en modo edicion.
     */
    public function getAccionById($idAccion)
    {
        $data = DB::table('TblD_Acciones as A')
            ->select(
                'A.IDAcciones as idAccion',
                'A.OP_Año as anio',
                'A.AC_Accion as accion',
                'A.IDtipoejecucion as idTipoEjecucion',
                'A.IDTipoaccion as idTipoAccion',
                'A.IDLocalidad as idLocalidad',
                'A.IDSubRubroEspecifico as idSubrubroEspecifico',
                'A.Beneficiario as beneficiarios',
                'A.Tipobeneficiario as tipoBeneficiario',
                'A.Dictamenconfuente as dictamenFuente',
                'A.Autorizadasinrecurso as autorizado',
                'A.Liberada as liberada',
                'A.AC_Descripcionaccion as descripcionObra',
                'A.descripcionlocalidad as descripcionLocalidad',
                'A.finalidad',
                'A.funcion',
                'A.subfuncion',
                'A.programa',
                'A.subprograma',
                'A.proyecto'
            )
            ->where('A.IDAcciones', $idAccion)
            ->first();

        return response()->json($data);
    }

    /**
     * POST /api/obras/guardarAccion
     * Igual que guardarObra: un solo endpoint para crear y editar, la
     * variable payload.accion ('add' / cualquier otra cosa) decide el modo.
     *
     * DIFERENCIA vs guardarObra: aqui SI hay logica extra antes del
     * INSERT/UPDATE -- hay que garantizar que exista un registro en
     * TBLD_añoobraproyecto para el año de esta accion (si no existe, se
     * crea). Se replica tal cual la logica de tu C# original.
     */
    public function guardarAccion(Request $request)
    {
        $idobra = (int) $request->input('idobra');
        $anio = (int) $request->input('anio');
        $cveMun = $request->input('cveMunicipio', '061');

        // ── 1) Garantizar que exista TBLD_añoobraproyecto para este año ──
        $anioObra = DB::table('TBLD_añoobraproyecto')
            ->where('IDobraproyecto', $idobra)
            ->where('OP_Año', $anio)
            ->first();

        if ($anioObra) {
            $idAnioObra = $anioObra->IDañoobraproyecto;
        } else {
            // ¿Es continuidad? (la obra ya tenia algun año registrado antes)
            $esContinuidad = DB::table('TBLD_añoobraproyecto')
                ->where('IDobraproyecto', $idobra)
                ->exists();

            $idAnioObra = DB::table('TBLD_añoobraproyecto')->insertGetId([
                'IDobraproyecto' => $idobra,
                'OP_Año' => $anio,
                'OP_Continuidad' => $esContinuidad,
                'OP_InversionEstatal' => 0,
                'OP_InversionFederal' => 0,
                'OP_InversioMunicipal' => 0,
                'OP_Concepto' => null,
            ], 'IDañoobraproyecto');
        }

        // ── 2) Datos de localidad (para desnormalizar nombre/clave, igual que el original) ──
        $idLocalidad = (int) $request->input('idLocalidad', 0);
        $cveLocalidad = '';
        $nombreLocalidad = '';
        if ($idLocalidad > 0) {
            $loc = DB::table('TBLC_Localidades')
                ->select('CveLocalidades', 'LCL_Nombre')
                ->where('IDLocalidad', $idLocalidad)
                ->first();
            if ($loc) {
                $cveLocalidad = $loc->CveLocalidades ?? '';
                $nombreLocalidad = $loc->LCL_Nombre ?? '';
            }
        }

        $accionTexto = mb_strtoupper($request->input('accionTexto', ''));

        $campos = [
            'IDañoobraproyecto' => $idAnioObra,
            'OP_Año' => $anio,
            'AC_Accion' => $accionTexto,
            'IDtipoejecucion' => $request->input('idTipoEjecucion'),
            'Dictamenconfuente' => $request->boolean('dictamenFuente'),
            'Autorizadasinrecurso' => $request->boolean('autorizado'),
            'Liberada' => $request->boolean('liberada'),
            'Beneficiario' => $request->input('beneficiarios', 0),
            'Tipobeneficiario' => $request->input('tipoBeneficiario'),
            'IDTipoaccion' => $request->input('idTipoAccion'),
            'IDSubRubroEspecifico' => $request->input('idSubrubroEspecifico'),
            'IDLocalidad' => $idLocalidad,
            'LCL_Nombre' => $nombreLocalidad,
            'CveLocalidades' => $cveLocalidad,
            'AC_Descripcionaccion' => $request->input('descripcionObra'),
            'descripcionlocalidad' => $request->input('descripcionLocalidad'),
            'finalidad' => $request->input('finalidad'),
            'funcion' => $request->input('funcion'),
            'subfuncion' => $request->input('subfuncion'),
            'programa' => $request->input('programa'),
            'subprograma' => $request->input('subprograma'),
            'proyecto' => $request->input('proyecto'),
        ];

        if ($request->input('accion') === 'add') {
            $fecha = now();
            $id = DB::table('TblD_Acciones')->insertGetId(array_merge($campos, [
                'IDobraproyecto' => $idobra,
                'Meta' => 0,
                'IDunidadmedida' => 75,
                'IDTipoPoa' => 1,
                'Autorizadas' => 1,
                'Fecha' => $fecha,
                'Fechaaccion' => $fecha,
                'IDstatusaccion' => 1,
                'Metamodificada' => 0,
                'CveMunicipio' => $cveMun,
                'MNP_Nombre' => 'OCOZOCOAUTLA DE ESPINOSA',
                'IDstatusobra' => 12,
            ]), 'IDAcciones');

            return response()->json(['success' => true, 'id' => $id]);
        }

        $idAccion = (int) $request->input('idAccion');
        DB::table('TblD_Acciones')
            ->where('IDAcciones', $idAccion)
            ->update($campos);

        return response()->json(['success' => true]);
    }

    /**
     * GET /api/obras/getAniosAccion/{idobra}
     * Alimenta el filtro por año (que quedo oculto visualmente, pero el
     * elemento sigue existiendo en el DOM -- ver nota en la vista Blade).
     */
    public function getAniosAccion($idobra)
    {
        $lista = DB::table('TblD_Acciones')
            ->select('OP_Año as anio')
            ->where('IDobraproyecto', $idobra)
            ->distinct()
            ->orderBy('OP_Año', 'desc')
            ->get();

        return response()->json($lista);
    }

    /**
     * GET /api/obras/getTipoEjecucion
     */
    public function getTipoEjecucion()
    {
        $lista = DB::table('TBLC_Tipoejecucion')
            ->select('IDtipoejecucion as id', 'TPE_Nombre as nombre')
            ->orderBy('TPE_Nombre')
            ->get();

        return response()->json($lista);
    }

    /**
     * GET /api/obras/getTipoAccion
     */
    public function getTipoAccion()
    {
        $lista = DB::table('TblC_TipoAccion')
            ->select('IDTipoaccion as id', 'Descripcion as nombre')
            ->orderBy('Descripcion')
            ->get();

        return response()->json($lista);
    }

    /**
     * GET /api/obras/getLocalidades
     */
    public function getLocalidades()
    {
        $lista = DB::table('TBLC_Localidades')
            ->select('IDLocalidad as id', 'LCL_Nombre as nombre')
            ->where('CveMunicipio', '061')
            ->orderBy('LCL_Nombre')
            ->get();

        return response()->json($lista);
    }

    /**
     * GET /api/obras/getSubrubroEspecifico/{idobra}
     */
    public function getSubrubroEspecifico($idobra)
    {
        $idPrograma = DB::table('TBLP_Obraproyecto')
            ->where('IDobraproyecto', $idobra)
            ->value('IDPrograma') ?? 0;

        $lista = DB::table('TblC_Subrubroespecifico')
            ->select('IDSubRubroEspecifico as id', 'Descripcion as nombre')
            ->where('IDSubrubro', $idPrograma)
            ->orderBy('Descripcion')
            ->get();

        return response()->json($lista);
    }

    // ══════════════════════════════════════════════════════════════
    // ORIGEN DE INVERSIÓN (submodal dentro de Acciones)
    // ══════════════════════════════════════════════════════════════

    /**
     * GET /api/obras/getOrigenes/{idAccion}
     */
    public function getOrigenes($idAccion)
    {
        $sql = "
            SELECT F.IdFuenteinversion AS idFuente,
                   O.OF_Origenfuente   AS origen,
                   FF.PRF_Nombre       AS fuente,
                   F.IdOrigenfuente    AS idOrigen,
                   F.IDProgramafinanciamiento AS idFuenteFin,
                   F.FI_Inversion      AS inversion,
                   DATE_FORMAT(F.Fechavencimiento, '%d/%m/%Y') AS fechaVencimiento
            FROM TBLD_Financiamientoinversion F
            INNER JOIN TBLC_Origenfuente O ON F.IdOrigenfuente = O.IdOrigenfuente
            INNER JOIN TblC_FuenteFinanciamiento FF ON F.IDProgramafinanciamiento = FF.IDFuenteFinanciamiento
            WHERE F.IDAcciones = :id
        ";
        return response()->json(DB::select($sql, ['id' => $idAccion]));
    }

    /**
     * GET /api/obras/getOrigenById/{idFuente}
     *
     * NOTA -- corrige un bug real del C# original: ahi la misma columna
     * alias "idFuente" se usaba dos veces (IdFuenteinversion Y
     * IDProgramafinanciamiento), y la segunda pisaba a la primera.
     * Por casualidad eso "funcionaba" porque el JS solo necesitaba el
     * segundo valor -- pero es fragil y confuso. Aqui se usan 3 nombres
     * distintos y sin ambiguedad. Esto obliga a ajustar UNA linea del
     * JS (ver nota que te doy aparte).
     */
    public function getOrigenById($idFuente)
    {
        $data = DB::table('TBLD_Financiamientoinversion')
            ->select(
                'IdFuenteinversion as idRegistro',
                'IdOrigenfuente as idOrigen',
                'IDProgramafinanciamiento as idFuenteFinanciamiento',
                'FI_Inversion as inversion',
                DB::raw("DATE_FORMAT(Fechavencimiento, '%d/%m/%Y') as fechaVencimiento")
            )
            ->where('IdFuenteinversion', $idFuente)
            ->first();

        return response()->json($data);
    }

    /**
     * POST /api/obras/guardarOrigen
     */
    public function guardarOrigen(Request $request)
    {
        $idAccion = (int) $request->input('idAccion', 0);
        $idOrigen = (int) $request->input('idOrigen', 0);
        $idFuenteFin = (int) $request->input('idFuenteFinanciamiento', 0);
        $inversion = (float) $request->input('inversion', 0);
        $fVcto = $request->input('fechaVencimiento', '');

        // Convertir dd/mm/yyyy -> Y-m-d para MySQL
        $vctoMysql = null;
        if ($fVcto) {
            $partes = explode('/', $fVcto);
            if (count($partes) === 3) {
                $vctoMysql = "{$partes[2]}-{$partes[1]}-{$partes[0]}";
            }
        }

        if ($request->input('accion') === 'add') {
            $existe = DB::table('TBLD_Financiamientoinversion')
                ->where('IDAcciones', $idAccion)
                ->where('IDProgramafinanciamiento', $idFuenteFin)
                ->where('IdOrigenfuente', $idOrigen)
                ->exists();

            if ($existe) {
                return response()->json('Fuente de Financiamiento duplicada.', 400);
            }

            DB::table('TBLD_Financiamientoinversion')->insert([
                'IDAcciones' => $idAccion,
                'IDañoobraproyecto' => 0,
                'OP_Año' => 0,
                'IdOrigenfuente' => $idOrigen,
                'IDProgramafinanciamiento' => $idFuenteFin,
                'FI_Inversion' => $inversion,
                'Fechavencimiento' => $vctoMysql,
            ]);
        } else {
            $idRegistro = (int) $request->input('idFuente', 0);
            DB::table('TBLD_Financiamientoinversion')
                ->where('IdFuenteinversion', $idRegistro)
                ->update([
                    'IdOrigenfuente' => $idOrigen,
                    'IDProgramafinanciamiento' => $idFuenteFin,
                    'FI_Inversion' => $inversion,
                    'Fechavencimiento' => $vctoMysql,
                ]);
        }

        // Recalcular sumas municipal/estatal/federal para la obra+año de esta accion
        $accInfo = DB::table('TblD_Acciones')
            ->select('IDobraproyecto', 'OP_Año')
            ->where('IDAcciones', $idAccion)
            ->first();

        if ($accInfo) {
            $this->sumaInversion($accInfo->IDobraproyecto, $accInfo->OP_Año);
        }

        return response()->json(['success' => true]);
    }

    /**
     * DELETE /api/obras/eliminarOrigen/{idFuente}
     */
    public function eliminarOrigen($idFuente)
    {
        $tieneCOCI = DB::table('TblD_COCI')->where('IdFuenteinversion', $idFuente)->exists();
        if ($tieneCOCI) {
            return response()->json('No puede eliminar. Existe Clave Presupuestal (COCI) registrada.', 400);
        }

        $fi = DB::table('TBLD_Financiamientoinversion as F')
            ->join('TblD_Acciones as A', 'F.IDAcciones', '=', 'A.IDAcciones')
            ->select('F.IDAcciones', 'A.IDobraproyecto', 'A.OP_Año')
            ->where('F.IdFuenteinversion', $idFuente)
            ->first();

        DB::table('TBLD_Financiamientoinversion')->where('IdFuenteinversion', $idFuente)->delete();

        if ($fi) {
            $this->sumaInversion($fi->IDobraproyecto, $fi->OP_Año);
        }

        return response()->json(['success' => true]);
    }

    /**
     * GET /api/obras/getOrigenFuente
     */
    public function getOrigenFuente()
    {
        $lista = DB::table('TBLC_Origenfuente')
            ->select('IdOrigenfuente as id', 'OF_Origenfuente as nombre')
            ->where('OF_Origenfuente', 'NOT LIKE', '%(SELECCIONE)%')
            ->orderBy('OF_Origenfuente')
            ->get();

        return response()->json($lista);
    }

    /**
     * GET /api/obras/getFuenteFinanciamiento
     */
    public function getFuenteFinanciamiento()
    {
        $lista = DB::table('TblC_FuenteFinanciamiento')
            ->select('IDFuenteFinanciamiento as id', 'PRF_Nombre as nombre')
            ->where('PRF_Nombre', 'NOT LIKE', '%(SELECCIONE)%')
            ->orderBy('PRF_Nombre')
            ->get();

        return response()->json($lista);
    }

    /**
     * Recalcula OP_InversioMunicipal / OP_InversionEstatal / OP_InversionFederal
     * en TBLD_añoobraproyecto, sumando VW_Sumainversiones por origen
     * (1=Municipal, 2=Estatal, 3=Federal -- codigos fijos del catalogo
     * TBLC_Origenfuente, igual que en el C# original).
     */
    private function sumaInversion($idobra, $anio)
    {
        $sumaMun = DB::table('VW_Sumainversiones')
            ->where('IDobraproyecto', $idobra)->where('OP_Año', $anio)
            ->where('IdOrigenfuente', 1)->sum('FI_Inversion');

        $sumaEst = DB::table('VW_Sumainversiones')
            ->where('IDobraproyecto', $idobra)->where('OP_Año', $anio)
            ->where('IdOrigenfuente', 2)->sum('FI_Inversion');

        $sumaFed = DB::table('VW_Sumainversiones')
            ->where('IDobraproyecto', $idobra)->where('OP_Año', $anio)
            ->where('IdOrigenfuente', 3)->sum('FI_Inversion');

        DB::table('TBLD_añoobraproyecto')
            ->where('IDobraproyecto', $idobra)->where('OP_Año', $anio)
            ->update([
                'OP_InversioMunicipal' => $sumaMun,
                'OP_InversionEstatal' => $sumaEst,
                'OP_InversionFederal' => $sumaFed,
            ]);
    }

    // ══════════════════════════════════════════════════════════════
    // COCI (Costo Obra / Costo Indirecto) -- submodal dentro de Origenes
    // ══════════════════════════════════════════════════════════════

    /**
     * GET /api/obras/getCOCI?idFuente=..&status=CO|CI
     */
    public function getCOCI(Request $request)
    {
        $idFuente = $request->query('idFuente');
        $status = $request->query('status', 'CO');

        $lista = DB::table('TblD_COCI')
            ->select('IDcoci as idCoci', 'Cvepresupuestal as cvePresupuestal',
                     'Inversioncoci as inversion', 'Statuscoci as status')
            ->where('IdFuenteinversion', $idFuente)
            ->where('Statuscoci', $status)
            ->get();

        return response()->json($lista);
    }

    /**
     * GET /api/obras/getCOCIById/{idCoci}
     */
    public function getCOCIById($idCoci)
    {
        $data = DB::table('TblD_COCI')
            ->select('IDcoci as idCoci', 'Cvepresupuestal as cvePresupuestal',
                     'Inversioncoci as inversion', 'Statuscoci as status')
            ->where('IDcoci', $idCoci)
            ->first();

        return response()->json($data);
    }

    /**
     * POST /api/obras/guardarCOCI
     */
    public function guardarCOCI(Request $request)
    {
        $idFuente = (int) $request->input('idFuente', 0);
        $status = $request->input('status', 'CO');
        $cvePres = mb_strtoupper($request->input('cvePresupuestal', ''));
        $inversion = (float) $request->input('inversion', 0);

        if ($request->input('accion') === 'add') {
            $existe = DB::table('TblD_COCI')
                ->where('IdFuenteinversion', $idFuente)
                ->where('Cvepresupuestal', $cvePres)
                ->where('Statuscoci', $status)
                ->exists();

            if ($existe) {
                return response()->json('Clave Presupuestal duplicada.', 400);
            }

            DB::table('TblD_COCI')->insert([
                'IdFuenteinversion' => $idFuente,
                'Cvepresupuestal' => $cvePres,
                'Inversioncoci' => $inversion,
                'Statuscoci' => $status,
                'IDmovimiento' => 1,
            ]);
        } else {
            $idCoci = (int) $request->input('idCoci', 0);
            DB::table('TblD_COCI')
                ->where('IDcoci', $idCoci)
                ->update([
                    'Cvepresupuestal' => $cvePres,
                    'Inversioncoci' => $inversion,
                ]);
        }

        $this->sumaInversionCoci($idFuente);

        return response()->json(['success' => true]);
    }

    /**
     * DELETE /api/obras/eliminarCOCI/{idCoci}
     */
    public function eliminarCOCI($idCoci)
    {
        $idFuente = DB::table('TblD_COCI')->where('IDcoci', $idCoci)->value('IdFuenteinversion');

        DB::table('TblD_COCI')->where('IDcoci', $idCoci)->delete();

        if ($idFuente) {
            $this->sumaInversionCoci($idFuente);
        }

        return response()->json(['success' => true]);
    }

    /**
     * Replica SumaInversionCOCI() del C# original:
     * 1) Suma todos los COCI de esa fuente -> sobreescribe FI_Inversion
     *    en TBLD_Financiamientoinversion (el monto del Origen pasa a ser
     *    la suma de sus claves presupuestales).
     * 2) Dispara sumaInversion() para recalcular municipal/estatal/federal
     *    de la obra+año, igual que hace guardarOrigen().
     */
    private function sumaInversionCoci($idFuente)
    {
        $sumaTotal = DB::table('TblD_COCI')
            ->where('IdFuenteinversion', $idFuente)
            ->sum('Inversioncoci');

        DB::table('TBLD_Financiamientoinversion')
            ->where('IdFuenteinversion', $idFuente)
            ->update(['FI_Inversion' => $sumaTotal]);

        $fi = DB::table('TBLD_Financiamientoinversion as F')
            ->join('TblD_Acciones as A', 'F.IDAcciones', '=', 'A.IDAcciones')
            ->select('A.IDobraproyecto', 'A.OP_Año')
            ->where('F.IdFuenteinversion', $idFuente)
            ->first();

        if ($fi) {
            $this->sumaInversion($fi->IDobraproyecto, $fi->OP_Año);
        }
    }
}
