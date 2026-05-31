import { useEffect, useState } from 'react';
import {
  ActivityIndicator,
  Alert,
  Modal,
  Pressable,
  RefreshControl,
  ScrollView,
  StyleSheet,
  Text,
  TextInput,
  View,
} from 'react-native';

import {
  getStudentExam,
  getStudentQuiz,
  getStudentTasks,
  startStudentExam,
  startStudentQuiz,
  submitStudentExam,
  submitStudentQuiz,
} from '@/api/student';
import { darkTheme as colors } from '@/constants/colors';

export default function StudentTasksScreen({ token, setActiveTab: parentSetActiveTab, setSelectedItem, theme }) {
  const [loading, setLoading] = useState(true);
  const [refreshing, setRefreshing] = useState(false);
  const [error, setError] = useState('');
  const [activeTab, setActiveTabState] = useState('assignments');
  const [assessmentOpen, setAssessmentOpen] = useState(false);
  const [assessmentKind, setAssessmentKind] = useState('');
  const [selectedAssessment, setSelectedAssessment] = useState(null);
  const [assessmentDetail, setAssessmentDetail] = useState(null);
  const [assessmentMode, setAssessmentMode] = useState('detail');
  const [assessmentLoading, setAssessmentLoading] = useState(false);
  const [assessmentSubmitting, setAssessmentSubmitting] = useState(false);
  const [assessmentAnswers, setAssessmentAnswers] = useState({});
  const [assessmentAttempt, setAssessmentAttempt] = useState(null);
  const [data, setData] = useState({
    assignments: [],
    exams: [],
    quizzes: [],
    courses: [],
  });

  const taskCounts = {
    assignments: data.assignments?.length || 0,
    exams: data.exams?.length || 0,
    quizzes: data.quizzes?.length || 0,
  };

  const loadTasks = async () => {
    setError('');
    setLoading(true);

    try {
      const response = await getStudentTasks(token, { tab: activeTab });
      setData(response?.data || {
        assignments: [],
        exams: [],
        quizzes: [],
        courses: [],
      });
    } catch (err) {
      setError(err?.message || 'Unable to load tasks.');
    } finally {
      setLoading(false);
    }
  };

  const openAssignment = (assignment) => {
    const id = assignment?.assignment_id ?? assignment?.id;
    const normalized = { ...assignment, assignment_id: id };
    if (parentSetActiveTab && setSelectedItem) {
      setSelectedItem(normalized);
      parentSetActiveTab('assignmentSubmit');
    }
  };

  const resolveAssessmentId = (kind, item) => {
    if (kind === 'exam') return item?.exam_id ?? item?.id;
    if (kind === 'quiz') return item?.quiz_id ?? item?.id;
    return item?.id;
  };

  const openAssessment = async (kind, item) => {
    // Normalize IDs because backend responses can differ (id vs quiz_id/exam_id)
    const normalizedId = resolveAssessmentId(kind, item);

    if (!normalizedId) {
      return;
    }

    const normalizedItem =
      kind === 'exam'
        ? { ...item, id: normalizedId, exam_id: normalizedId }
        : { ...item, id: normalizedId, quiz_id: normalizedId };

    setAssessmentKind(kind);
    setSelectedAssessment(normalizedItem);
    setAssessmentOpen(true);
    setAssessmentLoading(true);
    setAssessmentMode('detail');
    setAssessmentDetail(null);
    setAssessmentAttempt(null);
    setAssessmentAnswers({});
    setError('');

    try {
      const response =
        kind === 'exam'
          ? await getStudentExam(token, normalizedId)
          : await getStudentQuiz(token, normalizedId);

      const detail = response?.data || null;
      setAssessmentDetail(detail);

      if (detail?.state === 'in_progress') {
        setAssessmentMode('taking');
        setAssessmentAttempt(detail?.selected_attempt?.attempt || null);
        const initialAnswers = {};
        (detail?.selected_attempt?.answers || []).forEach((answer) => {
          initialAnswers[String(answer.question_id)] = answer.answer ?? '';
        });
        setAssessmentAnswers(initialAnswers);
      } else if (detail?.selected_attempt) {
        setAssessmentMode('review');
      }

      // Navigate to appropriate screen after loading detail if navigation props exist
      if (parentSetActiveTab && setSelectedItem) {
        setSelectedItem(normalizedItem);
        parentSetActiveTab(kind);
      }
    } catch (err) {
      setError(err?.message || `Unable to load ${kind} details.`);
    } finally {
      setAssessmentLoading(false);
    }
  };

  const closeAssessment = () => {
    setAssessmentOpen(false);
    setAssessmentKind('');
    setSelectedAssessment(null);
    setAssessmentDetail(null);
    setAssessmentMode('detail');
    setAssessmentLoading(false);
    setAssessmentSubmitting(false);
    setAssessmentAnswers({});
    setAssessmentAttempt(null);
  };

  const startAssessment = async () => {
    if (!assessmentKind || !selectedAssessment) {
      return;
    }

    // Prefer normalized id keys
    const assessmentId = resolveAssessmentId(assessmentKind, selectedAssessment);

    if (!assessmentId) {
      return;
    }

    setAssessmentSubmitting(true);
    setError('');

    try {
      const response =
        assessmentKind === 'exam'
          ? await startStudentExam(token, assessmentId)
          : await startStudentQuiz(token, assessmentId);

      const payload = response?.data || {};

      if (response?.success === false || payload?.error) {
        throw new Error(response?.message || payload?.error || `The ${assessmentKind} is not open yet.`);
      }

      console.log(`[Assessment Start] Kind: ${assessmentKind}, Success`, payload);

      setAssessmentAttempt(payload?.attempt || null);
      setAssessmentAnswers({});
      setAssessmentMode('taking');
      setAssessmentDetail((current) => ({
        ...(current || {}),
        state: 'in_progress',
        questions: payload?.questions || current?.questions || [],
      }));
    } catch (err) {
      const errorMsg = err?.message || `Unable to start ${assessmentKind}.`;
      setError(errorMsg);
      Alert.alert('Access Denied', errorMsg);
      // Re-fetch detail to sync state if start failed
      if (assessmentId) {
        const kind = assessmentKind;
        setTimeout(() => openAssessment(kind, selectedAssessment), 500);
      }
    } finally {
      setAssessmentSubmitting(false);
    }
  };

  const submitAssessment = async () => {
    if (!assessmentKind || !selectedAssessment) {
      return;
    }

    const assessmentId = resolveAssessmentId(assessmentKind, selectedAssessment);

    if (!assessmentId) {
      return;
    }

    setAssessmentSubmitting(true);
    setError('');

    try {
      const payload = {
        answers: Object.keys(assessmentAnswers).map((id) => ({
          question_id: id,
          answer: assessmentAnswers[id],
        })),
      };
      const response =
        assessmentKind === 'exam'
          ? await submitStudentExam(token, assessmentId, payload)
          : await submitStudentQuiz(token, assessmentId, payload);

      const result = response?.data || null;
      setAssessmentMode('review');
      setAssessmentDetail((current) => ({
        ...(current || {}),
        state: 'submitted',
        selected_attempt: result?.attempt ?? result ?? current?.selected_attempt,
        result,
      }));

      await loadTasks();
    } catch (err) {
      setError(err?.message || `Unable to submit ${assessmentKind}.`);
    } finally {
      setAssessmentSubmitting(false);
    }
  };

  const toggleAnswer = (questionId, value) => {
    setAssessmentAnswers((current) => ({
      ...current,
      [String(questionId)]: value,
    }));
  };

  useEffect(() => {
    const timer = setTimeout(() => {
      loadTasks();
    }, 0);

    return () => clearTimeout(timer);
  }, [token, activeTab]);

  const handleRefresh = async () => {
    setRefreshing(true);
    try {
      const response = await getStudentTasks(token, { tab: activeTab });
      setData(response?.data || data);
    } catch (err) {
      setError(err?.message || 'Unable to refresh tasks.');
    } finally {
      setRefreshing(false);
    }
  };

  return (
    <>
      <ScrollView 
        style={[styles.screen, { backgroundColor: theme.background }]} 
        contentContainerStyle={styles.container}
        refreshControl={
          <RefreshControl refreshing={refreshing} onRefresh={handleRefresh} tintColor={theme.accent} />
        }
      >
      <View style={styles.headerSection}>
        <Text style={[styles.title, { color: theme.text }]}>TASKS</Text>
        <Text style={[styles.subtitle, { color: theme.muted }]}>
          Your tasks from assignments, exams, and quizzes.
        </Text>


        <View style={styles.tabRow}>
          {['assignments', 'exams', 'quizzes'].map((tab) => {
            const active = activeTab === tab;
            const tabMeta = getTaskTabMeta(tab, theme);

            return (
              <Pressable
                key={tab}
                onPress={() => setActiveTabState(tab)}
                style={({ pressed }) => [
                  styles.taskSegmentTab,
                  active && styles.taskSegmentTabActive,
                  pressed && styles.taskSegmentTabPressed,
                  { borderColor: active ? tabMeta.activeBorderColor : theme.border, backgroundColor: theme.card },
                ]}
              >
                <View style={styles.taskSegmentTop}>
                  <View
                    style={[
                      styles.taskSegmentIcon,
                      { backgroundColor: tabMeta.iconBg, borderColor: tabMeta.iconBorderColor },
                      active && { borderColor: tabMeta.activeBorderColor },
                    ]}
                  >
                    <Text style={styles.taskSegmentIconText}>{tabMeta.iconText}</Text>
                  </View>
                  <Text
                    style={[
                      styles.taskSegmentLabel,
                      active && styles.taskSegmentLabelActive,
                      { color: tabMeta.activeTextColor },
                    ]}
                  >
                    {tabMeta.label}
                  </Text>
                </View>

                <View
                  style={[
                    styles.taskSegmentUnderline,
                    active && { backgroundColor: tabMeta.activeUnderlineColor },
                  ]}
                />
              </Pressable>
            );
          })}
        </View>

      </View>

      {loading ? (
        <View style={[styles.loadingCard, { backgroundColor: theme.card, borderColor: theme.border }]}>
          <ActivityIndicator color={theme.accent} size="large" />
          <Text style={[styles.loadingText, { color: theme.muted }]}>Loading tasks...</Text>
        </View>
      ) : (
        <>
          {!!error && <Text style={[styles.error, { color: theme.danger }]}>{error}</Text>}

          <TaskSection
            title=""
            items={data.assignments}
            activeTab={activeTab}
            kind="assignment"
            onSelectItem={(item) => openAssignment(item)}
            theme={theme}
          />
          <TaskSection
            title=""
            items={data.exams}
            activeTab={activeTab}
            kind="exam"
            onSelectItem={(item) => openAssessment('exam', item)}
            theme={theme}
          />
          <TaskSection
            title=""
            items={data.quizzes}
            activeTab={activeTab}
            kind="quiz"
            onSelectItem={(item) => openAssessment('quiz', item)}
            theme={theme}
          />

        </>
      )}
      </ScrollView>

      <AssessmentModal
        open={assessmentOpen}
        kind={assessmentKind}
        theme={theme}
        loading={assessmentLoading}
        submitting={assessmentSubmitting}
        detail={assessmentDetail}
        mode={assessmentMode}
        attempt={assessmentAttempt}
        selectedAssessment={selectedAssessment}
        answers={assessmentAnswers}
        onClose={closeAssessment}
        onStart={startAssessment}
        onSubmit={submitAssessment}
        onChangeAnswer={toggleAnswer}
      />
    </>
  );
}

