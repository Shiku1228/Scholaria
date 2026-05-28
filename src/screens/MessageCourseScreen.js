import { useEffect, useState } from 'react';
import {
  ActivityIndicator,
  Pressable,
  ScrollView,
  StyleSheet,
  Text,
  View,
} from 'react-native';

import { getStudentMessages } from '@/api/student';

export default function MessageCourseScreen({ token, user, setActiveTab, setSelectedItem, theme }) {
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState('');
  const [courses, setCourses] = useState([]);

  const loadCourses = async () => {
    setLoading(true);
    setError('');
    try {
      const response = await getStudentMessages(token);
      const list = response?.data?.courses || [];
      setCourses(list);
    } catch (err) {
      setError(err?.message || 'Unable to load courses.');
    } finally {
      setLoading(false);
    }
  };

  useEffect(() => {
    loadCourses();
  }, [token]);

  const handleCoursePress = (course) => {
    setSelectedItem(course);
    setActiveTab('chat');
  };

  return (
    <View style={[styles.screen, { backgroundColor: theme.background }]}>
      <View style={[styles.header, { backgroundColor: theme.header }]}>
        <View style={{ flex: 1 }}>
          <Text style={[styles.title, { color: theme.text }]}>MESSAGES</Text>
          <Text style={[styles.sub, { color: theme.muted }]}>Select a course to start chatting</Text>
        </View>
      </View>

      {loading ? (
        <View style={styles.center}>
          <ActivityIndicator color={theme.accent} />
          <Text style={[styles.centerText, { color: theme.muted }]}>Loading courses...</Text>
        </View>
      ) : (
        <ScrollView style={styles.scroll} contentContainerStyle={styles.scrollContent}>
          {!!error && <Text style={[styles.error, { color: theme.danger }]}>{error}</Text>}

          {courses.length ? (
            courses.map((course) => {
              const courseId = course.id || course.course_id;
              const courseName = course.name || course.course_name || 'Course';
              const courseCode = course.course_number || course.course_code || 'Active';
              const initials = courseName
                .split(' ')
                .map((word) => word[0])
                .join('')
                .toUpperCase()
                .slice(0, 2);

              return (
                <Pressable
                  key={courseId}
                  onPress={() => handleCoursePress(course)}
                  style={({ pressed }) => [
                    styles.courseCard,
                    { backgroundColor: theme.card, borderColor: theme.border },
                    pressed && styles.pressed,
                  ]}
                >
                  <View style={[styles.avatar, { backgroundColor: theme.accentSoft, borderColor: theme.accent }]}>
                    <Text style={[styles.avatarText, { color: theme.accent }]}>{initials}</Text>
                  </View>
                  <View style={styles.courseInfo}>
                    <Text style={[styles.courseName, { color: theme.text }]}>{courseName}</Text>
                    <Text style={[styles.courseCode, { color: theme.muted }]}>{courseCode}</Text>
                  </View>
                  <Text style={[styles.arrow, { color: theme.muted }]}>›</Text>
                </Pressable>
              );
            })
          ) : (
            <View style={styles.empty}>
              <Text style={[styles.emptyTitle, { color: theme.text }]}>No courses found</Text>
              <Text style={[styles.emptyText, { color: theme.muted }]}>You don't have any courses with messaging enabled.</Text>
            </View>
          )}
        </ScrollView>
      )}
    </View>
  );
}

const styles = StyleSheet.create({
  screen: {
    flex: 1,
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
    fontSize: 22,
    fontWeight: '900',
    letterSpacing: -0.3,
  },
  sub: {
    marginTop: 4,
    fontSize: 13,
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
  scroll: {
    flex: 1,
  },
  scrollContent: {
    paddingHorizontal: 16,
    paddingBottom: 26,
    gap: 12,
  },
  courseCard: {
    borderRadius: 18,
    padding: 16,
    borderWidth: 1,
    flexDirection: 'row',
    alignItems: 'center',
    gap: 12,
  },
  pressed: {
    opacity: 0.75,
  },
  avatar: {
    width: 56,
    height: 56,
    borderRadius: 28,
    borderWidth: 1,
    alignItems: 'center',
    justifyContent: 'center',
  },
  avatarText: {
    fontSize: 18,
    fontWeight: '900',
  },
  courseInfo: {
    flex: 1,
    gap: 4,
  },
  courseName: {
    fontSize: 16,
    fontWeight: '800',
  },
  courseCode: {
    fontSize: 13,
  },
  arrow: {
    fontSize: 28,
    fontWeight: '300',
  },
  empty: {
    padding: 40,
    alignItems: 'center',
    gap: 8,
  },
  emptyTitle: {
    fontSize: 18,
    fontWeight: '900',
  },
  emptyText: {
    fontSize: 13,
    textAlign: 'center',
  },
});
