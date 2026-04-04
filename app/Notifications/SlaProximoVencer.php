<?php

namespace App\Notifications;

use App\Models\Bug;
use App\Notifications\Channels\SistemaChannel;
use Illuminate\Notifications\Notification;

class SlaProximoVencer extends Notification
{
    public function __construct(private readonly Bug $bug) {}

    public function via(mixed $notifiable): array
    {
        return [SistemaChannel::class];
    }

    public function toSistema(mixed $notifiable): array
    {
        $minutos = (int) now()->diffInMinutes($this->bug->sla_vence_en);

        return [
            'tipo'    => 'sla_vence',
            'titulo'  => "SLA próximo a vencer — {$this->bug->ticket_num}",
            'mensaje' => "El bug \"{$this->bug->titulo}\" vence su SLA en {$minutos} minuto(s). "
                        . "Prioridad: {$this->bug->prioridad}.",
            'canal'   => 'sistema',
            'bug_id'  => $this->bug->id,
        ];
    }
}
