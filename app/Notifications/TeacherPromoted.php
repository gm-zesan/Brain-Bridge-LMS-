<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class TeacherPromoted extends Notification
{
    // use Queueable;

    private string $newLevel;
    private float $newBasePay;

    public function __construct(string $newLevel, float $newBasePay)
    {
        $this->newLevel = $newLevel;
        $this->newBasePay = $newBasePay;
    }

    public function via($notifiable): array
    {
        return ['mail', 'database']; // Email + App Notification
    }

    public function toMail($notifiable): MailMessage
    {
        return (new MailMessage)
                    ->subject('🎉 Congratulations! You got promoted')
                    ->line("You have been promoted to **{$this->newLevel}** level!")
                    ->line("Your new base pay is: $" . number_format($this->newBasePay, 2) . " per session")
                    ->line('Your pay has increased accordingly!')
                    ->action('View Profile', url('/profile'))
                    ->line('Keep up the great teaching!');
    }

    public function toArray($notifiable): array
    {
        return [
            'message' => "You have been promoted to {$this->newLevel}! New base pay: $" . number_format($this->newBasePay, 2),
            'level' => $this->newLevel,
            'new_base_pay' => $this->newBasePay
        ];
    }
}