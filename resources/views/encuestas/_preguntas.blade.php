{{-- Tabla de preguntas de un bloque. Recibe $lista (preguntas) y usa $respuestas (keyBy IDPregunta) --}}
<table class="preguntas">
    <thead>
        <tr>
            <th style="width:50%;">Pregunta</th>
            <th style="width:16%;">Respuesta</th>
            <th>Por qué / Cuál</th>
        </tr>
    </thead>
    <tbody>
        @foreach($lista as $p)
            @php $r = $respuestas[$p->IDPregunta] ?? null; @endphp
            <tr>
                <td>{{ $p->Orden }}. {{ $p->Texto }}</td>
                <td class="chk">
                    @if($p->TipoRespuesta === 'HOMBRES_MUJERES')
                        H: {{ $r->RespuestaHombres ?? '-' }} &nbsp; M: {{ $r->RespuestaMujeres ?? '-' }}
                    @else
                        {{ ($r && $r->Respuesta !== null && $r->Respuesta !== '') ? $r->Respuesta : '-' }}
                    @endif
                </td>
                <td>
                    @if($p->RequiereJustificacion)
                        <span class="etq">{{ $p->EtiquetaJustificacion }}</span> {{ $r->Justificacion ?? '-' }}
                    @else
                        &nbsp;
                    @endif
                </td>
            </tr>
        @endforeach
    </tbody>
</table>
