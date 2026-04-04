<?php

namespace App\Notifications;

use App\Models\Bug;
use App\Notifications\Channels\SistemaChannel;
use Illuminate\Notifications\Notification;

class SlaVencido extends Notification
{
    public function __construct(private readonly Bug $bug) {}

    public function via(mixed $notifiable): array
    {
        return [SistemaChannel::class];
    }

    public function toSistema(mixed $notifiable): array
    {
        $minutosVencido = (int) $this->bug->sla_vence_en->diffInMinutes(now());

        return [
            'tipo'    => 'sla_vence',
            'titulo'  => "SLA vencido — {$this->bug->ticket_num}",
            'mensaje' => "El bug \"{$this->bug->titulo}\" superó su tiempo máximo de resolución "
                        . "hace {$minutosVencido} minuto(s). Requiere atención inmediata.",
            'canal'   => 'sistema',
            'bug_id'  => $this->bug->id,
        ];
    }
}
