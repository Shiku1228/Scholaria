<?php

namespace App\Policies;

use App\Models\ChatMessage;
use App\Models\User;
use App\Services\CourseChatGroupService;

class ChatMessagePolicy
{
    public function view(User $user, ChatMessage $message): bool
    {
        $conversation = $message->conversation()->with('courseSpace.course')->first();
        if ($conversation) {
            return app(ChatConversationPolicy::class)->view($user, $conversation);
        }

        $group = $message->group()->with('course')->first();
        if (!$group || !$group->course) {
            return false;
        }
        return app(CourseChatGroupService::class)->userHasCourseAccess($user, $group->course);
    }

    public function update(User $user, ChatMessage $message): bool
    {
        if (!$this->view($user, $message)) {
            return false;
        }

        if ((int) $message->user_id !== (int) $user->id) {
            return false;
        }

        if ($message->deleted_at !== null) {
            return false;
        }

        return optional($message->created_at)->gt(now()->subMinutes(3)) ?? false;
    }

    public function delete(User $user, ChatMessage $message): bool
    {
        if (!$this->view($user, $message)) {
            return false;
        }

        return (int) $message->user_id === (int) $user->id || $user->hasRole('Admin');
    }

    public function react(User $user, ChatMessage $message): bool
    {
        return $this->view($user, $message) && $message->deleted_at === null;
    }
}
