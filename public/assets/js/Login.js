/**
 * Login Module — VS2026 Metronic 7
 * Sistema Cédula de Verificación y Seguimiento de Obra
 */
const LoginModule = (function () {

    const LOGIN_URL = '/login';

    // ── Toggle mostrar/ocultar contraseña ────────────────────────────
    var togglePassword = function () {
        var input = document.getElementById('txtPassword');
        var icon = document.getElementById('iconTogglePwd');
        if (input.type === 'password') {
            input.type = 'text';
            icon.className = 'fas fa-eye-slash';
        } else {
            input.type = 'password';
            icon.className = 'fas fa-eye';
        }
    };

    // ── Validar campos vacíos ─────────────────────────────────────────
    var validar = function (user, pass) {
        if (!user) {
            marcarError('txtUsuario', 'Ingrese su nombre de usuario.');
            return false;
        }
        limpiarError('txtUsuario');
        if (!pass) {
            marcarError('txtPassword', 'Ingrese su contraseña.');
            return false;
        }
        limpiarError('txtPassword');
        return true;
    };

    var marcarError = function (id, msg) {
        var $input = $('#' + id);
        $input.css('border-color', '#F64E60');
        $input.focus();
        // Shake animation
        $input.addClass('animate__animated animate__shakeX');
        setTimeout(function () {
            $input.removeClass('animate__animated animate__shakeX');
        }, 600);
    };

    var limpiarError = function (id) {
        $('#' + id).css('border-color', '#E5EAEE');
    };

    // ── Login principal ───────────────────────────────────────────────
    var login = function () {
        var user = $('#txtUsuario').val().trim();
        var pass = $('#txtPassword').val().trim();

        if (!validar(user, pass)) return;

        // Mostrar loading en botón
        var $btn = $('#btnLogin');
        $btn.addClass('loading').prop('disabled', true);

        $.ajax({
            type: 'POST',
            url: LOGIN_URL,
            data: { user: user, password: pass, _token: $('meta[name="csrf-token"]').attr('content') },
            success: function (response) {
                $btn.removeClass('loading').prop('disabled', false);

                if (!response.success) {
                    // Error de credenciales
                    $('#txtUsuario, #txtPassword').css('border-color', '#F64E60');
                    Swal.fire({
                        icon: response.blocked ? 'warning' : 'error',
                        title: response.blocked ? 'Cuenta bloqueada' : 'Acceso denegado',
                        text: response.message || 'Usuario o contraseña incorrectos.',
                        confirmButtonColor: '#1565C0',
                        confirmButtonText: 'Intentar de nuevo'
                    }).then(function () {
                        $('#txtPassword').val('').focus();
                        limpiarError('txtUsuario');
                        limpiarError('txtPassword');
                    });
                    return;
                }

                var datos = response.data;

                // Bienvenida y redirección
                Swal.fire({
                    icon: 'success',
                    title: '¡Bienvenido!',
                    html: '<strong>' + (datos.nombre || datos.usuario) + '</strong>',
                    timer: 2500,
                    timerProgressBar: true,
                    showConfirmButton: false,
                    allowOutsideClick: false
                }).then(function () {
                    window.location.href = response.redirect;
                });
            },
            error: function (xhr) {
                $btn.removeClass('loading').prop('disabled', false);
                var msg = 'Error de conexión con el servidor.';
                try {
                    var err = JSON.parse(xhr.responseText);
                    msg = err.message || (err.errors && Object.values(err.errors)[0][0]) || msg;
                } catch (e) { }
                Swal.fire({
                    icon: 'error',
                    title: 'Error del servidor',
                    text: msg,
                    confirmButtonColor: '#1565C0'
                });
            }
        });
    };

    // ── Init ──────────────────────────────────────────────────────────
    var init = function () {
        $('#loginForm').on('submit', function (e) {
            e.preventDefault();
            login();
        });

        $('#togglePassword').on('click', togglePassword);

        // Enter en usuario pasa al password
        $('#txtUsuario').on('keypress', function (e) {
            if (e.which === 13) {
                e.preventDefault();
                $('#txtPassword').focus();
            }
        });

        // Enter en password hace login
        $('#txtPassword').on('keypress', function (e) {
            if (e.which === 13) {
                e.preventDefault();
                login();
            }
        });

        // Limpiar error al escribir
        $('#txtUsuario, #txtPassword').on('input', function () {
            $(this).css('border-color', '#E5EAEE');
        });

        // Focus en usuario al cargar
        setTimeout(function () {
            $('#txtUsuario').focus();
        }, 300);
    };

    // Inicializar cuando el DOM esté listo
    $(document).ready(function () { init(); });

    return {
        login: login,
        togglePassword: togglePassword
    };

})();
