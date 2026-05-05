<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\MailMessage;

class NewWorkOrderNotification extends Notification
{
    use Queueable;

    protected $workOrder;
    protected $userName;
    protected $propertyName;

    public function __construct($workOrder, $userName, $propertyName)
    {
        $this->workOrder = $workOrder;
        $this->userName = $userName;
        $this->propertyName = $propertyName;
    }

    public function via($notifiable)
    {
        // Enviamos por base de datos (para la campana en la app) y por correo
        return ['database', 'mail'];
    }

    public function toMail($notifiable)
    {
        $mail = (new MailMessage)
            ->subject('¡Nueva Solicitud de Servicio!')
            ->greeting('Hola Administrador,')
            ->line("El usuario **{$this->userName}** ha solicitado un servicio de **{$this->workOrder->type}**.")
            ->line("Propiedad: **{$this->propertyName}**")
            ->line("Zona: **{$this->workOrder->zone}**")
            ->line("Descripción: {$this->workOrder->description}");

        if ($this->workOrder->evidence_path) {
            $mail->line('Evidencia 1:')
                 ->line("![Foto 1]({$this->workOrder->evidence_path})");
        }

        if ($this->workOrder->evidence_path_2) {
            $mail->line('Evidencia 2:')
                 ->line("![Foto 2]({$this->workOrder->evidence_path_2})");
        }

        return $mail->action('Ver Levantamientos', url('/levantamientos'))
            ->line('Gracias por usar Agente Solutions.');
    }

    public function toArray($notifiable)
    {
        return [
            'work_order_id' => $this->workOrder->id,
            'alert_type' => 'new_work_order',
            'title' => 'Nueva Solicitud de Servicio',
            'message' => "{$this->userName} solicitó {$this->workOrder->type} en {$this->propertyName}",
            'url' => "/levantamientos"
        ];
    }
}
