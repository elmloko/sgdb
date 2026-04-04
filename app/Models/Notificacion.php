<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Notificacion extends Model
{
    protected $table = 'notificaciones';

    // Solo tiene created_at, sin updated_at
    public $timestamps = false;

    protected $fillable = [
        'user_id',
        'tipo',
        'titulo',
        'mensaje',
        'leido',
        'canal',
        'bug_id',
    ];

    protected function casts(): array
    {
        return [
            'leido'      => 'boolean',
            'created_at' => 'datetime',
        ];
    }

    // Destinatario de la notificación
    public function usuario(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    // Bug relacionado (nullable)
    public function bug(): BelongsTo
    {
        return $this->belongsTo(Bug::class);
    }
}
