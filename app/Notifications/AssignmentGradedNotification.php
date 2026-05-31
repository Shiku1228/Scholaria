<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class AssignmentGradedNotification extends Notification
{
    use Queueable;

    public $courseId;
    public $title;
    public $message;
    public $url;

    public function __construct($courseId, $title, $message, $url)
    {
        $this->courseId = $courseId;
        $this->title = $title;
        $this->message = $message;
        $this->url = $url;
    }

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'assignment_graded',
            'course_id' => $this->courseId,
            'title' => $this->title,
            'message' => $this->message,
            'url' => $this->url,
            'icon' => 'check-circle'
        ];
    }
}
