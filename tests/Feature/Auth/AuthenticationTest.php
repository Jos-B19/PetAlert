<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_page_is_available_to_guests(): void
    {
        $this->get('/login')
            ->assertOk()
            ->assertInertia(fn ($page) => $page->component('Auth/Login'));
    }

    public function test_active_user_can_login_and_logout(): void
    {
        $user = User::factory()->create([
            'email' => 'vet@example.test',
            'rol' => User::ROLE_ADMINISTRADOR,
            'estado' => 'activo',
        ]);

        $this->post('/login', [
            'email' => $user->email,
            'password' => 'password',
        ])->assertRedirect('/admin/dashboard');

        $this->assertAuthenticatedAs($user);

        $this->post('/logout')->assertRedirect('/login');

        $this->assertGuest();
    }

    public function test_invalid_credentials_do_not_authenticate(): void
    {
        $user = User::factory()->create([
            'email' => 'vet@example.test',
            'estado' => 'activo',
        ]);

        $this->from('/login')->post('/login', [
            'email' => $user->email,
            'password' => 'wrong-password',
        ])->assertRedirect('/login')->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    public function test_inactive_users_cannot_login(): void
    {
        $user = User::factory()->create([
            'email' => 'inactive@example.test',
            'estado' => 'inactivo',
        ]);

        $this->post('/login', [
            'email' => $user->email,
            'password' => 'password',
        ])->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    public function test_internal_routes_require_authentication(): void
    {
        $this->get('/dashboard')->assertRedirect(route('login', ['redirect' => '/dashboard']));
        $this->get('/pets/create')->assertRedirect(route('login', ['redirect' => '/pets/create']));
        $this->post('/logout')->assertRedirect(route('login'));
    }

    public function test_active_staff_user_returns_to_the_requested_route_after_login(): void
    {
        $user = User::factory()->create([
            'rol' => User::ROLE_ADMINISTRADOR,
            'estado' => 'activo',
        ]);

        $this->get('/pets/create')
            ->assertRedirect(route('login', ['redirect' => '/pets/create']));

        $this->post('/login', [
            'email' => $user->email,
            'password' => 'password',
            'redirect' => '/pets/create',
        ])->assertRedirect('/pets/create');

        $this->assertAuthenticatedAs($user);
    }

    public function test_login_ignores_external_redirect_urls(): void
    {
        $user = User::factory()->create([
            'rol' => User::ROLE_ADMINISTRADOR,
            'estado' => 'activo',
        ]);

        $this->post('/login', [
            'email' => $user->email,
            'password' => 'password',
            'redirect' => 'https://example.test',
        ])->assertRedirect('/admin/dashboard');
    }

    public function test_login_does_not_redirect_a_receptionist_to_the_administrator_dashboard(): void
    {
        $user = User::factory()->create([
            'rol' => User::ROLE_RECEPCION,
            'estado' => 'activo',
        ]);

        $this->post('/login', [
            'email' => $user->email,
            'password' => 'password',
            'redirect' => '/admin/dashboard',
        ])->assertRedirect('/recepcion/dashboard');
    }
}
