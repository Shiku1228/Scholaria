<?php

namespace App\Policies;

use App\Models\ChatGroup;
use App\Models\User;
use App\Services\CourseChatGroupService;

class ChatGroupPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, ChatGroup $group): bool
    {
        $course = $group->course()->first();
        if (!$course) {
            return false;
        }
        return app(CourseChatGroupService::class)->userHasCourseAccess($user, $course);
    }

    public function sendMessage(User $user, ChatGroup $group): bool
    {
        return $this->view($user, $group);
    }
}
