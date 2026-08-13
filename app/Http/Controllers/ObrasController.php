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
}
