<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;

class Medicamento extends Model
{
    protected $fillable = [
        'nombre',
        'presentacion',
        'activo',
    ];

    protected function casts(): array
    {
        return [
            'activo' => 'boolean',
        ];
    }

    public function indicaciones(): HasMany
    {
        return $this->hasMany(AtencionMedicamento::class);
    }

    public function administraciones(): HasManyThrough
    {
        return $this->hasManyThrough(
            AdministracionMedicamento::class,
            AtencionMedicamento::class,
            'medicamento_id',
            'atencion_medicamento_id',
            'id',
            'id',
        );
    }
}
