<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class PasswordChangeTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_user_can_change_their_password(): void
    {
        $user = User::factory()->create([
            'rol' => User::ROLE_RECEPCION,
            'estado' => 'activo',
        ]);

        $this->actingAs($user)
            ->get(route('password.edit'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page->component('Auth/Password'));

        $this->patch(route('password.update'), [
            'current_password' => 'password',
            'password' => 'NewSafePassword2026',
            'password_confirmation' => 'NewSafePassword2026',
        ])->assertRedirect('/recepcion/dashboard');

        $this->assertAuthenticatedAs($user);
        $this->assertTrue(Hash::check('NewSafePassword2026', $user->fresh()->password));
    }

    public function test_password_change_rejects_an_incorrect_current_password(): void
    {
        $user = User::factory()->create([
            'rol' => User::ROLE_PROPIETARIO,
            'estado' => 'activo',
        ]);

        $this->actingAs($user)
            ->patch(route('password.update'), [
                'current_password' => 'wrong-password',
                'password' => 'NewSafePassword2026',
                'password_confirmation' => 'NewSafePassword2026',
            ])
            ->assertSessionHasErrors('current_password');

        $this->assertTrue(Hash::check('password', $user->fresh()->password));
    }
}
