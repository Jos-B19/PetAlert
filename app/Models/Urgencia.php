<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Urgencia extends Model
{
    protected $fillable = [
        'mascota_id',
        'registrado_por',
        'veterinario_id',
        'tipo_urgencia',
        'descripcion',
        'nivel_prioridad',
        'estado',
        'fecha_ingreso',
        'fecha_egreso',
        'egreso_registrado_por',
        'estado_final_mascota',
    ];

    protected function casts(): array
    {
        return [
            'fecha_ingreso' => 'datetime',
            'fecha_egreso' => 'datetime',
        ];
    }

    public function mascota(): BelongsTo
    {
        return $this->belongsTo(Mascota::class);
    }

    public function registradoPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'registrado_por');
    }

    public function veterinario(): BelongsTo
    {
        return $this->belongsTo(User::class, 'veterinario_id');
    }

    public function egresoRegistradoPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'egreso_registrado_por');
    }

    public function atenciones(): HasMany
    {
        return $this->hasMany(Atencion::class);
    }
}
