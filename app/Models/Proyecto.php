<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Proyecto extends Model
{
    protected $fillable = [
        'nombre',
        'descripcion',
        'estado',
        'fecha_inicio',
        'fecha_fin_estimada',
        'creado_por',
    ];

    protected function casts(): array
    {
        return [
            'fecha_inicio'       => 'date',
            'fecha_fin_estimada' => 'date',
        ];
    }

    // Usuario que creó el proyecto
    public function creadoPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'creado_por');
    }

    // Miembros del proyecto con su rol (tabla pivot proyecto_usuario)
    public function usuarios(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'proyecto_usuario')
                    ->withPivot('rol_proyecto');
    }

    public function bugs(): HasMany
    {
        return $this->hasMany(Bug::class);
    }

    public function sprints(): HasMany
    {
        return $this->hasMany(Sprint::class);
    }
}
