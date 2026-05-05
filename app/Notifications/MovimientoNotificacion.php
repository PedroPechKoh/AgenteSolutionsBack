<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use NotificationChannels\OneSignal\OneSignalChannel;
use NotificationChannels\OneSignal\OneSignalMessage;

class MovimientoNotificacion extends Notification
{
    use Queueable;

    protected $datosMovimiento;

    public function __construct($datosMovimiento)
    {
        $this->datosMovimiento = $datosMovimiento;
    }

    // Aquí le decimos a Laravel que guarde en la BD y envíe por OneSignal
    public function via($notifiable)
    {
        return ['database', OneSignalChannel::class];
    }

    // 1. LO QUE SE GUARDA PARA TU CAMPANITA EN REACT
    public function toDatabase($notifiable)
    {
        return [
            'titulo' => $this->datosMovimiento['titulo'] ?? 'Nuevo Movimiento',
            'mensaje' => $this->datosMovimiento['mensaje'],
            'url' => $this->datosMovimiento['url']
        ];
    }

    // 2. LO QUE HACE VIBRAR EL TELÉFONO DEL USUARIO
    public function toOneSignal($notifiable)
    {
        return OneSignalMessage::create()
            ->setSubject($this->datosMovimiento['titulo'] ?? 'Design Async')
            ->setBody($this->datosMovimiento['mensaje'])
            ->setUrl(url($this->datosMovimiento['url'])); // Redirige a la ruta correcta
    }
}
