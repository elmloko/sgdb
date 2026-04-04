<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Resolucion extends Model
{
    protected $table = 'resoluciones';

    // Solo tiene created_at, sin updated_at
    public $timestamps = false;

    protected $fillable = [
        'bug_id',
        'tipo',
        'causa_raiz',
        'solucion_aplicada',
        'archivos_modificados',
        'commit_ref',
        'requiere_deploy',
        'notas_qa',
        'prevencion_futura',
        'resuelto_por',
    ];

    protected function casts(): array
    {
        return [
            'archivos_modificados' => 'array',
            'requiere_deploy'      => 'boolean',
            'created_at'           => 'datetime',
        ];
    }

    public function bug(): BelongsTo
    {
        return $this->belongsTo(Bug::class);
    }

    // Desarrollador que resolvió el bug
    public function resueltoPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'resuelto_por');
    }
}
