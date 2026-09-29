{{-- resources/views/encuestas/ficha.blade.php --}}
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Ficha de Verificación #{{ $encuesta->IDEncuesta }}</title>
    <style>
        body { font-family: Arial, Helvetica, sans-serif; font-size: 11px; color: #222; }
        h1 { font-size: 15px; text-align: center; margin: 0 0 2px; }
        h2 { font-size: 12px; text-align: center; margin: 0 0 14px; color: #444; }
        table.datos { width: 100%; border-collapse: collapse; margin-bottom: 10px; }
        table.datos td { border: 1px solid #999; padding: 4px 6px; vertical-align: top; }
        table.datos td.label { background: #f0f0f0; font-weight: bold; width: 22%; }
        .seccion-title { background: #dfe9f5; font-weight: bold; padding: 4px 6px; margin: 12px 0 6px; border: 1px solid #99b3d1; }
        table.preguntas { width: 100%; border-collapse: collapse; margin-bottom: 10px; }
        table.preguntas td, table.preguntas th { border: 1px solid #999; padding: 4px 6px; }
        table.preguntas th { background: #f0f0f0; text-align: left; }
        .chk { text-align: center; font-weight: bold; }
        .no-print { display: block; }
        @media print { .no-print { display: none; } }
    </style>
</head>
<body>

    <div class="no-print" style="text-align:right; margin-bottom:8px;">
        <button onclick="window.print()">Imprimir</button>
    </div>

    <h1>GOBIERNO DEL ESTADO DE CHIAPAS</h1>
    <h2>FICHA DE VERIFICACIÓN — {{ strtoupper($tipoOrigen) }}</h2>

    <table class="datos">
        <tr>
            <td class="label">Folio:</td><td>{{ $encuesta->Folio ?? $encuesta->IDEncuesta }}</td>
            <td class="label">Fecha Visita:</td><td>{{ $encuesta->FechaVisita }}</td>
        </tr>
        <tr>
            {{-- Punto 1 y 2: Dependencia Ejecutora -- viene de TBLC_Area.Area_Nombre,
                 ya seleccionada en datosFicha() como $accion->dependenciaEjecutora.
                 Si sale en blanco, revisa que la Obra tenga Área asignada en su
                 formulario -- no es falta de este campo, es dato de origen vacío. --}}
            <td class="label">Dependencia Ejecutora:</td><td colspan="3">{{ $accion->dependenciaEjecutora ?? '-' }}</td>
        </tr>
        <tr>
            <td class="label">Programa:</td><td colspan="3">{{ $accion->programa }}</td>
        </tr>
        <tr>
            <td class="label">Nombre del Proyecto:</td><td colspan="3">{{ $accion->nombreObra }}</td>
        </tr>

        @if($tipoOrigen === 'Programa')
        {{-- Punto 2: Dirección del proyecto -- solo aplica a fichas de Programa,
             tal como en tu PDF de ejemplo (ficha_programa_descarga.pdf) --}}
        <tr>
            <td class="label">Dirección:</td><td colspan="3">{{ $encuesta->Direccion ?? '-' }}</td>
        </tr>
        @endif

        <tr>
            <td class="label">Municipio:</td><td>{{ $municipio }}</td>
            <td class="label">Localidad:</td><td>{{ $accion->LCL_Nombre }}</td>
        </tr>
        <tr>
            <td class="label">Ejercicio:</td><td>{{ $accion->OP_Año }}</td>
            <td class="label">No. Contrato:</td><td>{{ $accion->noContrato ?? '-' }}</td>
        </tr>
        <tr>
            {{-- Punto 1 y 2: Meta -- viene directo de TblD_Acciones.Meta
                 ($accion->Meta, ya incluida en el SELECT 'A.*' de datosFicha()).
                 OJO: si siempre sale "0", es porque guardarAccion() todavía
                 guarda Meta en 0 fijo al crear la acción -- revisa si quieres
                 agregar un campo editable de Meta en el formulario de Acción;
                 no es un problema de la ficha, es que nunca se captura otro valor. --}}
            <td class="label">Meta:</td><td>{{ $accion->Meta }}</td>
            <td class="label">Beneficiarios:</td>
            <td>{{ $encuesta->Beneficiarios ?? $accion->Beneficiario }} ({{ $encuesta->TipoBeneficiario ?? $accion->Tipobeneficiario }})</td>
        </tr>
        <tr>
            <td class="label">Inversión Programada:</td>
            <td colspan="3">${{ number_format($encuesta->InversionProgramada ?? 0, 2) }}</td>
        </tr>
        <tr>
            <td class="label">Avance Físico:</td><td>{{ $encuesta->PorcentajeAvance }}%</td>
            <td class="label">Situación Reportada:</td><td>{{ $estatus }}</td>
        </tr>
        <tr>
            <td class="label">Situación Encontrada:</td><td colspan="3">{{ $situacion ?? '-' }}</td>
        </tr>
        <tr>
            {{-- Punto 5: Detalle Situación Encontrada -- texto libre, aplica a
                 ambos tipos (Obra y Programa) --}}
            <td class="label">Detalle Situación Encontrada:</td>
            <td colspan="3">{{ $encuesta->DetalleSituacionEncontrada ?? '-' }}</td>
        </tr>
        <tr>
            <td class="label">Recomendación:</td><td colspan="3">{{ $encuesta->Recomendacion }}</td>
        </tr>
    </table>

    <div class="seccion-title">Datos de la persona que proporcionó información de la obra o acción</div>
    <table class="datos">
        <tr>
            <td class="label">Nombre:</td><td>{{ $encuesta->InformanteNombre }}</td>
            <td class="label">Domicilio:</td><td>{{ $encuesta->InformanteDomicilio }}</td>
        </tr>
        @if($tipoOrigen === 'Programa')
        <tr>
            <td class="label">Sexo:</td><td>{{ $encuesta->InformanteSexo === 'M' ? 'Masculino' : ($encuesta->InformanteSexo === 'F' ? 'Femenino' : '-') }}</td>
            <td class="label">Edad:</td><td>{{ $encuesta->InformanteEdad }}</td>
        </tr>
        @endif
    </table>

    <div class="seccion-title">Cuestionario para verificar {{ $tipoOrigen === 'Programa' ? 'programas' : 'obras' }}</div>
    <table class="preguntas">
        <thead>
            <tr>
                <th style="width:55%;">Pregunta</th>
                <th style="width:15%;">Respuesta</th>
                <th>¿Por qué? / Comentario</th>
            </tr>
        </thead>
        <tbody>
            @foreach($preguntas as $i => $p)
                @php $r = $respuestas[$p->IDPregunta] ?? null; @endphp
                <tr>
                    <td>{{ $i + 1 }}. {{ $p->Texto }}</td>
                    <td class="chk">{{ $r->Respuesta ?? '-' }}</td>
                    <td>{{ $r->Justificacion ?? '-' }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <div class="seccion-title">Datos y comentarios del encuestador/verificador</div>
    <table class="datos">
        <tr>
            {{-- Punto 6: Organismo Público -- aplica a ambos tipos --}}
            <td class="label">Organismo Público:</td><td colspan="3">{{ $encuesta->OrganismoPublico ?? '-' }}</td>
        </tr>
        <tr>
            <td class="label">Encuestador:</td><td colspan="3">{{ $encuesta->NombreEncuestador }}</td>
        </tr>
        <tr>
            <td class="label">Comentarios:</td><td colspan="3">{{ $encuesta->Observaciones }}</td>
        </tr>
    </table>

</body>
</html>
