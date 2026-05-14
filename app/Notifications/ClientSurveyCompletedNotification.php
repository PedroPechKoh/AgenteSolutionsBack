<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class ClientSurveyCompletedNotification extends Notification
{
    use Queueable;

    protected $property;

    public function __construct($property)
    {
        $this->property = $property;
    }

    public function via($notifiable)
    {
        return ['database'];
    }

    public function toArray($notifiable)
    {
        $clienteNombre = $this->property->propietario ?? 'Un cliente';

        return [
            'property_id' => $this->property->id,
            'alert_type' => 'client_survey_completed',
            'title' => '¡Levantamiento de Cliente Finalizado!',
            'message' => "El cliente {$clienteNombre} ha terminado de registrar las zonas y componentes de su propiedad ({$this->property->custom_curp}).",
            'url' => "/detalle-propiedad/{$this->property->id}" 
        ];
    }
}