function TaskSection({ title, items, activeTab, kind, onSelectItem, theme }) {
  const tabKey = kind === 'quiz' ? 'quizzes' : `${kind}s`;
  const visible = activeTab === tabKey;
  const pluralLabel = kind === 'quiz' ? 'quizzes' : `${kind}s`;

  // Layout fix: if not visible, render nothing (removes "Switch to ..." text)
  if (!visible) return null;

  return (
    <View style={styles.section}>
      {!!title ? <Text style={styles.sectionTitle}>{title}</Text> : null}
      {items?.length ? (
        items.map((item, index) => (
          <Pressable
            key={getTaskItemKey(item, kind, index)}
            onPress={() => onSelectItem?.(item)}
          style={({ pressed }) => [styles.itemCard, { backgroundColor: theme.card, borderColor: theme.border }, pressed && styles.itemCardPressed]}
          >
            <View style={styles.itemHeader}>
            <Text style={[styles.itemTitle, { color: theme.text }]}>{item.title}</Text>
              <View
                style={[
                  styles.statusPill,
                  { backgroundColor: item.is_overdue ? (theme.danger + '22') : (theme.accentSoft) },
                ]}
              >
                <Text
                  style={[
                    styles.statusPillText,
                    { color: item.is_overdue ? theme.danger : theme.accent },
                  ]}
                >
                  {item.is_overdue ? 'Overdue' : item.status || 'Pending'}
                </Text>
              </View>
            </View>
          <Text style={[styles.itemMeta, { color: theme.muted }]}>{item.course_title || item.course_name}</Text>
          {item.questions_count != null ? (
            <Text style={[styles.itemMeta, { color: theme.muted }]}>Questions: {item.questions_count}</Text>
            ) : null}
          <Text style={[styles.itemMeta, { color: theme.muted }]}>
              {item.is_overdue ? 'Overdue' : 'Due'}:{' '}
              {formatReadableDate(item.due_date || item.exam_date || item.start_date)}
            </Text>
          </Pressable>
        ))
      ) : (
      <Text style={[styles.emptyText, { color: theme.muted }]}>No {pluralLabel} found.</Text>
      )}
    </View>
  );
}

