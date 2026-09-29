// ════════════════════════════════════════════════════════════════════════
// EncuestasModule -- Encuestas de Seguimiento a nivel ACCIÓN
// Sigue el mismo patrón/estilo que ya usa ObrasModule en Obras.js (mismo
// jQuery llano, mismos helpers de Swal, mismo esquema panel-por-panel).
// ════════════════════════════════════════════════════════════════════════
var EncuestasModule = (function () {

    var API = 'api/Encuestas/';
    var preguntasActuales = []; // cuestionario cargado según el tipo de la acción

    // ── Abrir el modal para una Acción concreta ─────────────────────────
    // idAccion: PK de TblD_Acciones
    // idTipoOrigen: 1 = Obra, 2 = Programa (viene del listado de Acciones)
    // nombreAccion: solo para el encabezado del modal
    var verEncuestas = function (idAccion, idTipoOrigen, nombreAccion) {
        $('#hddIdAccionEncuestas').val(idAccion);
        $('#hddIdTipoOrigenEncuestas').val(idTipoOrigen);
        $('#lblAccionEncuestas').text(nombreAccion || '');

        mostrarPanelEncuestas('grid');
        cargarCatalogos();
        cargarTablaEncuestas(idAccion);
        $('#modalEncuestas').modal('show');
    };

    var mostrarPanelEncuestas = function (panel) {
        $('#panelGridEncuestas, #panelFormEncuesta').hide();
        if (panel === 'grid') $('#panelGridEncuestas').show();
        if (panel === 'form') $('#panelFormEncuesta').show();
    };

    var cargarCatalogos = function () {
        $.get(API + 'getEstatusEncuesta', function (res) {
            $('#cboEstatusEncuesta').empty().append('<option value="">(SELECCIONE)</option>');
            res.forEach(function (i) {
                $('#cboEstatusEncuesta').append('<option value="' + i.id + '">' + i.nombre + '</option>');
            });
        });
        $.get(API + 'getSituacionEncontrada', function (res) {
            $('#cboSituacionEncontrada').empty().append('<option value="">(SELECCIONE)</option>');
            res.forEach(function (i) {
                $('#cboSituacionEncontrada').append('<option value="' + i.id + '">' + i.nombre + '</option>');
            });
        });
    };

    // Traduce idTipoOrigen (1/2) a texto + color de badge -- se usa tanto
    // en la bitácora de Encuestas como en cualquier otro lugar que necesite
    // pintar Obra/Programa sin volver a pedirlo al catálogo cada vez.
    var badgeOrigen = function (idTipoOrigen) {
        var esPrograma = String(idTipoOrigen) === '2';
        var texto = esPrograma ? 'Programa' : 'Obra';
        var clase = esPrograma ? 'badge-info' : 'badge-success';
        return '<span class="badge ' + clase + '">' + texto + '</span>';
    };

    // ── Grid / bitácora ──────────────────────────────────────────────────
    var cargarTablaEncuestas = function (idAccion) {
        $('#tbodyEncuestas').html('<tr><td colspan="8" class="text-center py-3">' +
            '<i class="fas fa-spinner fa-spin text-warning"></i> Cargando...</td></tr>');

        $.get(API + 'getEncuestas/' + idAccion, function (data) {
            var $tbody = $('#tbodyEncuestas').empty();
            $('#badgeTotalEncuestas').text(data.length);

            if (!data.length) {
                $tbody.html('<tr><td colspan="8" class="text-center text-muted py-4">' +
                    'Sin encuestas registradas</td></tr>');
                return;
            }

            data.forEach(function (e) {
                $tbody.append(
                    '<tr>' +
                    '<td class="small">' + (e.fechaVisita || '') + '</td>' +
                    '<td><span class="badge badge-light-info">' + (e.avance || 0) + '%</span></td>' +
                    '<td class="small">' + (e.estatus || '') + '</td>' +
                    '<td>' + badgeOrigen(e.idTipoOrigen) + '</td>' +
                    '<td class="small">' + (e.encuestador || '') + '</td>' +
                    '<td class="text-center">' + (e.numFotos || 0) + '</td>' +
                    '<td class="text-center">' + (e.numDocumentos || 0) + '</td>' +
                    '<td class="text-center" style="white-space:nowrap;">' +
                    '<button type="button" class="btn btn-xs btn-icon btn-light-primary mr-1" ' +
                    'onclick="EncuestasModule.editarEncuesta(' + e.idEncuesta + ')" title="Modificar">' +
                    '<i class="fas fa-edit"></i></button>' +
                    '<button type="button" class="btn btn-xs btn-icon btn-light-info mr-1" ' +
                    'onclick="EncuestasModule.imprimirFicha(' + e.idEncuesta + ')" title="Ver / Imprimir Ficha">' +
                    '<i class="fas fa-print"></i></button>' +
                    '<button type="button" class="btn btn-xs btn-icon btn-light-success mr-1" ' +
                    'onclick="EncuestasModule.descargarPdf(' + e.idEncuesta + ')" title="Descargar PDF">' +
                    '<i class="fas fa-file-pdf"></i></button>' +
                    '<button type="button" class="btn btn-xs btn-icon btn-light-danger" ' +
                    'onclick="EncuestasModule.eliminarEncuesta(' + e.idEncuesta + ')" title="Eliminar">' +
                    '<i class="fas fa-trash"></i></button>' +
                    '</td></tr>'
                );
            });
        });
    };

    // ── Cuestionario dinámico (según Obra/Programa de la Acción) ───────
    var renderCuestionario = function (preguntas, respuestasPrevias) {
        preguntasActuales = preguntas;
        respuestasPrevias = respuestasPrevias || {}; // {idPregunta: {Respuesta, Justificacion}}

        var $cont = $('#cuestionarioEncuesta').empty();

        if (!preguntas.length) {
            $cont.html('<div class="text-muted small">No hay preguntas configuradas para este tipo de cuestionario.</div>');
            return;
        }

        preguntas.forEach(function (p, idx) {
            var prev = respuestasPrevias[p.id] || {};
            var html = '<div class="border-bottom py-2" data-idpregunta="' + p.id + '">' +
                '<label class="font-weight-bold small mb-1">' + (idx + 1) + '. ' + p.texto + '</label>';

            if (p.tipo === 'SI_NO') {
                html +=
                    '<div>' +
                    '<label class="radio radio-outline radio-primary mr-4">' +
                    '<input type="radio" name="preg_' + p.id + '" value="SI"' + (prev.Respuesta === 'SI' ? ' checked' : '') + '>' +
                    '<span></span> Sí</label>' +
                    '<label class="radio radio-outline radio-danger">' +
                    '<input type="radio" name="preg_' + p.id + '" value="NO"' + (prev.Respuesta === 'NO' ? ' checked' : '') + '>' +
                    '<span></span> No</label>' +
                    '</div>';
                if (p.requiereJustificacion) {
                    html += '<input type="text" class="form-control form-control-sm mt-1" ' +
                        'placeholder="¿Por qué?" data-justificacion="' + p.id + '" value="' +
                        (prev.Justificacion || '').replace(/"/g, '&quot;') + '">';
                }
            } else if (p.tipo === 'NUMERO') {
                html += '<input type="number" class="form-control form-control-sm" data-respuesta="' + p.id +
                    '" value="' + (prev.Respuesta || '') + '">';
            } else if (p.tipo === 'ESCALA') {
                html += '<select class="form-control form-control-sm" data-respuesta="' + p.id + '">' +
                    '<option value="">(SELECCIONE)</option>';
                for (var n = 1; n <= 10; n++) {
                    html += '<option value="' + n + '"' + (String(prev.Respuesta) === String(n) ? ' selected' : '') + '>' + n + '</option>';
                }
                html += '</select>';
            } else { // TEXTO
                html += '<textarea class="form-control form-control-sm" rows="2" data-respuesta="' + p.id + '">' +
                    (prev.Respuesta || '') + '</textarea>';
            }

            html += '</div>';
            $cont.append(html);
        });
    };

    var leerRespuestasCuestionario = function () {
        var respuestas = [];
        $('#cuestionarioEncuesta [data-idpregunta]').each(function () {
            var idPregunta = $(this).data('idpregunta');
            var pregunta = preguntasActuales.filter(function (p) { return p.id === idPregunta; })[0];
            var respuesta = '';
            var justificacion = '';

            if (pregunta && pregunta.tipo === 'SI_NO') {
                respuesta = $(this).find('input[type=radio]:checked').val() || '';
                justificacion = $(this).find('[data-justificacion]').val() || '';
            } else {
                respuesta = $(this).find('[data-respuesta]').val() || '';
            }

            respuestas.push({ idPregunta: idPregunta, respuesta: respuesta, justificacion: justificacion });
        });
        return respuestas;
    };

    // ── Mostrar/ocultar bloque de datos del informante (sexo/edad solo
    //    aplica en el cuestionario de Programa, pero se dejan visibles en
    //    ambos por si acaso -- ajusta a gusto) ───────────────────────────
    var actualizarSeccionesPorTipo = function (idTipoOrigen) {
        // Espacio para mostrar/ocultar bloques específicos si en el futuro
        // el formulario de Obra y el de Programa divergen más. Por ahora
        // el único cambio real es el cuestionario (renderCuestionario).
    };

    // ── Nueva / Editar ───────────────────────────────────────────────────
    var nuevaEncuesta = function () {
        limpiarFormEncuesta();
        $('#hddIdEncuestaActual').val('');
        $('#hddAccionMovEncuesta').val('add');
        $('#lblTituloFormEncuesta').text('Nueva Encuesta');

        var idTipoOrigen = $('#hddIdTipoOrigenEncuestas').val();
        $.get(API + 'getPreguntas/' + idTipoOrigen, function (preguntas) {
            renderCuestionario(preguntas, {});
        });

        mostrarPanelEncuestas('form');
    };

    var editarEncuesta = function (idEncuesta) {
        $.get(API + 'getEncuestaById/' + idEncuesta, function (data) {
            var e = data.encuesta;
            limpiarFormEncuesta();

            $('#hddIdEncuestaActual').val(e.IDEncuesta);
            $('#hddAccionMovEncuesta').val('edit');
            $('#lblTituloFormEncuesta').text('Editar Encuesta');

            $('#txtFolio').val(e.Folio);
            $('#txtFechaVisita').val(e.FechaVisita);
            $('#txtPorcentajeAvance').val(e.PorcentajeAvance);
            $('#cboEstatusEncuesta').val(e.IDEstatus || '');
            $('#cboSituacionEncontrada').val(e.IDSituacionEncontrada || '');
            $('#txtDetalleSituacionEncontrada').val(e.DetalleSituacionEncontrada);
            $('#txtRecomendacion').val(e.Recomendacion);
            $('#txtDireccion').val(e.Direccion);
            $('#txtOrganismoPublico').val(e.OrganismoPublico);
            $('#txtNombreEncuestador').val(e.NombreEncuestador);
            $('#txtObservacionesEncuesta').val(e.Observaciones);
            $('#txtInformanteNombre').val(e.InformanteNombre);
            $('#txtInformanteDomicilio').val(e.InformanteDomicilio);
            $('#cboInformanteSexo').val(e.InformanteSexo || '');
            $('#txtInformanteEdad').val(e.InformanteEdad);
            $('#txtInversionProgramada').val(e.InversionProgramada);
            $('#txtBeneficiariosEncuesta').val(e.Beneficiarios);
            $('#txtTipoBeneficiarioEncuesta').val(e.TipoBeneficiario);

            // Respuestas previas indexadas por idPregunta para precargar el cuestionario
            var prevMap = {};
            (data.respuestas || []).forEach(function (r) {
                prevMap[r.IDPregunta] = r;
            });

            $.get(API + 'getPreguntas/' + data.idTipoOrigen, function (preguntas) {
                renderCuestionario(preguntas, prevMap);
            });

            renderEvidenciaExistente(data.fotos, data.documentos);

            mostrarPanelEncuestas('form');
        });
    };

    var renderEvidenciaExistente = function (fotos, documentos) {
        var $fotos = $('#previewFotos').empty();
        (fotos || []).forEach(function (f) {
            $fotos.append(
                '<span class="badge badge-light-secondary mr-1 mb-1 p-2">' + f.nombre +
                ' <a href="javascript:void(0)" onclick="EncuestasModule.eliminarFoto(' + f.id + ')" class="text-danger ml-1"><i class="fas fa-times"></i></a></span>'
            );
        });

        var $docs = $('#previewDocumentos').empty();
        (documentos || []).forEach(function (d) {
            $docs.append(
                '<span class="badge badge-light-secondary mr-1 mb-1 p-2">' + d.nombre +
                ' <a href="javascript:void(0)" onclick="EncuestasModule.eliminarDocumento(' + d.id + ')" class="text-danger ml-1"><i class="fas fa-times"></i></a></span>'
            );
        });
    };

    var eliminarFoto = function (idFoto) {
        $.ajax({ url: API + 'eliminarFoto/' + idFoto, type: 'DELETE', success: function () {
            $('[onclick*="eliminarFoto(' + idFoto + ')"]').closest('span').remove();
        }});
    };

    var eliminarDocumento = function (idDocumento) {
        $.ajax({ url: API + 'eliminarDocumento/' + idDocumento, type: 'DELETE', success: function () {
            $('[onclick*="eliminarDocumento(' + idDocumento + ')"]').closest('span').remove();
        }});
    };

    var limpiarFormEncuesta = function () {
        $('#formEncuesta')[0].reset();
        $('#cuestionarioEncuesta').empty();
        $('#previewFotos, #previewDocumentos').empty();
    };

    // ── Guardar ──────────────────────────────────────────────────────────
    var guardarEncuesta = function () {
        if (!$('#txtFechaVisita').val()) {
            Swal.fire('Validación', 'Seleccione la Fecha de Visita.', 'warning'); return;
        }

        var respuestas = leerRespuestasCuestionario();

        var fd = new FormData($('#formEncuesta')[0]);
        fd.append('idAccion', $('#hddIdAccionEncuestas').val());
        fd.append('idEncuesta', $('#hddIdEncuestaActual').val());
        fd.append('accion', $('#hddAccionMovEncuesta').val());
        fd.append('folio', $('#txtFolio').val());
        fd.append('fechaVisita', $('#txtFechaVisita').val());
        fd.append('avance', $('#txtPorcentajeAvance').val() || 0);
        fd.append('idEstatus', $('#cboEstatusEncuesta').val() || '');
        fd.append('idSituacionEncontrada', $('#cboSituacionEncontrada').val() || '');
        fd.append('detalleSituacionEncontrada', $('#txtDetalleSituacionEncontrada').val());
        fd.append('recomendacion', $('#txtRecomendacion').val());
        fd.append('direccion', $('#txtDireccion').val());
        fd.append('organismoPublico', $('#txtOrganismoPublico').val());
        fd.append('nombreEncuestador', $('#txtNombreEncuestador').val());
        fd.append('observaciones', $('#txtObservacionesEncuesta').val());
        fd.append('informanteNombre', $('#txtInformanteNombre').val());
        fd.append('informanteDomicilio', $('#txtInformanteDomicilio').val());
        fd.append('informanteSexo', $('#cboInformanteSexo').val() || '');
        fd.append('informanteEdad', $('#txtInformanteEdad').val() || '');
        fd.append('inversionProgramada', $('#txtInversionProgramada').val() || 0);
        fd.append('beneficiarios', $('#txtBeneficiariosEncuesta').val() || 0);
        fd.append('tipoBeneficiario', $('#txtTipoBeneficiarioEncuesta').val());
        fd.append('respuestasJson', JSON.stringify(respuestas));

        $.ajax({
            url: API + 'guardarEncuesta',
            type: 'POST',
            data: fd,
            processData: false,
            contentType: false,
            success: function () {
                var idAccion = $('#hddIdAccionEncuestas').val();
                mostrarPanelEncuestas('grid');
                cargarTablaEncuestas(idAccion);
                Swal.fire({ title: '¡Éxito!', text: 'Encuesta guardada.', icon: 'success', timer: 1500, showConfirmButton: false });
            },
            error: function (xhr) { Swal.fire('Error', xhr.responseText, 'error'); }
        });
    };

    var eliminarEncuesta = function (idEncuesta) {
        Swal.fire({
            title: '¿Eliminar encuesta?',
            text: 'La evidencia (fotos/documentos) NO se borra, solo el registro de la ficha.',
            icon: 'warning', showCancelButton: true,
            confirmButtonText: 'Sí, eliminar', cancelButtonText: 'Cancelar',
            confirmButtonColor: '#F64E60'
        }).then(function (result) {
            if (result.value) {
                $.ajax({
                    url: API + 'eliminarEncuesta/' + idEncuesta,
                    type: 'DELETE',
                    success: function () {
                        cargarTablaEncuestas($('#hddIdAccionEncuestas').val());
                        Swal.fire({ title: 'Eliminada', icon: 'success', timer: 1500, showConfirmButton: false });
                    }
                });
            }
        });
    };

    // ── Reporte / Ficha ──────────────────────────────────────────────────
    var imprimirFicha = function (idEncuesta) {
        window.open(API + 'fichaHtml/' + idEncuesta, '_blank');
    };

    var descargarPdf = function (idEncuesta) {
        window.location.href = API + 'fichaPdf/' + idEncuesta;
    };

    // ── Eventos ──────────────────────────────────────────────────────────
    var init = function () {
        $('#btnNuevaEncuesta').on('click', function () { nuevaEncuesta(); });
        $('#btnCancelarEncuesta').on('click', function () { mostrarPanelEncuestas('grid'); });
        $('#btnGuardarEncuesta').on('click', function () { guardarEncuesta(); });
    };

    $(document).ready(function () { init(); });

    return {
        verEncuestas: verEncuestas,
        nuevaEncuesta: nuevaEncuesta,
        editarEncuesta: editarEncuesta,
        guardarEncuesta: guardarEncuesta,
        eliminarEncuesta: eliminarEncuesta,
        eliminarFoto: eliminarFoto,
        eliminarDocumento: eliminarDocumento,
        imprimirFicha: imprimirFicha,
        descargarPdf: descargarPdf
    };

})();
