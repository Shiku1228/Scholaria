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

import {
    getStudentQuiz,
    startStudentQuiz,
    submitStudentQuiz,
} from '@/api/student';

export default function QuizScreen({ token, item, setActiveTab, theme, onAuthFailure }) {
  const quizId = item?.id ?? item?.quiz_id;

  const [loading, setLoading] = useState(true);
  const [refreshing, setRefreshing] = useState(false);
  const [starting, setStarting] = useState(false);
  const [submitting, setSubmitting] = useState(false);
  const [error, setError] = useState('');

  const [detail, setDetail] = useState(null);
  const [mode, setMode] = useState('loading'); // loading | detail | taking | review
  const [answers, setAnswers] = useState({});

  const questions = useMemo(() => detail?.questions ?? [], [detail]);
  const selectedAttempt = detail?.selected_attempt ?? null;

  const loadQuiz = useCallback(async (isRefresh = false) => {
    try {
      if (!isRefresh) setLoading(true);
      setError('');

      if (!quizId) {
        throw new Error('Quiz ID not found.');
      }

      const response = await getStudentQuiz(token, quizId);
      const data = response?.data ?? null;

      setDetail(data);
      console.log(`[Quiz Detail] ID: ${quizId}, State: ${data?.state}`);

      const state = data?.state;
      if (state === 'in_progress') {
        // hydrate answers from selected_attempt
        const nextAnswers = {};
        (data?.selected_attempt?.answers ?? []).forEach((a) => {
          nextAnswers[String(a.question_id)] = a.answer ?? '';
        });

        setAnswers(nextAnswers);
        setDetail({
          ...data,
          answers: nextAnswers,
        });
        setMode('taking');
      } else if (data?.selected_attempt) {
        setAnswers({});
        setMode('review');
      } else {
        setAnswers({});
        setMode('detail');
      }
    } catch (e) {
      setError(e?.message || 'Unable to load quiz.');
      setMode('detail');
      if (e?.status === 401 || /token is invalid|unauthorized|unauthenticated/i.test(String(e?.message || ''))) {
        onAuthFailure?.();
      }
    } finally {
      setLoading(false);
      setRefreshing(false);
    }
  }, [token, quizId]);

  useEffect(() => {
    loadQuiz();
  }, [loadQuiz]);

  const handleRefresh = () => {
    setRefreshing(true);
    loadQuiz(true);
  };

  const start = async () => {
    if (!quizId) return;

    setStarting(true);
    setError('');
    try {
      const response = await startStudentQuiz(token, quizId);
      const payload = response?.data ?? {};

      // Check both response.success and potential error messages from the backend
      if (response?.success === false || payload?.error) {
        throw new Error(response?.message || payload?.error || 'The quiz is not open yet.');
      }

      console.log('[Quiz Start] Success:', payload);

      const initialAnswers = {};
      setDetail((current) => ({
        ...(current || {}),
        state: 'in_progress',
        selected_attempt: payload?.attempt ?? current?.selected_attempt ?? null,
        questions: payload?.questions ?? current?.questions ?? [],
        answers: initialAnswers,
      }));
      setAnswers(initialAnswers);

      setMode('taking');
    } catch (e) {
      const errorMsg = e?.message || 'Unable to start quiz.';
      setError(errorMsg);
      Alert.alert('Access Denied', errorMsg);
      if (e?.status === 401 || /token is invalid|unauthorized|unauthenticated/i.test(String(errorMsg))) {
        onAuthFailure?.();
      }
      loadQuiz(true); // Auto-refresh detail to sync state with server after failure
    } finally {
      setStarting(false);
    }
  };

  const submit = async () => {
    if (!quizId) return;

    setSubmitting(true);
    setError('');
    try {
      const payloadAnswers = {};
      const answerList = [];
      questions.forEach((question) => {
        const questionId = String(question?.id);
        const normalizedAnswer = String(answers[questionId] ?? '').trim();
        payloadAnswers[questionId] = normalizedAnswer;
        answerList.push(normalizedAnswer);
      });

      const answeredCount = Object.values(payloadAnswers).filter((value) => value !== '').length;

      if (!answeredCount) {
        throw new Error('Please answer at least one question before submitting.');
      }

      const payload = {
        answer: answerList,
        answers: payloadAnswers,
      };
      const response = await submitStudentQuiz(token, quizId, payload);
      const result = response?.data ?? null;

      if (response?.success === false || result?.error) {
        throw new Error(response?.message || result?.error || 'Unable to submit quiz.');
      }

      setDetail((current) => ({
        ...(current || {}),
        state: 'submitted',
        selected_attempt: result?.attempt ?? result ?? current?.selected_attempt,
        result,
      }));

      setMode('review');
    } catch (e) {
      const errorMsg = e?.message || 'Unable to submit quiz.';
      setError(errorMsg);
      Alert.alert('Unable to submit quiz', errorMsg);
      if (e?.status === 401 || /token is invalid|unauthorized|unauthenticated/i.test(String(errorMsg))) {
        onAuthFailure?.();
      }
    } finally {
      setSubmitting(false);
    }
  };

  const setAnswer = (questionId, value) => {
    setAnswers((current) => ({
      ...(current || {}),
      [String(questionId)]: value,
    }));

    setDetail((current) => ({
      ...(current || {}),
      answers: {
        ...(current?.answers ?? {}),
        [String(questionId)]: value,
      },
    }));
  };

  const title = detail?.quiz?.title || detail?.title || item?.title || 'Quiz';
  const courseName = detail?.quiz?.course?.title || detail?.course?.title || '';
  const rawState = detail?.state || '';
  const state = String(rawState).trim().toLowerCase();

  return (
    <View style={[styles.screen, { backgroundColor: theme.background }]}>
      <View style={[styles.header, { backgroundColor: theme.header }]}>
        <View style={{ flex: 1 }}>
          <Text style={[styles.title, { color: theme.text }]}>{title}</Text>
          <Text style={[styles.sub, { color: theme.muted }]}>
            {courseName ? `${courseName} • ` : ''}
            {state ? String(state).replace(/_/g, ' ') : 'Ready'}
          </Text>
        </View>

        {setActiveTab ? (
          <Pressable // Added theme to closeButton
            onPress={() => setActiveTab('tasks')}
            style={[styles.closeButton, { backgroundColor: theme.accentSoft, borderColor: theme.border }]}
          >
            <Text style={[styles.closeButtonText, { color: theme.accent }]}>Close</Text>
          </Pressable>
        ) : null}
      </View>

      {loading ? (
        <View style={styles.center}>
          <ActivityIndicator color={theme.accent} />
          <Text style={[styles.centerText, { color: theme.muted }]}>Loading quiz...</Text>
        </View>
      ) : (
        <ScrollView
          style={styles.scroll}
          contentContainerStyle={styles.scrollContent}
          keyboardShouldPersistTaps="handled"
          refreshControl={<RefreshControl refreshing={refreshing} onRefresh={handleRefresh} tintColor={theme.accent} />}
        >
          {!!error ? <Text style={[styles.error, { color: theme.danger }]}>{error}</Text> : null}

          {mode === 'detail' ? (
            <View style={[styles.card, { backgroundColor: theme.card, borderColor: theme.border }]}>
              <Text style={[styles.cardTitle, { color: theme.text }]}>Ready?</Text>
              <Text style={[styles.cardText, { color: theme.muted }]}>
                {detail?.quiz?.questions_count ?? questions.length} question(s). Start when you are ready.
              </Text>
              <Pressable
                disabled={starting || (state !== '' && state !== 'available' && state !== 'in_progress' && (detail?.quiz?.questions_count ?? 0) === 0)}
                onPress={start}
                style={[styles.primaryButton, { backgroundColor: theme.accent }, (state !== '' && state !== 'available' && state !== 'in_progress' && (detail?.quiz?.questions_count ?? 0) === 0) && { opacity: 0.5 }]}
              >
                <Text style={[styles.primaryButtonText, { color: theme.background }]}>
                  {starting ? 'Starting...' : (state === 'available' || state === 'in_progress' || state === '' || (detail?.quiz?.questions_count ?? 0) > 0) 
                    ? 'Start quiz' 
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
                  <Text style={[styles.primaryButtonText, { color: theme.background }]}>{submitting ? 'Submitting...' : 'Submit quiz'}</Text>
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

  let rawOptions = question?.choices ?? question?.options ?? question?.question_options ?? [];
  if (typeof rawOptions === 'string') {
    try {
      rawOptions = JSON.parse(rawOptions);
    } catch (e) {
      rawOptions = [];
    }
  }

  const qType = String(question?.question_type || '').toLowerCase().trim().replace(/[- ]/g, '_');
  let options = [];
  if (Array.isArray(rawOptions)) {
    options = rawOptions.map((opt, index) => ({
      key: String(
        typeof opt === 'object'
          ? opt.id ?? opt.choice_id ?? opt.key ?? (qType === 'true_false' ? (index === 0 ? 'true' : 'false') : getChoiceLetter(index))
          : (qType === 'true_false' ? (index === 0 ? 'true' : 'false') : getChoiceLetter(index))
      ),
      label: typeof opt === 'object'
        ? opt.choice_text ?? opt.label ?? opt.text ?? opt.value ?? String(index + 1)
        : String(opt),
    }));
  } else if (rawOptions && typeof rawOptions === 'object') {
    options = Object.entries(rawOptions).map(([key, opt], index) => ({
      key: String(key),
      label: typeof opt === 'object'
        ? opt.choice_text ?? opt.label ?? opt.text ?? opt.value ?? String(index + 1)
        : String(opt),
    }));
  }

  if (qType === 'true_false' && options.length === 0) {
    options = [
      { key: 'true', label: 'True' },
      { key: 'false', label: 'False' },
    ];
  }

  const isChoiceType = ['multiple_choice', 'true_false'].includes(qType) || options.length > 0;

  if (isChoiceType) {
    return (
      <View style={styles.optionStack}>
        <Text style={[styles.choiceHint, { color: theme.muted }]}>Answer keys: A, B, C, D</Text>
        {options.length ? (
          options.map((opt, index) => {
            const optionKey = String(opt?.key ?? getChoiceLetter(index));
            const optionLabel = String(opt?.label ?? '');
            const displayKey = qType === 'true_false'
              ? (optionKey === 'true' ? 'T' : optionKey === 'false' ? 'F' : optionKey)
              : optionKey;
            const active = String(value) === optionKey;

            return (
              <Pressable // Added theme to optionButton and optionButtonText
                key={`${questionId}-${optionKey}-${index}`}
                onPress={() => setAnswer(questionId, optionKey)}
                style={[styles.optionButton, { backgroundColor: theme.card, borderColor: theme.border }, active && styles.optionButtonActive, active && { backgroundColor: theme.accentSoft, borderColor: theme.accent }]}
              >
                <View style={styles.optionRow}>
                  <View style={[styles.optionBadge, { borderColor: active ? theme.accent : '#f59e0b', backgroundColor: active ? theme.accent : '#111827' }]}>
                    <Text style={[styles.optionBadgeText, { color: active ? theme.background : '#ffffff' }]}>{displayKey}</Text>
                  </View>
                  <Text style={[styles.optionKeyLabel, { color: theme.muted }]}>{displayKey}.</Text>
                  <Text style={[styles.optionButtonText, { color: theme.text }, active && styles.optionButtonTextActive, active && { color: theme.accent }]} numberOfLines={2}>
                    {optionLabel}
                  </Text>
                </View>
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

function getChoiceLetter(index) {
  if (index >= 0 && index < 26) {
    return String.fromCharCode(65 + index);
  }

  return String(index + 1);
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
  choiceHint: {
    fontSize: 12,
    fontWeight: '700',
  },
  optionButton: {
    borderRadius: 12,
    paddingVertical: 10,
    paddingHorizontal: 12,
  },
  optionRow: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 10,
  },
  optionBadge: {
    minWidth: 32,
    height: 32,
    borderRadius: 999,
    borderWidth: 1,
    alignItems: 'center',
    justifyContent: 'center',
  },
  optionBadgeText: {
    fontWeight: '900',
    fontSize: 13,
    letterSpacing: 0.4,
  },
  optionKeyLabel: {
    color: '#f59e0b',
    fontSize: 13,
    fontWeight: '900',
    marginRight: -4,
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
