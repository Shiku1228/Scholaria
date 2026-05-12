<?php

namespace App\Services;

use App\Models\ChatConversation;
use App\Models\ChatGroup;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;

class CourseChatGroupService
{
    public function resolveForCourse(Course $course): ChatGroup
    {
        return ChatGroup::query()->updateOrCreate(
            ['course_id' => (int) $course->id],
            [
                'name' => $this->buildGroupName($course),
                'type' => 'course',
            ]
        );
    }

    public function resolveGroupConversation(Course $course): ChatConversation
    {
        $courseSpace = $this->resolveForCourse($course);

        return ChatConversation::query()->updateOrCreate(
            [
                'chat_group_id' => (int) $courseSpace->id,
                'type' => 'group',
            ],
            [
                'name' => 'Course Group Chat',
            ]
        );
    }

    public function resolvePrivateConversation(Course $course, User $actor, User $target): ?ChatConversation
    {
        $eligible = $this->eligibleMemberIds($course);
        if (!in_array((int) $actor->id, $eligible, true) || !in_array((int) $target->id, $eligible, true)) {
            return null;
        }

        $courseSpace = $this->resolveForCourse($course);

        $existing = ChatConversation::query()
            ->where('chat_group_id', (int) $courseSpace->id)
            ->where('type', 'private')
            ->whereHas('participants', fn ($q) => $q->where('users.id', (int) $actor->id))
            ->whereHas('participants', fn ($q) => $q->where('users.id', (int) $target->id))
            ->withCount('participants')
            ->get()
            ->first(fn ($conversation) => (int) $conversation->participants_count === 2);

        if (!$existing) {
            $existing = ChatConversation::query()->create([
                'chat_group_id' => (int) $courseSpace->id,
                'type' => 'private',
                'name' => null,
                'created_by' => (int) $actor->id,
            ]);
        }

        $now = now();
        $existing->participants()->syncWithoutDetaching([
            (int) $actor->id => ['joined_at' => $now],
            (int) $target->id => ['joined_at' => $now],
        ]);

        return $existing->fresh(['participants']);
    }

    public function syncCourseMembers(Course $course): ChatGroup
    {
        $courseSpace = $this->resolveForCourse($course);
        $groupConversation = $this->resolveGroupConversation($course);

        $eligible = $this->eligibleMembers($course);
        $payload = [];
        $now = now();
        foreach ($eligible as $member) {
            $payload[(int) $member['user_id']] = [
                'role_in_group' => (string) $member['role_in_group'],
                'joined_at' => $now,
            ];
        }

        if (!empty($payload)) {
            $courseSpace->members()->syncWithoutDetaching($payload);
        }

        $keepUserIds = array_map('intval', array_keys($payload));
        if (!empty($keepUserIds)) {
            $courseSpace->members()->wherePivotNotIn('user_id', $keepUserIds)->detach();
        } else {
            $courseSpace->members()->detach();
        }

        $convPayload = [];
        foreach ($keepUserIds as $userId) {
            $convPayload[$userId] = ['joined_at' => $now];
        }
        if (!empty($convPayload)) {
            $groupConversation->participants()->syncWithoutDetaching($convPayload);
        }

        if (!empty($keepUserIds)) {
            $groupConversation->participants()->wherePivotNotIn('user_id', $keepUserIds)->detach();
        } else {
            $groupConversation->participants()->detach();
        }

        ChatConversation::query()
            ->where('chat_group_id', (int) $courseSpace->id)
            ->where('type', 'private')
            ->each(function (ChatConversation $conversation) use ($keepUserIds): void {
                if (empty($keepUserIds)) {
                    $conversation->participants()->detach();
                    return;
                }

                $conversation->participants()->wherePivotNotIn('user_id', $keepUserIds)->detach();
            });

        return $courseSpace->fresh(['members', 'conversations']);
    }

    public function syncAllCourses(): int
    {
        $count = 0;
        Course::query()->orderBy('id')->chunk(100, function ($courses) use (&$count): void {
            foreach ($courses as $course) {
                $this->syncCourseMembers($course);
                $count++;
            }
        });

        return $count;
    }

    public function userHasCourseAccess(User $user, Course $course): bool
    {
        if ($user->hasRole('Admin')) {
            return true;
        }
        if ($user->hasRole('Teacher')) {
            return (int) $course->teacher_id === (int) $user->id;
        }

        $query = Enrollment::query()
            ->where('course_id', (int) $course->id)
            ->where('student_id', (int) $user->id);

        if (Schema::hasColumn('enrollments', 'status')) {
            $query->where('status', '!=', 'dropped');
        }

        return $query->exists();
    }

    public function eligibleMemberIds(Course $course): array
    {
        return $this->eligibleMembers($course)->pluck('user_id')->map(fn ($v) => (int) $v)->values()->all();
    }

    private function eligibleMembers(Course $course): Collection
    {
        $members = collect();

        $teacherId = (int) ($course->teacher_id ?? 0);
        if ($teacherId > 0) {
            $teacherExists = User::query()->whereKey($teacherId)->whereNull('deleted_at')->exists();
            if ($teacherExists) {
                $members->push(['user_id' => $teacherId, 'role_in_group' => 'teacher']);
            }
        }

        $studentQuery = Enrollment::query()
            ->where('course_id', (int) $course->id)
            ->whereHas('student', fn ($q) => $q->whereNull('deleted_at'));

        if (Schema::hasColumn('enrollments', 'status')) {
            $studentQuery->where('status', '!=', 'dropped');
        }

        $studentIds = $studentQuery->pluck('student_id')
            ->map(fn ($id) => (int) $id)
            ->filter(fn ($id) => $id > 0)
            ->unique()
            ->values();

        foreach ($studentIds as $studentId) {
            $members->push(['user_id' => $studentId, 'role_in_group' => 'student']);
        }

        return $members->unique(fn ($row) => (int) $row['user_id'])->values();
    }

    private function buildGroupName(Course $course): string
    {
        $number = trim((string) ($course->course_number ?? ''));
        $title = trim((string) ($course->title ?? ''));
        if ($number !== '' && $title !== '') {
            return $number . ' - ' . $title;
        }
        if ($title !== '') {
            return $title;
        }
        if ($number !== '') {
            return $number;
        }
        return 'Course #' . (int) $course->id;
    }
}
