<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>Cédula de Verificación | Acceso</title>
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="{{ asset('assets/plugins/global/plugins.bundle.css') }}" rel="stylesheet">
    <link href="{{ asset('assets/css/style.bundle.css') }}" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link rel="shortcut icon" href="{{ asset('assets/media/logos/favicon.ico') }}">
    <style>
        :root {
            --rojo: #D60106; --verde: #008028; --amarillo: #FDC703; --negro: #15151B;
            --gris: #D6D6E0; --gris-claro: #ECECF2; --gris-medio: #8f8fa6;
        }
        body { font-family: Poppins, sans-serif; }

        /* ── Fondo: bandas diagonales con los colores del logo (rojo / amarillo / negro) ── */
        .login-page { min-height: 100vh; position: relative; overflow: hidden; background: #E9E9F0; }
        .login-page .banda { position: absolute; left: 0; right: 0; top: 0; pointer-events: none; }
        .login-page .banda-roja     { height: 62%; background: var(--rojo);     clip-path: polygon(0 0, 100% 0, 100% 62%, 0 100%); }
        .login-page .banda-amarilla { height: 59%; background: var(--amarillo); clip-path: polygon(0 0, 100% 0, 100% 62%, 0 100%); }
        .login-page .banda-negra    { height: 55%; background: var(--negro);    clip-path: polygon(0 0, 100% 0, 100% 62%, 0 100%); }

        /* esferas decorativas, discretas */
        .esfera { position: absolute; border-radius: 50%; pointer-events: none; }
        .esfera-1 { width: 420px; height: 420px; top: -150px; left: -120px; background: rgba(255,255,255,.05); }
        .esfera-2 { width: 200px; height: 200px; top: 14%; right: 9%;  background: rgba(253,199,3,.14); }
        .esfera-3 { width: 120px; height: 120px; top: 36%; left: 12%;  background: rgba(214,1,6,.28); }
        .esfera-4 { width: 460px; height: 460px; bottom: -190px; right: -140px; background: rgba(214,214,224,.75); }
        .esfera-5 { width: 150px; height: 150px; bottom: 9%; left: 7%;  background: rgba(0,128,40,.14); }

        /* ── Tarjeta ── */
        .login-card {
            width: 100%; max-width: 440px; padding: 40px 44px 34px; background: #fff;
            border-radius: 10px; border-top: 5px solid var(--rojo);
            box-shadow: 0 22px 55px rgba(21,21,27,.35);
        }
        .logo-sistema { width: 285px; max-width: 100%; height: auto; margin: 0 auto 18px; display: block; }
        .login-card h3 { color: var(--negro); }
        .login-sub { color: #6b6b7a; font-size: 13px; }
        .login-sep { height: 3px; width: 70px; margin: 14px auto 0; background: linear-gradient(90deg, var(--rojo) 0 50%, var(--amarillo) 50% 100%); border-radius: 2px; }

        .aviso-acceso {
            background: var(--gris-claro); border-left: 4px solid var(--amarillo);
            color: var(--negro); border-radius: 4px; padding: 10px 14px; font-size: 13px;
        }

        /* ── Campos ── */
        .input-icon { position: relative; }
        .input-icon > i { position: absolute; z-index: 2; left: 16px; top: 17px; color: var(--gris-medio); }
        .input-icon input { height: 52px; padding-left: 45px; background: var(--gris-claro); border: 1.5px solid var(--gris); color: var(--negro); }
        .input-icon input:focus { background: #fff; border-color: var(--negro); box-shadow: 0 0 0 .15rem rgba(21,21,27,.10); }
        .password-toggle { position: absolute; z-index: 2; border: 0; background: transparent; right: 10px; top: 9px; color: var(--gris-medio); padding: 8px; }
        .password-toggle:hover { color: var(--negro); }

        /* ── Botón oscuro ── */
        .btn-login {
            background: var(--negro) !important; border: 0 !important; border-bottom: 3px solid var(--amarillo) !important;
            color: #fff !important; letter-spacing: .3px; border-radius: 6px; transition: background .2s, border-color .2s;
        }
        .btn-login:hover, .btn-login:focus { background: #2B2B35 !important; border-bottom-color: var(--rojo) !important; }
        .btn-login:disabled { opacity: .75; }

        .login-pie { color: #8a8a99; font-size: 12px; }
        @media (max-width: 991px) { .login-card { padding: 30px 24px; } }
    </style>
</head>
<body id="kt_body" class="header-fixed">
    <div class="login-page d-flex flex-column flex-root align-items-center justify-content-center p-6">
        <span class="banda banda-roja"></span>
        <span class="banda banda-amarilla"></span>
        <span class="banda banda-negra"></span>
        <span class="esfera esfera-1"></span><span class="esfera esfera-2"></span><span class="esfera esfera-3"></span>
        <span class="esfera esfera-4"></span><span class="esfera esfera-5"></span>

        <main class="position-relative" style="z-index:1">
            <div class="login-card">
                <div class="text-center mb-7">
                    <img class="logo-sistema" src="{{ asset('assets/media/logonew.png') }}" alt="Sistema de Verificación de Obras y Programas">
                    <h3 class="font-weight-bolder mb-1">Iniciar sesión</h3>
                    <p class="login-sub mb-0">Sistema de Verificación de Obras y Programas</p>
                    <div class="login-sep"></div>
                </div>

                <div class="aviso-acceso mb-6"><i class="fas fa-exclamation-triangle mr-2" style="color:var(--amarillo)"></i>Uso exclusivo para personal autorizado.</div>

                <form id="loginForm" novalidate>
                    <div class="form-group input-icon">
                        <i class="fas fa-user"></i>
                        <input id="txtUsuario" name="user" class="form-control" type="text" maxlength="100" autocomplete="username" placeholder="Nombre de usuario">
                    </div>
                    <div class="form-group input-icon">
                        <i class="fas fa-lock"></i>
                        <input id="txtPassword" name="password" class="form-control" type="password" maxlength="255" autocomplete="current-password" placeholder="Contraseña">
                        <button class="password-toggle" id="togglePassword" type="button" aria-label="Mostrar contraseña"><i class="fas fa-eye" id="iconTogglePwd" style="position:static"></i></button>
                    </div>
                    <button id="btnLogin" type="submit" class="btn btn-login font-weight-bolder w-100 py-3">
                        <span class="btn-text"><i class="fas fa-sign-in-alt mr-2"></i>Ingresar al sistema</span>
                        <span class="spinner-border spinner-border-sm d-none" role="status"></span>
                    </button>
                </form>

                <div class="text-center login-pie mt-7">Dirección de Obras Públicas</div>
            </div>
        </main>
    </div>
    <script src="{{ asset('assets/vendor/general/jquery/dist/jquery.js') }}"></script><script src="{{ asset('assets/plugins/global/plugins.bundle.js') }}"></script><script src="{{ asset('assets/js/Login.js') }}"></script>
</body>
</html>