function getTaskItemKey(item, kind, index) {
  const candidates = [
    item?.assignment_id,
    item?.exam_id,
    item?.quiz_id,
    item?.id,
    item?.task_id,
    item?.assessment_id,
    item?.uuid,
  ];

  for (const candidate of candidates) {
    if (candidate !== null && candidate !== undefined && candidate !== '') {
      return `${kind}-${index}-${String(candidate)}`;
    }
  }

  const title = String(item?.title || item?.name || item?.course_title || item?.course_name || 'item')
    .trim()
    .toLowerCase()
    .replace(/[^a-z0-9]+/g, '-')
    .replace(/^-+|-+$/g, '');

  return `${kind}-${title || 'item'}-${index}`;
}

function getTaskTabMeta(tabKey, theme) {
  // Distinct look per tab while staying on the same dark theme.
  switch (tabKey) {
    case 'assignments':
      return {
        label: 'Assignments',
        iconText: 'A',
        iconBg: theme.accentSoft,
        iconBorderColor: theme.accent,
        activeBorderColor: theme.accent,
        activeTextColor: theme.accent,
        activeUnderlineColor: theme.accent,
      };
    case 'exams':
      return {
        label: 'Exams',
        iconText: 'E',
        iconBg: theme.danger + '22',
        iconBorderColor: theme.danger,
        activeBorderColor: theme.danger,
        activeTextColor: theme.danger,
        activeUnderlineColor: theme.danger,
      };
    case 'quizzes':
      return {
        label: 'Quizzes',
        iconText: 'Q',
        iconBg: '#f59e0b22',
        iconBorderColor: '#f59e0b',
        activeBorderColor: '#f59e0b',
        activeTextColor: '#fbbf24',
        activeUnderlineColor: '#f59e0b',
      };
    default:
      return {
        label: tabKey,
        iconText: '?',
        iconBg: theme.accentSoft,
        iconBorderColor: theme.border,
        activeBorderColor: theme.accent,
        activeTextColor: theme.accent,
        activeUnderlineColor: theme.accent,
      };
  }
}

