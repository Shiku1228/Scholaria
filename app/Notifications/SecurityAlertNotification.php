<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Messages\NexmoMessage;

class SecurityAlertNotification extends Notification implements ShouldQueue
{
    use Queueable;

    protected $alert;

    /**
     * Create a new notification instance.
     */
    public function __construct(array $alert)
    {
        $this->alert = $alert;
    }

    /**
     * Get the notification's delivery channels.
     */
    public function via($notifiable)
    {
        return $this->alert['channels'] ?? ['mail'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail($notifiable)
    {
        $subject = "[{$this->alert['severity']}] Security Alert: {$this->alert['message']}";
        
        return (new MailMessage)
            ->subject($subject)
            ->view('emails.security-alert', [
                'alert' => $this->alert,
            ]);
    }

    /**
     * Get the Nexmo representation of the notification.
     */
    public function toNexmo($notifiable)
    {
        if (in_array('sms', $this->alert['channels'] ?? [])) {
            return (new NexmoMessage)
                ->content($this->formatSmsMessage());
        }
    }

    /**
     * Format SMS message.
     */
    private function formatSmsMessage(): string
    {
        $severity = strtoupper($this->alert['severity']);
        $message = $this->alert['message'];
        
        return "[{$severity}] {$message}";
    }

    /**
     * Get the array representation of the notification.
     */
    public function toArray($notifiable)
    {
        return [
            'alert_id' => $this->alert['id'],
            'type' => $this->alert['type'],
            'severity' => $this->alert['severity'],
            'message' => $this->alert['message'],
            'data' => $this->alert['data'],
        ];
    }
}
