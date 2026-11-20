<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class LoginController extends Controller
{
    public function create(Request $request): Response
    {
        return Inertia::render('Auth/Login', [
            'redirect' => $this->safeRedirectPath($request->query('redirect')),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);
        $throttleKey = Str::lower($credentials['email']).'|'.$request->ip();

        if (RateLimiter::tooManyAttempts($throttleKey, 5)) {
            throw ValidationException::withMessages([
                'email' => 'Demasiados intentos. Espera un momento antes de volver a intentarlo.',
            ]);
        }

        if (! Auth::attempt([
            ...$credentials,
            'estado' => 'activo',
        ], $request->boolean('remember'))) {
            RateLimiter::hit($throttleKey, 60);

            throw ValidationException::withMessages([
                'email' => 'Las credenciales no son válidas o la cuenta está inactiva.',
            ]);
        }

        RateLimiter::clear($throttleKey);
        $request->session()->regenerate();

        $user = $request->user();
        $redirect = $this->safeRedirectPath($request->input('redirect'));
        $intended = $this->safeRedirectPath($request->session()->get('url.intended'));

        if ($redirect !== null && $this->redirectMatchesRole($redirect, $user)) {
            return redirect()->to($redirect);
        }

        if ($intended !== null && $this->redirectMatchesRole($intended, $user)) {
            return redirect()->to($intended);
        }

        return redirect()->route($user->dashboardRouteName());
    }

    public function destroy(Request $request): RedirectResponse
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }

    private function safeRedirectPath(mixed $path): ?string
    {
        if (! is_string($path)
            || ! str_starts_with($path, '/')
            || str_starts_with($path, '//')
            || str_contains($path, '\\')
            || preg_match('/[\x00-\x1F\x7F]/', $path) === 1) {
            return null;
        }

        $parsedPath = parse_url($path);

        if ($parsedPath === false || isset($parsedPath['scheme']) || isset($parsedPath['host'])) {
            return null;
        }

        return $path;
    }

    private function redirectMatchesRole(string $path, User $user): bool
    {
        $dashboardPaths = [
            User::ROLE_ADMINISTRADOR => '/admin/dashboard',
            User::ROLE_RECEPCION => '/recepcion/dashboard',
            User::ROLE_VETERINARIO => '/veterinario/dashboard',
            User::ROLE_PROPIETARIO => '/propietario/dashboard',
        ];
        $requestedPath = parse_url($path, PHP_URL_PATH);

        foreach ($dashboardPaths as $role => $dashboardPath) {
            if (is_string($requestedPath)
                && ($requestedPath === $dashboardPath || str_starts_with($requestedPath, $dashboardPath.'/'))
                && $user->rol !== $role) {
                return false;
            }
        }

        return true;
    }
}
