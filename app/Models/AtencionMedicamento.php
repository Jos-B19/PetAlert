<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AtencionMedicamento extends Model
{
    protected $fillable = [
        'atencion_id',
        'medicamento_id',
        'veterinario_id',
        'dosis',
        'cantidad',
        'frecuencia',
        'via_administracion',
    ];

    protected function casts(): array
    {
        return [
            'cantidad' => 'decimal:2',
        ];
    }

    public function atencion(): BelongsTo
    {
        return $this->belongsTo(Atencion::class);
    }

    public function medicamento(): BelongsTo
    {
        return $this->belongsTo(Medicamento::class);
    }

    public function veterinario(): BelongsTo
    {
        return $this->belongsTo(User::class, 'veterinario_id');
    }

    public function administraciones(): HasMany
    {
        return $this->hasMany(AdministracionMedicamento::class);
    }
}
