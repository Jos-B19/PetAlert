<?php

namespace Tests\Feature\Auth;

use App\Models\Propietario;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class RegistrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_registration_page_is_public(): void
    {
        $this->get('/register')
            ->assertOk()
            ->assertInertia(fn ($page) => $page->component('Auth/Register'));
    }

    public function test_public_pages_are_available_to_guests(): void
    {
        foreach (['/', '/nosotros', '/servicios', '/informacion-veterinaria', '/contacto'] as $path) {
            $this->get($path)
                ->assertOk()
                ->assertInertia(fn ($page) => $page->component('Public/Site'));
        }
    }

    public function test_public_registration_creates_a_linked_owner_account_with_a_hashed_password(): void
    {
        $registration = $this->validRegistrationData([
            'rol' => User::ROLE_ADMINISTRADOR,
        ]);

        $this->post('/register', $registration)
            ->assertRedirect(route('account.show'));

        $user = User::where('email', $registration['email'])->firstOrFail();
        $owner = Propietario::where('numero_documento', $registration['numero_documento'])->firstOrFail();

        $this->assertAuthenticatedAs($user);
        $this->assertSame(User::ROLE_PROPIETARIO, $user->rol);
        $this->assertSame($user->id, $owner->user_id);
        $this->assertTrue(Hash::check($registration['password'], $user->password));
        $this->assertSame('Laura Gómez', $user->name);
        $this->assertSame($registration['telefono'], $owner->telefono);
    }

    public function test_registration_rejects_an_existing_email_with_a_clear_message(): void
    {
        User::factory()->create(['email' => 'laura@example.test']);
        $registration = $this->validRegistrationData(['email' => 'laura@example.test']);

        $this->post('/register', $registration)
            ->assertInvalid([
                'email' => 'Ya existe una cuenta o perfil con ese correo electrónico.',
            ]);

        $this->assertDatabaseCount('propietarios', 0);
    }

    public function test_registration_rejects_an_email_already_used_by_an_owner_profile(): void
    {
        Propietario::create([
            'nombres' => 'Persona',
            'apellidos' => 'Existente',
            'tipo_documento' => 'CC',
            'numero_documento' => '987654321',
            'telefono' => '3001234567',
            'correo' => 'laura@example.test',
        ]);
        $registration = $this->validRegistrationData();

        $this->post('/register', $registration)
            ->assertInvalid([
                'email' => 'Ya existe una cuenta o perfil con ese correo electrónico.',
            ]);

        $this->assertDatabaseCount('users', 0);
    }

    public function test_registration_rejects_an_existing_document_number(): void
    {
        Propietario::create([
            'nombres' => 'Persona',
            'apellidos' => 'Existente',
            'tipo_documento' => 'CC',
            'numero_documento' => '123456789',
            'telefono' => '3001234567',
        ]);
        $registration = $this->validRegistrationData(['numero_documento' => '123456789']);

        $this->post('/register', $registration)
            ->assertInvalid([
                'numero_documento' => 'Ya existe un perfil con ese número de documento.',
            ]);

        $this->assertDatabaseCount('users', 0);
    }

    public function test_registration_rejects_invalid_phone_and_mismatched_passwords(): void
    {
        $registration = $this->validRegistrationData([
            'telefono' => 'no-es-un-telefono',
            'password_confirmation' => 'DifferentPassword2026',
        ]);

        $this->post('/register', $registration)
            ->assertInvalid([
                'telefono' => 'Ingresa un teléfono válido de 7 a 20 dígitos.',
                'password' => 'La confirmación de contraseña no coincide.',
            ]);

        $this->assertDatabaseCount('users', 0);
    }

    public function test_registration_reports_required_fields_in_spanish(): void
    {
        $this->post('/register', [])
            ->assertInvalid([
                'nombres' => 'Ingresa tus nombres.',
                'apellidos' => 'Ingresa tus apellidos.',
                'tipo_documento' => 'Selecciona el tipo de documento.',
                'numero_documento' => 'Ingresa tu número de documento.',
                'telefono' => 'Ingresa un número de teléfono.',
                'email' => 'Ingresa tu correo electrónico.',
                'password' => 'Ingresa una contraseña.',
            ]);

        $this->assertDatabaseCount('users', 0);
    }

    public function test_owner_can_view_their_profile_but_not_internal_modules(): void
    {
        $this->post('/register', $this->validRegistrationData());
        $owner = User::where('email', 'laura@example.test')->firstOrFail();
        $this->actingAs($owner);

        $this->get('/mi-cuenta')
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Auth/Account')
                ->where('ownerProfile.numero_documento', '123456789'));

        foreach ([
            '/dashboard',
            '/users',
            '/owners',
            '/pets',
            '/pets/create',
            '/urgencies',
            '/urgencies/create',
            '/attentions',
            '/evolutions',
            '/histories',
        ] as $path) {
            $this->get($path)->assertForbidden();
        }

        $this->post('/owners', [])->assertForbidden();
        $this->post('/pets', [])->assertForbidden();
        $this->post('/urgencies', [])->assertForbidden();
    }

    public function test_owner_can_log_in_and_log_out(): void
    {
        $user = User::factory()->create([
            'email' => 'owner@example.test',
            'rol' => User::ROLE_PROPIETARIO,
            'estado' => 'activo',
        ]);

        $this->post('/login', [
            'email' => $user->email,
            'password' => 'password',
        ])->assertRedirect(route('owner.dashboard'));

        $this->assertAuthenticatedAs($user);

        $this->post('/logout')->assertRedirect(route('login'));
        $this->assertGuest();
    }

    /**
     * @param  array<string, string>  $overrides
     * @return array<string, string>
     */
    private function validRegistrationData(array $overrides = []): array
    {
        return array_replace([
            'nombres' => 'Laura',
            'apellidos' => 'Gómez',
            'tipo_documento' => 'CC',
            'numero_documento' => '123456789',
            'telefono' => '3001234567',
            'email' => 'laura@example.test',
            'password' => 'PetAlertSecure2026',
            'password_confirmation' => 'PetAlertSecure2026',
        ], $overrides);
    }
}