function SummaryChip({ label, value, theme }) {
  return (
    <View style={[styles.summaryChip, { backgroundColor: theme.background, borderColor: theme.border }]}>
      <Text style={[styles.summaryLabel, { color: theme.muted }]}>{label}</Text>
      <Text style={[styles.summaryValue, { color: theme.text }]}>{value}</Text>
    </View>
  );
}

function AssessmentModal({
  open,
  kind,
  loading,
  submitting,
  detail,
  mode,
  attempt,
  selectedAssessment,
  answers,
  onClose,
  onStart,
  onSubmit,
  onChangeAnswer,
  theme,
}) {
  const item = detail?.[kind] || selectedAssessment || {};
  const questions = detail?.questions || [];
  const attempts = detail?.attempts || [];
  const selectedAttempt = detail?.selected_attempt || null;
  const title = item?.title || selectedAssessment?.title || `${kind || 'Assessment'}`;
  const courseName = item?.course?.title || item?.course?.course_number || item?.course_name || '';
  const rawState = detail?.state || '';
  const state = rawState ? String(rawState).trim().toLowerCase() : 'available';
  const questionsCount = item?.questions_count ?? questions.length;
  const canStart = state === 'available' || state === 'in_progress' || questionsCount > 0;

  return (
    <Modal visible={open} transparent animationType="fade" onRequestClose={onClose}>
      <View style={styles.modalBackdrop}>
        <Pressable style={StyleSheet.absoluteFill} onPress={onClose} />
        <View style={[styles.assessmentModalCard, { backgroundColor: theme.card, borderColor: theme.border }]}>
          <View style={styles.assessmentModalHeader}>
            <View style={styles.assessmentHeaderText}>
              <Text style={[styles.modalTitle, { color: theme.text }]}>{title}</Text>
              <Text style={[styles.modalSub, { color: theme.muted }]}>
                {courseName || 'Course'} {'\u2022'} {state.replace(/_/g, ' ')}
              </Text>
            </View>
            <Pressable onPress={onClose} style={[styles.modalCloseIcon, { backgroundColor: theme.accentSoft }]}>
              <Text style={[styles.modalCloseIconText, { color: theme.accent }]}>×</Text>
            </Pressable>
          </View>

          {loading ? (
            <View style={styles.loadingCard}>
              <ActivityIndicator color={theme.accent} />
              <Text style={styles.loadingText}>Loading {kind}...</Text>
            </View>
          ) : (
            <>
              <Text style={[styles.itemMeta, { color: theme.text }]}>
                {item?.description || 'No description provided.'}
              </Text>
              <View style={styles.summaryGrid}>
                <SummaryChip theme={theme} label="Attempts" value={attempts.length} />
                <SummaryChip theme={theme} label="Questions" value={item?.questions_count ?? questions.length} />
                <SummaryChip theme={theme} label="Max Score" value={item?.max_score ?? 0} />
                <SummaryChip theme={theme} label="State" value={state.replace(/_/g, ' ')} />
                <SummaryChip theme={theme} label="Due" value={formatReadableDate(item?.due_date || item?.exam_date || item?.start_date)} />
              </View>

              {mode === 'detail' && (
                <View style={styles.modalActionsRow}>
                  <Pressable onPress={onClose} style={styles.secondaryButton}>
                    <Text style={styles.secondaryButtonText}>Close</Text>
                  </Pressable>
                  {canStart ? (
                    <Pressable onPress={onStart} style={styles.submitButton} disabled={submitting}>
                      <Text style={styles.submitButtonText}>{submitting ? 'Starting...' : 'Start now'}</Text>
                    </Pressable>
                  ) : null}
                </View>
              )}

              {mode === 'taking' && (
                <>
                  <Text style={[styles.sectionTitle, { color: theme.text }]}>Questions</Text>
                  <ScrollView style={styles.questionScroll} contentContainerStyle={styles.questionStack}>
                    {questions.length ? (
                      questions.map((question, index) => (
                        <View key={question.id} style={[styles.questionCard, { backgroundColor: theme.input, borderColor: theme.border }]}>
                          <Text style={[styles.questionNumber, { color: theme.accent }]}>Question {index + 1}</Text>
                          <Text style={[styles.questionText, { color: theme.text }]}>{question.question_text}</Text>
                          <Text style={[styles.questionMeta, { color: theme.muted }]}>{question.question_type || 'question'}</Text>
                          {renderQuestionAnswerInput(question, answers, onChangeAnswer, theme)}
                        </View>
                      ))
                    ) : (
                      <Text style={styles.emptyText}>No questions available.</Text>
                    )}
                  </ScrollView>

                  <View style={styles.modalActionsRow}>
                    <Pressable onPress={onClose} style={styles.secondaryButton}>
                      <Text style={styles.secondaryButtonText}>Close</Text>
                    </Pressable>
                    <Pressable onPress={onSubmit} style={styles.submitButton} disabled={submitting}>
                      <Text style={styles.submitButtonText}>{submitting ? 'Submitting...' : `Submit ${kind}`}</Text>
                    </Pressable>
                  </View>
                </>
              )}

              {mode === 'review' && (
                <>
                  <Text style={[styles.sectionTitle, { color: theme.text }]}>Review</Text>
                  {selectedAttempt ? (
                    <View style={[styles.reviewCard, { backgroundColor: theme.input, borderColor: theme.border }]}>
                      <Text style={[styles.reviewMeta, { color: theme.muted }]}>Attempt #{selectedAttempt.attempt_number || 1}</Text>
                      <Text style={[styles.reviewMeta, { color: theme.muted }]}>
                        Score: {selectedAttempt.score ?? selectedAttempt.total_score ?? 'N/A'}
                      </Text>
                      <Text style={[styles.reviewMeta, { color: theme.muted }]}>
                        Submitted: {selectedAttempt.submitted_at || 'N/A'}
                      </Text>
                    </View>
                  ) : null}

                  <ScrollView style={styles.questionScroll} contentContainerStyle={styles.questionStack}>
                    {selectedAttempt?.answers?.length ? (
                      selectedAttempt.answers.map((answer, index) => (
                        <View key={answer.id || `${answer.question_id}-${index}`} style={[styles.questionCard, { backgroundColor: theme.input, borderColor: theme.border }]}>
                          <Text style={[styles.questionNumber, { color: theme.accent }]}>Answer {index + 1}</Text>
                          <Text style={[styles.questionText, { color: theme.text }]}>{answer.question?.question_text || 'Question'}</Text>
                          <Text style={[styles.questionMeta, { color: theme.muted }]}>Your answer: {String(answer.answer ?? 'No answer')}</Text>
                          {'score' in answer ? (
                            <Text style={[styles.questionMeta, { color: theme.muted }]}>Score: {answer.score ?? 0}</Text>
                          ) : null}
                          {'points_earned' in answer ? (
                            <Text style={[styles.questionMeta, { color: theme.muted }]}>Points earned: {answer.points_earned ?? 0}</Text>
                          ) : null}
                        </View>
                      ))
                    ) : (
                      <Text style={styles.emptyText}>No review data yet.</Text>
                    )}
                  </ScrollView>

                  <View style={styles.modalActionsRow}>
                    <Pressable onPress={onClose} style={styles.submitButton}>
                      <Text style={styles.submitButtonText}>Close review</Text>
                    </Pressable>
                  </View>
                </>
              )}
            </>
          )}
        </View>
      </View>
    </Modal>
  );
}

