import { useEffect, useState } from 'react';
import { ActivityIndicator, Pressable, ScrollView, StyleSheet, Text, View } from 'react-native';

import { getStudentNotifications, markAllStudentNotificationsRead } from '@/api/student';
import { darkTheme as colors } from '@/constants/colors';


export default function StudentNotificationsScreen({ token, theme }) {
  const [loading, setLoading] = useState(true);
  const [refreshing, setRefreshing] = useState(false);
  const [error, setError] = useState('');
  const [notifications, setNotifications] = useState([]);
  const [unreadCount, setUnreadCount] = useState(0);

  const loadNotifications = async () => {
    setError('');
    setLoading(true);

    try {
      const response = await getStudentNotifications(token);
      setNotifications(response?.data?.notifications || []);
      setUnreadCount(response?.data?.unread_count || 0);
    } catch (err) {
      setError(err?.message || 'Unable to load notifications.');
    } finally {
      setLoading(false);
    }
  };

  useEffect(() => {
    loadNotifications();
  }, [token]);

  const handleRefresh = async () => {
    setRefreshing(true);
    try {
      const response = await getStudentNotifications(token);
      setNotifications(response?.data?.notifications || []);
      setUnreadCount(response?.data?.unread_count || 0);
    } catch (err) {
      setError(err?.message || 'Unable to refresh notifications.');
    } finally {
      setRefreshing(false);
    }
  };

  const handleMarkAllRead = async () => {
    try {
      await markAllStudentNotificationsRead(token);
      await loadNotifications();
    } catch (err) {
      setError(err?.message || 'Unable to mark notifications as read.');
    }
  };

  return (
    <ScrollView style={[styles.screen, { backgroundColor: theme.background }]} contentContainerStyle={styles.container}>
      <View style={styles.headerSection}>
        <Text style={[styles.title, { color: theme.text }]}>NOTIFICATIONS</Text>
        <Text style={[styles.subtitle, { color: theme.muted }]}>
          {unreadCount} unread notification{unreadCount === 1 ? '' : 's'}.
        </Text>

        <View style={styles.actionsRow}>
          <Pressable onPress={handleRefresh} style={[styles.secondaryButton, { backgroundColor: theme.accentSoft, borderColor: theme.accent }]}>
            <Text style={[styles.secondaryButtonText, { color: theme.accent }]}>
              {refreshing ? 'Refreshing...' : 'Refresh'}
            </Text>
          </Pressable>
          <Pressable onPress={handleMarkAllRead} style={[styles.primaryButton, { backgroundColor: theme.accent }]}>
            <Text style={[styles.primaryButtonText, { color: theme.background }]}>Mark all read</Text>
          </Pressable>
        </View>
      </View>

      {loading ? (
        <View style={[styles.loadingCard, { backgroundColor: theme.card, borderColor: theme.border }]}>
          <ActivityIndicator size="large" color={theme.accent} />
          <Text style={[styles.loadingText, { color: theme.muted }]}>Loading notifications...</Text>
        </View>
      ) : (
        <>
          {!!error && <Text style={[styles.error, { color: theme.danger }]}>{error}</Text>}

          {notifications.length ? (
            notifications.map((notification) => (
              <View key={notification.id} style={[styles.itemCard, { backgroundColor: theme.card, borderColor: theme.border }]}>
                <Text style={[styles.itemTitle, { color: theme.text }]}>{notification.title}</Text>
                <Text style={[styles.itemMeta, { color: theme.muted }]}>{notification.created_at || 'No date'}</Text>
                <Text style={[styles.body, { color: theme.text }]}>{notification.message || 'No message provided.'}</Text>
                <Text style={[styles.status, { color: theme.muted }, !notification.read_at && { color: theme.accent }]}>
                  {notification.read_at ? 'Read' : 'Unread'}
                </Text>
              </View>
            ))
          ) : (
            <Text style={[styles.emptyText, { color: theme.muted }]}>No notifications yet.</Text>
          )}
        </>
      )}
    </ScrollView>
  );
}

const styles = StyleSheet.create({
  screen: { flex: 1 },
  container: { padding: 16, gap: 16 },
  headerSection: {
    paddingHorizontal: 4,
    paddingTop: 2,
    gap: 10,
  },
  title: {
    fontSize: 30,
    lineHeight: 34,
    fontWeight: '900',
    letterSpacing: -0.4,
  },
  subtitle: { color: colors.muted, fontSize: 14, lineHeight: 20 },
  actionsRow: { flexDirection: 'row', gap: 12, marginTop: 6 },
  secondaryButton: {
    paddingVertical: 12,
    paddingHorizontal: 16,
    borderRadius: 14,
  },
  secondaryButtonText: { color: colors.accent, fontWeight: '800' },
  primaryButton: {
    paddingVertical: 12,
    paddingHorizontal: 16,
    borderRadius: 14,
  },
  primaryButtonText: { color: '#fff', fontWeight: '800' },
  loadingCard: {
    borderRadius: 20,
    padding: 20,
    alignItems: 'center',
    gap: 10,
    borderWidth: 1,
  },
  loadingText: { color: colors.muted },
  error: { color: '#dc2626', fontWeight: '700' },
  itemCard: {
    borderRadius: 18,
    padding: 16,
    borderWidth: 1,
    gap: 6,
  },
  itemTitle: { color: colors.text, fontSize: 16, fontWeight: '800' },
  itemMeta: { color: colors.muted, fontSize: 13 },
  body: { color: colors.text, fontSize: 14, lineHeight: 20 },
  status: { color: colors.muted, fontSize: 12, fontWeight: '800' },
  unread: { color: colors.accent },
  emptyText: { color: colors.muted, fontStyle: 'italic' },
});
