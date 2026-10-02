{{-- resources/views/encuestas/reporte_general.blade.php
     Reporte general de TODAS las encuestas de una acción: datos generales de la acción,
     folios relacionados y fotografías agrupadas en Inicio / En proceso / Conclusión. --}}
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Reporte general de encuestas</title>
    <style>
        body { font-family: Arial, Helvetica, sans-serif; font-size: 10.5px; color: #15151B; margin: 0; }
        table { width: 100%; border-collapse: collapse; }
        .encabezado td { vertical-align: middle; }
        .titulo { font-size: 17px; font-weight: bold; text-align: center; margin: 0; }
        .subtitulo { font-size: 11px; text-align: center; color: #555; margin-top: 3px; }
        .linea { height: 4px; background: #FDC703; margin-top: 8px; }
        .linea2 { height: 4px; background: #D60106; margin-bottom: 10px; }
        table.datos { margin-bottom: 10px; }
        table.datos td { border: 1px solid #8f8fa6; padding: 4px 6px; vertical-align: top; }
        table.datos td.label { background: #D6D6E0; font-weight: bold; width: 15%; }
        .resumen { background: #15151B; color: #fff; padding: 5px 8px; font-weight: bold; margin-bottom: 8px; }
        .grupo { padding: 6px 9px; font-size: 13px; font-weight: bold; margin-top: 14px; }
        .g-INICIO { background: #15151B; color: #fff; }
        .g-PROCESO { background: #FDC703; color: #15151B; }
        .g-CONCLUSION { background: #008028; color: #fff; }
        .encuesta-cab { background: #ECECF2; border: 1px solid #B4B4C4; border-left: 5px solid #D60106; padding: 5px 8px; margin-top: 8px; }
        .encuesta-cab b { color: #D60106; }
        table.fotos { margin: 4px 0 6px; }
        table.fotos td { width: 33.3%; border: 1px solid #B4B4C4; text-align: center; vertical-align: top; padding: 4px; }
        table.fotos tr { page-break-inside: avoid; }
        table.fotos img { max-width: 100%; max-height: 175px; }
        .vacio { color: #777; font-style: italic; padding: 6px 2px; }
        .pie { margin-top: 14px; font-size: 9px; color: #666; text-align: right; }
        .no-print { text-align: right; margin-bottom: 8px; }
        @media print { .no-print { display: none; } }
    </style>
</head>
<body>
@php
    $v = function ($x) { return ($x === null || $x === '') ? '-' : $x; };
@endphp

    <div class="no-print"><button onclick="window.print()">Imprimir</button></div>

    <table class="encabezado">
        <tr>
            <td style="width:150px;">@if($logo)<img src="{{ $logo }}" style="width:140px;" alt="Logo">@endif</td>
            <td>
                <p class="titulo">REPORTE GENERAL DE ENCUESTAS</p>
                <p class="subtitulo">Sistema de Verificación de Obras y Programas</p>
            </td>
        </tr>
    </table>
    <div class="linea"></div><div class="linea2"></div>

    {{-- ── Datos generales de la acción (los campos sombreados del formulario) ── --}}
    <table class="datos">
        <tr>
            <td class="label">Dependencia Ejecutora:</td><td>{{ $v($info['dependenciaEjecutora']) }}</td>
            <td class="label">Municipio:</td><td>{{ $v($info['municipio']) }}</td>
            <td class="label">Localidad:</td><td>{{ $v($info['localidad']) }}</td>
        </tr>
        <tr>
            <td class="label">Beneficiados:</td><td>{{ $v($info['beneficiados']) }}</td>
            <td class="label">Ejercicio:</td><td>{{ $v($info['ejercicio']) }}</td>
            <td class="label">Inversión Programada:</td><td>${{ number_format($info['inversionProgramada'], 2) }}</td>
        </tr>
        <tr>
            <td class="label">Programa:</td><td>{{ $v($info['programa']) }}</td>
            <td class="label">Latitud:</td><td>{{ $v($info['latitud']) }}</td>
            <td class="label">Longitud:</td><td>{{ $v($info['longitud']) }}</td>
        </tr>
        <tr>
            <td class="label">Nombre del Proyecto:</td><td colspan="5">{{ $v($info['nombreProyecto']) }}</td>
        </tr>
        <tr>
            <td class="label">Meta:</td><td colspan="5">{{ $v($info['meta']) }}</td>
        </tr>
        <tr>
            <td class="label">Fuentes de Financiamiento:</td><td colspan="5">{!! nl2br(e($v($info['fuentes']))) !!}</td>
        </tr>
        <tr>
            <td class="label">Folios Relacionados:</td><td colspan="5">{{ $foliosRelacionados }}</td>
        </tr>
    </table>

    <div class="resumen">
        Encuestas: {{ $totalEncuestas }} &nbsp;|&nbsp; Fotografías: {{ $totalFotos }}
    </div>

    {{-- ── Fotografías agrupadas: Inicio / En proceso / Conclusión ── --}}
    @foreach($grupos as $tipo => $lista)
        <div class="grupo g-{{ $tipo }}">
            {{ strtoupper($etiquetas[$tipo] ?? $tipo) }}
            <span style="font-weight:normal; font-size:11px;"> &mdash; {{ count($lista) }} {{ count($lista) === 1 ? 'encuesta' : 'encuestas' }}</span>
        </div>

        @forelse($lista as $e)
            <div class="encuesta-cab">
                <b>Folio:</b> {{ $e['folio'] }} &nbsp;&nbsp;
                <b>Fecha de visita:</b> {{ $e['fecha'] }} &nbsp;&nbsp;
                <b>Avance encontrado:</b> {{ $e['avance'] }} &nbsp;&nbsp;
                <b>Folios relacionados:</b> {{ $e['relacionados'] }}
            </div>

            @if(count($e['fotos']))
                <table class="fotos">
                    @foreach(array_chunk($e['fotos'], 3) as $fila)
                        <tr>
                            @foreach($fila as $f)
                                <td><strong>Foto {{ $f['pos'] }}</strong><br><img src="{{ $f['src'] }}" alt="Foto {{ $f['pos'] }}"></td>
                            @endforeach
                            @for($k = count($fila); $k < 3; $k++)
                                <td style="border:none;">&nbsp;</td>
                            @endfor
                        </tr>
                    @endforeach
                </table>
            @else
                <div class="vacio">Esta encuesta no tiene fotografías.</div>
            @endif
        @empty
            <div class="vacio">Sin encuestas de este tipo.</div>
        @endforelse
    @endforeach

    <div class="pie">Emitido el {{ $emitido }}</div>
</body>
</html>
