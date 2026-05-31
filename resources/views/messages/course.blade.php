@php
    $user = auth()->user();
    $isTeacher = $user && method_exists($user, 'hasRole') && $user->hasRole('Teacher');
    $isStudent = $user && method_exists($user, 'hasRole') && $user->hasRole('Student');
    $layout = $isTeacher ? 'layouts.teacher' : ($isStudent ? 'layouts.student' : 'layouts.dashboard');
@endphp

@extends($layout)

@section('content')
    <div class="mx-auto w-full max-w-[1680px] h-[calc(100vh-7rem)] min-h-[640px]">
        <div class="grid grid-cols-1 md:grid-cols-[310px_1fr] lg:grid-cols-[300px_1fr_280px] gap-3 h-full">
            <aside class="rounded-2xl border border-slate-200 bg-slate-50 shadow-sm flex flex-col min-h-0 overflow-hidden">
                <div class="p-4 border-b border-slate-200 bg-white space-y-2.5">
                    <a href="{{ route('messages.index') }}" class="inline-flex items-center gap-1.5 text-xs font-semibold text-[#0b2d6b] hover:text-[#0a275c] transition-colors">
                        <i data-lucide="arrow-left" class="w-3.5 h-3.5"></i>
                        Back to courses
                    </a>
                    <div class="text-xl font-semibold text-slate-900 leading-tight">Messages</div>
                    <div class="text-sm font-medium text-slate-700 truncate">{{ ($course->course_number ? $course->course_number . ' - ' : '') . $course->title }}</div>
                    <div class="text-xs text-slate-500">Course messaging space</div>
                    <div class="relative">
                        <i data-lucide="search" class="h-4 w-4 text-slate-400 absolute left-3 top-1/2 -translate-y-1/2"></i>
                        <input id="conversationSearch" type="text" placeholder="Search member or chat..." class="h-10 w-full rounded-xl border border-slate-300 bg-white pl-9 pr-3 text-sm text-slate-700 focus:border-[#0b2d6b] focus:ring-[#0b2d6b]" />
                    </div>
                </div>
                <div id="conversationList" class="flex-1 overflow-y-auto p-2.5 space-y-1.5"></div>
            </aside>

            <section class="rounded-2xl border border-slate-200 shadow-sm flex flex-col min-h-0 bg-white overflow-hidden">
                <div class="h-16 sticky top-0 z-10 border-b border-slate-200 px-5 flex items-center justify-between bg-gradient-to-b from-white to-slate-50">
                    <div>
                        <div id="conversationTitle" class="text-sm font-semibold text-slate-900">Conversation</div>
                        <div id="conversationMeta" class="text-xs text-slate-500">Course messaging</div>
                    </div>
                    <div id="conversationCount" class="text-xs text-slate-500 hidden sm:block"></div>
                </div>

                <div id="messagePane" class="flex-1 min-h-0 overflow-y-auto bg-slate-50/80 px-5 py-5 space-y-2.5"></div>

                <div class="sticky bottom-0 border-t border-slate-200 bg-white p-3.5">
                    <form id="messageForm" class="flex items-center gap-2">
                        @csrf
                        <label for="attachmentInput" class="inline-flex items-center justify-center h-11 w-11 shrink-0 rounded-xl border border-slate-300 bg-slate-50 text-slate-600 hover:bg-slate-100 transition-colors cursor-pointer">
                            <i data-lucide="paperclip" class="h-4 w-4"></i>
                        </label>
                        <input id="attachmentInput" name="attachment" type="file" class="hidden" />
                        <div class="flex-1 min-w-0">
                            <input id="messageInput" name="message" type="text" maxlength="2000" placeholder="Type a message..." class="h-11 w-full rounded-xl border border-slate-300 bg-slate-50 px-3 text-sm text-slate-700 focus:bg-white focus:border-[#0b2d6b] focus:ring-[#0b2d6b]" />
                            <div id="attachmentMeta" class="mt-1 text-xs text-slate-500 truncate hidden"></div>
                        </div>
                        <button type="submit" class="h-11 px-5 shrink-0 rounded-xl bg-[#0b2d6b] text-white text-sm font-semibold hover:bg-[#0a275c] transition-colors">Send</button>
                    </form>
                </div>
            </section>

            <aside class="hidden lg:flex rounded-2xl border border-slate-200 bg-white shadow-sm flex-col min-h-0 overflow-hidden">
                <div class="p-5 border-b border-slate-200 bg-slate-50">
                    <div class="mx-auto h-14 w-14 rounded-full bg-[#eaf0fb] border border-[#c9d7f2] text-[#0b2d6b] flex items-center justify-center text-lg font-semibold" id="detailsAvatar">C</div>
                    <div id="detailsTitle" class="mt-3 text-center text-sm font-semibold text-slate-900">Conversation</div>
                    <div id="detailsSubtitle" class="mt-1 text-center text-xs text-slate-500">Course chat details</div>
                </div>
                <div class="flex-1 min-h-0 overflow-y-auto p-4 space-y-4">
                    <div class="rounded-xl border border-slate-200 bg-slate-50 p-3">
                        <div class="text-xs font-semibold text-slate-700">Overview</div>
                        <div class="mt-2 text-xs text-slate-500 space-y-1">
                            <div>Course: <span class="text-slate-700 font-medium">{{ ($course->course_number ? $course->course_number . ' - ' : '') . $course->title }}</span></div>
                            <div id="detailsType">Type: --</div>
                            <div id="detailsMembers">Members: {{ $courseMemberCount ?? 0 }}</div>
                        </div>
                    </div>

                    <div class="rounded-xl border border-slate-200 bg-white p-3">
                        <div class="text-xs font-semibold text-slate-700">Course Members</div>
                        <div id="detailsMemberList" class="mt-2 space-y-1.5">
                            @foreach (($courseMemberSamples ?? collect()) as $member)
                                <div class="flex items-center gap-2 text-xs text-slate-600">
                                    <span class="h-6 w-6 rounded-full border border-slate-200 bg-slate-100 text-slate-700 font-semibold flex items-center justify-center">{{ strtoupper(substr((string) $member['name'], 0, 1)) }}</span>
                                    <span class="truncate">{{ $member['name'] }}</span>
                                </div>
                            @endforeach
                        </div>
                    </div>

                    <div class="rounded-xl border border-dashed border-slate-300 bg-slate-50 p-3">
                        <div class="text-xs font-semibold text-slate-700">Shared Files</div>
                        <div id="detailsSharedFiles" class="mt-2 space-y-1.5">
                            <div class="text-xs text-slate-500">Attachments and media will appear here as conversations grow.</div>
                        </div>
                    </div>
                </div>
            </aside>
        </div>
    </div>

    <script>
        (function () {
            let conversations = @json($conversationItems->values());
            let selectedConversation = @json($selectedConversation);
            let messages = @json($messages->values());
            let lastMessageId = messages.length ? messages[messages.length - 1].id : 0;
            const csrf = '{{ csrf_token() }}';
            const myUserId = {{ (int) auth()->id() }};
            const courseId = {{ (int) $course->id }};
            let previousUnreadMap = Object.fromEntries(conversations.map(c => [c.id, c.unread_count || 0]));

            const searchEl = document.getElementById('conversationSearch');
            const listEl = document.getElementById('conversationList');
            const paneEl = document.getElementById('messagePane');
            const titleEl = document.getElementById('conversationTitle');
            const metaEl = document.getElementById('conversationMeta');
            const countEl = document.getElementById('conversationCount');
            const formEl = document.getElementById('messageForm');
            const inputEl = document.getElementById('messageInput');
            const attachmentInput = document.getElementById('attachmentInput');
            const attachmentMeta = document.getElementById('attachmentMeta');
            let messageErrorEl = document.getElementById('messageError');
            if (!messageErrorEl) {
                messageErrorEl = document.createElement('div');
                messageErrorEl.id = 'messageError';
                messageErrorEl.className = 'mt-1 text-xs text-red-600 hidden';
                attachmentMeta.parentElement.appendChild(messageErrorEl);
            }
            const detailsAvatarEl = document.getElementById('detailsAvatar');
            const detailsTitleEl = document.getElementById('detailsTitle');
            const detailsSubtitleEl = document.getElementById('detailsSubtitle');
            const detailsTypeEl = document.getElementById('detailsType');
            const detailsMembersEl = document.getElementById('detailsMembers');
            const detailsSharedFilesEl = document.getElementById('detailsSharedFiles');
            const sidebarMessageUnreadBadge = document.getElementById('sidebarMessageUnreadBadge');
            const toastHost = document.createElement('div');
            toastHost.className = 'fixed top-20 right-6 z-[2500] space-y-2';
            document.body.appendChild(toastHost);

            function timeText(v) {
                if (!v) return '';
                const d = new Date(v.replace(' ', 'T'));
                if (Number.isNaN(d.getTime())) return '';
                return d.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
            }

            function updateDetails() {
                if (!selectedConversation) return;
                const title = selectedConversation.name || 'Conversation';
                detailsAvatarEl.textContent = title.charAt(0).toUpperCase();
                detailsTitleEl.textContent = title;
                detailsSubtitleEl.textContent = selectedConversation.type === 'group' ? 'Group conversation' : 'Private conversation';
                detailsTypeEl.textContent = 'Type: ' + (selectedConversation.type === 'group' ? 'Group chat' : 'Private chat');
                detailsMembersEl.textContent = 'Members: ' + (selectedConversation.participant_count || 0);
            }

            function renderSharedFiles() {
                if (!detailsSharedFilesEl) return;
                detailsSharedFilesEl.innerHTML = '';

                const attachments = messages
                    .filter(message => !!message.attachment_url)
                    .slice()
                    .reverse()
                    .slice(0, 8);

                if (!attachments.length) {
                    detailsSharedFilesEl.innerHTML = '<div class="text-xs text-slate-500">No shared files yet in this conversation.</div>';
                    return;
                }

                attachments.forEach(item => {
                    const link = document.createElement('a');
                    link.href = item.attachment_url;
                    link.className = 'block rounded-lg border border-slate-200 bg-white px-2.5 py-2 hover:bg-slate-50 transition';
                    link.target = '_blank';
                    link.rel = 'noopener';

                    const name = document.createElement('div');
                    name.className = 'text-xs font-semibold text-slate-700 truncate';
                    name.textContent = item.attachment_name || 'Attachment';

                    const meta = document.createElement('div');
                    meta.className = 'mt-0.5 text-[11px] text-slate-500 truncate';
                    meta.textContent = item.created_human || '';

                    link.appendChild(name);
                    link.appendChild(meta);
                    detailsSharedFilesEl.appendChild(link);
                });
            }

            function renderConversations() {
                const term = (searchEl.value || '').toLowerCase().trim();
                listEl.innerHTML = '';
                const filtered = conversations.filter(c => !term || (c.name || '').toLowerCase().includes(term));

                if (!filtered.length) {
                    listEl.innerHTML = '<div class="rounded-xl border border-dashed border-slate-300 bg-white p-4 text-center"><div class="text-xs font-semibold text-slate-700">No chat found</div><div class="mt-1 text-[11px] text-slate-500">Try another keyword.</div></div>';
                    return;
                }

                const groupList = filtered.filter(c => c.type === 'group');
                const privateList = filtered.filter(c => c.type === 'private');

                function section(label, items) {
                    if (!items.length) return;
                    const heading = document.createElement('div');
                    heading.className = 'px-1 pt-1 pb-0.5 text-[11px] uppercase tracking-wide font-semibold text-slate-400';
                    heading.textContent = label;
                    listEl.appendChild(heading);

                    items.forEach(conversation => {
                        const active = selectedConversation && selectedConversation.id === conversation.id;
                        const btn = document.createElement('button');
                        btn.type = 'button';
                        btn.className = 'w-full text-left rounded-xl border px-3 py-2.5 transition ' + (active
                            ? 'bg-[#eaf0fb] border-[#c9d7f2] shadow-sm'
                            : 'bg-white border-slate-200 hover:bg-slate-100');
                        btn.onclick = () => openConversation(conversation.id);

                        const row = document.createElement('div');
                        row.className = 'flex items-start gap-2.5';

                        const avatar = document.createElement('div');
                        avatar.className = 'h-8 w-8 shrink-0 rounded-full border flex items-center justify-center text-[11px] font-semibold ' + (active
                            ? 'border-[#c9d7f2] bg-white text-[#0b2d6b]'
                            : 'border-slate-200 bg-slate-100 text-slate-600');
                        avatar.textContent = conversation.type === 'group'
                            ? 'G'
                            : (conversation.name || 'U').charAt(0).toUpperCase();

                        const content = document.createElement('div');
                        content.className = 'min-w-0 flex-1';

                        const top = document.createElement('div');
                        top.className = 'flex items-center justify-between gap-2';
                        const name = document.createElement('div');
                        name.className = 'text-sm font-semibold text-slate-800 truncate';
                        name.textContent = conversation.name;
                        const time = document.createElement('div');
                        time.className = 'text-[10px] text-slate-400 shrink-0';
                        time.textContent = timeText(conversation.latest_at);
                        top.appendChild(name);
                        if (time.textContent) top.appendChild(time);

                        const bottom = document.createElement('div');
                        bottom.className = 'mt-1 flex items-center justify-between gap-2';
                        const preview = document.createElement('div');
                        preview.className = 'text-xs text-slate-500 truncate';
                        preview.textContent = conversation.latest_preview || 'No messages yet.';
                        bottom.appendChild(preview);

                        if ((conversation.unread_count || 0) > 0) {
                            const badge = document.createElement('span');
                            badge.className = 'inline-flex min-w-[18px] h-[18px] px-1 items-center justify-center rounded-full bg-[#0b2d6b] text-white text-[10px] font-bold';
                            badge.textContent = conversation.unread_count;
                            bottom.appendChild(badge);
                        }

                        content.appendChild(top);
                        content.appendChild(bottom);
                        row.appendChild(avatar);
                        row.appendChild(content);
                        btn.appendChild(row);
                        listEl.appendChild(btn);
                    });
                }

                section('Group Chat', groupList);
                section('Private Chats', privateList);
            }

            function showToast(title, body) {
                const toast = document.createElement('div');
                toast.className = 'w-[280px] rounded-xl border border-slate-200 bg-white shadow-lg p-3';
                toast.innerHTML = `<div class="text-xs font-semibold text-slate-800">${title}</div><div class="mt-1 text-xs text-slate-500">${body}</div>`;
                toastHost.appendChild(toast);
                setTimeout(() => {
                    toast.remove();
                }, 3500);
            }

            function browserNotify(title, body) {
                if (!('Notification' in window)) return;
                if (Notification.permission === 'granted') {
                    new Notification(title, { body: body });
                } else if (Notification.permission === 'default') {
                    Notification.requestPermission();
                }
            }

            function syncSidebarUnreadBadge() {
                if (!sidebarMessageUnreadBadge) return;
                const totalUnread = conversations.reduce((sum, item) => sum + (item.unread_count || 0), 0);
                if (totalUnread > 0) {
                    sidebarMessageUnreadBadge.textContent = String(Math.min(99, totalUnread));
                    sidebarMessageUnreadBadge.classList.remove('hidden');
                } else {
                    sidebarMessageUnreadBadge.classList.add('hidden');
                }
            }

            function renderMessages() {
                paneEl.innerHTML = '';
                if (!selectedConversation) {
                    paneEl.innerHTML = '<div class="h-full flex items-center justify-center"><div class="rounded-2xl border border-dashed border-slate-300 bg-white px-7 py-9 text-center shadow-sm"><div class="mx-auto h-10 w-10 rounded-full bg-[#eaf0fb] border border-[#c9d7f2] flex items-center justify-center text-[#0b2d6b]"><svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M7 10h10"/><path d="M7 14h6"/><path d="M21 12c0 4.97-4.03 9-9 9-1.5 0-2.9-.37-4.13-1.02L3 21l1.02-4.87A8.96 8.96 0 0 1 3 12c0-4.97 4.03-9 9-9s9 4.03 9 9Z"/></svg></div><div class="mt-3 text-sm font-semibold text-slate-700">No conversation selected</div><div class="mt-1 text-xs text-slate-500">Choose a chat from the left panel.</div></div></div>';
                    renderSharedFiles();
                    return;
                }

                titleEl.textContent = selectedConversation.name || 'Conversation';
                metaEl.textContent = selectedConversation.type === 'group' ? 'Group Chat' : 'Private Chat';
                countEl.textContent = (selectedConversation.participant_count || 0) + ' members';
                updateDetails();

                if (!messages.length) {
                    paneEl.innerHTML = '<div class="h-full flex items-center justify-center"><div class="rounded-2xl border border-dashed border-slate-300 bg-white px-7 py-9 text-center shadow-sm"><div class="text-sm font-semibold text-slate-700">No messages yet</div><div class="mt-1 text-xs text-slate-500">Start the conversation below.</div></div></div>';
                    renderSharedFiles();
                    return;
                }

                messages.forEach(message => {
                    const mine = Number(message.user_id) === myUserId;
                    const row = document.createElement('div');
                    row.className = 'flex ' + (mine ? 'justify-end' : 'justify-start');
                    const bubbleWrap = document.createElement('div');
                    bubbleWrap.className = 'max-w-[76%]';
                    const bubble = document.createElement('div');
                    bubble.className = mine
                        ? 'rounded-2xl rounded-br-md bg-[#0b2d6b] text-white px-3.5 py-2.5 shadow-sm'
                        : 'rounded-2xl rounded-bl-md bg-white border border-slate-200 text-slate-800 px-3.5 py-2.5 shadow-sm';

                    if (!mine && selectedConversation.type === 'group') {
                        const author = document.createElement('div');
                        author.className = 'text-[11px] text-slate-500 mb-1';
                        author.textContent = message.user_name || 'User';
                        bubble.appendChild(author);
                    }

                    if (message.is_deleted) {
                        const deleted = document.createElement('div');
                        deleted.className = 'text-sm italic ' + (mine ? 'text-white/85' : 'text-slate-500');
                        deleted.textContent = 'This message was deleted';
                        bubble.appendChild(deleted);
                    } else if (message.message) {
                        const text = document.createElement('div');
                        text.className = 'text-sm whitespace-pre-wrap break-words';
                        text.textContent = message.message;
                        bubble.appendChild(text);
                    }

                    if (message.attachment_url) {
                        const isImage = (message.attachment_type || '').toLowerCase().startsWith('image/');
                        if (isImage) {
                            const imageLink = document.createElement('a');
                            imageLink.href = message.attachment_url;
                            imageLink.target = '_blank';
                            imageLink.rel = 'noopener';
                            imageLink.className = 'mt-2 block';

                            const image = document.createElement('img');
                            image.src = message.attachment_url;
                            image.alt = message.attachment_name || 'Image attachment';
                            image.className = 'max-h-56 max-w-[260px] rounded-xl border border-white/20 object-cover';
                            image.loading = 'lazy';

                            imageLink.appendChild(image);
                            bubble.appendChild(imageLink);

                            const imageName = document.createElement('div');
                            imageName.className = 'mt-1 text-[11px] ' + (mine ? 'text-white/85' : 'text-slate-500');
                            imageName.textContent = message.attachment_name || 'Image';
                            bubble.appendChild(imageName);
                        } else {
                            const link = document.createElement('a');
                            link.href = message.attachment_url;
                            link.className = 'mt-1 inline-flex text-xs underline';
                            link.textContent = message.attachment_name || 'Download attachment';
                            bubble.appendChild(link);
                        }
                    }

                    const time = document.createElement('div');
                    time.className = 'mt-1 text-[10px] ' + (mine ? 'text-white/75' : 'text-slate-400');
                    time.textContent = (message.created_human || '') + (message.is_edited ? ' • edited' : '');
                    bubble.appendChild(time);

                    bubbleWrap.appendChild(bubble);

                    if (!message.is_deleted && message.reactions && message.reactions.length) {
                        const reactions = document.createElement('div');
                        reactions.className = 'mt-1 flex flex-wrap gap-1';
                        message.reactions.forEach(r => {
                            const btn = document.createElement('button');
                            btn.type = 'button';
                            btn.className = 'inline-flex items-center gap-1 rounded-full border px-2 py-0.5 text-[11px] ' + (r.mine
                                ? 'bg-[#eaf0fb] border-[#c9d7f2] text-[#0b2d6b]'
                                : 'bg-white border-slate-200 text-slate-600');
                            btn.textContent = `${r.emoji} ${r.count}`;
                            btn.onclick = () => toggleReaction(message.id, r.emoji, !!r.mine);
                            reactions.appendChild(btn);
                        });
                        bubbleWrap.appendChild(reactions);
                    }

                    if (!message.is_deleted && (message.can_react || message.can_edit || message.can_delete)) {
                        const actions = document.createElement('div');
                        actions.className = 'mt-1 flex items-center gap-1.5';
                        if (message.can_react) {
                            ['👍', '❤️', '😂'].forEach(emoji => {
                                const ebtn = document.createElement('button');
                                ebtn.type = 'button';
                                ebtn.className = 'text-[12px] rounded-md border border-slate-200 bg-white px-1.5 py-0.5 hover:bg-slate-50';
                                ebtn.textContent = emoji;
                                ebtn.onclick = () => toggleReaction(message.id, emoji, false);
                                actions.appendChild(ebtn);
                            });
                        }
                        if (message.can_edit) {
                            const editBtn = document.createElement('button');
                            editBtn.type = 'button';
                            editBtn.className = 'text-[11px] rounded-md border border-slate-200 bg-white px-2 py-0.5 text-slate-600 hover:bg-slate-50';
                            editBtn.textContent = 'Edit';
                            editBtn.onclick = () => editMessage(message.id, message.message || '');
                            actions.appendChild(editBtn);
                        }
                        if (message.can_delete) {
                            const delBtn = document.createElement('button');
                            delBtn.type = 'button';
                            delBtn.className = 'text-[11px] rounded-md border border-red-200 bg-red-50 px-2 py-0.5 text-red-600 hover:bg-red-100';
                            delBtn.textContent = 'Delete';
                            delBtn.onclick = () => deleteMessage(message.id);
                            actions.appendChild(delBtn);
                        }
                        bubbleWrap.appendChild(actions);
                    }

                    row.appendChild(bubbleWrap);
                    paneEl.appendChild(row);
                });

                paneEl.scrollTop = paneEl.scrollHeight;
                renderSharedFiles();
            }

            async function openConversation(id) {
                selectedConversation = conversations.find(c => c.id === id) || null;
                if (!selectedConversation) return;

                const response = await fetch(`/messages/conversations/${id}/messages`, { headers: { 'Accept': 'application/json' } });
                if (!response.ok) return;
                const data = await response.json();
                messages = (data.data && data.data.messages) || [];
                lastMessageId = messages.length ? messages[messages.length - 1].id : 0;

                selectedConversation.unread_count = 0;
                previousUnreadMap[id] = 0;
                renderConversations();
                syncSidebarUnreadBadge();
                renderMessages();
            }

            function upsertMessage(updated) {
                const index = messages.findIndex(m => Number(m.id) === Number(updated.id));
                if (index >= 0) {
                    messages[index] = updated;
                }
                renderMessages();
            }

            async function editMessage(messageId, currentText) {
                const next = window.prompt('Edit message (3-minute limit):', currentText || '');
                if (next === null) return;
                const text = next.trim();
                if (!text) return;

                const response = await fetch(`/messages/messages/${messageId}`, {
                    method: 'PATCH',
                    headers: {
                        'Accept': 'application/json',
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrf,
                    },
                    body: JSON.stringify({ message: text }),
                });
                if (!response.ok) return;
                const data = await response.json();
                if (data.data && data.data.message) {
                    upsertMessage(data.data.message);
                }
            }

            async function deleteMessage(messageId) {
                if (!window.confirm('Delete this message?')) return;
                const response = await fetch(`/messages/messages/${messageId}`, {
                    method: 'DELETE',
                    headers: {
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': csrf,
                    },
                });
                if (!response.ok) return;
                const data = await response.json();
                if (data.data && data.data.message) {
                    upsertMessage(data.data.message);
                }
            }

            async function toggleReaction(messageId, emoji, alreadyMine) {
                const method = alreadyMine ? 'DELETE' : 'POST';
                const response = await fetch(`/messages/messages/${messageId}/reactions`, {
                    method,
                    headers: {
                        'Accept': 'application/json',
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrf,
                    },
                    body: JSON.stringify({ emoji }),
                });
                if (!response.ok) return;
                const data = await response.json();
                if (data.message) {
                    upsertMessage(data.message);
                }
            }

            async function refreshConversations() {
                const response = await fetch(`/messages/courses/${courseId}/conversations`, { headers: { 'Accept': 'application/json' } });
                if (!response.ok) return;
                const data = await response.json();
                const updated = (data.data && Array.isArray(data.data.conversations)) ? data.data.conversations : [];

                updated.forEach(item => {
                    const prev = previousUnreadMap[item.id] || 0;
                    const current = item.unread_count || 0;
                    if (current > prev) {
                        const diff = current - prev;
                        showToast('New message', `${item.name}: ${diff} new message${diff > 1 ? 's' : ''}`);
                        browserNotify('Scholaria Messages', `${item.name}: ${diff} new message${diff > 1 ? 's' : ''}`);
                    }
                    previousUnreadMap[item.id] = current;
                });

                conversations = updated;
                if (selectedConversation) {
                    selectedConversation = conversations.find(c => c.id === selectedConversation.id) || selectedConversation;
                    selectedConversation.unread_count = 0;
                    previousUnreadMap[selectedConversation.id] = 0;
                }
                renderConversations();
                syncSidebarUnreadBadge();
            }

            async function poll() {
                if (!selectedConversation) return;
                const response = await fetch(`/messages/conversations/${selectedConversation.id}/messages?after_id=${lastMessageId}`, { headers: { 'Accept': 'application/json' } });
                if (!response.ok) return;
                const data = await response.json();
                const chunk = (data.data && data.data.messages) || [];
                if (chunk.length) {
                    messages = messages.concat(chunk);
                    lastMessageId = messages[messages.length - 1].id;
                    renderMessages();
                }
                await refreshConversations();
            }

            formEl.addEventListener('submit', async (event) => {
                event.preventDefault();
                if (!selectedConversation) return;
                messageErrorEl.textContent = '';
                messageErrorEl.classList.add('hidden');
                const trimmedMessage = (inputEl.value || '').trim();
                const selectedFile = attachmentInput.files[0] || null;

                if (!trimmedMessage && !selectedFile) {
                    messageErrorEl.textContent = 'Message or attachment is required.';
                    messageErrorEl.classList.remove('hidden');
                    return;
                }

                const fd = new FormData();
                fd.append('_token', csrf);
                fd.append('message', trimmedMessage);
                if (selectedFile) fd.append('attachment', selectedFile);

                const response = await fetch(`/messages/conversations/${selectedConversation.id}/messages`, {
                    method: 'POST',
                    headers: { 'Accept': 'application/json' },
                    body: fd,
                });

                if (!response.ok) {
                    let errorText = 'Unable to send message.';
                    try {
                        const payload = await response.json();
                        if (payload?.message) {
                            errorText = payload.message;
                        }
                        if (payload?.errors) {
                            const firstKey = Object.keys(payload.errors)[0];
                            if (firstKey && Array.isArray(payload.errors[firstKey]) && payload.errors[firstKey][0]) {
                                errorText = payload.errors[firstKey][0];
                            }
                        }
                    } catch (e) {
                    }
                    messageErrorEl.textContent = errorText;
                    messageErrorEl.classList.remove('hidden');
                    return;
                }
                const data = await response.json();
                if (data.data && data.data.message) {
                    messages.push(data.data.message);
                    lastMessageId = data.data.message.id;
                    inputEl.value = '';
                    attachmentInput.value = '';
                    attachmentMeta.classList.add('hidden');
                    attachmentMeta.textContent = '';
                    messageErrorEl.textContent = '';
                    messageErrorEl.classList.add('hidden');
                    renderMessages();
                }
            });

            attachmentInput.addEventListener('change', function () {
                const file = attachmentInput.files[0];
                messageErrorEl.textContent = '';
                messageErrorEl.classList.add('hidden');
                if (!file) {
                    attachmentMeta.textContent = '';
                    attachmentMeta.classList.add('hidden');
                    return;
                }
                attachmentMeta.textContent = `Attached: ${file.name}`;
                attachmentMeta.classList.remove('hidden');
            });

            inputEl.addEventListener('keydown', function (event) {
                if (event.key === 'Enter') {
                    event.preventDefault();
                    formEl.requestSubmit();
                }
            });

            searchEl.addEventListener('input', renderConversations);

            renderConversations();
            syncSidebarUnreadBadge();
            renderMessages();
            if (selectedConversation && selectedConversation.id) openConversation(selectedConversation.id);
            setInterval(refreshConversations, 6000);
            setInterval(poll, 4000);
        })();
    </script>
@endsection
