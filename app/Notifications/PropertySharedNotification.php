<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class PropertySharedNotification extends Notification
{
    use Queueable;

    protected $ownerName;
    protected $guestName;
    protected $propertyName;
    protected $role; // 'owner', 'guest', 'admin'

    public function __construct($ownerName, $guestName, $propertyName, $role)
    {
        $this->ownerName = $ownerName;
        $this->guestName = $guestName;
        $this->propertyName = $propertyName;
        $this->role = $role;
    }

    public function via($notifiable)
    {
        return ['database']; // Opcional: OneSignal si aplica
    }

    public function toArray($notifiable)
    {
        $message = "";
        if ($this->role === 'owner') {
            $message = "Has compartido tu propiedad '{$this->propertyName}' con {$this->guestName}.";
        } elseif ($this->role === 'guest') {
            $message = "{$this->ownerName} te ha compartido su propiedad '{$this->propertyName}'.";
        } else {
            $message = "El cliente {$this->ownerName} ha compartido su propiedad '{$this->propertyName}' con el cliente {$this->guestName}.";
        }

        return [
            'alert_type' => 'property_shared',
            'title' => 'Propiedad Compartida',
            'message' => $message,
            'url' => "/propiedades"
        ];
    }
}
