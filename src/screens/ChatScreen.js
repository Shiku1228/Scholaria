import { useEffect, useRef, useState } from 'react';
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
  sendStudentConversationMessageWithAttachment,
} from '@/api/student';
import { darkTheme as colors } from '@/constants/colors';
import { createUploadFormData, pickDocumentAsset } from '@/utils/uploads';

export default function ChatScreen({ token, user, item, setActiveTab, theme }) {
  const courseId = item?.id || item?.course_id;

  const [loading, setLoading] = useState(true);
  const [refreshing, setRefreshing] = useState(false);
  const [error, setError] = useState('');
  const [conversations, setConversations] = useState([]);
  const [selectedConversation, setSelectedConversation] = useState(null);
  const [messages, setMessages] = useState([]);
  const [messageText, setMessageText] = useState('');
  const [sending, setSending] = useState(false);
  const [attachment, setAttachment] = useState(null);

  const scrollViewRef = useRef(null);
  const selectedConversationId = selectedConversation?.id || null;

  const loadConversations = async ({ isInitial = false } = {}) => {
    if (!courseId) {
      setConversations([]);
      setSelectedConversation(null);
      setMessages([]);
      if (isInitial) setLoading(false);
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
      setError(err?.message || 'Unable to load conversations.');
    } finally {
      if (isInitial) setLoading(false);
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
      }
    }
  };

  useEffect(() => {
    loadConversations({ isInitial: true });
  }, [courseId, token]);

  useEffect(() => {
    if (selectedConversationId) {
      loadMessages(selectedConversationId);
    } else {
      setMessages([]);
    }

    if (!selectedConversationId) return;

    const interval = setInterval(() => {
      loadMessages(selectedConversationId, { isSilent: true });
    }, 10000);

    return () => clearInterval(interval);
  }, [selectedConversationId, courseId, token]);

  useEffect(() => {
    if (messages.length > 0) {
      scrollViewRef.current?.scrollToEnd({ animated: true });
    }
  }, [messages.length]);

  const handleRefresh = async () => {
    setRefreshing(true);
    try {
      await loadConversations();
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
      await loadConversations();
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
        console.log('[File Pick] ChatScreen - Picked asset:', asset);
        setAttachment(asset);
      }
    } catch (err) {
      Alert.alert('Attachment', err?.message || 'Unable to choose a file.');
    }
  };

  const resolveAttachmentUrl = (value) => {
    if (!value) return '';
    if (/^https?:\/\//i.test(value)) return value;
    return value.startsWith('/')
      ? `${API_BASE_URL.replace(/\/api$/, '')}${value}`
      : `${API_BASE_URL.replace(/\/api$/, '')}/${value}`;
  };

  const courseName = item?.name || item?.course_name || 'Course';

  return (
    <View style={[styles.screen, { backgroundColor: theme.background }]}>
      <View style={[styles.header, { backgroundColor: theme.header }]}>
        <View style={{ flex: 1 }}>
          <Text style={[styles.title, { color: theme.text }]}>{courseName}</Text>
          <Text style={[styles.sub, { color: theme.muted }]}>
            {selectedConversation?.name || 'Select a conversation'}
          </Text>
        </View>

        <Pressable onPress={handleRefresh} style={[styles.iconButton, { backgroundColor: theme.accentSoft, borderColor: theme.border }]}>
          <Text style={[styles.iconButtonText, { color: theme.accent }]}>↻</Text>
        </Pressable>

        {setActiveTab ? (
          <Pressable onPress={() => setActiveTab('messages')} style={[styles.closeButton, { backgroundColor: theme.accentSoft, borderColor: theme.border }]}>
            <Text style={[styles.closeButtonText, { color: theme.accent }]}>Close</Text>
          </Pressable>
        ) : null}
      </View>

      {loading ? (
        <View style={styles.center}>
          <ActivityIndicator color={theme.accent} />
          <Text style={[styles.centerText, { color: theme.muted }]}>Loading conversations...</Text>
        </View>
      ) : (
        <>
          <ScrollView
            horizontal
            showsHorizontalScrollIndicator={false}
            style={styles.conversationList}
            contentContainerStyle={styles.conversationListContent}
          >
            {conversations.map((conversation, index) => {
              const active = selectedConversationId === conversation.id;
              const initials = (conversation.name || 'C')
                .split(' ')
                .map((word) => word[0])
                .join('')
                .toUpperCase()
                .slice(0, 2);

              return (
                <Pressable
                  key={`conv-${index}-${conversation.id || 'no-id'}`}
                  onPress={() => setSelectedConversation(conversation)}
                  style={({ pressed }) => [
                    styles.conversationChip,
                    { backgroundColor: theme.card, borderColor: theme.border },
                    active && styles.conversationChipActive,
                    active && { backgroundColor: theme.accentSoft, borderColor: theme.accent },
                    pressed && styles.pressed,
                  ]}
                >
                  <View
                    style={[
                      styles.conversationAvatar,
                      { backgroundColor: theme.card, borderColor: theme.border },
                      active && styles.conversationAvatarActive,
                      active && { backgroundColor: theme.accent, borderColor: theme.accent },
                    ]}
                  >
                    <Text
                      style={[
                        styles.conversationAvatarText,
                        { color: theme.text },
                        active && styles.conversationAvatarTextActive,
                        active && { color: theme.background },
                      ]}
                    >
                      {initials}
                    </Text>
                  </View>
                </Pressable>
              );
            })}
          </ScrollView>

          <View style={[styles.divider, { backgroundColor: theme.border }]} />

          {selectedConversation ? (
            <View style={styles.threadTypeIndicator}>
              <Text style={[styles.threadTypeText, { color: theme.muted }]}>
                {selectedConversation.is_group || selectedConversation.group || selectedConversation.group_name
                  ? 'Group chat'
                  : 'Users'}
              </Text>
            </View>
          ) : null}

          <ScrollView
            ref={scrollViewRef}
            style={styles.messagesScroll}
            contentContainerStyle={styles.messagesContent}
          >
            {!!error && <Text style={[styles.error, { color: theme.danger }]}>{error}</Text>}

            {selectedConversation ? (
              messages.length ? (
                messages.map((message, index) => {
                  const mine = isOwnMessage(message, user);
                  const attachmentUrl = resolveAttachmentUrl(
                    message.attachment_url || message.file_url || ''
                  );
                  const attachmentName =
                    message.attachment_name || message.file_name || 'Attachment';

                  return (
                    <View
                      key={`msg-${index}-${message.id || 'no-id'}`}
                      style={[
                        styles.bubbleRow,
                        mine ? styles.bubbleRowMine : styles.bubbleRowTheirs,
                      ]}
                    >
                      {!mine ? (
                        <View style={[styles.bubbleAvatar, { backgroundColor: theme.accentSoft, borderColor: theme.accent }]}>
                          <Text style={[styles.bubbleAvatarText, { color: theme.accent }]}>
                            {initialsFromName(
                              message.user_name || message.sender_name || 'T'
                            )}
                          </Text>
                        </View>
                      ) : null}

                      <View
                        style={[
                          styles.bubble,
                          { backgroundColor: theme.card, borderColor: theme.border },
                          mine ? styles.bubbleMine : styles.bubbleTheirs,
                          mine && { backgroundColor: theme.accent, borderBottomRightRadius: 4 },
                        ]}
                      >
                        {!mine ? (
                          <Text style={[styles.bubbleAuthor, { color: theme.accent }]}>
                            {message.user_name || message.sender_name || 'Member'}
                          </Text>
                        ) : null}

                        <Text
                          style={[styles.bubbleText, { color: theme.text }, mine && styles.bubbleTextMine, mine && { color: theme.background }]}
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
                            <Text style={[styles.attachmentPillText, { color: theme.accent }]}>
                              {attachmentName}
                            </Text>
                          </Pressable>
                        ) : null}

                        <Text style={[styles.bubbleMeta, { color: theme.muted }, mine && styles.bubbleMetaMine, mine && { color: theme.background, opacity: 0.7 }]}>
                          {message.created_human || message.created_at || ''}
                        </Text>
                      </View>
                    </View>
                  );
                })
              ) : (
                <View style={styles.empty}>
                  <Text style={[styles.emptyTitle, { color: theme.text }]}>No messages yet</Text>
                  <Text style={[styles.emptyText, { color: theme.muted }]}>Start the conversation!</Text>
                </View>
              )
            ) : (
              <View style={styles.empty}>
                <Text style={[styles.emptyTitle, { color: theme.text }]}>No conversation selected</Text>
                <Text style={[styles.emptyText, { color: theme.muted }]}>Select a conversation above to start chatting.</Text>
              </View>
            )}
          </ScrollView>

          <View style={[styles.composer, { backgroundColor: theme.header, borderTopColor: theme.border }]}>
            {attachment?.name ? (
              <View style={[styles.selectedAttachment, { backgroundColor: theme.accentSoft }]}>
                <Text style={[styles.selectedAttachmentText, { color: theme.accent }]}>
                  📎 {attachment.name}
                </Text>
                <Pressable onPress={() => setAttachment(null)} style={styles.removeAttachment}>
                  <Text style={[styles.removeAttachmentText, { color: theme.danger }]}>✕</Text>
                </Pressable>
              </View>
            ) : null}

            <View style={styles.inputRow}>
              <Pressable onPress={pickAttachment} style={styles.attachButton}>
                <Text style={styles.attachButtonText}>📎</Text>
              </Pressable>

              <TextInput
                value={messageText}
                onChangeText={setMessageText}
                placeholder="Type a message..."
                placeholderTextColor={theme.muted}
                multiline
                style={[styles.input, { backgroundColor: theme.input, color: theme.text, borderColor: theme.border }]}
              />

              <Pressable
                onPress={handleSend}
                disabled={sending}
                style={({ pressed }) => [
                  styles.sendButton,
                  { backgroundColor: theme.accent },
                  pressed && styles.pressed,
                  sending && styles.sendButtonDisabled,
                ]}
              >
                <Text style={[styles.sendButtonText, { color: theme.background }]}>
                  {sending ? '...' : '➤'}
                </Text>
              </Pressable>
            </View>
          </View>
        </>
      )}
    </View>
  );
}

