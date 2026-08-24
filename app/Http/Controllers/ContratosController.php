<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

/**
 * Equivalente a Ceduver.Controllers.ContratosController (ApiController de .NET).
 *
 * Igual que EncuestasController, este modulo maneja ARCHIVOS (el contrato
 * adjunto), asi que guardarContrato() recibe FormData (multipart), no JSON
 * -- por eso Contratos.js usa processData:false / contentType:false en el
 * guardado, exactamente igual que Encuestas.js.
 */
class ContratosController extends Controller
{
    // Extensiones permitidas para el archivo del contrato (igual que el .NET original)
    private const EXTENSIONES_PERMITIDAS = 'pdf,doc,docx,xls,xlsx,jpg,jpeg,png,gif,bmp';

    // ══════════════════════════════════════════════════════════════════
    // GET CONTRATOS
    // ══════════════════════════════════════════════════════════════════

    /**
     * GET /api/contratos/getContratos?contrato=&idContratista=&idTipoOrden=
     */
    public function getContratos(Request $request)
    {
        $contrato = $request->query('contrato', '');
        $idContratista = $request->query('idContratista', '');
        $idTipoOrden = $request->query('idTipoOrden', '');

        $query = DB::table('TBLD_Contrato as C')
            ->leftJoin('TBLC_Contratistas as CT', 'C.IDContratista', '=', 'CT.IDContratista')
            ->select(
                'C.IDContrato as idContrato',
                'C.CON_Contrato as numContrato',
                'C.CON_Descripcion as descripcion',
                'CT.CTS_Nombre as contratista',
                'C.IDContratista as idContratista',
                DB::raw("DATE_FORMAT(C.CON_Inicio, '%d/%m/%Y') as fechaInicio"),
                DB::raw("DATE_FORMAT(C.CON_Final, '%d/%m/%Y') as fechaTermino"),
                DB::raw("DATE_FORMAT(C.CON_Firma, '%d/%m/%Y') as fechaFirma"),
                'C.CON_Montocontratado as montoContratado',
                'C.CON_montoanticipo as montoAnticipo',
                'C.AvanceFinanciero as avanceFinanciero',
                'C.IDTipocontrato as idTipoContrato',
                'C.IDConceptocontratado as idConceptoContratado',
                'C.IDTipoorden as idTipoOrden',
                'C.CON_RutaContrato as rutaArchivo'
            );

        if ($contrato !== '') {
            $query->where('C.CON_Contrato', 'like', '%' . $contrato . '%');
        }
        if ($idContratista !== '') {
            $query->where('C.IDContratista', $idContratista);
        }
        if ($idTipoOrden !== '') {
            $query->where('C.IDTipoorden', $idTipoOrden);
        }

        $lista = $query->orderBy('C.IDContrato', 'desc')->get();

        return response()->json($lista);
    }

    /**
     * GET /api/contratos/getContratoById/{idContrato}
     */
    public function getContratoById($idContrato)
    {
        $c = DB::table('TBLD_Contrato as C')
            ->select(
                'C.IDContrato as idContrato',
                'C.CON_Contrato as numContrato',
                'C.CON_Descripcion as descripcion',
                'C.IDContratista as idContratista',
                DB::raw("DATE_FORMAT(C.CON_Firma, '%d/%m/%Y') as fechaFirma"),
                DB::raw("DATE_FORMAT(C.CON_Inicio, '%d/%m/%Y') as fechaInicio"),
                DB::raw("DATE_FORMAT(C.CON_Final, '%d/%m/%Y') as fechaTermino"),
                'C.CON_Montocontratado as montoContratado',
                'C.CON_montoanticipo as montoAnticipo',
                'C.AvanceFinanciero as avanceFinanciero',
                'C.IDTipocontrato as idTipoContrato',
                'C.IDConceptocontratado as idConceptoContratado',
                'C.IDTipoorden as idTipoOrden',
                'C.CON_RutaContrato as rutaArchivo'
            )
            ->where('C.IDContrato', $idContrato)
            ->first();

        return response()->json($c);
    }

    // ══════════════════════════════════════════════════════════════════
    // GUARDAR (INSERT + UPDATE + historial + archivo)
    // ══════════════════════════════════════════════════════════════════

