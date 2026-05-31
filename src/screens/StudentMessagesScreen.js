import { useEffect, useMemo, useRef, useState } from 'react';
import {
  ActivityIndicator,
  Alert,
  Linking,
  Pressable,
  ScrollView,
  StyleSheet,
  Text,
  TextInput,
  View,
} from 'react-native';

import { API_BASE_URL } from '@/api/client';
import {
  getStudentConversationMessages,
  getStudentCourseConversations,
  getStudentMessages,
  sendStudentConversationMessageWithAttachment,
} from '@/api/student';
import { createUploadFormData, pickDocumentAsset } from '@/utils/uploads';

const colors = {
  background: '#0b0b0b',
  card: '#181818',
  cardAlt: '#111111',
  accent: '#12d6ad',
  accentSoft: '#173d38',
  accentSoftAlt: '#14352f',
  text: '#f4f4f4',
  muted: '#9d9d9d',
  border: '#333333',
  chipText: '#c9c9c9',
};

export default function StudentMessagesScreen({ token, user }) {
  const [loading, setLoading] = useState(true);
  const [refreshing, setRefreshing] = useState(false);
  const [error, setError] = useState('');
  const [courses, setCourses] = useState([]);
  const [selectedCourse, setSelectedCourse] = useState(null);
  const [conversations, setConversations] = useState([]);
  const [selectedConversation, setSelectedConversation] = useState(null);
  const [messages, setMessages] = useState([]);
  const [searchText, setSearchText] = useState('');
  const [filter, setFilter] = useState('all');
  const [messageText, setMessageText] = useState('');
  const [sending, setSending] = useState(false);
  const [attachment, setAttachment] = useState(null);
  const [coursePaneExpanded, setCoursePaneExpanded] = useState(true);

  const scrollViewRef = useRef(null);

  const selectedCourseId = selectedCourse?.id || selectedCourse?.course_id || null;
  const selectedConversationId = selectedConversation?.id || null;

  const loadCourses = async ({ keepSelection = false, showLoading = true } = {}) => {
    if (showLoading) {
      setLoading(true);
    }
    setError('');

    try {
      const response = await getStudentMessages(token);
      const list = response?.data?.courses || [];
      setCourses(list);
      if (!keepSelection) {
        setSelectedCourse(list[0] || null);
      }
    } catch (err) {
      setError(err?.message || 'Unable to load messages.');
    } finally {
      if (showLoading) {
        setLoading(false);
      }
    }
  };

  const loadConversations = async (courseId, { isSilent = false } = {}) => {
    if (!courseId) {
      setConversations([]);
      setSelectedConversation(null);
      setMessages([]);
      return;
    }

    try {
      const response = await getStudentCourseConversations(token, courseId);
      const list = response?.data?.conversations || [];
      setConversations(list);
      setSelectedConversation((current) => {
        if (current && list.some((conversation) => conversation.id === current.id)) {
          return current;
        }

        return list[0] || null;
      });
      setError('');
    } catch (err) {
      if (!isSilent) {
        setError(err?.message || 'Unable to load conversations.');
      } else {
        console.warn('Background conversation load failed:', err);
      }
    }
  };

  const loadMessages = async (conversationId, { isSilent = false } = {}) => {
    if (!conversationId) {
      setMessages([]);
      return;
    }

    try {
      const response = await getStudentConversationMessages(token, conversationId);
      setMessages(response?.data?.messages || []);
      setError('');
    } catch (err) {
      if (!isSilent) {
        setError(err?.message || 'Unable to load messages.');
      } else {
        console.warn('Background message load failed:', err);
      }
    }
  };

  useEffect(() => {
    const timer = setTimeout(() => {
      loadCourses();
    }, 0);

    return () => clearTimeout(timer);
  }, [token]);

  useEffect(() => {
    const timer = setTimeout(() => {
      loadConversations(selectedCourseId);
    }, 0);

    return () => clearTimeout(timer);
  }, [selectedCourseId]);

  useEffect(() => {
    let timer;
    if (selectedConversationId) {
      timer = setTimeout(() => {
        loadMessages(selectedConversationId);
      }, 0);
    } else {
      timer = setTimeout(() => {
        setMessages([]);
      }, 0);
    }

    if (!selectedConversationId) return;

    const interval = setInterval(() => {
      loadMessages(selectedConversationId, { isSilent: true });
      if (selectedCourseId) {
        loadConversations(selectedCourseId, { isSilent: true });
      }
    }, 4000);

    return () => {
      if (timer) clearTimeout(timer);
      clearInterval(interval);
    };
  }, [selectedConversationId, selectedCourseId]);

  useEffect(() => {
    if (messages.length > 0) {
      scrollViewRef.current?.scrollToEnd({ animated: true });
    }
  }, [messages.length]);

  const courseGroups = useMemo(() => {
    return courses.map((course) => ({
      id: course.id || course.course_id,
      label: course.name || course.course_name || 'Course',
      meta: course.course_number || course.course_code || 'Active',
      original: course,
    }));
  }, [courses]);

  const filteredConversations = useMemo(() => {
    const query = searchText.trim().toLowerCase();

    return conversations.filter((conversation) => {
      const haystack = [
        conversation.name,
        conversation.latest_preview,
        conversation.course_name,
        conversation.updated_at,
      ]
        .filter(Boolean)
        .join(' ')
        .toLowerCase();

      const matchesQuery = !query || haystack.includes(query);
      const unread = conversation.unread_count || 0;
      const isGroup = !!conversation.is_group || !!conversation.group || !!conversation.group_name;

      const matchesFilter =
        filter === 'all' ||
        (filter === 'unread' && unread > 0) ||
        (filter === 'groups' && isGroup);

      return matchesQuery && matchesFilter;
    });
  }, [conversations, filter, searchText]);

  const unreadCount = useMemo(
    () => conversations.reduce((sum, conversation) => sum + (conversation.unread_count || 0), 0),
    [conversations]
  );

  const handleRefresh = async () => {
    setRefreshing(true);
    try {
      await loadCourses({ keepSelection: true, showLoading: false });
      if (selectedCourseId) {
        await loadConversations(selectedCourseId);
      }
      if (selectedConversationId) {
        await loadMessages(selectedConversationId);
      }
    } finally {
      setRefreshing(false);
    }
  };

  const handleSend = async () => {
    const trimmed = messageText.trim();
    if (!trimmed && !attachment?.uri) return;
    if (!selectedConversationId) return;

    setSending(true);
    try {
      if (attachment?.uri) {
        const formData = createUploadFormData({ message: trimmed }, 'attachment', attachment);
        await sendStudentConversationMessageWithAttachment(token, selectedConversationId, formData);
      } else {
        await sendStudentConversationMessageWithAttachment(
          token,
          selectedConversationId,
          createUploadFormData({ message: trimmed })
        );
      }
      setMessageText('');
      setAttachment(null);
      await loadMessages(selectedConversationId);
      await loadConversations(selectedCourseId);
    } catch (err) {
      setError(err?.message || 'Unable to send message.');
    } finally {
      setSending(false);
    }
  };

  const pickAttachment = async () => {
    try {
      const asset = await pickDocumentAsset();
      if (asset) {
        setAttachment(asset);
      }
    } catch (err) {
      Alert.alert('Attachment', err?.message || 'Unable to choose a file.');
    }
  };

  const resolveAttachmentUrl = (value) => {
    if (!value) {
      return '';
    }

    if (/^https?:\/\//i.test(value)) {
      return value;
    }

    return value.startsWith('/')
      ? `${API_BASE_URL.replace(/\/api$/, '')}${value}`
      : `${API_BASE_URL.replace(/\/api$/, '')}/${value}`;
  };

  return (
    <ScrollView style={styles.screen} contentContainerStyle={styles.container}>
      <View style={styles.headerSection}>
        <View style={styles.headerTopRow}>
          <View style={styles.headerTextBlock}>
            <Text style={styles.title}>MESSAGES</Text>
            <Text style={styles.subtitle}>Connect with your instructors and peers</Text>
          </View>
          <View style={styles.chatHeaderRightSpacer} />
        </View>
      </View>

      <View style={styles.searchCard}>
        <View style={styles.searchIcon}>
          <Text style={styles.searchIconText}>⌕</Text>
        </View>
        <TextInput
          value={searchText}
          onChangeText={setSearchText}
          placeholder="Search"
          placeholderTextColor={colors.muted}
          style={styles.searchInput}
        />
      </View>

      {loading ? (
        <View style={styles.loadingCard}>
          <ActivityIndicator size="large" color={colors.accent} />
          <Text style={styles.loadingText}>Loading message spaces...</Text>
        </View>
      ) : (
        <>
          {!!error && <Text style={styles.error}>{error}</Text>}

          <View style={styles.sectionBlock}>
            <View style={styles.coursePaneHeader}>
              <Text style={styles.sectionLabel}>Course Groups</Text>
              <Pressable
                onPress={() => setCoursePaneExpanded((v) => !v)}
                style={styles.collapseButton}
              >
                <Text style={styles.collapseButtonText}>
                  {coursePaneExpanded ? 'Hide' : 'Show'}
                </Text>
              </Pressable>
            </View>

            {coursePaneExpanded ? (
              <ScrollView
                horizontal
                showsHorizontalScrollIndicator={false}
                contentContainerStyle={styles.suggestedRow}
              >
                {courseGroups.length ? (
                  courseGroups.map((group) => {
                    const active = selectedCourseId === group.id;
                    return (
                      <Pressable
                        key={group.id}
                        onPress={() => {
                          setSelectedCourse(group.original);
                          setSelectedConversation(null);
                          setAttachment(null);
                          setMessageText('');
                        }}
                        style={({ pressed }) => [
                          styles.suggestedCard,
                          active && styles.suggestedCardActive,
                          pressed && styles.pressed,
                        ]}
                      >
                        <View
                          style={[
                            styles.suggestedAvatar,
                            active && styles.suggestedAvatarActive,
                          ]}
                        >
                          <Text
                            style={[
                              styles.suggestedAvatarText,
                              active && styles.suggestedAvatarTextActive,
                            ]}
                          >
                            {initialsFromName(group.label)}
                          </Text>
                        </View>
                        <Text
                          style={[styles.suggestedName, active && styles.suggestedNameActive]}
                          numberOfLines={1}
                        >
                          {group.label}
                        </Text>
                        <Text
                          style={[styles.suggestedMeta, active && styles.suggestedMetaActive]}
                          numberOfLines={1}
                        >
                          {group.meta}
                        </Text>
                      </Pressable>
                    );
                  })
                ) : (
                  <Text style={styles.emptyText}>No course groups found.</Text>
                )}
              </ScrollView>
            ) : null}

            <ScrollView
              horizontal
              showsHorizontalScrollIndicator={false}
              contentContainerStyle={styles.chipRow}
            >
              <FilterChip
                label="All Messages"
                active={filter === 'all'}
                onPress={() => setFilter('all')}
              />
              <FilterChip
                label="Unread"
                active={filter === 'unread'}
                onPress={() => setFilter('unread')}
              />
              <FilterChip
                label="Groups"
                active={filter === 'groups'}
                onPress={() => setFilter('groups')}
              />
            </ScrollView>

            <View style={styles.sectionBlock}>
              <Text style={styles.sectionLabel}>Messages</Text>
              <Text style={styles.sectionHint}>
                {unreadCount
                  ? `${unreadCount} unread message${unreadCount === 1 ? '' : 's'}`
                  : 'All caught up'}
              </Text>

              <View style={styles.messagesList}>
                {filteredConversations.length ? (
                  filteredConversations.map((conversation) => {
                    const active = selectedConversationId === conversation.id;
                    const unread = conversation.unread_count || 0;

                    return (
                      <Pressable
                        key={conversation.id}
                        onPress={() => {
                          setSelectedConversation(conversation);
                          setAttachment(null);
                          setMessageText('');
                        }}
                        style={({ pressed }) => [
                          styles.messageCard,
                          active && styles.messageCardActive,
                          pressed && styles.pressed,
                        ]}
                      >
                        <View style={styles.messageCardTop}>
                          <View style={styles.messageAvatar}>
                            <Text style={styles.messageAvatarText}>
                              {initialsFromName(
                                conversation.name || conversation.latest_preview || 'Chat'
                              )}
                            </Text>
                          </View>

                          <View style={styles.messageCardBody}>
                            <View style={styles.messageCardHeader}>
                              <Text style={styles.messageTitle} numberOfLines={1}>
                                {conversation.name || 'Conversation'}
                              </Text>
                              <Text style={styles.messageTime}>
                                {conversation.last_message_at || conversation.updated_at || ''}
                              </Text>
                            </View>

                            <Text style={styles.messagePreview} numberOfLines={2}>
                              {conversation.latest_preview || 'No preview available.'}
                            </Text>

                            <View style={styles.messageCardFooter}>
                              <Text style={styles.messageCourse} numberOfLines={1}>
                                {conversation.course_name || selectedCourse?.name || 'Course'}
                              </Text>
                              {unread > 0 ? (
                                <View style={styles.unreadBadge}>
                                  <Text style={styles.unreadBadgeText}>{unread}</Text>
                                </View>
                              ) : null}
                            </View>
                          </View>
                        </View>
                      </Pressable>
                    );
                  })
                ) : (
                  <View style={styles.emptyCard}>
                    <Text style={styles.emptyTitle}>No messages found</Text>
                    <Text style={styles.emptyText}>
                      Try another search or switch the filter chips above.
                    </Text>
                  </View>
                )}
              </View>
            </View>

            {selectedConversation ? (
              <View style={styles.threadCard}>
                <View style={styles.threadHeader}>
                  <View>
                    <Text style={styles.sectionLabel}>Selected Chat</Text>
                    <Text style={styles.threadTitle}>
                      {selectedConversation.name || 'Conversation'}
                    </Text>
                  </View>
                  <Pressable onPress={handleRefresh} style={styles.secondaryButton}>
                    <Text style={styles.secondaryButtonText}>
                      {refreshing ? 'Refreshing...' : 'Refresh'}
                    </Text>
                  </Pressable>
                </View>

                <ScrollView
                  ref={scrollViewRef}
                  style={styles.threadMessages}
                  contentContainerStyle={styles.threadMessagesContent}
                  nestedScrollEnabled
                >
                  {messages.length ? (
                    messages.map((message) => {
                      const mine = isOwnMessage(message, user);
                      const attachmentUrl = resolveAttachmentUrl(
                        message.attachment_url || message.file_url || ''
                      );
                      const attachmentName =
                        message.attachment_name || message.file_name || 'Attachment';

                      return (
                        <View
                          key={message.id}
                          style={[
                            styles.bubbleRow,
                            mine ? styles.bubbleRowMine : styles.bubbleRowTheirs,
                          ]}
                        >
                          {!mine ? (
                            <View style={styles.bubbleAvatar}>
                              <Text style={styles.bubbleAvatarText}>
                                {initialsFromName(
                                  message.user_name || message.sender_name || 'T'
                                )}
                              </Text>
                            </View>
                          ) : null}

                          <View
                            style={[
                              styles.bubble,
                              mine ? styles.bubbleMine : styles.bubbleTheirs,
                            ]}
                          >
                            {!mine ? (
                              <Text style={styles.bubbleAuthor}>
                                {message.user_name || message.sender_name || 'Member'}
                              </Text>
                            ) : null}

                            <Text
                              style={[styles.bubbleText, mine && styles.bubbleTextMine]}
                            >
                              {message.message || message.body || '[Attachment]'}
                            </Text>

                            {attachmentUrl ? (
                              <Pressable
                                onPress={() => Linking.openURL(attachmentUrl)}
                                style={({ pressed }) => [
                                  styles.attachmentPill,
                                  pressed && styles.pressed,
                                ]}
                              >
                                <Text style={styles.attachmentPillText}>
                                  {attachmentName}
                                </Text>
                              </Pressable>
                            ) : null}

                            <Text style={[styles.bubbleMeta, mine && styles.bubbleMetaMine]}>
                              {message.created_human || message.created_at || ''}
                            </Text>
                          </View>
                        </View>
                      );
                    })
                  ) : (
                    <View style={styles.emptyThread}>
                      <Text style={styles.emptyTitle}>No thread yet</Text>
                      <Text style={styles.emptyText}>
                        Open the conversation above to see the chat content.
                      </Text>
                    </View>
                  )}
                </ScrollView>

                <View style={styles.composer}>
                  {attachment?.name ? (
                    <View style={styles.selectedAttachmentCard}>
                      <Text style={styles.selectedAttachmentLabel}>Attachment</Text>
                      <Text style={styles.selectedAttachmentName}>{attachment.name}</Text>
                    </View>
                  ) : null}

                  <TextInput
                    value={messageText}
                    onChangeText={setMessageText}
                    placeholder="Write a message..."
                    placeholderTextColor={colors.muted}
                    multiline
                    style={styles.input}
                  />

                  <View style={styles.composerActions}>
                    <Pressable onPress={pickAttachment} style={styles.attachButton}>
                      <Text style={styles.attachButtonText}>
                        {attachment ? 'Change file' : 'Attach file'}
                      </Text>
                    </Pressable>

                    {attachment ? (
                      <Pressable
                        onPress={() => setAttachment(null)}
                        style={styles.attachButtonGhost}
                      >
                        <Text style={styles.attachButtonGhostText}>Remove</Text>
                      </Pressable>
                    ) : null}
                  </View>

                  <Pressable
                    onPress={handleSend}
                    disabled={sending}
                    style={({ pressed }) => [
                      styles.sendButton,
                      pressed && styles.pressed,
                      sending && styles.sendButtonDisabled,
                    ]}
                  >
                    <Text style={styles.sendButtonText}>
                      {sending ? 'Sending...' : 'Send'}
                    </Text>
                  </Pressable>
                </View>
              </View>
            ) : null}
          </View>
        </>
      )}
    </ScrollView>
  );
}

