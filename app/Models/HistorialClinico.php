<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HistorialClinico extends Model
{
    protected $table = 'historiales_clinicos';

    protected $fillable = [
        'mascota_id',
        'antecedentes',
        'alergias',
        'enfermedades_previas',
        'vacunas',
        'observaciones',
    ];

    public function mascota(): BelongsTo
    {
        return $this->belongsTo(Mascota::class);
    }
}
