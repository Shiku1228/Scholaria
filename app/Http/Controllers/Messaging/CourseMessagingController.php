<?php

namespace App\Http\Controllers\Messaging;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreChatMessageRequest;
use App\Http\Requests\UpdateChatMessageRequest;
use App\Models\ChatConversation;
use App\Models\ChatGroup;
use App\Models\ChatMessage;
use App\Models\ChatMessageReaction;
use App\Models\Course;
use App\Models\User;
use App\Services\CourseChatGroupService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class CourseMessagingController extends Controller
{
    public function __construct(private readonly CourseChatGroupService $chatService)
    {
    }

    public function index(Request $request): View
    {
        $user = $request->user();
        $courses = $this->accessibleCourses($user);

        foreach ($courses as $course) {
            $this->chatService->syncCourseMembers($course);
        }

        $courseCards = $courses->map(function (Course $course) use ($user): array {
            $space = ChatGroup::query()
                ->where('course_id', (int) $course->id)
                ->with(['members:id', 'conversations.latestMessage'])
                ->first();

            $latestMessage = $space
                ? ChatMessage::query()->where('chat_group_id', (int) $space->id)->latest('created_at')->first()
                : null;

            return [
                'id' => (int) $course->id,
                'name' => trim(($course->course_number ? $course->course_number . ' - ' : '') . (string) $course->title),
                'member_count' => (int) ($space?->members?->count() ?? 0),
                'latest_preview' => $latestMessage ? (string) ($latestMessage->message ?: '[Attachment]') : 'No messages yet.',
                'unread_count' => $space ? $this->unreadCountForCourseSpace($space, (int) $user->id) : 0,
            ];
        })->values();

        return view('messages.index', ['courses' => $courseCards]);
    }

    public function course(Request $request, Course $course): View
    {
        $this->ensureCourseAccess($request->user(), $course);
        $space = $this->chatService->syncCourseMembers($course);
        $groupConversation = $this->chatService->resolveGroupConversation($course);

        $members = $space->members()
            ->select('users.id', 'users.name', 'users.email')
            ->where('users.id', '!=', (int) $request->user()->id)
            ->orderBy('users.name')
            ->get();

        $conversationItems = collect();
        $conversationItems->push($this->serializeConversationItem($groupConversation, $request->user()->id, 'Course Group Chat'));

        foreach ($members as $member) {
            $private = $this->chatService->resolvePrivateConversation($course, $request->user(), $member);
            if (!$private) {
                continue;
            }
            $conversationItems->push($this->serializeConversationItem($private, $request->user()->id, (string) $member->name));
        }

        $selectedId = (int) $request->query('conversation', (int) $groupConversation->id);
        $selected = $conversationItems->firstWhere('id', $selectedId) ?: $conversationItems->first();
        $selectedModel = $selected ? ChatConversation::query()->find((int) $selected['id']) : null;
        if ($selectedModel) {
            $selectedModel->participants()->updateExistingPivot((int) $request->user()->id, ['last_read_at' => now()]);
            $conversationItems = $conversationItems->map(function (array $item) use ($selectedModel) {
                if ((int) $item['id'] === (int) $selectedModel->id) {
                    $item['unread_count'] = 0;
                }
                return $item;
            })->values();
            if ($selected) {
                $selected['unread_count'] = 0;
            }
        }
        $memberSamples = $space->members()
            ->select('users.id', 'users.name')
            ->orderBy('users.name')
            ->limit(8)
            ->get()
            ->map(fn ($member) => [
                'id' => (int) $member->id,
                'name' => (string) $member->name,
            ])
            ->values();

        return view('messages.course', [
            'course' => $course,
            'conversationItems' => $conversationItems->values(),
            'selectedConversation' => $selected,
            'messages' => $selectedModel ? $this->messagesCollection($selectedModel) : collect(),
            'courseMemberCount' => (int) $space->members()->count(),
            'courseMemberSamples' => $memberSamples,
        ]);
    }

    public function members(Request $request, Course $course): JsonResponse
    {
        $this->ensureCourseAccess($request->user(), $course);
        $space = $this->chatService->syncCourseMembers($course);

        $members = $space->members()
            ->select('users.id', 'users.name', 'users.email')
            ->where('users.id', '!=', (int) $request->user()->id)
            ->orderBy('users.name')
            ->get();

        return response()->json(['members' => $members]);
    }

    public function conversations(Request $request, Course $course): JsonResponse
    {
        $this->ensureCourseAccess($request->user(), $course);
        $space = $this->chatService->syncCourseMembers($course);
        $groupConversation = $this->chatService->resolveGroupConversation($course);

        $items = collect();
        $items->push($this->serializeConversationItem($groupConversation, (int) $request->user()->id, 'Course Group Chat'));

        $members = $space->members()
            ->select('users.id', 'users.name')
            ->where('users.id', '!=', (int) $request->user()->id)
            ->orderBy('users.name')
            ->get();

        foreach ($members as $member) {
            $private = $this->chatService->resolvePrivateConversation($course, $request->user(), $member);
            if ($private) {
                $items->push($this->serializeConversationItem($private, (int) $request->user()->id, (string) $member->name));
            }
        }

        return response()->json(['conversations' => $items->values()]);
    }

    public function startPrivate(Request $request, Course $course, User $user): JsonResponse
    {
        $this->ensureCourseAccess($request->user(), $course);
        $conversation = $this->chatService->resolvePrivateConversation($course, $request->user(), $user);
        if (!$conversation) {
            abort(403, 'Not allowed to start private chat with this user in this course.');
        }

        $this->authorize('view', $conversation);

        return response()->json([
            'conversation' => $this->serializeConversationItem($conversation, (int) $request->user()->id, (string) $user->name),
        ]);
    }

    public function showConversation(Request $request, Course $course, ChatConversation $conversation): JsonResponse
    {
        $this->ensureCourseAccess($request->user(), $course);
        if ((int) $conversation->chat_group_id !== (int) optional($course->chatGroup)->id) {
            abort(403);
        }
        $this->authorize('view', $conversation);

        return response()->json([
            'conversation' => $this->serializeConversationItem($conversation, (int) $request->user()->id),
        ]);
    }

    public function conversationMessages(Request $request, ChatConversation $conversation): JsonResponse
    {
        $this->authorize('view', $conversation);
        $afterId = max(0, (int) $request->query('after_id', 0));

        $query = ChatMessage::query()
            ->with(['user:id,name', 'reactions:user_id,emoji,chat_message_id'])
            ->where('chat_conversation_id', (int) $conversation->id)
            ->orderBy('id');

        if ($afterId > 0) {
            $query->where('id', '>', $afterId);
        }

        $messages = $query->limit(100)->get()->map(fn (ChatMessage $m) => $this->serializeMessage($m));
        $conversation->participants()->updateExistingPivot((int) $request->user()->id, ['last_read_at' => now()]);

        return response()->json(['messages' => $messages]);
    }

    public function storeMessage(StoreChatMessageRequest $request, ChatConversation $conversation): JsonResponse
    {
        $this->authorize('sendMessage', $conversation);

        $messageText = trim((string) $request->input('message', ''));
        $attachmentPath = null;
        $attachmentName = null;
        $attachmentType = null;
        if ($request->hasFile('attachment')) {
            $file = $request->file('attachment');
            $attachmentPath = $file->store('chat-attachments/' . (int) $conversation->id, 'local');
            $attachmentName = (string) $file->getClientOriginalName();
            $attachmentType = (string) $file->getClientMimeType();
        }

        $message = ChatMessage::query()->create([
            'chat_group_id' => (int) $conversation->chat_group_id,
            'chat_conversation_id' => (int) $conversation->id,
            'user_id' => (int) $request->user()->id,
            'message' => $messageText !== '' ? $messageText : null,
            'attachment_path' => $attachmentPath,
            'attachment_name' => $attachmentName,
            'attachment_type' => $attachmentType,
            'is_system' => false,
        ])->load('user:id,name');

        $conversation->participants()->updateExistingPivot((int) $request->user()->id, ['last_read_at' => now()]);

        return response()->json(['message' => $this->serializeMessage($message)], 201);
    }

    public function updateMessage(UpdateChatMessageRequest $request, ChatMessage $message): JsonResponse
    {
        $this->authorize('update', $message);

        $message->update([
            'message' => trim((string) $request->input('message')),
            'edited_at' => now(),
        ]);

        return response()->json([
            'message' => $this->serializeMessage($message->fresh(['user:id,name', 'reactions:user_id,emoji,chat_message_id'])),
        ]);
    }

    public function deleteMessage(Request $request, ChatMessage $message): JsonResponse
    {
        $this->authorize('delete', $message);

        $message->update([
            'message' => null,
            'attachment_path' => null,
            'attachment_name' => null,
            'attachment_type' => null,
            'deleted_at' => now(),
            'deleted_by' => (int) $request->user()->id,
        ]);

        return response()->json([
            'message' => $this->serializeMessage($message->fresh(['user:id,name', 'reactions:user_id,emoji,chat_message_id'])),
        ]);
    }

    public function reactMessage(Request $request, ChatMessage $message): JsonResponse
    {
        $this->authorize('react', $message);
        $emoji = trim((string) $request->input('emoji', ''));
        if (!in_array($emoji, ['👍', '❤️', '😂', '😮', '😢'], true)) {
            return response()->json(['message' => 'Invalid reaction.'], 422);
        }

        ChatMessageReaction::query()->firstOrCreate([
            'chat_message_id' => (int) $message->id,
            'user_id' => (int) $request->user()->id,
            'emoji' => $emoji,
        ]);

        return response()->json([
            'message' => $this->serializeMessage($message->fresh(['user:id,name', 'reactions:user_id,emoji,chat_message_id'])),
        ]);
    }

    public function unreactMessage(Request $request, ChatMessage $message): JsonResponse
    {
        $this->authorize('react', $message);
        $emoji = trim((string) $request->input('emoji', ''));
        if (!in_array($emoji, ['👍', '❤️', '😂', '😮', '😢'], true)) {
            return response()->json(['message' => 'Invalid reaction.'], 422);
        }

        ChatMessageReaction::query()
            ->where('chat_message_id', (int) $message->id)
            ->where('user_id', (int) $request->user()->id)
            ->where('emoji', $emoji)
            ->delete();

        return response()->json([
            'message' => $this->serializeMessage($message->fresh(['user:id,name', 'reactions:user_id,emoji,chat_message_id'])),
        ]);
    }

    public function attachment(Request $request, ChatMessage $message)
    {
        $this->authorize('view', $message);
        if (!$message->attachment_path || !Storage::disk('local')->exists($message->attachment_path)) {
            abort(404);
        }

        $filename = $message->attachment_name ?: basename($message->attachment_path);
        $mime = (string) ($message->attachment_type ?: Storage::disk('local')->mimeType($message->attachment_path));

        if (str_starts_with(strtolower($mime), 'image/')) {
            return Storage::disk('local')->response(
                $message->attachment_path,
                $filename,
                [
                    'Content-Type' => $mime,
                    'Content-Disposition' => 'inline; filename="' . addslashes($filename) . '"',
                ]
            );
        }

        return Storage::disk('local')->download($message->attachment_path, $filename);
    }

    private function accessibleCourses(User $user)
    {
        if ($user->hasRole('Admin')) {
            return Course::query()->orderBy('title')->get();
        }
        if ($user->hasRole('Teacher')) {
            return Course::query()->where('teacher_id', (int) $user->id)->orderBy('title')->get();
        }

        $courseIds = \App\Models\Enrollment::query()
            ->where('student_id', (int) $user->id)
            ->where('status', '!=', 'dropped')
            ->pluck('course_id')
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values()
            ->all();

        return Course::query()->whereIn('id', $courseIds)->orderBy('title')->get();
    }

    private function ensureCourseAccess(User $user, Course $course): void
    {
        if (!$this->chatService->userHasCourseAccess($user, $course)) {
            abort(403);
        }
    }

    private function serializeConversationItem(ChatConversation $conversation, int $viewerId, ?string $label = null): array
    {
        $conversation->loadMissing(['participants:id,name', 'latestMessage']);
        $latest = $conversation->latestMessage->first();
        $lastReadAt = $conversation->participants()
            ->where('users.id', $viewerId)
            ->value('chat_conversation_user.last_read_at');

        $unread = ChatMessage::query()
            ->where('chat_conversation_id', (int) $conversation->id)
            ->where('user_id', '!=', $viewerId)
            ->when($lastReadAt, fn ($q) => $q->where('created_at', '>', $lastReadAt))
            ->count();

        if (!$label) {
            if ($conversation->type === 'group') {
                $label = $conversation->name ?: 'Course Group Chat';
            } else {
                $other = $conversation->participants->firstWhere('id', '!=', $viewerId);
                $label = $other ? (string) $other->name : 'Private Chat';
            }
        }

        return [
            'id' => (int) $conversation->id,
            'type' => (string) $conversation->type,
            'name' => (string) $label,
            'participant_count' => (int) $conversation->participants()->count(),
            'latest_preview' => $latest ? (string) ($latest->message ?: '[Attachment]') : 'No messages yet.',
            'latest_at' => $latest?->created_at?->toDateTimeString(),
            'unread_count' => (int) $unread,
        ];
    }

    private function messagesCollection(ChatConversation $conversation)
    {
        return ChatMessage::query()
            ->with(['user:id,name', 'reactions:user_id,emoji,chat_message_id'])
            ->where('chat_conversation_id', (int) $conversation->id)
            ->latest('id')
            ->limit(80)
            ->get()
            ->reverse()
            ->values()
            ->map(fn (ChatMessage $m) => $this->serializeMessage($m));
    }

    private function serializeMessage(ChatMessage $message): array
    {
        $reactions = $message->reactions
            ->groupBy('emoji')
            ->map(fn ($rows, $emoji) => [
                'emoji' => (string) $emoji,
                'count' => (int) $rows->count(),
                'mine' => $rows->contains(fn ($r) => (int) $r->user_id === (int) auth()->id()),
            ])
            ->values();

        $canEdit = false;
        $canDelete = false;
        $canReact = false;
        try {
            if (auth()->check()) {
                $canEdit = auth()->user()->can('update', $message);
                $canDelete = auth()->user()->can('delete', $message);
                $canReact = auth()->user()->can('react', $message);
            }
        } catch (\Throwable) {
        }

        return [
            'id' => (int) $message->id,
            'conversation_id' => (int) $message->chat_conversation_id,
            'user_id' => (int) ($message->user_id ?? 0),
            'user_name' => (string) data_get($message, 'user.name', 'System'),
            'message' => (string) ($message->message ?? ''),
            'attachment_name' => $message->attachment_name,
            'attachment_type' => $message->attachment_type,
            'attachment_url' => $message->attachment_path ? route('messages.attachment', $message) : null,
            'is_system' => (bool) $message->is_system,
            'is_deleted' => $message->deleted_at !== null,
            'is_edited' => $message->edited_at !== null,
            'can_edit' => $canEdit,
            'can_delete' => $canDelete,
            'can_react' => $canReact,
            'reactions' => $reactions,
            'created_at' => optional($message->created_at)->toDateTimeString(),
            'created_human' => optional($message->created_at)->diffForHumans(),
        ];
    }

    private function unreadCountForCourseSpace(ChatGroup $space, int $viewerId): int
    {
        $conversationIds = ChatConversation::query()
            ->where('chat_group_id', (int) $space->id)
            ->whereHas('participants', fn ($q) => $q->where('users.id', $viewerId))
            ->pluck('id')
            ->all();
        if (empty($conversationIds)) {
            return 0;
        }

        $lastReads = ChatConversation::query()
            ->whereIn('id', $conversationIds)
            ->get()
            ->mapWithKeys(function (ChatConversation $conv) use ($viewerId) {
                $val = $conv->participants()->where('users.id', $viewerId)->value('chat_conversation_user.last_read_at');
                return [(int) $conv->id => $val];
            });

        $unread = 0;
        foreach ($conversationIds as $conversationId) {
            $unread += ChatMessage::query()
                ->where('chat_conversation_id', (int) $conversationId)
                ->where('user_id', '!=', $viewerId)
                ->when($lastReads[(int) $conversationId] ?? null, fn ($q, $ts) => $q->where('created_at', '>', $ts))
                ->count();
        }

        return $unread;
    }
}
