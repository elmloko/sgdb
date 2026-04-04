<?php

namespace App\Notifications;

use App\Models\Bug;
use App\Models\User;
use App\Notifications\Channels\SistemaChannel;
use Illuminate\Notifications\Notification;

class NuevoComentario extends Notification
{
    public function __construct(
        private readonly Bug  $bug,
        private readonly User $autor,
        private readonly string $comentario
    ) {}

    public function via(mixed $notifiable): array
    {
        return [SistemaChannel::class];
    }

    public function toSistema(mixed $notifiable): array
    {
        $extracto = mb_strimwidth($this->comentario, 0, 120, '...');

        return [
            'tipo'    => 'comentario',
            'titulo'  => "Comentario en {$this->bug->ticket_num}",
            'mensaje' => "{$this->autor->name} comentó en \"{$this->bug->titulo}\": {$extracto}",
            'canal'   => 'sistema',
            'bug_id'  => $this->bug->id,
        ];
    }
}
