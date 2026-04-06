<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BugAdjunto extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'bug_id',
        'nombre_original',
        'ruta',
        'mime_type',
        'tamanio',
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
}
