import { useCallback, useEffect, useMemo, useState } from 'react';
import {
    ActivityIndicator,
    Alert,
    Pressable,
    RefreshControl,
    ScrollView,
    StyleSheet,
    Text,
    TextInput,
    View
} from 'react-native';

import { getStudentExam, startStudentExam, submitStudentExam } from '@/api/student';
import { darkTheme as colors } from '@/constants/colors';

export default function ExamScreen({ token, item, setActiveTab, theme }) {
  const examId = item?.id ?? item?.exam_id;

  const [loading, setLoading] = useState(true);
  const [refreshing, setRefreshing] = useState(false);
  const [starting, setStarting] = useState(false);
  const [submitting, setSubmitting] = useState(false);
  const [error, setError] = useState('');

  const [detail, setDetail] = useState(null);
  const [mode, setMode] = useState('loading'); // loading | detail | taking | review

  const questions = useMemo(() => detail?.questions ?? [], [detail]);
  const selectedAttempt = detail?.selected_attempt ?? null;
  const answers = detail?.answers ?? {};

  const loadExam = useCallback(async (isRefresh = false) => {
    try {
      if (!isRefresh) setLoading(true);
      setError('');

      if (!examId) {
        throw new Error('Exam ID not found.');
      }

      const response = await getStudentExam(token, examId);
      const data = response?.data ?? null;

      setDetail(data);
      console.log(`[Exam Detail] ID: ${examId}, State: ${data?.state}`);

      const state = data?.state;
      if (state === 'in_progress') {
        const nextAnswers = {};
        (data?.selected_attempt?.answers ?? []).forEach((a) => {
          nextAnswers[String(a.question_id)] = a.answer ?? '';
        });

        setDetail({
          ...data,
          answers: nextAnswers,
        });
        setMode('taking');
      } else if (data?.selected_attempt) {
        setMode('review');
      } else {
        setMode('detail');
      }
    } catch (e) {
      setError(e?.message || 'Unable to load exam.');
      setMode('detail');
    } finally {
      setLoading(false);
      setRefreshing(false);
    }
  }, [token, examId]);

  useEffect(() => {
    loadExam();
  }, [loadExam]);

  const handleRefresh = () => {
    setRefreshing(true);
    loadExam(true);
  };

  const start = async () => {
    if (!examId) return;

    setStarting(true);
    setError('');
    try {
      const response = await startStudentExam(token, examId);
      const payload = response?.data ?? {};

      // Check both response.success and potential error messages from the backend
      if (response?.success === false || payload?.error) {
        throw new Error(response?.message || payload?.error || 'The exam is not open yet.');
      }

      console.log('[Exam Start] Success:', payload);

      setDetail((current) => ({
        ...(current || {}),
        state: 'in_progress',
        selected_attempt: payload?.attempt ?? current?.selected_attempt ?? null,
        questions: payload?.questions ?? current?.questions ?? [],
        answers: {},
      }));

      setMode('taking');
    } catch (e) {
      const errorMsg = e?.message || 'Unable to start exam.';
      setError(errorMsg);
      Alert.alert('Access Denied', errorMsg);
      loadExam(true); // Auto-refresh detail to sync state with server after failure
    } finally {
      setStarting(false);
    }
  };

  const submit = async () => {
    if (!examId) return;

    setSubmitting(true);
    setError('');
    try {
      const payload = {
        answers: Object.keys(answers).map((id) => ({
          question_id: id,
          answer: answers[id],
        })),
      };
      const response = await submitStudentExam(token, examId, payload);
      const result = response?.data ?? null;

      setDetail((current) => ({
        ...(current || {}),
        state: 'submitted',
        selected_attempt: result?.attempt ?? result ?? current?.selected_attempt,
        result,
      }));

      setMode('review');
    } catch (e) {
      setError(e?.message || 'Unable to submit exam.');
    } finally {
      setSubmitting(false);
    }
  };

  const setAnswer = (questionId, value) => {
    setDetail((current) => ({
      ...(current || {}),
      answers: {
        ...(current?.answers ?? {}),
        [String(questionId)]: value,
      },
    }));
  };

  const title = detail?.exam?.title || detail?.title || item?.title || 'Exam';
  const courseName = detail?.exam?.course?.title || detail?.course?.title || '';
  const rawState = detail?.state || '';
  const state = String(rawState).trim().toLowerCase();

  return (
    <View style={[styles.screen, { backgroundColor: theme.background }]}>
      <View style={[styles.header, { backgroundColor: theme.header }]}>
        <View style={{ flex: 1 }}>
          <Text style={[styles.title, { color: theme.text }]}>{title}</Text>
          <Text style={[styles.sub, { color: theme.muted }]}>
            {courseName ? `${courseName} • ` : ''}
            {state ? String(state).replace(/_/g, ' ') : ''}
          </Text>
        </View>

        {setActiveTab ? (
          <Pressable onPress={() => setActiveTab('tasks')} style={[styles.closeButton, { backgroundColor: theme.accentSoft, borderColor: theme.border }]}>
            <Text style={[styles.closeButtonText, { color: theme.accent }]}>Close</Text>
          </Pressable>
        ) : null}
      </View>

      {loading ? (
        <View style={styles.center}>
          <ActivityIndicator color={theme.accent} />
          <Text style={[styles.centerText, { color: theme.muted }]}>Loading exam...</Text>
        </View>
      ) : (
        <ScrollView 
          style={styles.scroll} 
          contentContainerStyle={styles.scrollContent}
          refreshControl={<RefreshControl refreshing={refreshing} onRefresh={handleRefresh} tintColor={theme.accent} />}
        >
          {!!error ? <Text style={[styles.error, { color: theme.danger }]}>{error}</Text> : null}

          {mode === 'detail' ? (
            <View style={[styles.card, { backgroundColor: theme.card, borderColor: theme.border }]}>
              <Text style={[styles.cardTitle, { color: theme.text }]}>Ready?</Text>
              <Text style={[styles.cardText, { color: theme.muted }]}>
                {detail?.exam?.questions_count ?? questions.length} question(s). Start when you are ready.
              </Text>
              <Pressable
                disabled={starting || (state !== '' && state !== 'available' && state !== 'in_progress' && (detail?.exam?.questions_count ?? 0) === 0)}
                onPress={start}
                style={[styles.primaryButton, { backgroundColor: theme.accent }, (state !== '' && state !== 'available' && state !== 'in_progress' && (detail?.exam?.questions_count ?? 0) === 0) && { opacity: 0.5 }]}
              >
                <Text style={[styles.primaryButtonText, { color: theme.background }]}>
                  {starting ? 'Starting...' : (state === 'available' || state === 'in_progress' || state === '' || (detail?.exam?.questions_count ?? 0) > 0) 
                    ? 'Start exam' 
                    : `Not open yet (${rawState})`}
                </Text>
              </Pressable>
            </View>
          ) : null}

          {mode === 'taking' ? (
            <View style={{ gap: 12 }}>
              {questions.map((q, index) => (
                <View key={q.id ?? index} style={[styles.qCard, { backgroundColor: theme.card, borderColor: theme.border }]}>
                  <Text style={[styles.qNum, { color: theme.accent }]}>Question {index + 1}</Text>
                  <Text style={[styles.qText, { color: theme.text }]}>{q.question_text}</Text>
                  <Text style={[styles.qType, { color: theme.muted }]}>Type: {q.question_type || 'question'}</Text>

                  {renderQuestionInput(q, answers, setAnswer, theme)}
                </View>
              ))}

              <View style={[styles.actionsRow, { paddingTop: 8 }]}>
                <Pressable disabled={submitting} onPress={submit} style={[styles.primaryButton, { backgroundColor: theme.accent }]}>
                  <Text style={[styles.primaryButtonText, { color: theme.background }]}>{submitting ? 'Submitting...' : 'Submit exam'}</Text>
                </Pressable>
              </View>
            </View>
          ) : null}

          {mode === 'review' ? (
            <View style={{ gap: 12 }}>
              <View style={[styles.card, { backgroundColor: theme.card, borderColor: theme.border }]}>
                <Text style={[styles.cardTitle, { color: theme.text }]}>Review</Text>
                <Text style={[styles.cardText, { color: theme.muted }]}>
                  Score: {selectedAttempt?.score ?? selectedAttempt?.total_score ?? 'N/A'}
                </Text>
                <Text style={[styles.cardText, { color: theme.muted }]}>
                  Submitted: {selectedAttempt?.submitted_at ?? 'N/A'}
                </Text>
              </View>

              <ScrollView style={{ maxHeight: 520 }} contentContainerStyle={{ gap: 12 }}>
                {(selectedAttempt?.answers ?? []).length ? (
                  (selectedAttempt?.answers ?? []).map((a, index) => (
                    <View key={a.id ?? `${a.question_id}-${index}`} style={[styles.qCard, { backgroundColor: theme.card, borderColor: theme.border }]}>
                      <Text style={[styles.qNum, { color: theme.accent }]}>Answer {index + 1}</Text>
                      <Text style={[styles.qText, { color: theme.text }]}>{a.question?.question_text || 'Question'}</Text>
                      <Text style={[styles.qType, { color: theme.muted }]}>Your answer: {String(a.answer ?? '')}</Text>
                      {'points_earned' in a ? (
                        <Text style={[styles.qType, { color: theme.muted }]}>Points: {a.points_earned ?? 0}</Text>
                      ) : null}
                    </View>
                  ))
                ) : ( // Added theme to muted text
                  <Text style={[styles.muted, { color: theme.muted }]}>No review data yet.</Text>
                )}
              </ScrollView>

              <View style={[styles.actionsRow, { paddingTop: 8 }]}>
                <Pressable onPress={() => setActiveTab?.('tasks')} style={[styles.secondaryButton, { backgroundColor: theme.accentSoft, borderColor: theme.accent }]}>
                  <Text style={[styles.secondaryButtonText, { color: theme.accent }]}>Back to tasks</Text>
                </Pressable>
              </View>
            </View>
          ) : null}
        </ScrollView>
      )}
    </View>
  );
}

