import { useEffect, useState } from 'react';
import { ActivityIndicator, Pressable, RefreshControl, ScrollView, StyleSheet, Text, View } from 'react-native';

import { getStudentDashboard } from '@/api/auth';

function formatDate(value) {
  if (!value) {
    return 'No due date';
  }

  const date = new Date(value);
  if (Number.isNaN(date.getTime())) {
    return value;
  }

  return date.toLocaleDateString();
}

export default function DashboardScreen({ token, user, onNavigate, theme }) {
  const [loading, setLoading] = useState(true);
  const [refreshing, setRefreshing] = useState(false);
  const [error, setError] = useState('');
  const [dashboard, setDashboard] = useState(null);

  const handleRefresh = async () => {
    setRefreshing(true);

    try {
      const response = await getStudentDashboard(token);
      setDashboard(response?.data || null);
    } catch (err) {
      setError(err?.message || 'Unable to refresh dashboard.');
    } finally {
      setRefreshing(false);
    }
  };

  useEffect(() => {
    let cancelled = false;

    const loadDashboard = async () => {
      setError('');
      setLoading(true);

      try {
        const response = await getStudentDashboard(token);
        if (!cancelled) {
          setDashboard(response?.data || null);
        }
      } catch (err) {
        if (!cancelled) {
          setError(err?.message || 'Unable to load dashboard.');
        }
      } finally {
        if (!cancelled) {
          setLoading(false);
        }
      }
    };

    loadDashboard();

    return () => {
      cancelled = true;
    };
  }, [token]);

  const stats = dashboard?.stats || {};
  const courses = dashboard?.myCourses || [];
  const assignments = dashboard?.upcomingAssignments || [];
  const announcements = dashboard?.recentAnnouncements || [];
  const subjects = courses.slice(0, 4);

  return (
    <View style={[styles.screen, { backgroundColor: theme.background }]}>
      <ScrollView
        style={[styles.scroll, { backgroundColor: theme.background }]}
        contentContainerStyle={styles.content}
        showsVerticalScrollIndicator={false}
        refreshControl={<RefreshControl refreshing={refreshing} onRefresh={handleRefresh} tintColor={theme.accent} />}
      >
        <View style={styles.header}>
          <Text style={[styles.welcomeText, { color: theme.text }]}>
            WELCOME, {((user?.name || 'STUDENT').split(' ')[0] || 'STUDENT').toUpperCase()}!
          </Text>
          <Text style={[styles.welcomeSubtext, { color: theme.muted }]}>
            Your classes, tasks, and updates are organized here.
          </Text>
        </View>

        {!!error && <Text style={[styles.error, { color: theme.danger }]}>{error}</Text>}

        {loading ? (
          <View style={[styles.loadingCard, { backgroundColor: theme.card, borderColor: theme.border }]}>
            <ActivityIndicator color={theme.accent} size="large" />
            <Text style={[styles.loadingText, { color: theme.muted }]}>Loading dashboard...</Text>
          </View>
        ) : (
          <>
            <View style={[styles.statsCard, { backgroundColor: theme.card, borderColor: theme.border }]}>
              <View style={styles.statsRow}>
                <StatItem theme={theme} label="Enrolled" value={stats.enrolled_courses ?? 0} />
                <StatItem theme={theme} label="In Progress" value={stats.in_progress ?? 0} />
                <StatItem theme={theme} label="Completed" value={stats.completed ?? 0} />
              </View>
            </View>

            <View style={styles.sectionHeader}>
              <Text style={[styles.sectionTitle, { color: theme.text }]}>Your Subject</Text>
              <Pressable onPress={() => onNavigate?.('courses')}>
                <Text style={[styles.sectionLink, { color: theme.accent }]}>See all subjects</Text>
              </Pressable>
            </View>

            {subjects.length ? (
              <ScrollView horizontal showsHorizontalScrollIndicator={false} contentContainerStyle={styles.subjectRow}>
                {subjects.map((course, index) => (
                  <SubjectCard theme={theme} key={course.course_id || course.id || `subject-${index}`} course={course} onPress={() => onNavigate?.('courses')} />
                ))}
              </ScrollView>
            ) : (
              <View style={[styles.emptySubjectCard, { backgroundColor: theme.card, borderColor: theme.border }]}>
                <Text style={[styles.emptyText, { color: theme.muted }]}>No enrolled courses found yet.</Text>
              </View>
            )}

            <SectionCard theme={theme} title="Upcoming">
              {assignments.length ? (
                assignments.slice(0, 3).map((item, index) => (
                  <ListRow theme={theme}
                    key={item.assignment_id || item.id || `assignment-${index}`}
                    title={item.assignment_title}
                    meta={`${item.course_name || 'Course'} - ${formatDate(item.due_date)}`}
                  />
                ))
              ) : (
                <Text style={[styles.emptyText, { color: theme.muted }]}>Nothing due soon.</Text>
              )}
            </SectionCard>

            <SectionCard theme={theme} title="Announcements">
              {announcements.length ? (
                announcements.slice(0, 3).map((item, index) => (
                  <ListRow theme={theme} key={`${item.title}-${index}`} title={item.title} meta={item.course_name} />
                ))
              ) : (
                <Text style={[styles.emptyText, { color: theme.muted }]}>No recent announcements.</Text>
              )}
            </SectionCard>
          </>
        )}
      </ScrollView>
    </View>
  );
}

function StatItem({ label, value, theme }) {
  return (
    <View style={styles.statItem}>
      <Text style={[styles.statItemLabel, { color: theme.text }]}>{label}</Text>
      <Text style={[styles.statItemValue, { color: theme.muted }]}>{value}</Text>
    </View>
  );
}

