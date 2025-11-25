<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class TeacherPromoted extends Notification implements ShouldQueue
{
    use Queueable;

    private $newLevel;

    public function __construct($newLevel)
    {
        $this->newLevel = $newLevel;
    }

    public function via($notifiable)
    {
        return ['mail', 'database']; // Email + App Notification
    }

    public function toMail($notifiable)
    {
        return (new MailMessage)
                    ->subject('Congratulations! You got promoted')
                    ->line("You have been promoted to {$this->newLevel}. Your pay has increased accordingly!")
                    ->action('View Profile', url('/profile'))
                    ->line('Keep up the great teaching!');
    }

    public function toArray($notifiable)
    {
        return [
            'message' => "You have been promoted to {$this->newLevel}!",
            'level' => $this->newLevel
        ];
    }
}