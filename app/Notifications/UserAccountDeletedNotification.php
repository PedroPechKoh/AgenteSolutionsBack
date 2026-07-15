<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class UserAccountDeletedNotification extends Notification
{
    use Queueable;

    protected $user;

    public function __construct($user)
    {
        $this->user = $user;
    }

    public function via($notifiable)
    {
        return ['database'];
    }

    public function toArray($notifiable)
    {
        $roleNombre = match((int)$this->user->role_id) {
            2 => 'Técnico Externo',
            3 => 'Cliente',
            4 => 'Autónomo (Empresarial)',
            5 => 'Autónomo (Personal)',
            default => 'Usuario'
        };

        $urlDestino = ((int)$this->user->role_id === 2) ? '/vista-tecnicos' : '/usuarios';

        return [
            'alert_type' => 'user_account_deleted',
            'user_id'    => $this->user->id,
            'role_id'    => $this->user->role_id,
            'title'      => '⚠️ Perfil Eliminado por Usuario',
            'message'    => "El {$roleNombre} {$this->user->name} ({$this->user->email}) ha decidido eliminar y desactivar su perfil.",
            'url'        => $urlDestino
        ];
    }
}