    /**
     * POST /api/contratos/guardarContrato
     * Recibe FormData (no JSON), porque puede traer el archivo del contrato.
     */
    public function guardarContrato(Request $request)
    {
        $request->validate([
            'Archivo' => 'nullable|file|mimes:' . self::EXTENSIONES_PERMITIDAS . '|max:15360', // 15 MB
        ], [
            'Archivo.mimes' => 'Formato de archivo no permitido.',
            'Archivo.max' => 'El archivo no debe pesar más de 15 MB.',
        ]);

        $accion = $request->input('Accion', 'add');
        $idContrato = $accion === 'edit' ? (int) $request->input('IdContrato', 0) : 0;

        $numContrato = mb_strtoupper($request->input('NumContrato', ''));
        $descripcion = mb_strtoupper($request->input('Descripcion', ''));
        $monto = $this->parseDecimal($request->input('MontoContratado'));
        $anticipo = $this->parseDecimal($request->input('MontoAnticipo'));
        $avance = $this->parseDecimal($request->input('AvanceFinanciero'));
        $idContratista = (int) $request->input('IdContratista', 0);
        $idTipoOrden = (int) $request->input('IdTipoOrden', 0);
        $idTipoContrato = (int) $request->input('IdTipoContrato', 0);
        $idConcepto = (int) $request->input('IdConceptoContratado', 0);

        $fechaFirma = $this->parseFecha($request->input('FechaFirma'));
        $fechaInicio = $this->parseFecha($request->input('FechaInicio'));
        $fechaTermino = $this->parseFecha($request->input('FechaTermino'));

        // Validar fechas (igual que el original)
        if ($fechaTermino->lt($fechaInicio)) {
            return response('La Fecha de Término debe ser mayor que la Fecha de Inicio.', 422);
        }

        // ── Manejo de archivo ──
        // Antes de tener IDContrato (alta nueva) el archivo se guarda temporalmente
        // en "contratos/nuevo" y se mueve a "contratos/{id}" despues del INSERT --
        // exactamente igual que el original hacia con la carpeta física "CONTRATOS/nuevo".
        $rutaArchivo = $request->input('RutaArchivoActual', '');
        if ($request->hasFile('Archivo')) {
            $carpeta = 'contratos/' . ($idContrato > 0 ? $idContrato : 'nuevo');
            $rutaArchivo = $request->file('Archivo')->storeAs(
                $carpeta,
                $request->file('Archivo')->getClientOriginalName(),
                'uploads'
            );
        }

        if ($accion === 'add') {
            // Historial del alta
            $this->guardarHistorial([
                'idContratista' => $idContratista,
                'numContrato' => $numContrato,
                'descripcion' => $descripcion,
                'fechaFirma' => $fechaFirma,
                'fechaInicio' => $fechaInicio,
                'fechaTermino' => $fechaTermino,
                'montoContratado' => $monto,
                'montoAnticipo' => $anticipo,
                'rutaArchivo' => $rutaArchivo,
                'idTipoContrato' => $idTipoContrato,
                'idConceptoContratado' => $idConcepto,
                'idTipoOrden' => $idTipoOrden,
            ], 'AGREGADO');

            $newId = DB::table('TBLD_Contrato')->insertGetId([
                'IDObraproyecto' => 0,
                'IDContratista' => $idContratista,
                'IDArea' => 0,
                'CON_Contrato' => $numContrato,
                'CON_Firma' => $fechaFirma,
                'CON_Descripcion' => $descripcion,
                'CON_Inicio' => $fechaInicio,
                'CON_Final' => $fechaTermino,
                'CON_Montocontratado' => $monto,
                'CON_NoEstimacion' => 0,
                'CON_montoanticipo' => $anticipo,
                'CON_Diacorte' => 0,
                'CON_RutaContrato' => $rutaArchivo,
                'IDTipocontrato' => $idTipoContrato,
                'IDConceptocontratado' => $idConcepto,
                'CON_Porcentaje' => 0,
                'FechaAlta' => now(),
                'IDTipoorden' => $idTipoOrden,
                'ClasificacionProyecto' => 'contratos',
                'AvanceFinanciero' => $avance,
            ], 'IDContrato');

            // Si el archivo se guardo en la carpeta temporal "nuevo", moverlo
            // ahora que ya tenemos el IDContrato definitivo.
            if ($rutaArchivo && str_contains($rutaArchivo, '/nuevo/') && $newId > 0) {
                $nombreArchivo = basename($rutaArchivo);
                $rutaDestino = 'contratos/' . $newId . '/' . $nombreArchivo;
                Storage::disk('uploads')->move($rutaArchivo, $rutaDestino);

                DB::table('TBLD_Contrato')
                    ->where('IDContrato', $newId)
                    ->update(['CON_RutaContrato' => $rutaDestino]);
            }

            return response()->json(['success' => true, 'idContrato' => $newId]);
        }

        // ── Edicion ──
        $anterior = DB::table('TBLD_Contrato')->where('IDContrato', $idContrato)->first();
        if ($anterior) {
            $this->guardarHistorial($this->filaAHistorial($anterior), 'MODIFICADO');
        }

        DB::table('TBLD_Contrato')
            ->where('IDContrato', $idContrato)
            ->update([
                'IDContratista' => $idContratista,
                'IDArea' => 0,
                'CON_Contrato' => $numContrato,
                'CON_Firma' => $fechaFirma,
                'CON_Descripcion' => $descripcion,
                'CON_Inicio' => $fechaInicio,
                'CON_Final' => $fechaTermino,
                'CON_Montocontratado' => $monto,
                'CON_NoEstimacion' => 0,
                'CON_montoanticipo' => $anticipo,
                'CON_Diacorte' => 0,
                'CON_RutaContrato' => $rutaArchivo,
                'IDTipocontrato' => $idTipoContrato,
                'IDConceptocontratado' => $idConcepto,
                'CON_Porcentaje' => 0,
                'IDTipoorden' => $idTipoOrden,
                'AvanceFinanciero' => $avance,
            ]);

        return response()->json(['success' => true, 'idContrato' => $idContrato]);
    }

