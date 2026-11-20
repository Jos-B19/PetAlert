<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AdministracionMedicamento extends Model
{
    protected $table = 'administraciones_medicamentos';

    protected $fillable = [
        'atencion_medicamento_id',
        'veterinario_id',
        'dosis_administrada',
        'cantidad_administrada',
        'via_administracion',
        'fecha_hora',
        'observaciones',
    ];

    protected function casts(): array
    {
        return [
            'cantidad_administrada' => 'decimal:2',
            'fecha_hora' => 'datetime',
        ];
    }

    public function atencionMedicamento(): BelongsTo
    {
        return $this->belongsTo(AtencionMedicamento::class);
    }

    public function veterinario(): BelongsTo
    {
        return $this->belongsTo(User::class, 'veterinario_id');
    }
}
