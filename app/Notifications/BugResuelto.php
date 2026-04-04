<?php

namespace App\Notifications;

use App\Models\Bug;
use App\Notifications\Channels\SistemaChannel;
use Illuminate\Notifications\Notification;

class BugResuelto extends Notification
{
    public function __construct(private readonly Bug $bug) {}

    public function via(mixed $notifiable): array
    {
        return [SistemaChannel::class];
    }

    public function toSistema(mixed $notifiable): array
    {
        $tipo = $this->bug->resolucion?->tipo ?? 'correccion';

        return [
            'tipo'    => 'resolucion',
            'titulo'  => "Bug resuelto: {$this->bug->ticket_num}",
            'mensaje' => "El bug \"{$this->bug->titulo}\" ha sido resuelto. "
                        . "Puedes verificar la solución y cerrarlo.",
            'canal'   => 'sistema',
            'bug_id'  => $this->bug->id,
        ];
    }
}
