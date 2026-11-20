<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;

class Propietario extends Model
{
    protected $fillable = [
        'nombres',
        'apellidos',
        'tipo_documento',
        'numero_documento',
        'telefono',
        'correo',
        'direccion',
        'estado',
    ];

    public function mascotas(): HasMany
    {
        return $this->hasMany(Mascota::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function urgencias(): HasManyThrough
    {
        return $this->hasManyThrough(Urgencia::class, Mascota::class);
    }
}
