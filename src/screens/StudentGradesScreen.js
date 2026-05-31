import { useEffect, useState } from 'react';
import { ActivityIndicator, Pressable, ScrollView, StyleSheet, Text, View } from 'react-native';

import { getStudentGrades } from '@/api/student';
import { darkTheme as colors } from '@/constants/colors';

export default function StudentGradesScreen({ token, theme }) {
  const [loading, setLoading] = useState(true);
  const [refreshing, setRefreshing] = useState(false);
  const [error, setError] = useState('');
  const [rows, setRows] = useState([]);

  const loadGrades = async () => {
    setError('');
    setLoading(true);

    try {
      const response = await getStudentGrades(token);
      setRows(response?.data?.rows || []);
    } catch (err) {
      setError(err?.message || 'Unable to load grades.');
    } finally {
      setLoading(false);
    }
  };

  useEffect(() => {
    loadGrades();
  }, [token]);

  const handleRefresh = async () => {
    setRefreshing(true);
    try {
      const response = await getStudentGrades(token);
      setRows(response?.data?.rows || []);
    } catch (err) {
      setError(err?.message || 'Unable to refresh grades.');
    } finally {
      setRefreshing(false);
    }
  };

  return (
    <ScrollView style={[styles.screen, { backgroundColor: theme.background }]} contentContainerStyle={styles.container}>
      <View style={styles.headerSection}>
        <Text style={[styles.title, { color: theme.text }]}>GRADES</Text>
        <Text style={[styles.subtitle, { color: theme.muted }]}>
          See the scores and feedback returned by your Laravel grading data.
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
          <Text style={[styles.loadingText, { color: theme.muted }]}>Loading grades...</Text>
        </View>
      ) : (
        <>
          {!!error && <Text style={[styles.error, { color: theme.danger }]}>{error}</Text>}

          {rows.length ? (
            rows.map((row, index) => (
              <View key={`${row.assignment_title}-${index}`} style={[styles.itemCard, { backgroundColor: theme.card, borderColor: theme.border }]}>
                <Text style={[styles.itemTitle, { color: theme.text }]}>{row.assignment_title || 'Assignment'}</Text>
                <Text style={[styles.itemMeta, { color: theme.muted }]}>{row.course_name || 'Course'}</Text>
                <Text style={[styles.score, { color: theme.accent }]}>Score: {row.score ?? 'N/A'}</Text>
                <Text style={[styles.body, { color: theme.text }]}>{row.feedback || 'No feedback yet.'}</Text>
              </View>
            ))
          ) : (
            <Text style={[styles.emptyText, { color: theme.muted }]}>No grades found yet.</Text>
          )}
        </>
      )}
    </ScrollView>
  );
}

const styles = StyleSheet.create({
  screen: {
    flex: 1,
    backgroundColor: colors.background,
  },
  container: {
    padding: 16,
    gap: 16,
  },
  headerSection: {
    paddingHorizontal: 4,
    paddingTop: 2,
    gap: 10,
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
  score: {
    color: colors.accent,
    fontSize: 13,
    fontWeight: '800',
  },
  body: {
    color: colors.text,
    fontSize: 14,
    lineHeight: 20,
  },
  emptyText: {
    color: colors.muted,
    fontStyle: 'italic',
  },
});
