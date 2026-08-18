<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8" />
    <title>CEDULA | VERIFICACIÓN</title>
    <meta name="description" content="Secretaria de Infraestructura" />
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no" />

    {{--
        TOKEN CSRF -- IMPORTANTE.
        Laravel protege por default toda peticion POST/PUT/DELETE contra
        ataques CSRF (algo que tu .NET original no traia configurado asi).
        Sin este token, guardarObra() te va a marcar error 419 "Page Expired".
        csrf_token() genera un codigo unico por sesion; lo ponemos aqui como
        meta tag para poder leerlo desde JS mas abajo, SIN tener que tocar
        ni una linea de tu Obras.js original.
    --}}
    <meta name="csrf-token" content="{{ csrf_token() }}">

    {{--
        IMPORTANTE: en .NET estas rutas ("assets/css/...") apuntaban a la
        carpeta física del sitio. En Laravel, TODO lo que va dentro de
        public/ se sirve tal cual desde la raíz del dominio, así que
        {{ asset('assets/...') }} genera la ruta correcta apuntando a
        public/assets/... -- por eso necesitas copiar tu carpeta "assets"
        completa a miapp/public/assets/ (te lo recordé en el mensaje anterior).
    --}}
    <script src="{{ asset('assets/vendor/general/jquery/dist/jquery.js') }}"></script>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">

    <link href="{{ asset('assets/plugins/custom/datatables/datatables.bundle.css') }}" rel="stylesheet" type="text/css" />
    <link href="{{ asset('assets/plugins/custom/datatables/buttons.dataTables.min.css') }}" rel="stylesheet" />
    <script src="{{ asset('assets/plugins/custom/datatables/jquery.dataTables.min.js') }}"></script>
    <script src="{{ asset('assets/plugins/custom/datatables/dataTables.buttons.min.js') }}"></script>
    <script src="{{ asset('assets/plugins/custom/datatables/buttons.html5.min.js') }}"></script>
    <script src="{{ asset('assets/plugins/custom/datatables/jszip.min.js') }}"></script>

    <link href="{{ asset('assets/plugins/global/plugins.bundle.css') }}" rel="stylesheet" type="text/css" />
    <link href="{{ asset('assets/css/style.bundle.css') }}" rel="stylesheet" type="text/css" />
    <link href="{{ asset('assets/css/medguard.css') }}" rel="stylesheet" />
    <link href="{{ asset('assets/css/chilon-theme.css') }}" rel="stylesheet" />
    {{-- chilon-theme.css se carga DESPUES de medguard.css a proposito, para
         que sus reglas (con !important) ganen sobre los colores default de
         Metronic. Si en algun momento quieres regresar a los colores
         originales, basta con borrar esta linea -- no se toco nada mas. --}}

    <link href="{{ asset('assets/css/themes/layout/header/base/light.css') }}" rel="stylesheet" type="text/css" />
    <link href="{{ asset('assets/css/themes/layout/header/menu/light.css') }}" rel="stylesheet" type="text/css" />
    <link href="{{ asset('assets/css/themes/layout/brand/dark.css') }}" rel="stylesheet" type="text/css" />
    <link href="{{ asset('assets/css/themes/layout/aside/dark.css') }}" rel="stylesheet" type="text/css" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link rel="shortcut icon" href="{{ asset('assets/media/logos/favicon.ico') }}" />
    <style>
        /* Mantiene el logotipo horizontal dentro del encabezado lateral de Metronic. */
        #kt_brand .brand-logo { height: 65px; display: flex; align-items: center; justify-content: center; }
        #kt_brand .brand-logo img { width: 190px; max-height: 56px; object-fit: contain; }
    </style>

    @stack('head')
