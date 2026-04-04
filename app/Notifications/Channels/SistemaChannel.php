<?php

namespace App\Notifications\Channels;

use App\Models\Notificacion;
use Illuminate\Notifications\Notification;

/**
 * Canal personalizado que persiste las notificaciones en la tabla
 * `notificaciones` del sistema (no en la tabla `notifications` de Laravel).
 *
 * Uso en cada clase Notification:
 *   public function via($notifiable): array { return [SistemaChannel::class]; }
 *   public function toSistema($notifiable): array { return [...]; }
 */
class SistemaChannel
{
    public function send(mixed $notifiable, Notification $notification): void
    {
        if (!method_exists($notification, 'toSistema')) {
            return;
        }

        $data = $notification->toSistema($notifiable);

        Notificacion::create([
            'user_id'    => $notifiable->id,
            'tipo'       => $data['tipo'],
            'titulo'     => $data['titulo'],
            'mensaje'    => $data['mensaje'],
            'canal'      => $data['canal'] ?? 'sistema',
            'bug_id'     => $data['bug_id'] ?? null,
            'leido'      => false,
        ]);
    }
}
