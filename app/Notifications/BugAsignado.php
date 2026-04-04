<?php

namespace App\Notifications;

use App\Models\Bug;
use App\Notifications\Channels\SistemaChannel;
use Illuminate\Notifications\Notification;

class BugAsignado extends Notification
{
    public function __construct(private readonly Bug $bug) {}

    public function via(mixed $notifiable): array
    {
        return [SistemaChannel::class];
    }

    public function toSistema(mixed $notifiable): array
    {
        return [
            'tipo'    => 'asignacion',
            'titulo'  => "Bug asignado: {$this->bug->ticket_num}",
            'mensaje' => "Se te ha asignado el bug \"{$this->bug->titulo}\" "
                        . "(prioridad: {$this->bug->prioridad}).",
            'canal'   => 'sistema',
            'bug_id'  => $this->bug->id,
        ];
    }
}