function renderQuestionInput(question, answers, setAnswer, theme) {
  const questionId = question?.id;
  const value = answers?.[String(questionId)] ?? '';

  let rawOptions = question?.options ?? question?.choices ?? question?.question_options ?? [];
  if (typeof rawOptions === 'string') {
    try {
      rawOptions = JSON.parse(rawOptions);
    } catch (e) {
      rawOptions = [];
    }
  }

  let options = Array.isArray(rawOptions) ? rawOptions : (rawOptions && typeof rawOptions === 'object' ? Object.values(rawOptions) : []);

  const qType = String(question?.question_type || '').toLowerCase().trim().replace(/[- ]/g, '_');
  const isChoiceType = ['multiple_choice', 'true_false'].includes(qType);

  if (qType === 'true_false' && options.length === 0) {
    options = ['True', 'False'];
  }

  if (isChoiceType) {
    return (
      <View style={styles.optionStack}>
        {options.length ? (
          options.map((opt, index) => {
            const optionValue =
              typeof opt === 'object'
                ? opt.value ?? opt.label ?? opt.text ?? String(index)
                : String(opt);
            const optionLabel =
              typeof opt === 'object'
                ? opt.label ?? opt.text ?? opt.value ?? String(index + 1)
                : String(opt);

            const active = String(value) === String(optionValue);

            return (
              <Pressable // Added theme to optionButton and optionButtonText
                key={`${questionId}-${optionValue}-${index}`}
                onPress={() => setAnswer(questionId, optionValue)}
                style={[styles.optionButton, { backgroundColor: theme.card, borderColor: theme.border }, active && styles.optionButtonActive, active && { backgroundColor: theme.accentSoft, borderColor: theme.accent }]}
              >
                <Text style={[styles.optionButtonText, { color: theme.text }, active && styles.optionButtonTextActive, active && { color: theme.accent }]}>
                  {optionLabel}
                </Text>
              </Pressable>
            );
          })
        ) : ( // Added theme to muted text
          <Text style={[styles.muted, { color: theme.muted }]}>No options available.</Text>
        )}
      </View>
    );
  }

  return (
    <TextInput
      value={String(value)}
      onChangeText={(text) => setAnswer(questionId, text)}
      placeholder="Type your answer..." // Added theme to input
      placeholderTextColor={theme.muted}
      style={[styles.input, { backgroundColor: theme.input, color: theme.text, borderColor: theme.border }]}
      multiline={question?.question_type === 'long_answer'}
    />
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
    color: colors.muted,
    marginTop: 4,
    fontSize: 13,
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
  scroll: {
    flex: 1,
  },
  scrollContent: {
    paddingHorizontal: 16,
    paddingBottom: 26,
    gap: 12,
  },
  card: {
    borderRadius: 18,
    padding: 16,
    gap: 8,
  },
  cardTitle: {
    fontWeight: '900',
    fontSize: 18,
  },
  cardText: {
    fontSize: 13,
    lineHeight: 18,
  },
  qCard: {
    borderRadius: 16,
    padding: 14,
    gap: 8,
  },
  qNum: {
    fontWeight: '900',
    fontSize: 12,
    textTransform: 'uppercase',
    letterSpacing: 0.6,
  },
  qText: {
    fontWeight: '800',
    fontSize: 15,
    lineHeight: 21,
  },
  qType: {
    fontSize: 12,
  },
  optionStack: {
    gap: 10,
    paddingTop: 4,
  },
  optionButton: {
    borderRadius: 12,
    paddingVertical: 10,
    paddingHorizontal: 12,
  },
  optionButtonActive: {
  },
  optionButtonText: {
    fontWeight: '800',
  },
  optionButtonTextActive: {
  },
  input: {
    borderRadius: 14,
    paddingHorizontal: 14,
    paddingVertical: 12,
    marginTop: 6,
    minHeight: 44,
  },
  actionsRow: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    gap: 12,
    paddingTop: 8,
  },
  primaryButton: {
    flex: 1,
    paddingVertical: 13,
    borderRadius: 14,
    alignItems: 'center',
  },
  primaryButtonText: {
    color: '#fff',
    fontWeight: '900',
  },
  secondaryButton: {
    borderRadius: 14,
    paddingVertical: 13,
    paddingHorizontal: 16,
    alignItems: 'center',
  },
  secondaryButtonText: {
    fontWeight: '900',
  },
  muted: {
  },
});
