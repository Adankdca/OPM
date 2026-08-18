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
        body { font-family: Poppins, sans-serif; }
        .login-page { min-height:100vh; position:relative; overflow:hidden; background:linear-gradient(135deg,#eef5ff 0%,#f7f9fc 48%,#e3f0ff 100%); }
        .login-page::before,.login-page::after,.bubble { content:''; position:absolute; border-radius:50%; pointer-events:none; }
        .login-page::before { width:520px; height:520px; top:-210px; left:-150px; background:rgba(21,101,192,.12); }
        .login-page::after { width:460px; height:460px; right:-160px; bottom:-170px; background:rgba(2,119,189,.12); }
        .bubble-one { width:130px; height:130px; top:18%; right:13%; background:rgba(21,101,192,.10); }
        .bubble-two { width:82px; height:82px; bottom:16%; left:15%; background:rgba(2,119,189,.14); }
        .logo-sistema { width:285px; max-width:100%; height:auto; margin:0 auto 22px; display:block; }
        .login-card { width:100%; max-width:430px; padding:44px; background:#fff; border-radius:14px; box-shadow:0 15px 45px rgba(31,45,61,.12); }
        .input-icon { position:relative; } .input-icon > i { position:absolute; z-index:2; left:16px; top:16px; color:#7e8299; } .input-icon input { height:52px; padding-left:45px; } .password-toggle { position:absolute; z-index:2; border:0; background:transparent; right:10px; top:9px; color:#7e8299; padding:8px; }
        @media(max-width:991px) { .login-card { padding:32px 25px; } }
    </style>
</head>
<body id="kt_body" class="header-fixed">
    <div class="login-page d-flex flex-column flex-root align-items-center justify-content-center p-6">
        <span class="bubble bubble-one"></span><span class="bubble bubble-two"></span>
        <main class="position-relative" style="z-index:1"><div class="login-card"><div class="text-center mb-8"><img class="logo-sistema" src="{{ asset('assets/media/logonew.png') }}" alt="Sistema de Verificación de Obras y Programas"><h3 class="font-weight-bolder mb-2">Iniciar sesión</h3><p class="text-muted mb-0">Sistema de Verificación de Obras y Programas</p></div><div class="alert alert-light-warning mb-6 font-size-sm"><i class="fas fa-exclamation-triangle text-warning mr-2"></i>Uso exclusivo para personal autorizado.</div><form id="loginForm" novalidate><div class="form-group input-icon"><i class="fas fa-user"></i><input id="txtUsuario" name="user" class="form-control form-control-solid" type="text" maxlength="100" autocomplete="username" placeholder="Nombre de usuario"></div><div class="form-group input-icon"><i class="fas fa-lock"></i><input id="txtPassword" name="password" class="form-control form-control-solid" type="password" maxlength="255" autocomplete="current-password" placeholder="Contraseña"><button class="password-toggle" id="togglePassword" type="button" aria-label="Mostrar contraseña"><i class="fas fa-eye"></i></button></div><button id="btnLogin" type="submit" class="btn btn-primary font-weight-bolder w-100 py-3"><span class="btn-text"><i class="fas fa-sign-in-alt mr-2"></i>Ingresar al sistema</span><span class="spinner-border spinner-border-sm d-none" role="status"></span></button></form><div class="text-center text-muted font-size-sm mt-7">Dirección de Obras Públicas</div></div></main>
    </div>
    <script src="{{ asset('assets/vendor/general/jquery/dist/jquery.js') }}"></script><script src="{{ asset('assets/plugins/global/plugins.bundle.js') }}"></script><script src="{{ asset('assets/js/Login.js') }}"></script>
</body>
</html>
