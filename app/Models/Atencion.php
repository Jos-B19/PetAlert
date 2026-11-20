<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Atencion extends Model
{
    protected $table = 'atenciones';

    protected $fillable = [
        'urgencia_id',
        'veterinario_id',
        'diagnostico',
        'tratamiento',
        'procedimientos',
        'observaciones',
        'fecha_atencion',
        'estado',
    ];

    protected function casts(): array
    {
        return [
            'fecha_atencion' => 'datetime',
        ];
    }

    public function urgencia(): BelongsTo
    {
        return $this->belongsTo(Urgencia::class);
    }

    public function veterinario(): BelongsTo
    {
        return $this->belongsTo(User::class, 'veterinario_id');
    }

    public function evolucionesClinicas(): HasMany
    {
        return $this->hasMany(EvolucionClinica::class);
    }

    public function medicamentosIndicados(): HasMany
    {
        return $this->hasMany(AtencionMedicamento::class);
    }
}