function renderQuestionAnswerInput(question, answers, onChangeAnswer, theme) {
  const questionId = question?.id;
  const value = answers[String(questionId)] ?? '';
  
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
          options.map((option, index) => {
            const optionValue = typeof option === 'object' ? option.value ?? option.label ?? option.text ?? String(index) : String(option);
            const optionLabel = typeof option === 'object' ? option.label ?? option.text ?? option.value ?? String(index + 1) : String(option);
            const active = value === optionValue;

            return (
              <Pressable // Added theme to optionButton and optionButtonText
                key={`${questionId}-${optionValue}-${index}`}
                onPress={() => onChangeAnswer(questionId, optionValue)}
                style={[styles.optionButton, { backgroundColor: theme.card, borderColor: theme.border }, active && styles.optionButtonActive, active && { backgroundColor: theme.accentSoft, borderColor: theme.accent }]}
              >
                <Text style={[styles.optionButtonText, { color: theme.text }, active && styles.optionButtonTextActive, active && { color: theme.accent }]}>{optionLabel}</Text>
              </Pressable>
            );
          })
        ) : (
          <Text style={styles.emptyText}>No answer options available.</Text>
        )}
      </View>
    );
  }

  return (
    <TextInput
      value={String(value)}
      onChangeText={(text) => onChangeAnswer(questionId, text)}
      placeholder="Type your answer..." // Added theme to input
      placeholderTextColor={theme.muted}
      style={[styles.input, { backgroundColor: theme.input, color: theme.text, borderColor: theme.border }]}
      multiline={question?.question_type === 'long_answer'}
    />
  );
}

