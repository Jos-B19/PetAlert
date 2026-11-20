<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

#[Fillable([
    'name',
    'email',
    'telefono',
    'tipo_documento',
    'numero_documento',
    'direccion',
    'cargo',
    'password',
    'rol',
    'estado',
])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    public const ROLE_ADMINISTRADOR = 'administrador';

    public const ROLE_VETERINARIO = 'veterinario';

    public const ROLE_RECEPCION = 'recepcion';

    public const ROLE_PROPIETARIO = 'propietario';

    public const STAFF_ROLES = [
        self::ROLE_ADMINISTRADOR,
        self::ROLE_VETERINARIO,
        self::ROLE_RECEPCION,
    ];

    public const ROLES = [
        self::ROLE_ADMINISTRADOR,
        self::ROLE_VETERINARIO,
        self::ROLE_RECEPCION,
        self::ROLE_PROPIETARIO,
    ];

    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function urgenciasRegistradas(): HasMany
    {
        return $this->hasMany(Urgencia::class, 'registrado_por');
    }

    public function urgenciasAsignadas(): HasMany
    {
        return $this->hasMany(Urgencia::class, 'veterinario_id');
    }

    public function atenciones(): HasMany
    {
        return $this->hasMany(Atencion::class, 'veterinario_id');
    }

    public function evolucionesClinicas(): HasMany
    {
        return $this->hasMany(EvolucionClinica::class, 'veterinario_id');
    }

    public function egresosRegistrados(): HasMany
    {
        return $this->hasMany(Urgencia::class, 'egreso_registrado_por');
    }

    public function medicamentosIndicados(): HasMany
    {
        return $this->hasMany(AtencionMedicamento::class, 'veterinario_id');
    }

    public function administracionesMedicamentos(): HasMany
    {
        return $this->hasMany(AdministracionMedicamento::class, 'veterinario_id');
    }

    public function propietario(): HasOne
    {
        return $this->hasOne(Propietario::class);
    }

    public function isStaff(): bool
    {
        return in_array($this->rol, self::STAFF_ROLES, true);
    }

    public function dashboardRouteName(): string
    {
        return match ($this->rol) {
            self::ROLE_ADMINISTRADOR => 'admin.dashboard',
            self::ROLE_RECEPCION => 'reception.dashboard',
            self::ROLE_VETERINARIO => 'veterinarian.dashboard',
            self::ROLE_PROPIETARIO => 'owner.dashboard',
            default => 'home',
        };
    }
}
