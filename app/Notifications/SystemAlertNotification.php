<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class SystemAlertNotification extends Notification
{
    use Queueable;

    public $title;
    public $message;
    public $icon;
    public $tone;

    // Pass the dynamic data when triggering the notification
    public function __construct($title, $message, $icon = 'fa-bell', $tone = 'text-info')
    {
        $this->title = $title;
        $this->message = $message;
        $this->icon = $icon;
        $this->tone = $tone;
    }

    // Tell Laravel to save this ONLY in the database
    public function via($notifiable)
    {
        return ['database'];
    }

    // Structure the JSON data that gets saved to the database
    public function toDatabase($notifiable)
    {
        return [
            'title'   => $this->title,
            'message' => $this->message,
            'icon'    => $this->icon,
            'tone'    => $this->tone,
        ];
    }
}
