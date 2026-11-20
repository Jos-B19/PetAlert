<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EvolucionClinica extends Model
{
    protected $table = 'evoluciones_clinicas';

    protected $fillable = [
        'atencion_id',
        'veterinario_id',
        'descripcion',
        'tratamiento_aplicado',
        'estado_mascota',
        'peso',
        'fecha_evolucion',
        'observaciones',
    ];

    protected function casts(): array
    {
        return [
            'fecha_evolucion' => 'datetime',
            'peso' => 'decimal:2',
        ];
    }

    public function atencion(): BelongsTo
    {
        return $this->belongsTo(Atencion::class);
    }

    public function veterinario(): BelongsTo
    {
        return $this->belongsTo(User::class, 'veterinario_id');
    }
}