function initialsFromName(value) {
  const parts = String(value || '')
    .trim()
    .split(/\s+/)
    .filter(Boolean);

  if (!parts.length) return '?';

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
  header: {
    paddingHorizontal: 16,
    paddingTop: 14,
    paddingBottom: 10,
    flexDirection: 'row',
    alignItems: 'flex-start',
    gap: 10,
  },
  title: {
    fontSize: 20,
    fontWeight: '900',
    letterSpacing: -0.3,
  },
  sub: {
    marginTop: 4,
    fontSize: 13,
  },
  iconButton: {
    width: 36,
    height: 36,
    borderRadius: 12,
    alignItems: 'center',
    justifyContent: 'center',
  },
  iconButtonText: {
    fontSize: 18,
    fontWeight: '700',
  },
  closeButton: {
    paddingVertical: 8,
    paddingHorizontal: 12,
    borderRadius: 12,
    alignSelf: 'flex-start',
  },
  closeButtonText: {
    fontWeight: '800',
  },
  center: {
    flex: 1,
    alignItems: 'center',
    justifyContent: 'center',
    gap: 10,
  },
  centerText: {
  },
  error: {
    color: '#dc2626',
    fontWeight: '800',
    marginBottom: 10,
  },
  conversationList: {
    maxHeight: 80,
  },
  conversationListContent: {
    paddingHorizontal: 16,
    gap: 10,
  },
  conversationChip: {
    width: 40,
    height: 40,
    borderRadius: 9999,
    padding: 0,
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'center',
    borderWidth: 1,
  },
  conversationChipActive: {
  },
  pressed: {
    opacity: 0.75,
  },
  conversationAvatar: {
    width: 34,
    height: 34,
    borderRadius: 9999,
    borderWidth: 1,
    alignItems: 'center',
    justifyContent: 'center',
  },
  conversationAvatarActive: {
  },
  conversationAvatarText: {
    fontSize: 12,
    fontWeight: '900',
    textAlign: 'center',
    lineHeight: 14,
  },
  conversationAvatarTextActive: {
  },
  conversationName: {
    fontSize: 13,
    fontWeight: '700',
    maxWidth: 120,
  },
  conversationNameActive: {
  },
  divider: {
    height: 1,
    marginHorizontal: 16,
  },
  messagesScroll: {
    flex: 1,
  },
  messagesContent: {
    paddingHorizontal: 16,
    paddingBottom: 16,
    gap: 12,
  },
  bubbleRow: {
    flexDirection: 'row',
    alignItems: 'flex-end',
    gap: 8,
  },
  bubbleRowMine: {
    alignSelf: 'flex-end',
    flexDirection: 'row-reverse',
  },
  bubbleRowTheirs: {
    alignSelf: 'flex-start',
  },
  bubbleAvatar: {
    width: 32,
    height: 32,
    borderRadius: 16,
    borderWidth: 1,
    alignItems: 'center',
    justifyContent: 'center',
  },
  bubbleAvatarText: {
    fontSize: 12,
    fontWeight: '900',
  },
  bubble: {
    maxWidth: '75%',
    borderRadius: 16,
    padding: 12,
    gap: 4,
  },
  bubbleMine: {
    borderBottomRightRadius: 4,
  },
  bubbleTheirs: {
    borderBottomLeftRadius: 4,
    borderWidth: 1,
  },
  bubbleAuthor: {
    fontSize: 11,
    fontWeight: '700',
  },
  bubbleText: {
    fontSize: 14,
    lineHeight: 20,
  },
  bubbleTextMine: {
  },
  bubbleMeta: {
    fontSize: 10,
    alignSelf: 'flex-end',
  },
  bubbleMetaMine: {
  },
  attachmentPill: {
    backgroundColor: 'rgba(255, 255, 255, 0.1)',
    borderRadius: 8,
    padding: 8,
    marginTop: 4,
  },
  attachmentPillText: {
    fontSize: 12,
    fontWeight: '700',
  },
  empty: {
    flex: 1,
    alignItems: 'center',
    justifyContent: 'center',
    gap: 8,
    paddingVertical: 60,
  },
  emptyTitle: {
    fontSize: 16,
    fontWeight: '900',
  },
  emptyText: {
    fontSize: 13,
    textAlign: 'center',
  },
  threadTypeIndicator: {
    paddingHorizontal: 16,
    paddingTop: 10,
    paddingBottom: 2,
  },
  threadTypeText: {
    fontSize: 12,
    fontWeight: '800',
  },
  composer: {
    borderTopWidth: 1,
    padding: 12,
    gap: 8,
  },
  selectedAttachment: {
    borderRadius: 8,
    padding: 8,
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'space-between',
  },
  selectedAttachmentText: {
    fontSize: 12,
    fontWeight: '700',
    flex: 1,
  },
  removeAttachment: {
    marginLeft: 8,
  },
  removeAttachmentText: {
    color: '#ff7d7d',
    fontSize: 14,
    fontWeight: '900',
  },
  inputRow: {
    flexDirection: 'row',
    alignItems: 'flex-end',
    gap: 8,
  },
  attachButton: {
    width: 44,
    height: 44,
    borderRadius: 12,
    alignItems: 'center',
    justifyContent: 'center',
  },
  attachButtonText: {
    fontSize: 18,
  },
  input: {
    flex: 1,
    borderRadius: 12,
    paddingHorizontal: 12,
    paddingVertical: 10,
    fontSize: 14,
    maxHeight: 100,
  },
  sendButton: {
    width: 44,
    height: 44,
    borderRadius: 12,
    alignItems: 'center',
    justifyContent: 'center',
  },
  sendButtonDisabled: {
    opacity: 0.5,
  },
  sendButtonText: {
    fontSize: 18,
    fontWeight: '900',
  },
});
