<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class LoginController extends Controller
{
    public function create(Request $request): View|RedirectResponse
    {
        if ($request->session()->has('usuario.id')) {
            return redirect()->route('obras.principal');
        }

        return view('login');
    }

    public function store(Request $request): JsonResponse
    {
        $credenciales = $request->validate([
            'user' => ['required', 'string', 'max:100'],
            'password' => ['required', 'string', 'max:255'],
        ], [
            'user.required' => 'Ingrese su nombre de usuario.',
            'password.required' => 'Ingrese su contraseña.',
        ]);

        // La tabla actual conserva Pwd sin hash. Al migrarla a hashes de
        // Laravel, esta comparación debe cambiarse por Hash::check().
        $usuario = DB::table('TblU_Usuario')
            ->select('IDUsuario', 'Nombre', 'Usuario', 'Bloqueo')
            ->where('Usuario', $credenciales['user'])
            ->where('Pwd', $credenciales['password'])
            ->first();

        if (! $usuario) {
            return response()->json([
                'success' => false,
                'message' => 'Usuario o contraseña incorrectos.',
            ]);
        }

        if ((bool) $usuario->Bloqueo) {
            return response()->json([
                'success' => false,
                'blocked' => true,
                'message' => 'Su cuenta está bloqueada. Contacte al administrador.',
            ]);
        }

        $request->session()->regenerate();
        $request->session()->put('usuario', [
            'id' => $usuario->IDUsuario,
            'nombre' => $usuario->Nombre,
            'usuario' => $usuario->Usuario,
        ]);

        return response()->json([
            'success' => true,
            'redirect' => route('obras.principal'),
            'data' => [
                'idusuario' => $usuario->IDUsuario,
                'nombre' => $usuario->Nombre,
                'usuario' => $usuario->Usuario,
            ],
        ]);
    }

    public function destroy(Request $request): RedirectResponse
    {
        $request->session()->forget('usuario');
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}
