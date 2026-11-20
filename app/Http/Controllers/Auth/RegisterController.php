<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class RegisterController extends Controller
{
    public function create(Request $request): Response
    {
        return Inertia::render('Auth/Register');
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'nombres' => ['required', 'string', 'max:100'],
            'apellidos' => ['required', 'string', 'max:100'],
            'tipo_documento' => ['required', 'string', 'max:20'],
            'numero_documento' => ['required', 'string', 'max:30', 'unique:propietarios,numero_documento'],
            'telefono' => ['required', 'string', 'max:20', 'regex:/^[0-9]{7,20}$/'],
            'email' => [
                'required',
                'email',
                'max:150',
                Rule::unique('users', 'email'),
                Rule::unique('propietarios', 'correo'),
            ],
            'password' => ['required', 'string', 'min:12', 'confirmed'],
        ], [
            'nombres.required' => 'Ingresa tus nombres.',
            'nombres.max' => 'Tus nombres no pueden superar los 100 caracteres.',
            'apellidos.required' => 'Ingresa tus apellidos.',
            'apellidos.max' => 'Tus apellidos no pueden superar los 100 caracteres.',
            'tipo_documento.required' => 'Selecciona el tipo de documento.',
            'numero_documento.required' => 'Ingresa tu número de documento.',
            'numero_documento.unique' => 'Ya existe un perfil con ese número de documento.',
            'telefono.required' => 'Ingresa un número de teléfono.',
            'telefono.regex' => 'Ingresa un teléfono válido de 7 a 20 dígitos.',
            'email.required' => 'Ingresa tu correo electrónico.',
            'email.email' => 'Ingresa un correo electrónico válido.',
            'email.unique' => 'Ya existe una cuenta o perfil con ese correo electrónico.',
            'password.required' => 'Ingresa una contraseña.',
            'password.min' => 'La contraseña debe tener al menos 12 caracteres.',
            'password.confirmed' => 'La confirmación de contraseña no coincide.',
        ]);

        $user = DB::transaction(function () use ($validated): User {
            $user = User::create([
                'name' => trim($validated['nombres'].' '.$validated['apellidos']),
                'email' => $validated['email'],
                'telefono' => $validated['telefono'],
                'password' => $validated['password'],
                'rol' => User::ROLE_PROPIETARIO,
                'estado' => 'activo',
            ]);

            $user->propietario()->create([
                'nombres' => $validated['nombres'],
                'apellidos' => $validated['apellidos'],
                'tipo_documento' => $validated['tipo_documento'],
                'numero_documento' => $validated['numero_documento'],
                'telefono' => $validated['telefono'],
                'correo' => $validated['email'],
            ]);

            return $user;
        });

        Auth::login($user);
        $request->session()->regenerate();

        return redirect()->route('account.show')->with('success', 'Tu cuenta se creó correctamente.');
    }
}
