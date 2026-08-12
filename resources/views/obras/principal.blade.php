{{--
    Equivalente a Principal.aspx.
    @extends() es el equivalente Blade de MasterPageFile="~/Site1.Master"
--}}
@extends('layouts.metronic')

@section('content')

<div class="subheader py-2 py-lg-4 subheader-solid" id="kt_subheader">
    <div class="container-fluid d-flex align-items-center justify-content-between flex-wrap flex-sm-nowrap">
        <div class="d-flex align-items-center flex-wrap mr-1">
            <h5 class="text-dark font-weight-bold my-2 mr-5">
                <i class="fas fa-hard-hat text-primary mr-2"></i> Seguimiento de Obras
            </h5>
            <span class="badge badge-danger px-3 py-2 ml-2" id="totalObrasBadge">0 obras</span>
        </div>
        <button type="button" id="btnNuevaObra" class="btn btn-primary font-weight-bolder">
            <i class="fas fa-plus mr-1"></i> NUEVA OBRA
        </button>
    </div>
</div>

<div class="d-flex flex-column-fluid">
    <div class="container-fluid">

        {{-- FILTROS (copiado tal cual de Principal.aspx, sin cambios) --}}
        <div class="card card-custom gutter-b shadow-sm">
            <div class="card-header" style="min-height:50px;background-color: #1c3248">
                <div class="card-title">
                    <i class="fas fa-filter text-primary mr-2"></i>
                    <span class="font-weight-bold" style="color: white;">Filtros de Búsqueda</span>
                </div>
            </div>
            <div class="card-body py-4">
                <div class="row">
                    <div class="col-md-3 form-group mb-3">
                        <label class="font-weight-bold">Rubro:</label>
                        <select id="filterRubro" class="form-control selectpicker"
                                data-live-search="true" data-size="8">
                        </select>
                    </div>
                    <div class="col-md-2 form-group mb-3">
                        <label class="font-weight-bold">Año:</label>
                        <select id="filterAnio" class="form-control selectpicker"
                                data-live-search="true">
                        </select>
                    </div>
                    <div class="col-md-3 form-group mb-3">
                        <label class="font-weight-bold">Nombre de la Obra:</label>
                        <input type="text" id="filterNombre" class="form-control"
                               placeholder="Buscar por nombre...">
                    </div>
                    <div class="col-md-2 form-group mb-3">
                        <label class="font-weight-bold">No. Contrato:</label>
                        <input type="text" id="filterContrato" class="form-control"
                               placeholder="Núm. contrato...">
                    </div>
                    <div class="col-md-2 form-group mb-3">
                        <label class="font-weight-bold">No. Obra:</label>
                        <input type="text" id="filterNumObra" class="form-control"
                               placeholder="Núm. obra...">
                    </div>
                </div>
                <div class="row">
                    <div class="col-12 d-flex justify-content-end">
                        <button type="button" id="btnLimpiarObras"
                                class="btn btn-light-secondary font-weight-bold mr-2" style="color: #082436;">
                            <i class="fas fa-times mr-1" style="color: #082436;"></i> Limpiar
                        </button>
                        <button type="button" id="btnBuscarObras"
                                class="btn btn-primary font-weight-bold px-8">
                            <i class="flaticon-search mr-1"></i> BUSCAR
                        </button>
                    </div>
                </div>
            </div>
        </div>

        {{-- TABLA (copiado tal cual de Principal.aspx, sin cambios) --}}
        <div class="card card-custom shadow-sm">
            <div class="card-header" style="min-height:50px;background-color: #1c3248">
                <div class="card-title">
                    <i class="fas fa-list text-primary mr-2"></i>
                    <span class="font-weight-bold" style="color: white;">Listado de Obras</span>
                </div>
                <div class="card-toolbar">
                    <span class="text-muted small mr-3">Total:
                        <strong id="lblTotalObras" class="text-primary">0</strong> obras
                    </span>
                </div>
            </div>
            <div class="card-body py-3">
                <div class="table-responsive">
                    <table class="table table-head-custom table-vertical-center table-hover" id="tblObras">
                        <thead class="thead-light" style="background-color:#464e5f !important">
                            <tr>
                                <th style="width:50px;color:#828385 !important;">No.</th>
                                <th style="width:60px;color:#828385 !important;">Año</th>
                                <th style="width:130px;color:#828385 !important;">Subrubro</th>
                                <th style="color:#828385 !important;min-width:270px;">Nombre de la Obra</th>
                                <th style="width:100px;color:#828385 !important;">No. Obra</th>
                                <th style="width:160px;color:#828385 !important;">Localidad</th>
                                <th style="width:100px;color:#828385 !important;">Financ.</th>
                                <th class="text-right" style="width:110px;color:#828385 !important;">Municipal</th>
                                <th class="text-right" style="width:110px;color:#828385 !important;">Estatal</th>
                                <th class="text-right" style="width:110px;color:#828385 !important;">Federal</th>
                                <th class="text-right" style="width:120px;color:#828385 !important;">Total</th>
                                <th class="text-center" style="width:150px;color:#828385 !important;">Acciones</th>
                            </tr>
                        </thead>
                        <tbody id="tbodyObras">
                            <tr>
                                <td colspan="12" class="text-center text-muted py-5">
                                    <i class="fas fa-search fa-2x mb-2 d-block"></i>
                                    Use los filtros y presione BUSCAR
                                </td>
                            </tr>
                        </tbody>
                        <tfoot>
                            <tr class="font-weight-bold bg-light-primary" id="rowTotales" style="display:none;">
                                <td colspan="7" class="text-right font-weight-bold">TOTALES:</td>
                                <td class="text-right text-primary" id="totalMunicipal"></td>
                                <td class="text-right text-primary" id="totalEstatal"></td>
                                <td class="text-right text-primary" id="totalFederal"></td>
                                <td class="text-right text-danger font-weight-bolder" id="totalGeneral"></td>
                                <td></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>

    </div>
</div>

@endsection

@push('scripts')
{{--
    Tu Obras.js va SIN MODIFICAR a public/assets/js/Obras.js.
    No hace falta llamar ObrasModule.init() aquí -- el propio archivo ya
    trae su arranque automático al final:
        $(document).ready(function () { ObrasModule.init(); Clock.init(); });
    Así que con solo incluir el <script> ya queda todo conectado.
--}}
<script src="{{ asset('assets/js/Obras.js') }}"></script>
@endpush
