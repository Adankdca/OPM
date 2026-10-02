// ════════════════════════════════════════════════════════════════════════
// EncuestasModule v2 -- Encuestas de Seguimiento a nivel ACCIÓN
// Cuestionario GENERAL único (ya no hay Obra / Programa): dos bloques de
// preguntas (ENCUESTA 1 y ENCUESTA 2). Cada encuesta tiene un TIPO (Inicio /
// En proceso / Conclusión), hasta 5 fotos con opción de sustituir o eliminar,
// y documentos. Una encuesta nueva se precarga con la última de la acción.
// Mismo estilo jQuery + Swal que Obras.js.
// ════════════════════════════════════════════════════════════════════════
var EncuestasModule = (function () {

    var API = 'api/Encuestas/';
    var STORAGE = ''; // fotos y documentos viven en public/uploads/encuestas/... -> la ruta guardada ya es relativa al sitio
    var preguntasCat = [];      // catálogo completo de preguntas (ambos bloques)
    var foliosAccion = [];      // [{id, folio}] de las encuestas de la acción (para "Folios relacionados")
    var catalogosListos = null; // promesa de catálogos (se cargan una sola vez por apertura)

    var esc = function (s) {
        return String(s === null || s === undefined ? '' : s)
            .replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
    };

    // ── Abrir el modal para una Acción ──────────────────────────────────
    // Se conserva la firma (idAccion, idTipoOrigen, nombreAccion) para no tocar
    // Obras.js; idTipoOrigen ya NO se usa.
    var verEncuestas = function (idAccion, idTipoOrigen, nombreAccion) {
        $('#hddIdAccionEncuestas').val(idAccion);
        $('#lblAccionEncuestas').text(nombreAccion || '');

        mostrarPanelEncuestas('grid');
        catalogosListos = cargarCatalogos();
        cargarTablaEncuestas(idAccion);
        $('#modalEncuestas').modal('show');
    };

    var mostrarPanelEncuestas = function (panel) {
        $('#panelGridEncuestas, #panelFormEncuesta').hide();
        if (panel === 'grid') $('#panelGridEncuestas').show();
        if (panel === 'form') $('#panelFormEncuesta').show();
    };

    // ── Catálogos ───────────────────────────────────────────────────────
    var llenarSelect = function (sel, lista, textoVacio) {
        var $s = $(sel).empty().append('<option value="">' + textoVacio + '</option>');
        (lista || []).forEach(function (i) {
            $s.append('<option value="' + esc(i.id) + '">' + esc(i.nombre) + '</option>');
        });
    };

    var llenarAvanceEncontrado = function () {
        // 0, 10, 15, 20 ... 100 (igual que el combo del sistema anterior)
        var $s = $('#cboAvanceEncontrado').empty().append('<option value="">Seleccione un avance</option>');
        $s.append('<option value="0">0</option>');
        for (var n = 10; n <= 100; n += 5) {
            $s.append('<option value="' + n + '">' + n + '</option>');
        }
    };

    var cargarCatalogos = function () {
        llenarAvanceEncontrado();
        return $.when(
            $.get(API + 'getSituacionEncontrada', function (r) { llenarSelect('#cboSituacionEncontrada', r, 'Seleccione una situación'); }),
            $.get(API + 'getSubsituacionEncontrada', function (r) { llenarSelect('#cboSubsituacion', r, 'Seleccione una sub-situación'); }),
            $.get(API + 'getSituacionEncuestador', function (r) { llenarSelect('#cboSituacionEncuestador', r, 'Seleccione una situación'); }),
            $.get(API + 'getMunicipios', function (r) {
                llenarSelect('#cboMunicipioPersonal', r, 'Seleccione un municipio');
                llenarSelect('#cboMunicipioEncuestado', r, 'Seleccione un municipio');
            }),
            $.get(API + 'getPreguntas', function (r) { preguntasCat = r; })
        );
    };

    // Localidades dependen del municipio elegido. Devuelve la promesa para poder
    // fijar el valor guardado cuando se edita.
    var cargarLocalidades = function (cveMunicipio, sel, seleccionada) {
        var $s = $(sel);
        if (!cveMunicipio) {
            $s.empty().append('<option value="">Seleccione una localidad</option>');
            return $.Deferred().resolve().promise();
        }
        return $.get(API + 'getLocalidades/' + encodeURIComponent(cveMunicipio), function (r) {
            llenarSelect(sel, r, 'Seleccione una localidad');
            if (seleccionada) $s.val(String(seleccionada));
        });
    };

    // ── Datos informativos de la acción ─────────────────────────────────
    var cargarInfoAccion = function (idAccion) {
        $.get(API + 'getDatosAccion/' + idAccion, function (d) {
            $('#txtInfoDependencia').val(d.dependenciaEjecutora || '');
            $('#txtInfoMunicipio').val(d.municipio || '');
            $('#txtInfoBeneficiados').val(d.beneficiados || '');
            $('#txtInfoEjercicio').val(d.ejercicio || '');
            $('#txtInfoLocalidad').val(d.localidad || '');
            $('#txtInfoInversion').val(d.inversionProgramada !== null && d.inversionProgramada !== undefined
                ? '$ ' + Number(d.inversionProgramada).toLocaleString('es-MX', { minimumFractionDigits: 2, maximumFractionDigits: 2 })
                : '');
            $('#txtInfoPrograma').val(d.programa || '');
            $('#txtInfoProyecto').val(d.nombreProyecto || '');
            $('#txtInfoMeta').val(d.meta || '');
            $('#txtInfoFuentes').val(d.fuentes || '');
            $('#txtInfoLatitud').val(d.latitud || '');
            $('#txtInfoLongitud').val(d.longitud || '');
        });
    };

    // ── Tipos de encuesta (texto + colores para resaltarlos en la bitácora) ──
    var TIPOS = { INICIO: 'Inicio', PROCESO: 'En proceso', CONCLUSION: 'Conclusión' };
    // Mismos colores que el reporte general: Inicio negro, En proceso amarillo, Conclusión verde
    var TIPO_ESTILO = {
        INICIO:     { bg: '#15151B', fg: '#fff',    fondo: '#e6e6ec' },
        PROCESO:    { bg: '#FDC703', fg: '#15151B', fondo: '#fff6d6' },
        CONCLUSION: { bg: '#008028', fg: '#fff',    fondo: '#e3f3e8' }
    };
    var MAX_FOTOS = 5;

    // ── Grid / bitácora (orden de captura: la más antigua primero) ──────
    var cargarTablaEncuestas = function (idAccion) {
        $('#tbodyEncuestas').html('<tr><td colspan="10" class="text-center py-3">' +
            '<i class="fas fa-spinner fa-spin text-warning"></i> Cargando...</td></tr>');

        $.get(API + 'getEncuestas/' + idAccion, function (data) {
            var $tbody = $('#tbodyEncuestas').empty();
            $('#badgeTotalEncuestas').text(data.length);
            foliosAccion = data.map(function (e) { return { id: e.idEncuesta, folio: e.folio || ('#' + e.idEncuesta) }; });

            if (!data.length) {
                $tbody.html('<tr><td colspan="10" class="text-center text-muted py-4">Sin encuestas registradas</td></tr>');
                return;
            }

            data.forEach(function (e) {
                var est = TIPO_ESTILO[e.tipo] || { bg: '#6c757d', fg: '#fff', fondo: '#f3f6f9' };
                var situacion = esc(e.situacion || '') +
                    (e.subsituacion ? '<div class="text-muted" style="font-size:.75rem;">' + esc(e.subsituacion) + '</div>' : '');
                $tbody.append(
                    '<tr>' +
                    '<td class="small font-weight-bold">' + esc(e.folio || '') + '</td>' +
                    '<td style="background:' + est.fondo + ';">' +
                    '<span class="badge font-weight-bolder" style="background:' + est.bg + ';color:' + est.fg + ';font-size:.85rem;padding:.5em .9em;">' +
                    esc(TIPOS[e.tipo] || e.tipo || '') + '</span></td>' +
                    '<td class="small">' + esc(e.fechaCaptura || '') + '</td>' +
                    '<td class="small">' + esc(e.fechaVisita || '') + '</td>' +
                    '<td><span class="badge badge-light-info">' + esc(e.avance === null ? '' : e.avance) + '%</span></td>' +
                    '<td class="small">' + situacion + '</td>' +
                    '<td class="small">' + esc(e.encuestador || '') + '</td>' +
                    '<td class="text-center">' + (e.numFotos || 0) + '/' + MAX_FOTOS + '</td>' +
                    '<td class="text-center">' + (e.numDocumentos || 0) + '</td>' +
                    '<td class="text-center" style="white-space:nowrap;">' +
                    '<button type="button" class="btn btn-xs btn-icon btn-light-primary mr-1" ' +
                    'onclick="EncuestasModule.editarEncuesta(' + e.idEncuesta + ')" title="Modificar">' +
                    '<i class="fas fa-edit"></i></button>' +
                    '<button type="button" class="btn btn-xs btn-icon btn-light-info mr-1" ' +
                    'onclick="EncuestasModule.imprimirFicha(' + e.idEncuesta + ')" title="Vista previa / Imprimir Ficha">' +
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

    // ── Folios relacionados (otras encuestas de la misma acción) ────────
    var llenarFoliosRelacionados = function (idActual, seleccionados) {
        var sel = (seleccionados || '').split(',').map(function (s) { return s.trim(); }).filter(Boolean);
        var $s = $('#cboFoliosRelacionados').empty();
        foliosAccion.forEach(function (f) {
            if (String(f.id) === String(idActual)) return; // no se relaciona consigo misma
            $s.append('<option value="' + esc(f.folio) + '"' + (sel.indexOf(String(f.folio)) >= 0 ? ' selected' : '') + '>' + esc(f.folio) + '</option>');
        });
        if (!$s.children().length) $s.append('<option value="" disabled>(sin otros folios)</option>');
    };

    // ── Cuestionario dinámico: 2 bloques desde catálogo ─────────────────
    var renderBloque = function (bloque, contenedor, prevMap) {
        var $cont = $(contenedor).empty();
        var lista = preguntasCat.filter(function (p) { return Number(p.bloque) === bloque; });

        if (!lista.length) {
            $cont.html('<div class="text-muted small">No hay preguntas configuradas para este bloque.</div>');
            return;
        }

        lista.forEach(function (p) {
            var prev = prevMap[p.id] || {};
            var req = Number(p.obligatoria) === 1;
            var html = '<div class="enc-pregunta" data-idpregunta="' + p.id + '" data-tipo="' + esc(p.tipo) + '" data-obligatoria="' + (req ? 1 : 0) + '">' +
                '<label class="enc-pregunta-texto d-block' + (req ? ' required-field' : '') + '">' + p.orden + '. ' + esc(p.texto) + '</label>';

            if (p.tipo === 'SI_NO') {
                html += '<div class="row"><div class="col-md-4">' +
                    '<select class="form-control form-control-sm" data-respuesta="' + p.id + '">' +
                    '<option value="">Seleccione una respuesta</option>' +
                    '<option value="NO"' + (prev.Respuesta === 'NO' ? ' selected' : '') + '>NO</option>' +
                    '<option value="SI"' + (prev.Respuesta === 'SI' ? ' selected' : '') + '>SI</option>' +
                    '</select></div>';
                if (Number(p.requiereJustificacion) === 1) {
                    html += '<div class="col-md-8"><div class="input-group input-group-sm">' +
                        '<div class="input-group-prepend"><span class="input-group-text">' + esc(p.etiqueta || '¿Por qué?') + '</span></div>' +
                        '<input type="text" class="form-control" data-justificacion="' + p.id + '" value="' + esc(prev.Justificacion) + '">' +
                        '</div></div>';
                }
                html += '</div>';
            } else if (p.tipo === 'HOMBRES_MUJERES') {
                html += '<div class="row"><div class="col-md-3"><div class="input-group input-group-sm">' +
                    '<div class="input-group-prepend"><span class="input-group-text">H</span></div>' +
                    '<input type="number" min="0" class="form-control" data-hombres="' + p.id + '" value="' + esc(prev.RespuestaHombres) + '"></div></div>' +
                    '<div class="col-md-3"><div class="input-group input-group-sm">' +
                    '<div class="input-group-prepend"><span class="input-group-text">M</span></div>' +
                    '<input type="number" min="0" class="form-control" data-mujeres="' + p.id + '" value="' + esc(prev.RespuestaMujeres) + '"></div></div></div>';
            } else if (p.tipo === 'ESCALA') {
                html += '<div class="row"><div class="col-md-3">' +
                    '<input type="number" min="1" max="10" step="1" class="form-control form-control-sm" data-respuesta="' + p.id + '" value="' + esc(prev.Respuesta) + '" placeholder="1 a 10">' +
                    '</div></div>';
            } else if (p.tipo === 'NUMERO') {
                html += '<input type="number" class="form-control form-control-sm" data-respuesta="' + p.id + '" value="' + esc(prev.Respuesta) + '">';
            } else { // TEXTO
                html += '<input type="text" class="form-control form-control-sm" data-respuesta="' + p.id + '" value="' + esc(prev.Respuesta) + '">';
            }

            html += '</div>';
            $cont.append(html);
        });
    };

    var renderCuestionarios = function (prevMap) {
        prevMap = prevMap || {};
        renderBloque(1, '#cuestionarioEncuesta1', prevMap);
        renderBloque(2, '#cuestionarioEncuesta2', prevMap);
    };

    var leerRespuestas = function () {
        var respuestas = [];
        $('#cuestionarioEncuesta1 [data-idpregunta], #cuestionarioEncuesta2 [data-idpregunta]').each(function () {
            var $p = $(this);
            var tipo = $p.data('tipo');
            var r = { idPregunta: $p.data('idpregunta'), respuesta: '', justificacion: '', hombres: '', mujeres: '' };

            if (tipo === 'HOMBRES_MUJERES') {
                r.hombres = $p.find('[data-hombres]').val() || '';
                r.mujeres = $p.find('[data-mujeres]').val() || '';
            } else {
                r.respuesta = $p.find('[data-respuesta]').val() || '';
                r.justificacion = $p.find('[data-justificacion]').val() || '';
            }
            respuestas.push(r);
        });
        return respuestas;
    };

    // ── Llenar el formulario con los datos de una encuesta ──────────────
    // data = respuesta de getEncuestaById / getUltimaEncuesta.
    // esCopia = true: es una encuesta NUEVA rellenada con la última de la acción;
    // en ese caso NO se copian Folio, Fecha de visita, Tipo, fotos ni documentos.
    var llenarFormulario = function (data, esCopia) {
        var e = data.encuesta;

        if (!esCopia) {
            $('#txtFolio').val(e.Folio);
            $('#txtFechaVisita').val(e.FechaVisita);
            $('#cboTipoEncuesta').val(e.TipoEncuesta || '');
        }

        $('#txtSituacionReportada').val(e.situacionreportada);
        $('#txtAvanceFisico').val(e.avancefisico);
        llenarFoliosRelacionados(esCopia ? null : e.IDEncuesta, e.foliosrelacionados);
        $('#txtDireccion').val(e.Direccion);
        $('#cboAvanceEncontrado').val(e.avanceencontrado === null ? '' : String(parseInt(e.avanceencontrado, 10)));
        $('#cboSituacionEncontrada').val(e.IDSituacionEncontrada || '');
        $('#cboSubsituacion').val(e.IDSubsituacionencontrada || '');
        $('#txtObservacionesSituacion').val(e.observacionessituacion);

        $('#txtIdentificacionPersonal').val(e.identificacionpersonal);
        $('#txtNombrePersonal').val(e.nombrecompletopersonal);
        $('#txtCargoPersonal').val(e.cargopersonal);
        $('#txtDomicilioPersonal').val(e.domiciliopersonal);
        $('#cboMunicipioPersonal').val(e.clavemunicipiopersonal || '');
        cargarLocalidades(e.clavemunicipiopersonal, '#cboLocalidadPersonal', e.idlocalidadpersonal);

        $('#txtNombreEncuestador').val(e.nombreencuestador);
        $('#txtRecomendacionesEncuestador').val(e.recomendacionesencuestador);
        $('#cboSituacionEncuestador').val(e.situacionencontradaencuestador || '');
        $('#txtObservacionesEncuestador').val(e.observacionesencuestador);

        $('#txtIdentificacionEncuestado').val(e.identificacionperencuestado);
        $('#txtNombreEncuestado').val(e.nombreencuestado);
        $('#cboSexoEncuestado').val(e.sexoencuestado || '');
        $('#txtEdadEncuestado').val(e.edadencuestado);
        $('#txtParentescoEncuestado').val(e.parentescoencuestado);
        $('#txtDomicilioEncuestado').val(e.domicilioencuestado);
        $('#cboMunicipioEncuestado').val(e.clavemunicipioencuestador || '');
        cargarLocalidades(e.clavemunicipioencuestador, '#cboLocalidadEncuestado', e.idlocalidadencuestador);

        $('#txtNombreEncuestadorFinal').val(e.nombreencuestadorfinal);
        $('#txtRecomendacionesEncuestadorFinal').val(e.recomendacionesencuestadorfinal);
        $('#txtComentarioFinal').val(e.comentariofinal);

        var prevMap = {};
        (data.respuestas || []).forEach(function (r) { prevMap[r.IDPregunta] = r; });
        renderCuestionarios(prevMap);

        actualizarTituloFotos();
    };

    var fechaCorta = function (ymd) {
        var p = String(ymd || '').substring(0, 10).split('-');
        return p.length === 3 ? p[2] + '/' + p[1] + '/' + p[0] : '';
    };

    // ── Nueva (precargada con la última encuesta de la acción) ──────────
    var nuevaEncuesta = function () {
        $.when(catalogosListos).then(function () {
            var idAccion = $('#hddIdAccionEncuestas').val();

            limpiarFormEncuesta();
            $('#hddIdEncuestaActual').val('');
            $('#hddAccionMovEncuesta').val('add');
            $('#lblTituloFormEncuesta').text('Nueva Encuesta');

            cargarInfoAccion(idAccion);
            llenarFoliosRelacionados(null, '');
            renderCuestionarios({});
            renderEvidenciaExistente([], []);
            aplicarReglaTipo(foliosAccion.length === 0);

            $.get(API + 'getUltimaEncuesta/' + idAccion, function (data) {
                if (!data || !data.encuesta) return; // primera encuesta de la acción: formulario vacío

                llenarFormulario(data, true);
                var o = data.encuesta;
                $('#avisoPrecarga').html(
                    '<div class="enc-aviso-icono"><i class="fas fa-history"></i></div>' +
                    '<div class="enc-aviso-cuerpo">' +
                    '<div class="enc-aviso-titulo">Datos precargados de la última encuesta de esta acción</div>' +
                    '<div class="enc-aviso-origen">' +
                    '<span class="enc-chip"><b>Origen:</b> última encuesta capturada</span>' +
                    '<span class="enc-chip"><b>Folio:</b> ' + esc(o.Folio || 's/f') + '</span>' +
                    '<span class="enc-chip"><b>Tipo:</b> ' + esc(TIPOS[o.TipoEncuesta] || '') + '</span>' +
                    '<span class="enc-chip"><b>Visita:</b> ' + fechaCorta(o.FechaVisita) + '</span>' +
                    '</div>' +
                    '<div class="enc-aviso-texto">Revisa y actualiza lo que haya cambiado antes de guardar. ' +
                    '<b>Se capturan de nuevo:</b> folio, tipo, fecha de visita, fotos y documentos.</div>' +
                    '</div>'
                ).show();
            }).always(function () {
                mostrarPanelEncuestas('form');
            });
        });
    };

    // ── Editar ───────────────────────────────────────────────────────────
    var editarEncuesta = function (idEncuesta) {
        $.when(catalogosListos).then(function () {
            $.get(API + 'getEncuestaById/' + idEncuesta, function (data) {
                limpiarFormEncuesta();

                $('#hddIdEncuestaActual').val(data.encuesta.IDEncuesta);
                $('#hddAccionMovEncuesta').val('edit');
                $('#lblTituloFormEncuesta').text('Editar Encuesta');

                cargarInfoAccion($('#hddIdAccionEncuestas').val());
                llenarFormulario(data, false);
                renderEvidenciaExistente(data.fotos, data.documentos);
                aplicarReglaTipo(foliosAccion.length > 0 && String(foliosAccion[0].id) === String(data.encuesta.IDEncuesta));

                mostrarPanelEncuestas('form');
            });
        });
    };

    // ── Fotos: 5 casillas (posiciones 1..5). Cada una: Agregar / Sustituir / Eliminar ──
    var slots = {}; // pos -> { id, ruta, nombre, preview }  (preview = foto nueva aún sin guardar)

    var actualizarTituloFotos = function () {
        var t = $('#cboTipoEncuesta').val();
        $('#lblTipoFotos').text(t ? (TIPOS[t] || t).toUpperCase() : '(elija el tipo de encuesta)');
    };

    // Regla: la primera encuesta de la acción siempre es de INICIO -> se bloquean las otras opciones.
    var aplicarReglaTipo = function (esPrimera) {
        var $s = $('#cboTipoEncuesta');
        $s.find('option').prop('disabled', false);
        $('#hintTipo').text('');
        if (esPrimera) {
            $s.val('INICIO');
            $s.find('option[value="PROCESO"], option[value="CONCLUSION"]').prop('disabled', true);
            $('#hintTipo').text('La primera encuesta de la acción siempre es de Inicio.');
            actualizarTituloFotos();
        }
    };

    var botonFoto = function (accion, texto, clase, icono) {
        return '<button type="button" class="btn btn-xs ' + clase + ' mr-1 mb-1" data-accion="' + accion + '">' +
            '<i class="fas ' + icono + '"></i> ' + texto + '</button>';
    };

    var pintarSlot = function (pos) {
        var s = slots[pos];
        var $slot = $('.foto-slot[data-pos="' + pos + '"]');
        var src = s.preview || (s.ruta ? STORAGE + s.ruta : null);

        $slot.find('.foto-box').html(src
            ? '<a href="' + esc(src) + '" target="_blank" title="Ver tamaño completo"><img src="' + esc(src) + '" style="max-width:100%;max-height:120px;"></a>'
            : '<i class="fas fa-image fa-2x text-muted"></i>');

        var h;
        if (s.preview) {            // foto nueva elegida, falta guardar
            h = '<span class="badge badge-light-warning d-block mb-1">Se guarda al pulsar Guardar</span>' +
                botonFoto('elegir', 'Cambiar', 'btn-light-primary', 'fa-sync-alt') +
                botonFoto('cancelar', 'Cancelar', 'btn-light-secondary', 'fa-undo');
        } else if (s.id) {          // foto ya guardada
            h = botonFoto('elegir', 'Sustituir', 'btn-light-primary', 'fa-sync-alt') +
                botonFoto('eliminar', 'Eliminar', 'btn-light-danger', 'fa-trash');
        } else {                    // casilla vacía
            h = botonFoto('elegir', 'Agregar', 'btn-light-success', 'fa-plus');
        }
        $slot.find('.foto-botones').html(h);
    };

    var armarSlots = function () {
        var $c = $('#contenedorFotos').empty();
        for (var i = 1; i <= MAX_FOTOS; i++) {
            slots[i] = { id: null, ruta: null, nombre: null, preview: null };
            $c.append(
                '<div class="col foto-slot mb-3" data-pos="' + i + '" style="min-width:165px;">' +
                '<div class="border rounded text-center bg-white p-2 h-100">' +
                '<div class="small font-weight-bold mb-1">Foto ' + i + '</div>' +
                '<div class="foto-box d-flex align-items-center justify-content-center bg-light rounded mb-2" style="height:120px;overflow:hidden;"></div>' +
                // el input va DENTRO del form para que FormData lo mande como foto_1..foto_5
                '<input type="file" class="d-none foto-input" name="foto_' + i + '" accept="image/*">' +
                '<div class="foto-botones"></div>' +
                '</div></div>'
            );
            pintarSlot(i);
        }
    };

    var onFotoElegida = function (input) {
        var pos = $(input).closest('.foto-slot').data('pos');
        var file = input.files && input.files[0];
        if (!file) return;

        if (!/^image\//.test(file.type)) {
            Swal.fire('Archivo no válido', 'Elige un archivo de imagen.', 'warning');
            input.value = ''; return;
        }
        if (file.size > 10 * 1024 * 1024) {
            Swal.fire('Imagen muy pesada', 'Cada foto puede pesar máximo 10 MB.', 'warning');
            input.value = ''; return;
        }

        var reader = new FileReader();
        reader.onload = function (ev) {
            slots[pos].preview = ev.target.result;
            pintarSlot(pos);
        };
        reader.readAsDataURL(file);
    };

    var accionFoto = function (pos, accion) {
        var $slot = $('.foto-slot[data-pos="' + pos + '"]');
        var s = slots[pos];

        if (accion === 'elegir') {
            $slot.find('.foto-input').trigger('click');
        } else if (accion === 'cancelar') {
            $slot.find('.foto-input').val('');
            s.preview = null;
            pintarSlot(pos);
        } else if (accion === 'eliminar') {
            Swal.fire({
                title: '¿Eliminar la foto ' + pos + '?',
                text: 'Se borra de inmediato, aunque después cancelen la edición.',
                icon: 'warning', showCancelButton: true,
                confirmButtonText: 'Sí, eliminar', cancelButtonText: 'Cancelar',
                confirmButtonColor: '#F64E60'
            }).then(function (result) {
                if (!result.value) return;
                $.ajax({
                    url: API + 'eliminarFoto/' + s.id, type: 'DELETE',
                    success: function () {
                        slots[pos] = { id: null, ruta: null, nombre: null, preview: null };
                        pintarSlot(pos);
                    },
                    error: function (xhr) { Swal.fire('Error', xhr.responseText, 'error'); }
                });
            });
        }
    };

    var renderEvidenciaExistente = function (fotos, documentos) {
        armarSlots();
        (fotos || []).forEach(function (f) {
            var pos = Number(f.posicion);
            if (!slots[pos]) return;
            slots[pos] = { id: f.id, ruta: f.ruta, nombre: f.nombre, preview: null };
            pintarSlot(pos);
        });

        var $docs = $('#previewDocumentos').empty();
        (documentos || []).forEach(function (d) {
            $docs.append(
                '<span class="badge badge-light-secondary mr-1 mb-1 p-2" style="font-size:.85rem;">' +
                '<a href="' + esc(STORAGE + d.ruta) + '" target="_blank" rel="noopener" title="Abrir documento">' +
                '<i class="fas fa-file-alt mr-1"></i>' + esc(d.nombre) + '</a>' +
                ' <a href="javascript:void(0)" onclick="EncuestasModule.eliminarDocumento(' + d.id + ')" class="text-danger ml-2" title="Eliminar documento"><i class="fas fa-times"></i></a></span>'
            );
        });
    };

    var eliminarDocumento = function (idDocumento) {
        Swal.fire({
            title: '¿Eliminar el documento?',
            text: 'Se borra de inmediato, aunque después cancelen la edición.',
            icon: 'warning', showCancelButton: true,
            confirmButtonText: 'Sí, eliminar', cancelButtonText: 'Cancelar',
            confirmButtonColor: '#F64E60'
        }).then(function (result) {
            if (!result.value) return;
            $.ajax({
                url: API + 'eliminarDocumento/' + idDocumento, type: 'DELETE',
                success: function () {
                    $('[onclick*="eliminarDocumento(' + idDocumento + ')"]').closest('span').remove();
                    cargarTablaEncuestas($('#hddIdAccionEncuestas').val()); // actualiza el conteo de Docs
                },
                error: function (xhr) { Swal.fire('Error', xhr.responseText, 'error'); }
            });
        });
    };

    var limpiarFormEncuesta = function () {
        $('#formEncuesta')[0].reset();
        $('#avisoPrecarga').hide().empty();
        $('#cuestionarioEncuesta1, #cuestionarioEncuesta2').empty();
        $('#previewDocumentos').empty();
        $('#cboLocalidadPersonal, #cboLocalidadEncuestado').empty().append('<option value="">Seleccione una localidad</option>');
        $('#formEncuesta .is-invalid').removeClass('is-invalid');
        armarSlots();
        aplicarReglaTipo(false);
        actualizarTituloFotos();
    };

    // ── Validación (campos marcados con * en el formulario) ─────────────
    var validar = function () {
        var faltan = [];
        var req = [
            ['#cboTipoEncuesta', 'Tipo de encuesta'],
            ['#txtFechaVisita', 'Fecha de Visita'],
            ['#txtDireccion', 'Ubicación de la obra'],
            ['#cboAvanceEncontrado', 'Avance encontrado'],
            ['#cboSituacionEncontrada', 'Situación'],
            ['#cboSubsituacion', 'Sub-Situación'],
            ['#txtNombrePersonal', 'Nombre (persona que proporcionó la información)'],
            ['#txtCargoPersonal', 'Cargo'],
            ['#txtDomicilioPersonal', 'Domicilio (persona que proporcionó la información)'],
            ['#txtNombreEncuestado', 'Nombre del encuestado'],
            ['#cboSexoEncuestado', 'Sexo del encuestado'],
            ['#txtEdadEncuestado', 'Edad del encuestado'],
            ['#txtParentescoEncuestado', 'Parentesco del encuestado'],
            ['#txtDomicilioEncuestado', 'Domicilio del encuestado']
        ];
        req.forEach(function (c) {
            var $c = $(c[0]);
            if (!String($c.val() || '').trim()) { faltan.push(c[1]); $c.addClass('is-invalid'); }
            else { $c.removeClass('is-invalid'); }
        });

        $('#cuestionarioEncuesta1 [data-obligatoria="1"], #cuestionarioEncuesta2 [data-obligatoria="1"]').each(function () {
            var $sel = $(this).find('[data-respuesta]');
            if (!$sel.val()) { faltan.push('Pregunta obligatoria: ' + $(this).find('label').first().text()); $sel.addClass('is-invalid'); }
            else { $sel.removeClass('is-invalid'); }
        });

        // Escala 1..10 (pregunta tipo ESCALA)
        var escalaInvalida = false;
        $('[data-tipo="ESCALA"] [data-respuesta]').each(function () {
            var v = $(this).val();
            if (v !== '' && (isNaN(v) || Number(v) < 1 || Number(v) > 10)) { escalaInvalida = true; $(this).addClass('is-invalid'); }
            else { $(this).removeClass('is-invalid'); }
        });
        if (escalaInvalida) faltan.push('La calificación debe ser un número de 1 a 10');

        return faltan;
    };

    // ── Guardar ──────────────────────────────────────────────────────────
    var guardarEncuesta = function () {
        var faltan = validar();
        if (faltan.length) {
            Swal.fire('Faltan datos', '<div style="text-align:left;font-size:.9rem;">• ' + faltan.map(esc).join('<br>• ') + '</div>', 'warning');
            return;
        }

        // new FormData(form) ya incluye foto_1..foto_5 (solo las que se eligieron) y documentos[]
        var fd = new FormData($('#formEncuesta')[0]);
        var add = function (k, v) { fd.append(k, v === null || v === undefined ? '' : v); };

        add('idAccion', $('#hddIdAccionEncuestas').val());
        add('idEncuesta', $('#hddIdEncuestaActual').val());
        add('accion', $('#hddAccionMovEncuesta').val());

        add('folio', $('#txtFolio').val());
        add('tipoEncuesta', $('#cboTipoEncuesta').val());
        add('fechaVisita', $('#txtFechaVisita').val());
        add('situacionreportada', $('#txtSituacionReportada').val());
        add('avancefisico', $('#txtAvanceFisico').val());
        add('foliosrelacionados', ($('#cboFoliosRelacionados').val() || []).join(','));
        add('direccion', $('#txtDireccion').val());
        add('avanceencontrado', $('#cboAvanceEncontrado').val());
        add('idSituacionEncontrada', $('#cboSituacionEncontrada').val());
        add('idSubsituacionencontrada', $('#cboSubsituacion').val());
        add('observacionessituacion', $('#txtObservacionesSituacion').val());

        add('identificacionpersonal', $('#txtIdentificacionPersonal').val());
        add('nombrecompletopersonal', $('#txtNombrePersonal').val());
        add('cargopersonal', $('#txtCargoPersonal').val());
        add('domiciliopersonal', $('#txtDomicilioPersonal').val());
        add('clavemunicipiopersonal', $('#cboMunicipioPersonal').val());
        add('idlocalidadpersonal', $('#cboLocalidadPersonal').val());

        add('nombreencuestador', $('#txtNombreEncuestador').val());
        add('recomendacionesencuestador', $('#txtRecomendacionesEncuestador').val());
        add('situacionencontradaencuestador', $('#cboSituacionEncuestador').val());
        add('observacionesencuestador', $('#txtObservacionesEncuestador').val());

        add('identificacionperencuestado', $('#txtIdentificacionEncuestado').val());
        add('nombreencuestado', $('#txtNombreEncuestado').val());
        add('sexoencuestado', $('#cboSexoEncuestado').val());
        add('edadencuestado', $('#txtEdadEncuestado').val());
        add('parentescoencuestado', $('#txtParentescoEncuestado').val());
        add('domicilioencuestado', $('#txtDomicilioEncuestado').val());
        add('clavemunicipioencuestador', $('#cboMunicipioEncuestado').val());
        add('idlocalidadencuestador', $('#cboLocalidadEncuestado').val());

        add('nombreencuestadorfinal', $('#txtNombreEncuestadorFinal').val());
        add('recomendacionesencuestadorfinal', $('#txtRecomendacionesEncuestadorFinal').val());
        add('comentariofinal', $('#txtComentarioFinal').val());

        add('respuestasJson', JSON.stringify(leerRespuestas()));

        var $btn = $('#btnGuardarEncuesta').prop('disabled', true);

        $.ajax({
            url: API + 'guardarEncuesta',
            type: 'POST',
            data: fd,
            processData: false,
            contentType: false,
            headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') || '' },
            success: function () {
                var idAccion = $('#hddIdAccionEncuestas').val();
                mostrarPanelEncuestas('grid');
                cargarTablaEncuestas(idAccion);
                Swal.fire({ title: '¡Éxito!', text: 'Encuesta guardada.', icon: 'success', timer: 1500, showConfirmButton: false });
            },
            error: function (xhr) {
                var msg = xhr.responseText;
                if (xhr.status === 422 && xhr.responseJSON && xhr.responseJSON.errors) {
                    msg = Object.keys(xhr.responseJSON.errors).map(function (k) { return xhr.responseJSON.errors[k][0]; }).join('\n');
                }
                Swal.fire('Error', msg, 'error');
            },
            complete: function () { $btn.prop('disabled', false); }
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

    // ── Ficha: vista previa / impresión y PDF ────────────────────────────
    var imprimirFicha = function (idEncuesta) {
        window.open(API + 'fichaHtml/' + idEncuesta, '_blank');
    };

    var descargarPdf = function (idEncuesta) {
        window.location.href = API + 'fichaPdf/' + idEncuesta;
    };

    // Reporte general de TODAS las encuestas de la acción (vista previa/impresión o PDF)
    var reporteGeneral = function (pdf) {
        if (!foliosAccion.length) {
            Swal.fire('Sin encuestas', 'Esta acción todavía no tiene encuestas para el reporte.', 'info');
            return;
        }
        var url = API + (pdf ? 'reporteGeneralPdf/' : 'reporteGeneral/') + $('#hddIdAccionEncuestas').val();
        if (pdf) { window.location.href = url; } else { window.open(url, '_blank'); }
    };

    // ── Eventos ──────────────────────────────────────────────────────────
    var init = function () {
        $('#btnNuevaEncuesta').on('click', function () { nuevaEncuesta(); });
        $('#btnCancelarEncuesta').on('click', function () { mostrarPanelEncuestas('grid'); });
        $('#btnGuardarEncuesta').on('click', function () { guardarEncuesta(); });

        $('#cboTipoEncuesta').on('change', actualizarTituloFotos);

        // casillas de fotos (se generan dinámicamente, por eso delegación de eventos)
        $('#contenedorFotos').on('click', '[data-accion]', function () {
            accionFoto($(this).closest('.foto-slot').data('pos'), $(this).data('accion'));
        });
        $('#contenedorFotos').on('change', '.foto-input', function () { onFotoElegida(this); });

        $('#cboMunicipioPersonal').on('change', function () { cargarLocalidades($(this).val(), '#cboLocalidadPersonal'); });
        $('#cboMunicipioEncuestado').on('change', function () { cargarLocalidades($(this).val(), '#cboLocalidadEncuestado'); });
    };

    $(document).ready(function () { init(); });

    return {
        verEncuestas: verEncuestas,
        nuevaEncuesta: nuevaEncuesta,
        editarEncuesta: editarEncuesta,
        guardarEncuesta: guardarEncuesta,
        eliminarEncuesta: eliminarEncuesta,
        eliminarDocumento: eliminarDocumento,
        imprimirFicha: imprimirFicha,
        descargarPdf: descargarPdf,
        reporteGeneral: reporteGeneral
    };

})();
