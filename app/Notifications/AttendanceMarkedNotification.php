<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class AttendanceMarkedNotification extends Notification
{
    use Queueable;

    public function __construct(
        private readonly int $courseId,
        private readonly string $courseLabel,
        private readonly string $date,
        private readonly string $status,
        private readonly ?string $remarks = null,
    ) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        $message = "Your attendance for {$this->courseLabel} on {$this->date} was marked as {$this->status}.";
        if ($this->remarks) {
            $message .= " Remarks: {$this->remarks}";
        }

        return [
            'type'      => 'attendance_marked',
            'course_id' => $this->courseId,
            'title'     => 'Attendance Updated',
            'message'   => $message,
            'url'       => '/student/attendance?course_id=' . $this->courseId,
            'icon'      => 'clipboard-check',
        ];
    }
}
