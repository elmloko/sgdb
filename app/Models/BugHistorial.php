<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BugHistorial extends Model
{
    protected $table = 'bug_historial';

    // Tabla inmutable: solo tiene created_at, sin updated_at
    public $timestamps = false;

    protected $fillable = [
        'bug_id',
        'user_id',
        'accion',
        'valor_anterior',
        'valor_nuevo',
        'comentario',
    ];

    protected function casts(): array
    {
        return [
            'created_at' => 'datetime',
        ];
    }

    public function bug(): BelongsTo
    {
        return $this->belongsTo(Bug::class);
    }

    // Usuario que realizó la acción
    public function usuario(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
