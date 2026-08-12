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
}