function SubjectCard({ course, onPress, theme }) {
  return (
    <Pressable onPress={onPress} style={[styles.subjectCard, { backgroundColor: theme.card, borderColor: theme.border }]}>
      <View style={[styles.subjectPreview, { backgroundColor: theme.background }]}>
        <Text style={styles.previewIcon}>[]</Text>
      </View>

      <View style={[styles.subjectBody, { backgroundColor: theme.card }]}>
        <View style={[styles.subjectPill, { backgroundColor: theme.accentSoft }]}>
          <Text style={[styles.subjectPillText, { color: theme.accent }]}>{course.course_number || 'SUBJECT'}</Text>
        </View>
        <Text style={[styles.subjectTitle, { color: theme.text }]} numberOfLines={2}>
          {course.course_name || 'Subject'}
        </Text>
        <Text style={[styles.subjectCode, { color: theme.muted }]}>{course.course_number || 'No code'}</Text>
        <Pressable onPress={onPress} style={[styles.subjectButton, { backgroundColor: theme.accent }]}>
          <Text style={[styles.subjectButtonText, { color: theme.background }]}>View Subject</Text>
        </Pressable>
      </View>
    </Pressable>
  );
}

function SectionCard({ title, children, theme }) {
  return (
    <View style={[styles.sectionCard, { backgroundColor: theme.card, borderColor: theme.border }]}>
      <Text style={[styles.sectionTitle, { color: theme.text }]}>{title}</Text>
      <View style={styles.sectionList}>{children}</View>
    </View>
  );
}

function ListRow({ title, meta, theme }) {
  return (
    <View style={[styles.listRow, { backgroundColor: theme.background, borderColor: theme.border }]}>
      <Text style={[styles.listTitle, { color: theme.text }]} numberOfLines={1}>
        {title}
      </Text>
      <Text style={[styles.listMeta, { color: theme.muted }]} numberOfLines={1}>
        {meta}
      </Text>
    </View>
  );
}

const styles = StyleSheet.create({
  screen: {
    flex: 1,
  },
  scroll: {
    flex: 1,
  },
  content: {
    paddingHorizontal: 16,
    paddingTop: 16,
    paddingBottom: 24,
    gap: 16,
  },
  header: {
    gap: 8,
    paddingTop: 4,
    paddingBottom: 6,
  },
  welcomeText: {
    fontSize: 38,
    lineHeight: 44,
    fontWeight: '900',
    letterSpacing: -0.5,
  },
  welcomeSubtext: {
    fontSize: 14,
    lineHeight: 20,
  },
  error: {
    color: '#ff7d7d',
    fontWeight: '700',
  },
  loadingCard: {
    borderRadius: 22,
    padding: 24,
    alignItems: 'center',
    justifyContent: 'center',
    gap: 12,
    borderWidth: 1,
  },
  loadingText: {
  },
  statsCard: {
    borderRadius: 20,
    borderWidth: 2,
    paddingHorizontal: 14,
    paddingVertical: 10,
  },
  statsRow: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    gap: 8,
  },
  statItem: {
    flex: 1,
    gap: 4,
  },
  statItemLabel: {
    fontSize: 12,
    fontWeight: '900',
  },
  statItemValue: {
    fontSize: 16,
    fontWeight: '500',
  },
  sectionHeader: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    alignItems: 'center',
    marginTop: 6,
  },
  sectionTitle: {
    fontSize: 20,
    fontWeight: '800',
  },
  sectionLink: {
    fontSize: 15,
    fontWeight: '700',
  },
  subjectRow: {
    gap: 16,
    paddingRight: 4,
  },
  subjectCard: {
    width: 252,
    overflow: 'hidden',
    borderRadius: 28,
    borderWidth: 1,
    borderColor: '#4c4c4c',
  },
  subjectPreview: {
    height: 160,
    alignItems: 'center',
    justifyContent: 'center',
  },
  previewIcon: {
    color: '#d0d7dd',
    fontSize: 62,
    fontWeight: '300',
    marginTop: -16,
  },
  subjectBody: {
    padding: 16,
    gap: 8,
  },
  subjectPill: {
    alignSelf: 'flex-end',
    backgroundColor: '#1f3657',
    paddingHorizontal: 14,
    paddingVertical: 7,
    borderRadius: 999,
    marginTop: -42,
  },
  subjectPillText: {
    fontSize: 12,
    fontWeight: '900',
  },
  subjectTitle: {
    color: '#1f3657',
    fontSize: 20,
    lineHeight: 24,
    fontWeight: '900',
    marginTop: 6,
  },
  subjectCode: {
    color: '#6f7a72',
    fontSize: 16,
    fontWeight: '500',
  },
  subjectButton: {
    marginTop: 8,
    borderRadius: 16,
    paddingVertical: 15,
    alignItems: 'center',
  },
  subjectButtonText: {
    fontSize: 16,
    fontWeight: '800',
  },
  emptySubjectCard: {
    borderRadius: 22,
    padding: 20,
    borderWidth: 1,
  },
  emptyText: {
    fontStyle: 'italic',
  },
  sectionCard: {
    borderRadius: 22,
    borderWidth: 1,
    padding: 16,
    gap: 10,
  },
  sectionList: {
    gap: 10,
  },
  listRow: {
    borderRadius: 16,
    padding: 12,
    gap: 4,
    borderWidth: 1,
  },
  listTitle: {
    fontSize: 15,
    fontWeight: '700',
  },
  listMeta: {
    fontSize: 12,
  },
});