</head>
<body id="kt_body" class="header-fixed header-mobile-fixed subheader-enabled subheader-fixed aside-enabled aside-fixed aside-minimize-hoverable">

    <div class="d-flex flex-column flex-root">
        <div class="d-flex flex-row flex-column-fluid page">

            {{-- Aside / menú lateral --}}
            <div class="aside aside-left aside-fixed d-flex flex-column flex-row-auto" id="kt_aside">
                <div class="brand flex-column-auto" id="kt_brand" style="background-color: #ededed;">
                    <a href="{{ route('obras.principal') }}" class="brand-logo">
                        <img alt="Sistema de Verificación de Obras y Programas" src="{{ asset('assets/media/logonew.png') }}" />
                    </a>
                </div>
                <div class="aside-menu-wrapper flex-column-fluid" id="kt_aside_menu_wrapper">
                    <div id="kt_aside_menu" class="aside-menu my-4" data-menu-vertical="1" data-menu-scroll="1">
                        <ul class="menu-nav">
                            <li class="menu-item" aria-haspopup="true">
                                <a href="{{ route('obras.principal') }}" class="menu-link">
                                    <span class="menu-text">Principal</span>
                                </a>
                            </li>
                            <li class="menu-section">
                                <h4 class="menu-text">Seguimiento de Obras</h4>
                            </li>
                            <li class="menu-item" aria-haspopup="true">
                                <a href="{{ route('obras.principal') }}" class="menu-link">
                                    <span class="menu-text">Proyecto-Obras</span>
                                </a>
                            </li>
                            {{-- Aquí se van agregando los demás módulos (Contratos, Cédula de
                                 Verificación, etc.) conforme los vayas migrando --}}
                        </ul>
                    </div>
                </div>
            </div>

            {{-- Wrapper principal --}}
            <div class="d-flex flex-column flex-row-fluid wrapper" id="kt_wrapper">
                <div id="kt_header" class="header header-fixed" style="background-color: #565656;">
                    <div class="container-fluid d-flex align-items-stretch justify-content-between">
                        <div></div>
                        <div class="topbar">
                            <div class="topbar-item">
                                <div class="btn btn-icon w-auto btn-clean d-flex align-items-center btn-lg px-2">
                                    <span class="text-muted font-weight-bold font-size-base d-none d-md-inline mr-1">HOLA,</span>
                                    <span class="text-dark-50 font-weight-bolder font-size-base d-none d-md-inline mr-3">
                                        {{ session('usuario.nombre', session('usuario.usuario', 'Usuario')) }}
                                    </span>
                                    <form action="{{ route('logout') }}" method="POST" class="d-inline">
                                        @csrf
                                        <button type="submit" class="btn btn-sm btn-light-danger font-weight-bold"><i class="fas fa-sign-out-alt mr-1"></i>Salir</button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="content d-flex flex-column flex-column-fluid" id="kt_content">
                    <div class="d-flex flex-column-fluid">
                        <div class="container-fluid">
                            {{-- Aquí se inyecta el contenido de cada vista hija.
                                 Equivalente al <asp:ContentPlaceHolder ID="ContentPlaceHolder1" /> --}}
                            @yield('content')
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="{{ asset('assets/plugins/global/plugins.bundle.js') }}"></script>
    <script src="{{ asset('assets/js/scripts.bundle.js') }}"></script>
    <script src="{{ asset('assets/plugins/custom/datatables/datatables.bundle.js') }}"></script>

    <script>
        // OJO -- este bloque va AQUI, DESPUES de plugins.bundle.js, y no en
        // el <head>. Metronic trae su propia copia de jQuery empaquetada
        // dentro de plugins.bundle.js; si el ajaxSetup se configura antes
        // (como en el <head>), esa segunda carga de jQuery pisa la
        // configuracion y el token CSRF deja de mandarse -> error 419.
        //
        // Con esto, CUALQUIER $.ajax/$.post/$.get que haga Obras.js (o
        // cualquier otro JS futuro) manda automaticamente el token CSRF
        // en el header -- una sola vez aqui, en vez de modificar cada
        // archivo .js del proyecto.
        $.ajaxSetup({
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            }
        });

        // Si la sesion expira (o el token queda desincronizado por
        // cualquier otra razon), en vez de que el usuario vea un JSON feo
        // de error, se le avisa claro y se recarga la pagina sola para
        // que obtenga un token fresco.
        $(document).ajaxError(function (event, xhr) {
            if (xhr.status === 419) {
                Swal.fire({
                    icon: 'warning',
                    title: 'Sesión vencida',
                    text: 'La página se actualizará para continuar.'
                }).then(function () {
                    window.location.reload();
                });
            }
        });
    </script>

    @stack('scripts')
</body>
</html>
