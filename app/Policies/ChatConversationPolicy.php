<?php

namespace App\Policies;

use App\Models\ChatConversation;
use App\Models\User;
use App\Services\CourseChatGroupService;

class ChatConversationPolicy
{
    public function view(User $user, ChatConversation $conversation): bool
    {
        $course = optional($conversation->courseSpace)->course;
        if (!$course) {
            return false;
        }

        if (!app(CourseChatGroupService::class)->userHasCourseAccess($user, $course)) {
            return false;
        }

        if ($conversation->type === 'group') {
            return true;
        }

        return $conversation->participants()
            ->where('users.id', (int) $user->id)
            ->exists();
    }

    public function sendMessage(User $user, ChatConversation $conversation): bool
    {
        return $this->view($user, $conversation);
    }
}