function formatReadableDate(value) {
  if (!value) {
    return 'No date';
  }

  const date = new Date(value);
  if (Number.isNaN(date.getTime())) {
    return String(value);
  }

  return date.toLocaleDateString();
}

const styles = StyleSheet.create({
  screen: {
    flex: 1,
  },
  container: {
    padding: 16,
    paddingBottom: 24,
    gap: 16,
  },
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
  subtitle: {
    fontSize: 14,
    lineHeight: 20,
  },
  countRow: {
    flexDirection: 'row',
    gap: 8,
    marginTop: 2,
  },
  countPill: {
    flex: 1,
    borderRadius: 16,
    borderWidth: 1,
    paddingVertical: 10,
    paddingHorizontal: 12,
    gap: 2,
  },
  countValue: {
    fontSize: 18,
    fontWeight: '900',
  },
  countLabel: {
    fontSize: 11,
    fontWeight: '700',
    textTransform: 'uppercase',
    letterSpacing: 0.8,
  },
  tabRow: {
    flexDirection: 'column',
    gap: 10,
    marginTop: 8,
  },

  // Updated: more distinctive Assignments/Exams/Quizzes segment buttons
  taskSegmentTab: {
    width: '100%',
    paddingVertical: 10,
    paddingHorizontal: 12,
    borderRadius: 16,
    borderWidth: 1,
    alignItems: 'stretch',
    gap: 10,
  },
  taskSegmentTabActive: {
    // Remove "carded" / shadowed horizontal-card feel; keep it flat but accent-colored.
    borderWidth: 1,
    shadowColor: 'transparent',
    shadowOpacity: 0,
    shadowRadius: 0,
    shadowOffset: { width: 0, height: 0 },
    elevation: 0,
  },
  taskSegmentTabPressed: {
    opacity: 0.85,
  },
  taskSegmentTop: {
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'flex-start',
    gap: 8,
  },
  taskSegmentIcon: {
    width: 30,
    height: 30,
    borderRadius: 15,
    borderWidth: 1,
    alignItems: 'center',
    justifyContent: 'center',
  },
  taskSegmentIconText: {
    color: '#fff',
    fontWeight: '900',
    letterSpacing: -0.2,
  },
  taskSegmentLabel: {
    flex: 1,
    fontWeight: '900',
    textTransform: 'uppercase',
    letterSpacing: 0.6,
    fontSize: 12,
  },
  taskSegmentLabelActive: {
  },
  taskSegmentUnderline: {
    height: 2.5,
    borderRadius: 999,
  },
  secondaryButton: {
    paddingVertical: 12,
    paddingHorizontal: 16,
    borderRadius: 14,
    alignSelf: 'flex-start',
    borderWidth: 1,
  },
  secondaryButtonText: {
    fontWeight: '800',
  },
  loadingCard: {
    borderRadius: 20,
    padding: 20,
    alignItems: 'center',
    gap: 10,
    borderWidth: 1,
  },
  loadingText: {
  },
  error: {
    color: '#dc2626',
    fontWeight: '700',
  },
  section: {
    gap: 10,
  },
  sectionTitle: {
    fontSize: 18,
    fontWeight: '800',
  },
  itemCard: {
    borderRadius: 16,
    paddingVertical: 10,
    paddingHorizontal: 12,
    borderWidth: 1,
    gap: 6,
  },
  itemCardPressed: {
    opacity: 0.75,
  },
  itemHeader: {
    flexDirection: 'row',
    alignItems: 'flex-start',
    justifyContent: 'space-between',
    gap: 10,
  },
  itemTitle: {
    fontSize: 16,
    fontWeight: '900',
    flex: 1,
  },
  itemMeta: {
    fontSize: 13,
  },
  emptyText: {
    fontStyle: 'italic',
  },
  detailCard: {
    borderRadius: 20,
    padding: 16,
    borderWidth: 1,
    gap: 10,
    shadowColor: '#000',
    shadowOpacity: 0.22,
    shadowRadius: 18,
    shadowOffset: { width: 0, height: 8 },
    elevation: 8,
  },
  submissionTabs: {
    flexDirection: 'row',
    gap: 8,
  },
  submissionTab: {
    flex: 1,
    paddingVertical: 10,
    borderRadius: 12,
    alignItems: 'center',
    borderWidth: 1,
  },
  submissionTabActive: {
  },
  submissionTabText: {
    fontWeight: '800',
    textTransform: 'capitalize',
  },
  submissionTabTextActive: {
  },
  input: {
    borderRadius: 14,
    borderWidth: 1,
    paddingHorizontal: 14,
    paddingVertical: 12,
  },
  textArea: {
    minHeight: 120,
    textAlignVertical: 'top',
    borderRadius: 14,
    borderWidth: 1,
    paddingHorizontal: 14,
    paddingVertical: 12,
  },
  submitButton: {
    paddingVertical: 13,
    borderRadius: 14,
    alignItems: 'center',
    shadowColor: colors.accent,
    shadowOpacity: 0.28,
    shadowRadius: 10,
    shadowOffset: { width: 0, height: 4 },
    elevation: 4,
  },
  submitButtonText: {
    color: '#fff',
    fontWeight: '800',
  },
  fieldLabel: {
    fontSize: 13,
    fontWeight: '700',
    textTransform: 'uppercase',
    letterSpacing: 0.6,
    marginTop: 4,
  },
  filePickerCard: {
    borderRadius: 14,
    borderWidth: 1,
    padding: 14,
    gap: 10,
  },
  statusPill: {
    alignSelf: 'flex-end',
    paddingHorizontal: 14,
    paddingVertical: 7,
    borderRadius: 999,
    borderWidth: 0,
  },
  statusPillNeutral: {
  },
  statusPillDanger: {
  },
  statusPillText: {
    fontSize: 12,
    fontWeight: '900',
    textTransform: 'capitalize',
  },
  statusPillTextNeutral: {
  },
  statusPillTextDanger: {
  },
  modalBackdrop: {
    flex: 1,
    backgroundColor: 'rgba(0,0,0,0.72)',
    justifyContent: 'center',
    alignItems: 'center',
    paddingHorizontal: 16,
    paddingVertical: 24,
  },
  modalTitle: {
    fontSize: 17,
    fontWeight: '800',
    lineHeight: 22,
  },
  modalSub: {
    fontSize: 13,
    lineHeight: 18,
  },
  summaryGrid: {
    flexDirection: 'row',
    flexWrap: 'wrap',
    gap: 8,
  },
  summaryChip: {
    borderRadius: 12,
    borderWidth: 1,
    paddingVertical: 8,
    paddingHorizontal: 12,
    minWidth: 80,
    gap: 2,
  },
  summaryLabel: {
    fontSize: 11,
    fontWeight: '700',
    textTransform: 'uppercase',
    letterSpacing: 0.5,
  },
  summaryValue: {
    fontSize: 14,
    fontWeight: '800',
  },
  assessmentModalCard: {
    borderRadius: 22,
    padding: 16,
    borderWidth: 1,
    gap: 12,
    width: '92%',
    maxWidth: 480,
    maxHeight: '90%',
  },
  assessmentModalHeader: {
    flexDirection: 'row',
    alignItems: 'flex-start',
    justifyContent: 'space-between',
    gap: 12,
  },
  assessmentHeaderText: {
    flex: 1,
    gap: 2,
  },
  modalCloseIcon: {
    width: 34,
    height: 34,
    borderRadius: 999,
    alignItems: 'center',
    justifyContent: 'center',
  },
  modalCloseIconText: {
    fontSize: 20,
    fontWeight: '900',
    marginTop: -2,
  },
  modalActionsRow: {
    flexDirection: 'row',
    gap: 10,
    alignItems: 'center',
    justifyContent: 'space-between',
  },
  questionScroll: {
    maxHeight: 320,
  },
  questionStack: {
    gap: 10,
  },
  questionCard: {
    borderRadius: 16,
    borderWidth: 1,
    padding: 14,
    gap: 8,
  },
  questionNumber: {
    fontSize: 12,
    fontWeight: '900',
    textTransform: 'uppercase',
    letterSpacing: 0.6,
  },
  questionText: {
    fontSize: 15,
    lineHeight: 21,
    fontWeight: '700',
  },
  questionMeta: {
    fontSize: 12,
  },
  optionStack: {
    gap: 8,
  },
  optionButton: {
    borderRadius: 12,
    paddingHorizontal: 12,
    paddingVertical: 10,
  },
  optionButtonActive: {
  },
  optionButtonText: {
    fontWeight: '700',
  },
  optionButtonTextActive: {
  },
  reviewCard: {
    borderRadius: 16,
    borderWidth: 1,
    padding: 14,
    gap: 4,
  },
  reviewMeta: {
    fontSize: 12,
  },
  });

function TaskCountPill({ label, value }) {
  return (
    <View style={styles.countPill}>
      <Text style={styles.countValue}>{value}</Text>
      <Text style={styles.countLabel}>{label}</Text>
    </View>
  );
}
