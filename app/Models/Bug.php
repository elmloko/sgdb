<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Bug extends Model
{
    /**
     * Propiedad temporal (no persistida) usada por BugService para pasar
     * un comentario al BugObserver durante un cambio de estado.
     */
    public ?string $comentarioCambioEstado = null;

    protected $fillable = [
        'ticket_num',
        'titulo',
        'descripcion',
        'prioridad',
        'estado',
        'proyecto_id',
        'modulo',
        'entorno',
        'pasos_reproducir',
        'comportamiento_esperado',
        'comportamiento_actual',
        'reportado_por',
        'asignado_a',
        'sla_vence_en',
        'cerrado_en',
    ];

    protected function casts(): array
    {
        return [
            'sla_vence_en' => 'datetime',
            'cerrado_en'   => 'datetime',
        ];
    }

    public function proyecto(): BelongsTo
    {
        return $this->belongsTo(Proyecto::class);
    }

    // Usuario que reportó el bug
    public function reportadoPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reportado_por');
    }

    // Desarrollador asignado al bug
    public function asignadoA(): BelongsTo
    {
        return $this->belongsTo(User::class, 'asignado_a');
    }

    public function historial(): HasMany
    {
        return $this->hasMany(BugHistorial::class);
    }

    // Un bug tiene como máximo una resolución
    public function resolucion(): HasOne
    {
        return $this->hasOne(Resolucion::class);
    }

    public function notificaciones(): HasMany
    {
        return $this->hasMany(Notificacion::class);
    }
}
