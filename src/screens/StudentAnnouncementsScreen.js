import { useEffect, useState } from 'react';
import { ActivityIndicator, Pressable, ScrollView, StyleSheet, Text, View } from 'react-native';

import { getStudentAnnouncements } from '@/api/student';
import { darkTheme as colors } from '@/constants/colors';


export default function StudentAnnouncementsScreen({ token, theme }) {
  const [loading, setLoading] = useState(true);
  const [refreshing, setRefreshing] = useState(false);
  const [error, setError] = useState('');
  const [announcements, setAnnouncements] = useState([]);

  const loadAnnouncements = async () => {
    setError('');
    setLoading(true);

    try {
      const response = await getStudentAnnouncements(token);
      setAnnouncements(response?.data?.announcements || []);
    } catch (err) {
      setError(err?.message || 'Unable to load announcements.');
    } finally {
      setLoading(false);
    }
  };

  useEffect(() => {
    loadAnnouncements();
  }, [token]);

  const handleRefresh = async () => {
    setRefreshing(true);
    try {
      const response = await getStudentAnnouncements(token);
      setAnnouncements(response?.data?.announcements || []);
    } catch (err) {
      setError(err?.message || 'Unable to refresh announcements.');
    } finally {
      setRefreshing(false);
    }
  };

  return (
    <ScrollView style={[styles.screen, { backgroundColor: theme.background }]} contentContainerStyle={styles.container}>
      <View style={styles.headerCard}>
        <Text style={[styles.kicker, { color: theme.accent }]}>Student side</Text>
        <Text style={[styles.title, { color: theme.text }]}>Announcements</Text>
        <Text style={[styles.subtitle, { color: theme.muted }]}>
          Updates from your enrolled courses appear here.
        </Text>

        <Pressable onPress={handleRefresh} style={[styles.secondaryButton, { backgroundColor: theme.accentSoft, borderColor: theme.accent }]}>
          <Text style={[styles.secondaryButtonText, { color: theme.accent }]}>
            {refreshing ? 'Refreshing...' : 'Refresh'}
          </Text>
        </Pressable>
      </View>

      {loading ? (
        <View style={[styles.loadingCard, { backgroundColor: theme.card, borderColor: theme.border }]}>
          <ActivityIndicator size="large" color={theme.accent} />
          <Text style={[styles.loadingText, { color: theme.muted }]}>Loading announcements...</Text>
        </View>
      ) : (
        <>
          {!!error && <Text style={[styles.error, { color: theme.danger }]}>{error}</Text>}

          {announcements.length ? (
            announcements.map((announcement, index) => (
              <View key={`announcement-${index}-${announcement.announcement_id || 'no-id'}`} style={[styles.itemCard, { backgroundColor: theme.card, borderColor: theme.border }]}>
                <Text style={[styles.itemTitle, { color: theme.text }]}>{announcement.title}</Text>
                <Text style={[styles.itemMeta, { color: theme.muted }]}>{announcement.course_name}</Text>
                <Text style={[styles.body, { color: theme.text }]}>{announcement.content || 'No content provided.'}</Text>
                <Text style={[styles.date, { color: theme.accent }]}>{announcement.created_at || 'No date'}</Text>
              </View>
            ))
          ) : (
            <Text style={[styles.emptyText, { color: theme.muted }]}>No announcements yet.</Text>
          )}
        </>
      )}
    </ScrollView>
  );
}

const styles = StyleSheet.create({
  screen: {
    flex: 1,
  },
  container: {
    padding: 16,
    gap: 16,
  },
  headerCard: {
    backgroundColor: colors.card,
    borderRadius: 24,
    padding: 20,
    borderWidth: 1,
    borderColor: colors.border,
    gap: 8,
  },
  kicker: {
    color: colors.accent,
    textTransform: 'uppercase',
    letterSpacing: 1.2,
    fontSize: 12,
    fontWeight: '800',
  },
  title: {
    color: colors.text,
    fontSize: 28,
    fontWeight: '800',
  },
  subtitle: {
    color: colors.muted,
    fontSize: 14,
    lineHeight: 20,
  },
  secondaryButton: {
    backgroundColor: colors.accentSoft,
    paddingVertical: 12,
    paddingHorizontal: 16,
    borderRadius: 14,
    alignSelf: 'flex-start',
    marginTop: 6,
  },
  secondaryButtonText: {
    color: colors.accent,
    fontWeight: '800',
  },
  loadingCard: {
    backgroundColor: colors.card,
    borderRadius: 20,
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
    color: '#dc2626',
    fontWeight: '700',
  },
  itemCard: {
    backgroundColor: colors.card,
    borderRadius: 18,
    padding: 16,
    borderWidth: 1,
    borderColor: colors.border,
    gap: 6,
  },
  itemTitle: {
    color: colors.text,
    fontSize: 16,
    fontWeight: '800',
  },
  itemMeta: {
    color: colors.muted,
    fontSize: 13,
  },
  body: {
    color: colors.text,
    fontSize: 14,
    lineHeight: 20,
  },
  date: {
    color: colors.accent,
    fontSize: 12,
    fontWeight: '700',
  },
  emptyText: {
    color: colors.muted,
    fontStyle: 'italic',
  },
});
