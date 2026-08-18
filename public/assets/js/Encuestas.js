/* ================================================
   Encuestas de Seguimiento de Obra (modulo NUEVO, beta)
   No existe en el sistema original -- JS escrito desde cero,
   separado de Obras.js a proposito para no arriesgar nada de
   lo que ya esta probado.
   ================================================ */
var EncuestasModule = (function () {

    var API = 'api/Encuestas/';
    var idObraActual = 0;

    // ── Formatea numero como moneda/porcentaje simple ──
    var fmtPct = function (n) {
        return (parseFloat(n) || 0).toFixed(2) + '%';
    };

    var badgeEstatus = function (estatus) {
        var colores = {
            'En tiempo': 'success',
            'Retrasada': 'warning',
            'Detenida': 'danger',
            'Concluida': 'primary'
        };
        var color = colores[estatus] || 'secondary';
        return '<span class="badge badge-' + color + '">' + (estatus || 'Sin estatus') + '</span>';
    };

    // ── Abre el modal y carga la bitacora de la obra ──
    var verEncuestas = function (idobra) {
        idObraActual = idobra;
        $('#hddIdObraEncuestas').val(idobra);
        $('#panelFormEncuesta').hide();
        cargarCatalogoEstatus();
        cargarBitacora(idobra);
        $('#modalEncuestas').modal('show');
    };

    var cargarCatalogoEstatus = function () {
        $.get(API + 'getEstatusEncuesta', function (res) {
            var $c = $('#cboEstatusEncuesta').empty().append('<option value="">(SELECCIONE)</option>');
            res.forEach(function (i) {
                $c.append('<option value="' + i.id + '">' + i.nombre + '</option>');
            });
        });
    };

    var cargarBitacora = function (idobra) {
        $.get(API + 'getEncuestas/' + idobra, function (res) {
            var $tbody = $('#tbodyEncuestas').empty();
            $('#badgeTotalEncuestas').text(res.length);

            if (res.length === 0) {
                $tbody.append('<tr><td colspan="7" class="text-center text-muted py-4">' +
                    'Sin encuestas registradas todavia</td></tr>');
                return;
            }

            res.forEach(function (e) {
                $tbody.append(
                    '<tr>' +
                    '<td>' + (e.fechaVisita || '') + '</td>' +
                    '<td>' + fmtPct(e.porcentajeAvance) + '</td>' +
                    '<td>' + badgeEstatus(e.estatus) + '</td>' +
                    '<td>' + (e.nombreEncuestador || '-') + '</td>' +
                    '<td class="text-center"><i class="fas fa-camera text-muted mr-1"></i>' + e.numFotos + '</td>' +
                    '<td class="text-center"><i class="fas fa-file-alt text-muted mr-1"></i>' + e.numDocumentos + '</td>' +
                    '<td class="text-center">' +
                    '<button type="button" class="btn btn-sm btn-icon btn-light-warning mr-1" ' +
                    'onclick="EncuestasModule.editarEncuesta(' + e.idEncuesta + ')" title="Editar">' +
                    '<i class="fas fa-edit"></i></button>' +
                    '<button type="button" class="btn btn-sm btn-icon btn-light-danger" ' +
                    'onclick="EncuestasModule.eliminarEncuesta(' + e.idEncuesta + ')" title="Eliminar">' +
                    '<i class="fas fa-trash"></i></button>' +
                    '</td>' +
                    '</tr>'
                );
            });
        });
    };

    // ── Nueva encuesta: limpia el formulario y lo muestra ──
    var nuevaEncuesta = function () {
        $('#hddIdEncuestaActual').val('');
        $('#formEncuesta')[0].reset();
        $('#previewFotos, #previewDocumentos').empty();
        $('#lblTituloFormEncuesta').text('Nueva Encuesta de Seguimiento');
        $('#panelFormEncuesta').show();
    };

    // ── Editar: precarga datos + lista de archivos ya subidos ──
    var editarEncuesta = function (id) {
        $.get(API + 'getEncuestaById/' + id, function (e) {
            $('#hddIdEncuestaActual').val(e.IDEncuesta);
            $('#txtFechaVisita').val(e.FechaVisita);
            $('#txtPorcentajeAvance').val(e.PorcentajeAvance);
            $('#cboEstatusEncuesta').val(e.IDEstatus);
            $('#txtNombreEncuestador').val(e.NombreEncuestador);
            $('#txtObservacionesEncuesta').val(e.Observaciones);

            var $prevFotos = $('#previewFotos').empty();
            e.fotos.forEach(function (f) {
                $prevFotos.append(
                    '<div class="d-inline-block mr-2 mb-2 text-center" style="width:90px;">' +
                    '<img src="/storage/' + f.ruta + '" class="img-thumbnail" style="height:70px;object-fit:cover;">' +
                    '<button type="button" class="btn btn-xs btn-danger btn-block mt-1" ' +
                    'onclick="EncuestasModule.eliminarFotoExistente(' + f.idFoto + ')">' +
                    '<i class="fas fa-trash"></i></button></div>'
                );
            });

            var $prevDocs = $('#previewDocumentos').empty();
            e.documentos.forEach(function (d) {
                $prevDocs.append(
                    '<div class="d-flex align-items-center justify-content-between border rounded p-2 mb-1">' +
                    '<a href="/storage/' + d.ruta + '" target="_blank"><i class="fas fa-file-alt mr-1"></i>' +
                    (d.nombre || 'Documento') + '</a>' +
                    '<button type="button" class="btn btn-xs btn-danger" ' +
                    'onclick="EncuestasModule.eliminarDocumentoExistente(' + d.idDocumento + ')">' +
                    '<i class="fas fa-trash"></i></button></div>'
                );
            });

            $('#lblTituloFormEncuesta').text('Editar Encuesta de Seguimiento');
            $('#panelFormEncuesta').show();
        });
    };

    // ── Guardar: usa FormData porque puede llevar archivos ──
    var guardarEncuesta = function () {
        if (!$('#txtFechaVisita').val() || !$('#txtPorcentajeAvance').val()) {
            Swal.fire('Atencion', 'Fecha y porcentaje de avance son obligatorios.', 'warning');
            return;
        }

        var formEl = document.getElementById('formEncuesta');
        var formData = new FormData(formEl);

        formData.append('idobra', idObraActual);
        formData.append('idEncuesta', $('#hddIdEncuestaActual').val() || 0);
        formData.append('fechaVisita', $('#txtFechaVisita').val());
        formData.append('porcentajeAvance', $('#txtPorcentajeAvance').val());
        formData.append('idEstatus', $('#cboEstatusEncuesta').val());
        formData.append('nombreEncuestador', $('#txtNombreEncuestador').val());
        formData.append('observaciones', $('#txtObservacionesEncuesta').val());

        // Los <input type="file" multiple> ya vienen incluidos en el
        // FormData automaticamente por new FormData(formEl), siempre y
        // cuando tengan el atributo name="fotos[]" / name="documentos[]"

        $.ajax({
            url: API + 'guardarEncuesta',
            method: 'POST',
            data: formData,
            processData: false,   // IMPORTANTE: no dejar que jQuery convierta el FormData
            contentType: false,   // IMPORTANTE: dejar que el navegador ponga el boundary correcto
            success: function () {
                Swal.fire('Listo', 'Encuesta guardada correctamente.', 'success');
                $('#panelFormEncuesta').hide();
                cargarBitacora(idObraActual);
            },
            error: function (xhr) {
                Swal.fire('Error', xhr.responseJSON ? JSON.stringify(xhr.responseJSON.errors || xhr.responseJSON) : xhr.responseText, 'error');
            }
        });
    };

    var eliminarEncuesta = function (id) {
        Swal.fire({
            title: 'Eliminar encuesta',
            text: 'Se borraran tambien sus fotos y documentos. Esta accion no se puede deshacer.',
            icon: 'warning', showCancelButton: true
        }).then(function (r) {
            if (r.isConfirmed) {
                $.ajax({ url: API + 'eliminarEncuesta/' + id, method: 'DELETE' })
                    .done(function () { cargarBitacora(idObraActual); });
            }
        });
    };

    var eliminarFotoExistente = function (idFoto) {
        $.ajax({ url: API + 'eliminarFoto/' + idFoto, method: 'DELETE' })
            .done(function () { $('#hddIdEncuestaActual').val() && editarEncuesta($('#hddIdEncuestaActual').val()); });
    };

    var eliminarDocumentoExistente = function (idDocumento) {
        $.ajax({ url: API + 'eliminarDocumento/' + idDocumento, method: 'DELETE' })
            .done(function () { $('#hddIdEncuestaActual').val() && editarEncuesta($('#hddIdEncuestaActual').val()); });
    };

    // ── Wiring de eventos ──
    $(document).ready(function () {
        $('#btnNuevaEncuesta').on('click', nuevaEncuesta);
        $('#btnGuardarEncuesta').on('click', guardarEncuesta);
        $('#btnCancelarEncuesta').on('click', function () { $('#panelFormEncuesta').hide(); });
    });

    return {
        verEncuestas: verEncuestas,
        editarEncuesta: editarEncuesta,
        eliminarEncuesta: eliminarEncuesta,
        eliminarFotoExistente: eliminarFotoExistente,
        eliminarDocumentoExistente: eliminarDocumentoExistente
    };
})();
