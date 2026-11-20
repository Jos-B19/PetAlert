<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Mascota extends Model
{
    protected $fillable = [
        'propietario_id',
        'nombre',
        'especie',
        'raza',
        'sexo',
        'fecha_nacimiento',
        'peso',
        'color',
        'observaciones',
        'estado',
    ];

    protected function casts(): array
    {
        return [
            'fecha_nacimiento' => 'date',
            'peso' => 'decimal:2',
        ];
    }

    public function propietario(): BelongsTo
    {
        return $this->belongsTo(Propietario::class);
    }

    public function urgencias(): HasMany
    {
        return $this->hasMany(Urgencia::class);
    }

    public function historialClinico(): HasOne
    {
        return $this->hasOne(HistorialClinico::class);
    }
}
