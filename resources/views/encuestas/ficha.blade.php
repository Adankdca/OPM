{{-- resources/views/encuestas/ficha.blade.php  (v2: ficha general, sigue el layout del formulario) --}}
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Ficha de Verificación {{ $encuesta->Folio ?? $encuesta->IDEncuesta }}</title>
    <style>
        body { font-family: Arial, Helvetica, sans-serif; font-size: 10.5px; color: #222; }
        h1 { font-size: 15px; text-align: center; margin: 0 0 2px; }
        h2 { font-size: 12px; text-align: center; margin: 0 0 12px; color: #444; }
        table { width: 100%; border-collapse: collapse; }
        table.datos { margin-bottom: 8px; }
        table.datos td { border: 1px solid #999; padding: 3px 5px; vertical-align: top; }
        table.datos td.label { background: #f0f0f0; font-weight: bold; width: 17%; }
        .seccion-title { background: #dfe9f5; font-weight: bold; padding: 3px 6px; margin: 10px 0 5px; border: 1px solid #99b3d1; }
        .bloque-title { background: #EE9D01; color: #fff; font-weight: bold; padding: 3px 6px; margin: 12px 0 5px; }
        table.preguntas { margin-bottom: 8px; }
        table.preguntas td, table.preguntas th { border: 1px solid #999; padding: 3px 5px; vertical-align: top; }
        table.preguntas th { background: #f0f0f0; text-align: left; }
        .chk { text-align: center; font-weight: bold; }
        .etq { color: #666; font-style: italic; }
        table.fotos td { width: 33%; border: 1px solid #999; text-align: center; padding: 4px; vertical-align: top; }
        table.fotos img { max-width: 100%; max-height: 190px; }
        .no-print { display: block; }
        @media print { .no-print { display: none; } }
    </style>
</head>
<body>
@php
    $fecha = $encuesta->FechaVisita ? \Carbon\Carbon::parse($encuesta->FechaVisita)->format('d/m/Y') : '-';
    $v = function ($x) { return ($x === null || $x === '') ? '-' : $x; };
@endphp

    <div class="no-print" style="text-align:right; margin-bottom:8px;">
        <button onclick="window.print()">Imprimir</button>
    </div>

    <table style="margin-bottom:0;">
        <tr>
            <td style="width:150px; vertical-align:middle;">@if($logo)<img src="{{ $logo }}" style="width:140px;" alt="Logo">@endif</td>
            <td style="vertical-align:middle;">
                <h1>FICHA DE VERIFICACIÓN</h1>
                <h2>ENCUESTA DE {{ strtoupper($tipoEncuesta) }}</h2>
            </td>
        </tr>
    </table>
    <div style="height:4px; background:#FDC703; margin-top:6px;"></div>
    <div style="height:4px; background:#D60106; margin-bottom:10px;"></div>

    {{-- ── Encabezado (mismo orden que el formulario) ── --}}
    <table class="datos">
        <tr>
            <td class="label">Folio:</td><td>{{ $v($encuesta->Folio) }}</td>
            <td class="label">Dependencia Ejecutora:</td><td>{{ $v($info['dependenciaEjecutora']) }}</td>
            <td class="label">Situación Reportada:</td><td>{{ $v($encuesta->situacionreportada) }}</td>
        </tr>
        <tr>
            <td class="label">Municipio:</td><td>{{ $v($info['municipio']) }}</td>
            <td class="label">Beneficiados:</td><td>{{ $v($info['beneficiados']) }}</td>
            <td class="label">Ejercicio:</td><td>{{ $v($info['ejercicio']) }}</td>
        </tr>
        <tr>
            <td class="label">Localidad:</td><td>{{ $v($info['localidad']) }}</td>
            <td class="label">Inversión Programada:</td><td>${{ number_format($info['inversionProgramada'], 2) }}</td>
            <td class="label">Fecha de Visita:</td><td>{{ $fecha }}</td>
        </tr>
        <tr>
            <td class="label">Programa:</td><td>{{ $v($info['programa']) }}</td>
            <td class="label">Avance Físico %:</td><td>{{ $encuesta->avancefisico !== null ? $encuesta->avancefisico . '%' : '-' }}</td>
            <td class="label">Folios Relacionados:</td><td>{{ $v($encuesta->foliosrelacionados) }}</td>
        </tr>
        <tr>
            <td class="label">Nombre del Proyecto:</td><td colspan="3">{{ $v($info['nombreProyecto']) }}</td>
            <td class="label">Meta:</td><td>{{ $v($info['meta']) }}</td>
        </tr>
        <tr>
            <td class="label">Fuentes de Financiamiento:</td><td colspan="3">{!! nl2br(e($v($info['fuentes']))) !!}</td>
            <td class="label">Latitud / Longitud:</td><td>{{ $v($info['latitud']) }} / {{ $v($info['longitud']) }}</td>
        </tr>
        <tr>
            <td class="label">Ubicación de la obra:</td><td colspan="3">{!! nl2br(e($v($encuesta->Direccion))) !!}</td>
            <td class="label">Avance encontrado:</td><td>{{ $encuesta->avanceencontrado !== null ? (int) $encuesta->avanceencontrado . '%' : '-' }}</td>
        </tr>
    </table>

    <div class="seccion-title">Situación Encontrada</div>
    <table class="datos">
        <tr>
            <td class="label">Situación:</td><td>{{ $v($situacion) }}</td>
            <td class="label">Sub-Situación:</td><td>{{ $v($subsituacion) }}</td>
        </tr>
        <tr>
            <td class="label">Observación de la situación encontrada:</td>
            <td colspan="3">{!! nl2br(e($v($encuesta->observacionessituacion))) !!}</td>
        </tr>
    </table>

    <div class="seccion-title">Datos de la persona que proporcionó información de la obra o acción</div>
    <table class="datos">
        <tr>
            <td class="label">Identificación Personal:</td><td>{{ $v($encuesta->identificacionpersonal) }}</td>
            <td class="label">Nombre:</td><td>{{ $v($encuesta->nombrecompletopersonal) }}</td>
        </tr>
        <tr>
            <td class="label">Cargo:</td><td>{{ $v($encuesta->cargopersonal) }}</td>
            <td class="label">Domicilio:</td><td>{{ $v($encuesta->domiciliopersonal) }}</td>
        </tr>
        <tr>
            <td class="label">Municipio:</td><td>{{ $v($municipioPersonal) }}</td>
            <td class="label">Localidad:</td><td>{{ $v($localidadPersonal) }}</td>
        </tr>
    </table>

    {{-- ── ENCUESTA 1 ── --}}
    <div class="bloque-title">ENCUESTA 1</div>
    @include('encuestas._preguntas', ['lista' => $preguntas1])

    <div class="seccion-title">Datos y comentarios del encuestador / verificador</div>
    <table class="datos">
        <tr><td class="label">Nombre:</td><td>{{ $v($encuesta->nombreencuestador) }}</td></tr>
        <tr><td class="label">Recomendaciones:</td><td>{!! nl2br(e($v($encuesta->recomendacionesencuestador))) !!}</td></tr>
        <tr><td class="label">Situación Encontrada:</td><td>{{ $v($situacionEncuestador) }}</td></tr>
        <tr><td class="label">Observaciones:</td><td>{!! nl2br(e($v($encuesta->observacionesencuestador))) !!}</td></tr>
    </table>

    <div class="seccion-title">Datos del Encuestado</div>
    <table class="datos">
        <tr>
            <td class="label">Identificación Personal:</td><td>{{ $v($encuesta->identificacionperencuestado) }}</td>
            <td class="label">Nombre:</td><td>{{ $v($encuesta->nombreencuestado) }}</td>
        </tr>
        <tr>
            <td class="label">Sexo:</td><td>{{ $v($encuesta->sexoencuestado) }}</td>
            <td class="label">Edad:</td><td>{{ $v($encuesta->edadencuestado) }}</td>
        </tr>
        <tr>
            <td class="label">Parentesco:</td><td>{{ $v($encuesta->parentescoencuestado) }}</td>
            <td class="label">Domicilio:</td><td>{{ $v($encuesta->domicilioencuestado) }}</td>
        </tr>
        <tr>
            <td class="label">Municipio:</td><td>{{ $v($municipioEncuestado) }}</td>
            <td class="label">Localidad:</td><td>{{ $v($localidadEncuestado) }}</td>
        </tr>
    </table>

    {{-- ── ENCUESTA 2 ── --}}
    <div class="bloque-title">ENCUESTA 2</div>
    @include('encuestas._preguntas', ['lista' => $preguntas2])

    <div class="seccion-title">Datos y comentarios del encuestador / verificador</div>
    <table class="datos">
        <tr><td class="label">Nombre:</td><td>{{ $v($encuesta->nombreencuestadorfinal) }}</td></tr>
        <tr><td class="label">Recomendaciones:</td><td>{!! nl2br(e($v($encuesta->recomendacionesencuestadorfinal))) !!}</td></tr>
        <tr><td class="label">Comentarios de la Dependencia Ejecutora:</td><td>{!! nl2br(e($v($encuesta->comentariofinal))) !!}</td></tr>
    </table>

    {{-- ── Evidencia fotográfica (hasta 5) ── --}}
    <div class="seccion-title">Evidencia fotográfica</div>
    @if(count($fotos))
    <table class="fotos">
        @foreach(array_chunk($fotos, 3) as $fila)
            <tr>
                @foreach($fila as $f)
                    <td><strong>Foto {{ $f['pos'] }}</strong><br><img src="{{ $f['src'] }}" alt="Foto {{ $f['pos'] }}"></td>
                @endforeach
                @for($k = count($fila); $k < 3; $k++)
                    <td>&nbsp;</td>
                @endfor
            </tr>
        @endforeach
    </table>
    @else
        <p style="color:#888;">Sin fotografías.</p>
    @endif

</body>
</html>
