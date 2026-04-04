<?php

namespace App\Notifications;

use App\Models\Bug;
use App\Notifications\Channels\SistemaChannel;
use Illuminate\Notifications\Notification;

class BugReabierto extends Notification
{
    public function __construct(
        private readonly Bug $bug,
        private readonly string $motivo = ''
    ) {}

    public function via(mixed $notifiable): array
    {
        return [SistemaChannel::class];
    }

    public function toSistema(mixed $notifiable): array
    {
        $mensaje = "El bug \"{$this->bug->titulo}\" ({$this->bug->ticket_num}) ha sido reabierto.";

        if ($this->motivo) {
            $mensaje .= " Motivo: {$this->motivo}";
        }

        return [
            'tipo'    => 'escalamiento',
            'titulo'  => "Bug reabierto: {$this->bug->ticket_num}",
            'mensaje' => $mensaje,
            'canal'   => 'sistema',
            'bug_id'  => $this->bug->id,
        ];
    }
}