    // ══════════════════════════════════════════════════════════════════
    // ELIMINAR
    // ══════════════════════════════════════════════════════════════════

    /**
     * DELETE /api/contratos/eliminarContrato/{idContrato}
     */
    public function eliminarContrato($idContrato)
    {
        // Validar que no tenga estimaciones asignadas
        $tieneEstimaciones = DB::table('TblP_Estimacion')
            ->where('IDContratoObra', $idContrato)
            ->count();

        if ($tieneEstimaciones > 0) {
            return response('Imposible eliminar. El contrato tiene estimaciones asignadas.', 422);
        }

        $c = DB::table('TBLD_Contrato')->where('IDContrato', $idContrato)->first();
        if ($c) {
            $this->guardarHistorial($this->filaAHistorial($c), 'ELIMINADO');
        }

        // Desasignar el contrato de cualquier accion que lo tuviera
        DB::table('TblD_Acciones')
            ->where('IDContrato', $idContrato)
            ->update(['IDContrato' => 0]);

        DB::table('TBLD_Contrato')->where('IDContrato', $idContrato)->delete();

        return response()->json(['success' => true]);
    }

    // ══════════════════════════════════════════════════════════════════
    // DESCARGAR ARCHIVO
    // ══════════════════════════════════════════════════════════════════

    /**
     * GET /api/contratos/descargarArchivo?ruta=contratos/12/archivo.pdf
     */
    public function descargarArchivo(Request $request)
    {
        $ruta = $request->query('ruta', '');

        if (! $ruta || ! Storage::disk('uploads')->exists($ruta)) {
            return response('Archivo no encontrado.', 404);
        }

        return Storage::disk('uploads')->download($ruta, basename($ruta));
    }

    // ══════════════════════════════════════════════════════════════════
    // CATÁLOGOS
    // ══════════════════════════════════════════════════════════════════

    public function getContratistas()
    {
        $lista = DB::table('TBLC_Contratistas')
            ->select('IDContratista as id', 'CTS_Nombre as nombre')
            ->where('CTS_Nombre', 'not like', '%(SELECCIONE)%')
            ->orderBy('CTS_Nombre')
            ->get();

        return response()->json($lista);
    }

    public function getTipoOrden()
    {
        $lista = DB::table('TblC_TipoOrden')
            ->select('IDTipoorden as id', 'Descripcion as nombre')
            ->where('Descripcion', 'not like', '%(SELECCIONE)%')
            ->orderBy('Descripcion')
            ->get();

        return response()->json($lista);
    }

