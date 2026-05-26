<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class PropertyShareRevokedNotification extends Notification
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
            $message = "Has dejado de compartir tu propiedad '{$this->propertyName}' con {$this->guestName}.";
        } elseif ($this->role === 'guest') {
            $message = "{$this->ownerName} ha revocado tu acceso a su propiedad '{$this->propertyName}'.";
        } else {
            $message = "El cliente {$this->ownerName} ha dejado de compartir su propiedad '{$this->propertyName}' con el cliente {$this->guestName}.";
        }

        return [
            'alert_type' => 'property_share_revoked',
            'title' => 'Herencia Revocada',
            'message' => $message,
            'url' => "/propiedades"
        ];
    }
}