function FilterChip({ label, active, onPress }) {
  return (
    <Pressable
      onPress={onPress}
      style={[styles.chip, active && styles.chipActive]}
    >
      <Text style={[styles.chipText, active && styles.chipTextActive]} numberOfLines={1}>
        {label}
      </Text>
    </Pressable>
  );
}

function initialsFromName(value) {
  const parts = String(value || '')
    .trim()
    .split(/\s+/)
    .filter(Boolean);

  if (!parts.length) {
    return '?';
  }

  return parts
    .slice(0, 2)
    .map((part) => part[0]?.toUpperCase() || '')
    .join('');
}

function isOwnMessage(message, user) {
  if (!message) return false;

  if (message.is_mine || message.isMine || message.from_me || message.sent_by_me) {
    return true;
  }

  const userId = user?.id != null ? String(user.id) : '';
  const senderId = message.user_id ?? message.sender_id ?? message.author_id ?? '';
  if (userId && senderId != null && String(senderId) === userId) {
    return true;
  }

  const userName = String(user?.name || '').trim().toLowerCase();
  const senderName = String(message.user_name || message.sender_name || message.author_name || '')
    .trim()
    .toLowerCase();

  return !!userName && userName === senderName;
}

const styles = StyleSheet.create({
  screen: {
    flex: 1,
    backgroundColor: colors.background,
  },
  container: {
    paddingTop: 18,
    paddingBottom: 24,
    gap: 16,
  },
  headerSection: {
    paddingHorizontal: 24,
    paddingTop: 2,
    gap: 12,
  },
  headerTopRow: {
    flexDirection: 'row',
    alignItems: 'flex-start',
    justifyContent: 'space-between',
    gap: 12,
  },
  headerTextBlock: {
    flex: 1,
  },
  title: {
    color: colors.text,
    fontSize: 30,
    lineHeight: 34,
    fontWeight: '900',
    letterSpacing: -0.4,
  },
  subtitle: {
    color: colors.muted,
    fontSize: 14,
    lineHeight: 20,
    marginTop: 4,
  },
  chatHeaderRightSpacer: {
    width: 38,
    height: 1,
  },
  pressed: {
    opacity: 0.75,
  },
  searchCard: {
    marginHorizontal: 24,
    backgroundColor: colors.card,
    borderRadius: 24,
    borderWidth: 1,
    borderColor: colors.border,
    flexDirection: 'row',
    alignItems: 'center',
    paddingHorizontal: 16,
    paddingVertical: 10,
    gap: 12,
  },
  searchIcon: {
    width: 22,
    height: 22,
    borderRadius: 11,
    alignItems: 'center',
    justifyContent: 'center',
  },
  searchIconText: {
    color: colors.muted,
    fontSize: 18,
    fontWeight: '700',
    marginTop: -2,
  },
  searchInput: {
    flex: 1,
    color: colors.text,
    fontSize: 16,
    paddingVertical: 0,
  },
  loadingCard: {
    backgroundColor: colors.card,
    borderRadius: 22,
    padding: 20,
    alignItems: 'center',
    gap: 10,
    borderWidth: 1,
    borderColor: colors.border,
  },
  loadingText: {
    color: colors.muted,
  },
  error: {
    color: '#ff7d7d',
    fontWeight: '700',
  },
  sectionBlock: {
    gap: 10,
  },
  sectionLabel: {
    color: colors.text,
    fontSize: 18,
    fontWeight: '900',
    paddingHorizontal: 24,
  },
  sectionHint: {
    color: colors.muted,
    fontSize: 12,
    paddingHorizontal: 24,
    marginTop: -4,
  },
  suggestedRow: {
    paddingHorizontal: 24,
    gap: 12,
  },
  suggestedCard: {
    width: 108,
    backgroundColor: colors.card,
    borderRadius: 22,
    borderWidth: 1,
    borderColor: colors.border,
    padding: 12,
    gap: 8,
  },
  suggestedAvatar: {
    width: 48,
    height: 48,
    borderRadius: 24,
    backgroundColor: colors.accentSoft,
    alignItems: 'center',
    justifyContent: 'center',
    borderWidth: 1,
    borderColor: colors.accent,
  },
  suggestedAvatarText: {
    color: colors.accent,
    fontSize: 15,
    fontWeight: '900',
  },
  suggestedName: {
    color: colors.text,
    fontSize: 12,
    fontWeight: '800',
  },
  suggestedMeta: {
    color: colors.muted,
    fontSize: 11,
  },
  suggestedCardActive: {
    borderColor: colors.accent,
    backgroundColor: '#122d29',
  },
  suggestedAvatarActive: {
    backgroundColor: colors.accent,
    borderColor: colors.accent,
  },
  suggestedAvatarTextActive: {
    color: '#081310',
  },
  suggestedNameActive: {
    color: colors.accent,
  },
  suggestedMetaActive: {
    color: colors.accent,
    opacity: 0.8,
  },
  chipRow: {
    paddingHorizontal: 24,
    gap: 10,
  },
  chip: {
    width: 34,
    height: 34,
    borderRadius: 9999,
    backgroundColor: colors.card,
    borderWidth: 1,
    borderColor: colors.border,
    alignItems: 'center',
    justifyContent: 'center',
    paddingHorizontal: 0,
    paddingVertical: 0,
  },
  chipActive: {
    backgroundColor: colors.accentSoft,
    borderColor: colors.accent,
  },
  chipText: {
    color: colors.chipText,
    fontSize: 10,
    fontWeight: '900',
    textAlign: 'center',
    lineHeight: 12,
  },
  chipTextActive: {
    color: colors.accent,
  },
  messagesList: {
    paddingHorizontal: 24,
    gap: 12,
  },
  messageCard: {
    backgroundColor: colors.card,
    borderRadius: 24,
    borderWidth: 1,
    borderColor: colors.border,
    padding: 14,
  },
  messageCardActive: {
    borderColor: colors.accent,
    backgroundColor: '#122d29',
  },
  messageCardTop: {
    flexDirection: 'row',
    gap: 12,
  },
  messageAvatar: {
    width: 50,
    height: 50,
    borderRadius: 25,
    backgroundColor: colors.accentSoft,
    borderWidth: 1,
    borderColor: colors.accent,
    alignItems: 'center',
    justifyContent: 'center',
  },
  messageAvatarText: {
    color: colors.accent,
    fontSize: 15,
    fontWeight: '900',
  },
  messageCardBody: {
    flex: 1,
    gap: 6,
  },
  messageCardHeader: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    gap: 10,
  },
  messageTitle: {
    flex: 1,
    color: colors.text,
    fontSize: 15,
    fontWeight: '900',
  },
  messageTime: {
    color: colors.muted,
    fontSize: 11,
    marginTop: 2,
  },
  messagePreview: {
    color: colors.muted,
    fontSize: 13,
    lineHeight: 18,
  },
  messageCardFooter: {
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'space-between',
    gap: 10,
  },
  messageCourse: {
    flex: 1,
    color: colors.chipText,
    fontSize: 11,
    fontWeight: '700',
  },
  unreadBadge: {
    minWidth: 20,
    height: 20,
    borderRadius: 10,
    paddingHorizontal: 6,
    backgroundColor: colors.accent,
    alignItems: 'center',
    justifyContent: 'center',
  },
  unreadBadgeText: {
    color: '#081310',
    fontSize: 11,
    fontWeight: '900',
  },
  emptyCard: {
    marginHorizontal: 24,
    backgroundColor: colors.card,
    borderRadius: 22,
    borderWidth: 1,
    borderColor: colors.border,
    padding: 18,
    gap: 6,
  },
  emptyTitle: {
    color: colors.text,
    fontSize: 16,
    fontWeight: '900',
  },
  emptyText: {
    color: colors.muted,
    fontStyle: 'italic',
    fontSize: 13,
    lineHeight: 20,
  },
  threadCard: {
    marginHorizontal: 24,
    backgroundColor: colors.card,
    borderRadius: 24,
    borderWidth: 1,
    borderColor: colors.border,
    overflow: 'hidden',
  },
  threadHeader: {
    padding: 16,
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'space-between',
    gap: 12,
    borderBottomWidth: 1,
    borderBottomColor: colors.border,
    backgroundColor: '#cdf7dd',
  },
  threadTitle: {
    color: colors.text,
    fontSize: 18,
    fontWeight: '900',
    marginTop: 2,
  },
  secondaryButton: {
    backgroundColor: colors.accentSoft,
    paddingHorizontal: 14,
    paddingVertical: 10,
    borderRadius: 14,
    borderWidth: 1,
    borderColor: colors.accent,
  },
  secondaryButtonText: {
    color: colors.accent,
    fontWeight: '800',
    fontSize: 12,
  },
  threadMessages: {
    maxHeight: 320,
  },
  threadMessagesContent: {
    gap: 12,
    padding: 16,
  },
  bubbleRow: {
    flexDirection: 'row',
    alignItems: 'flex-end',
    gap: 10,
  },
  bubbleRowMine: {
    alignSelf: 'flex-end',
    flexDirection: 'row-reverse',
  },
  bubbleRowTheirs: {
    alignSelf: 'flex-start',
  },
  bubbleAvatar: {
    width: 28,
    height: 28,
    borderRadius: 14,
    backgroundColor: '#1c1c1c',
    borderWidth: 1,
    borderColor: colors.border,
    alignItems: 'center',
    justifyContent: 'center',
    marginBottom: 2,
  },
  bubbleAvatarText: {
    color: colors.muted,
    fontSize: 10,
    fontWeight: '900',
  },
  bubble: {
    maxWidth: '82%',
    paddingHorizontal: 12,
    paddingVertical: 10,
    borderRadius: 20,
    borderWidth: 1,
    gap: 4,
  },
  bubbleMine: {
    backgroundColor: colors.accentSoftAlt,
    borderColor: colors.accentSoftAlt,
    borderTopRightRadius: 8,
  },
  bubbleTheirs: {
    backgroundColor: '#1c1c1c',
    borderColor: colors.border,
    borderTopLeftRadius: 8,
  },
  bubbleAuthor: {
    color: colors.accent,
    fontSize: 11,
    fontWeight: '900',
  },
  bubbleText: {
    color: colors.text,
    fontSize: 14,
    lineHeight: 20,
  },
  bubbleTextMine: {
    color: '#07120f',
  },
  attachmentPill: {
    alignSelf: 'flex-start',
    backgroundColor: '#1f3657',
    borderWidth: 0,
    borderColor: 'transparent',
    borderRadius: 999,
    paddingHorizontal: 12,
    paddingVertical: 8,
    marginTop: 4,
  },
  attachmentPillText: {
    color: '#f4f4f4',
    fontSize: 12,
    fontWeight: '900',
  },
  bubbleMeta: {
    color: colors.muted,
    fontSize: 11,
    alignSelf: 'flex-end',
  },
  bubbleMetaMine: {
    color: '#0a2a23',
  },
  emptyThread: {
    padding: 18,
    gap: 6,
  },
  composer: {
    padding: 14,
    borderTopWidth: 1,
    borderTopColor: colors.border,
    backgroundColor: '#151515',
    gap: 10,
  },
  composerActions: {
    flexDirection: 'row',
    gap: 10,
    alignItems: 'center',
  },
  attachButton: {
    backgroundColor: '#1f3657',
    borderWidth: 0,
    borderColor: 'transparent',
    paddingHorizontal: 14,
    paddingVertical: 10,
    borderRadius: 14,
  },
  attachButtonText: {
    color: colors.accent,
    fontWeight: '800',
    fontSize: 12,
  },
  attachButtonGhost: {
    backgroundColor: colors.cardAlt,
    borderWidth: 1,
    borderColor: colors.border,
    paddingHorizontal: 14,
    paddingVertical: 10,
    borderRadius: 14,
  },
  attachButtonGhostText: {
    color: colors.muted,
    fontWeight: '800',
    fontSize: 12,
  },
  selectedAttachmentCard: {
    backgroundColor: '#1c1c1c',
    borderWidth: 1,
    borderColor: colors.border,
    borderRadius: 16,
    padding: 12,
    gap: 3,
  },
  selectedAttachmentLabel: {
    color: colors.accent,
    fontWeight: '800',
    fontSize: 11,
    textTransform: 'uppercase',
    letterSpacing: 0.6,
  },
  selectedAttachmentName: {
    color: colors.text,
    fontSize: 13,
    fontWeight: '700',
  },
  input: {
    minHeight: 84,
    textAlignVertical: 'top',
    color: colors.text,
    borderWidth: 1,
    borderColor: colors.border,
    borderRadius: 18,
    paddingHorizontal: 14,
    paddingVertical: 12,
    backgroundColor: '#111111',
  },
  sendButton: {
    backgroundColor: colors.accent,
    paddingVertical: 13,
    borderRadius: 16,
    alignItems: 'center',
  },
  sendButtonDisabled: {
    opacity: 0.65,
  },
  sendButtonText: {
    color: '#081310',
    fontWeight: '900',
    fontSize: 15,
  },
});