    public function getTipoContrato()
    {
        $lista = DB::table('TblC_Tipocontrato')
            ->select('IDTipocontrato as id', 'Nombretipocontrato as nombre')
            ->where('Nombretipocontrato', 'not like', '%(SELECCIONE)%')
            ->orderBy('Nombretipocontrato')
            ->get();

        return response()->json($lista);
    }

    public function getConceptoContratado()
    {
        $lista = DB::table('tblc_conceptocontratado')
            ->select('idconceptocontratado as id', 'CCT_Nombre as nombre')
            ->where('CCT_Nombre', 'not like', '%(SELECCIONE)%')
            ->orderBy('CCT_Nombre')
            ->get();

        return response()->json($lista);
    }

    // ══════════════════════════════════════════════════════════════════
    // MÉTODOS PRIVADOS
    // ══════════════════════════════════════════════════════════════════

    /**
     * Convierte una fila actual de TBLD_Contrato al arreglo que
     * guardarHistorial() espera, para MODIFICADO/ELIMINADO.
     */
    private function filaAHistorial($fila): array
    {
        return [
            'idContratista' => (int) ($fila->IDContratista ?? 0),
            'numContrato' => $fila->CON_Contrato ?? '',
            'descripcion' => $fila->CON_Descripcion ?? '',
            'fechaFirma' => $fila->CON_Firma ?? null,
            'fechaInicio' => $fila->CON_Inicio ?? null,
            'fechaTermino' => $fila->CON_Final ?? null,
            'montoContratado' => (float) ($fila->CON_Montocontratado ?? 0),
            'montoAnticipo' => (float) ($fila->CON_montoanticipo ?? 0),
            'rutaArchivo' => $fila->CON_RutaContrato ?? '',
            'idTipoContrato' => (int) ($fila->IDTipocontrato ?? 0),
            'idConceptoContratado' => (int) ($fila->IDConceptocontratado ?? 0),
            'idTipoOrden' => (int) ($fila->IDTipoorden ?? 0),
        ];
    }

    /**
     * Inserta un renglon en TBLH_Contrato (bitacora de alta/baja/cambios).
     */
    private function guardarHistorial(array $datos, string $accion): void
    {
        $hoy = now();

        DB::table('TBLH_Contrato')->insert([
            'IDObraproyecto' => 0,
            'IDContratista' => $datos['idContratista'] ?? 0,
            'IDArea' => 0,
            'CON_Contrato' => $datos['numContrato'] ?? '',
            'CON_Firma' => $datos['fechaFirma'] ?? null,
            'CON_Descripcion' => $datos['descripcion'] ?? '',
            'CON_Inicio' => $datos['fechaInicio'] ?? null,
            'CON_Final' => $datos['fechaTermino'] ?? null,
            'CON_Montocontratado' => $datos['montoContratado'] ?? 0,
            'CON_NoEstimacion' => 0,
            'CON_montoanticipo' => $datos['montoAnticipo'] ?? 0,
            'CON_Diacorte' => 0,
            'CON_RutaContrato' => $datos['rutaArchivo'] ?? '',
            'IDTipocontrato' => $datos['idTipoContrato'] ?? 0,
            'IDConceptocontratado' => $datos['idConceptoContratado'] ?? 0,
            'CON_Porcentaje' => 0,
            'FechaAlta' => $hoy,
            'IDTipoorden' => $datos['idTipoOrden'] ?? 0,
            'ClasificacionProyecto' => 'contratos',
            'FechaAccion' => $hoy,
            'Accion' => $accion,
        ]);
    }

    private function parseDecimal($val): float
    {
        if ($val === null || $val === '') {
            return 0;
        }
        $val = str_replace(',', '', (string) $val);

        return is_numeric($val) ? (float) $val : 0;
    }

    /**
     * Convierte "dd/mm/yyyy" a Carbon. Si viene vacío o mal formado,
     * regresa 2000-01-01 -- igual que el DateTime por default del original.
     */
    private function parseFecha(?string $val): Carbon
    {
        if (! $val) {
            return Carbon::createFromDate(2000, 1, 1);
        }
        try {
            return Carbon::createFromFormat('d/m/Y', $val)->startOfDay();
        } catch (\Exception $e) {
            return Carbon::createFromDate(2000, 1, 1);
        }
    }
}
