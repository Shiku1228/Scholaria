import { getStudentAssignment, getStudentAssignmentReview, submitStudentAssignment } from '@/api/student';
import { createUploadFormData, pickDocumentAsset } from '@/utils/uploads';
import { useEffect, useState } from 'react';
import {
  ActivityIndicator,
  Alert,
  Pressable,
  ScrollView,
  StyleSheet,
  Text,
  TextInput,
  View,
} from 'react-native';

export default function AssignmentSubmitScreen({ token, item: assignment, setActiveTab, theme, refreshTasks }) {
  const assignmentId = assignment?.assignment_id ?? assignment?.id;

  const [detailLoading, setDetailLoading] = useState(false);
  const [assignmentDetail, setAssignmentDetail] = useState(null);
  const [submitting, setSubmitting] = useState(false);
  const [error, setError] = useState('');
  const [submitResult, setSubmitResult] = useState(null);

  // Question-based answers
  const [mcAnswers, setMcAnswers] = useState({});    // { [questionId]: choiceId }
  const [essayAnswers, setEssayAnswers] = useState({}); // { [questionId]: text }

  // Legacy submission
  const [submissionType, setSubmissionType] = useState('text');
  const [textContent, setTextContent] = useState('');
  const [linkContent, setLinkContent] = useState('');
  const [attachedFile, setAttachedFile] = useState(null);

  // Post-submission review (is_correct revealed)
  const [reviewLoading, setReviewLoading] = useState(false);
  const [reviewData, setReviewData] = useState(null);

  const onBack = () => setActiveTab?.('tasks');

  // Derived values
  const det = assignmentDetail?.assignment;
  const questions = assignmentDetail?.questions ?? [];
  const existingSubmission = assignmentDetail?.submission;
  const submission = existingSubmission ?? submitResult?.submission;
  const isSubmitted = !!submission;

  const hasQuestions = det?.has_questions === true && questions.length > 0;
  const format = det?.assignment_format;
  const uiMode = hasQuestions
    ? (format === 'multiple_choice' ? 'mc' : 'essay')
    : 'legacy';

  const allMcAnswered = questions.length > 0 && questions.every(q => mcAnswers[q.id] != null);
  const allEssayAnswered = questions.length > 0 && questions.every(q => essayAnswers[q.id]?.trim());
  const canSubmit = uiMode === 'mc' ? allMcAnswered : uiMode === 'essay' ? allEssayAnswered : true;

  // ─── Load detail ─────────────────────────────────────────────────────────────

  const loadDetail = async () => {
    if (!assignmentId) return;
    setDetailLoading(true);
    setError('');
    try {
      const response = await getStudentAssignment(token, assignmentId);
      const data = response?.data ?? null;
      setAssignmentDetail(data);
      const sub = data?.submission;
      if (sub) {
        setSubmissionType(sub.submission_type || 'text');
        setTextContent(sub.content || '');
        setLinkContent(sub.content || '');
      }
    } catch (err) {
      setError(err?.message || 'Unable to load assignment details.');
    } finally {
      setDetailLoading(false);
    }
  };

  const loadReview = async () => {
    if (!assignmentId) return;
    setReviewLoading(true);
    try {
      const response = await getStudentAssignmentReview(token, assignmentId);
      setReviewData(response?.data ?? null);
    } catch {
      // review is optional; silently skip
    } finally {
      setReviewLoading(false);
    }
  };

  useEffect(() => {
    loadDetail();
  }, [assignmentId]);

  // Load review when detail loads and there is already a submission
  useEffect(() => {
    if (existingSubmission && (uiMode === 'mc' || uiMode === 'essay')) {
      loadReview();
    }
  }, [existingSubmission?.id, uiMode]);

  // ─── Submit questions (MC or Essay) ──────────────────────────────────────────

  const handleSubmitQuestions = () => {
    setError('');
    const unansweredCount = uiMode === 'mc'
      ? questions.filter(q => mcAnswers[q.id] == null).length
      : questions.filter(q => !essayAnswers[q.id]?.trim()).length;

    if (unansweredCount > 0) {
      setError(`Please answer all questions (${unansweredCount} remaining).`);
      return;
    }

    doSubmitQuestions();
  };

  const doSubmitQuestions = async () => {
    setSubmitting(true);
    setError('');
    try {
      const answers = {};
      questions.forEach(q => {
        answers[String(q.id)] = uiMode === 'mc' ? mcAnswers[q.id] : essayAnswers[q.id];
      });
      const response = await submitStudentAssignment(token, assignmentId, { answers });
      setSubmitResult(response?.data ?? { submission: { status: 'submitted' } });
      if (uiMode === 'mc') await loadReview();
      if (refreshTasks) refreshTasks();
    } catch (err) {
      setError(err?.message || 'Unable to submit. Please try again.');
    } finally {
      setSubmitting(false);
    }
  };

  // ─── Submit legacy (text / link / file) ──────────────────────────────────────

  const handleSubmitLegacy = async () => {
    if (!assignmentId) return;
    if (submissionType === 'file' && !attachedFile?.uri) {
      Alert.alert('Attach a file', 'Please choose a file before submitting.');
      return;
    }
    setSubmitting(true);
    setError('');
    try {
      if (submissionType === 'file') {
        const formData = createUploadFormData({ submission_type: 'file' }, 'file', attachedFile);
        await submitStudentAssignment(token, assignmentId, formData);
      } else {
        await submitStudentAssignment(token, assignmentId, {
          submission_type: submissionType,
          text_content: submissionType === 'text' ? textContent : undefined,
          link_content: submissionType === 'link' ? linkContent : undefined,
        });
      }
      if (refreshTasks) await refreshTasks();
      onBack();
    } catch (err) {
      setError(err?.message || 'Unable to submit assignment.');
    } finally {
      setSubmitting(false);
    }
  };

  const pickAssignmentFile = async () => {
    try {
      const asset = await pickDocumentAsset();
      if (asset) {
        setAttachedFile(asset);
        setSubmissionType('file');
      }
    } catch (err) {
      setError(err?.message || 'Unable to choose a file.');
    }
  };

  // ─── Submission status ────────────────────────────────────────────────────────

  const submissionStatus =
    submitResult?.submission?.status ??
    submission?.status ??
    assignment?.status ??
    (isSubmitted ? 'submitted' : 'not_submitted');

  // ─── Render ───────────────────────────────────────────────────────────────────

  return (
    <ScrollView
      style={[styles.screen, { backgroundColor: theme.background }]}
      contentContainerStyle={styles.container}
      keyboardShouldPersistTaps="handled"
    >
      {/* Header */}
      <View style={styles.headerSection}>
        <Pressable onPress={onBack} style={styles.backButton}>
          <Text style={[styles.backButtonText, { color: theme.accent }]}>← Back</Text>
        </Pressable>
        <Text style={[styles.title, { color: theme.text }]}>
          {det?.title || assignment?.title || 'Assignment'}
        </Text>
        {error ? <Text style={styles.error}>{error}</Text> : null}
      </View>

      {detailLoading ? (
        <View style={styles.centered}>
          <ActivityIndicator size="large" color={theme.accent} />
          <Text style={[styles.mutedText, { color: theme.muted }]}>Loading assignment…</Text>
        </View>
      ) : (
        <>
          {/* Info card */}
          <View style={[styles.card, { backgroundColor: theme.card, borderColor: theme.border }]}>
            {(assignmentDetail?.course?.title || assignment?.course_title) ? (
              <InfoRow
                label="Course"
                value={assignmentDetail?.course?.title || assignment?.course_title || assignment?.course_name}
                theme={theme}
              />
            ) : null}
            <InfoRow label="Due" value={formatDate(det?.due_date || assignment?.due_date)} theme={theme} />
            {det?.max_score != null && (
              <InfoRow label="Max Score" value={`${det.max_score} pts`} theme={theme} />
            )}
            <View style={styles.infoRow}>
              <Text style={[styles.infoLabel, { color: theme.muted }]}>Status</Text>
              <StatusBadge status={submissionStatus} />
            </View>
            {submission?.score != null && (
              <View style={styles.infoRow}>
                <Text style={[styles.infoLabel, { color: theme.muted }]}>Score</Text>
                <Text style={[styles.scoreValue, { color: theme.accent }]}>
                  {submission.score}
                  {det?.max_score != null ? `/${det.max_score}` : ''}
                </Text>
              </View>
            )}
          </View>

          {/* Instructions */}
          {(det?.instructions || det?.description) ? (
            <View style={[styles.card, { backgroundColor: theme.card, borderColor: theme.border }]}>
              <Text style={[styles.sectionLabel, { color: theme.muted }]}>Instructions</Text>
              <Text style={[styles.bodyText, { color: theme.text }]}>
                {det.instructions || det.description}
              </Text>
            </View>
          ) : null}

          {/* ── MC / Essay question-based UI ── */}
          {(uiMode === 'mc' || uiMode === 'essay') && (
            <>
              {/* Post-submit success banners */}
              {submitResult && uiMode === 'mc' && (
                <View style={styles.successCard}>
                  <Text style={styles.successTitle}>Submitted!</Text>
                  {submitResult.submission?.score != null && (
                    <Text style={styles.successScore}>
                      Score: {submitResult.submission.score}
                      {det?.max_score != null ? `/${det.max_score}` : ''}
                    </Text>
                  )}
                  <Text style={styles.successSub}>
                    {submitResult.message || 'Your score has been calculated automatically.'}
                  </Text>
                </View>
              )}

              {submitResult && uiMode === 'essay' && (
                <View style={styles.pendingCard}>
                  <Text style={styles.pendingTitle}>Submitted!</Text>
                  <Text style={styles.pendingSub}>
                    {submitResult.message || 'Your teacher will review and grade your answers.'}
                  </Text>
                </View>
              )}

              {reviewLoading && (
                <ActivityIndicator style={styles.reviewLoader} size="small" color={theme.accent} />
              )}

              {/* Question cards */}
              {questions.map((q, idx) =>
                uiMode === 'mc' ? (
                  <MCQuestion
                    key={q.id}
                    question={q}
                    idx={idx}
                    selectedChoiceId={mcAnswers[q.id] ?? null}
                    onSelect={choiceId => setMcAnswers(prev => ({ ...prev, [q.id]: choiceId }))}
                    disabled={isSubmitted}
                    reviewAnswer={reviewData?.answers?.find(a => a.question_id === q.id) ?? null}
                    theme={theme}
                  />
                ) : (
                  <EssayQuestion
                    key={q.id}
                    question={q}
                    idx={idx}
                    value={
                      isSubmitted
                        ? (reviewData?.answers?.find(a => a.question_id === q.id)?.essay_answer
                            ?? essayAnswers[q.id]
                            ?? '')
                        : (essayAnswers[q.id] ?? '')
                    }
                    onChange={text => setEssayAnswers(prev => ({ ...prev, [q.id]: text }))}
                    disabled={isSubmitted}
                    theme={theme}
                  />
                )
              )}

              {!isSubmitted && (
                <Pressable
                  onPress={handleSubmitQuestions}
                  disabled={submitting || !canSubmit}
                  style={[
                    styles.submitButton,
                    { backgroundColor: theme.accent },
                    (submitting || !canSubmit) && styles.submitButtonDisabled,
                  ]}
                >
                  <Text style={[styles.submitButtonText, { color: theme.background }]}>
                    {submitting
                      ? 'Submitting…'
                      : `Submit Assignment (${
                          uiMode === 'mc'
                            ? `${Object.keys(mcAnswers).length}/${questions.length}`
                            : `${questions.filter(q => essayAnswers[q.id]?.trim()).length}/${questions.length}`
                        } answered)`}
                  </Text>
                </Pressable>
              )}
            </>
          )}

          {/* ── Legacy UI ── */}
          {uiMode === 'legacy' && (
            <View style={[styles.card, { backgroundColor: theme.card, borderColor: theme.border }]}>
              <Text style={[styles.sectionLabel, { color: theme.text }]}>
                {isSubmitted ? 'Your Submission' : 'Submit Assignment'}
              </Text>

              <View style={styles.tabRow}>
                {['text', 'link', 'file'].map(type => {
                  const active = submissionType === type;
                  return (
                    <Pressable
                      key={type}
                      onPress={() => !isSubmitted && setSubmissionType(type)}
                      style={[
                        styles.tab,
                        { borderColor: theme.border },
                        active && { backgroundColor: theme.accentSoft, borderColor: theme.accent },
                      ]}
                    >
                      <Text style={[styles.tabText, { color: theme.text }, active && { color: theme.accent }]}>
                        {type.charAt(0).toUpperCase() + type.slice(1)}
                      </Text>
                    </Pressable>
                  );
                })}
              </View>

              {submissionType === 'text' && (
                <TextInput
                  value={textContent}
                  onChangeText={isSubmitted ? undefined : setTextContent}
                  editable={!isSubmitted}
                  placeholder="Write your answer…"
                  placeholderTextColor={theme.muted}
                  multiline
                  style={[
                    styles.textArea,
                    { borderColor: theme.border, color: theme.text, backgroundColor: theme.input },
                  ]}
                />
              )}

              {submissionType === 'link' && (
                <TextInput
                  value={linkContent}
                  onChangeText={isSubmitted ? undefined : setLinkContent}
                  editable={!isSubmitted}
                  placeholder="Paste a link…"
                  placeholderTextColor={theme.muted}
                  autoCapitalize="none"
                  keyboardType="url"
                  style={[
                    styles.input,
                    { borderColor: theme.border, color: theme.text, backgroundColor: theme.input },
                  ]}
                />
              )}

              {submissionType === 'file' && (
                <View style={styles.fileCard}>
                  <Text style={[styles.bodyText, { color: theme.text }]}>
                    {attachedFile?.name || existingSubmission?.file_path || 'No file selected.'}
                  </Text>
                  {!isSubmitted && (
                    <Pressable
                      onPress={pickAssignmentFile}
                      style={[styles.secondaryButton, { backgroundColor: theme.accentSoft }]}
                    >
                      <Text style={[styles.secondaryButtonText, { color: theme.accent }]}>
                        {attachedFile ? 'Change file' : 'Choose file'}
                      </Text>
                    </Pressable>
                  )}
                </View>
              )}

              {!isSubmitted && (
                <Pressable
                  onPress={handleSubmitLegacy}
                  disabled={submitting}
                  style={[
                    styles.submitButton,
                    { backgroundColor: theme.accent },
                    submitting && styles.submitButtonDisabled,
                  ]}
                >
                  <Text style={[styles.submitButtonText, { color: theme.background }]}>
                    {submitting ? 'Submitting…' : 'Submit Assignment'}
                  </Text>
                </Pressable>
              )}
            </View>
          )}
        </>
      )}
    </ScrollView>
  );
}

// ─── Sub-components ───────────────────────────────────────────────────────────

function MCQuestion({ question, idx, selectedChoiceId, onSelect, disabled, reviewAnswer, theme }) {
  return (
    <View style={[styles.qCard, { backgroundColor: theme.card, borderColor: theme.border }]}>
      <View style={styles.qHeader}>
        <Text style={[styles.qNum, { color: theme.muted }]}>Question {idx + 1}</Text>
        <View style={[styles.ptsBadge, { backgroundColor: theme.accentSoft }]}>
          <Text style={[styles.ptsText, { color: theme.accent }]}>{question.points} pts</Text>
        </View>
      </View>
      <Text style={[styles.qText, { color: theme.text }]}>{question.question_text}</Text>

      {(question.choices ?? []).map(choice => {
        // is_correct comes from the review endpoint's nested choices array,
        // or from the initial detail load once the assignment is submitted.
        const reviewChoice = reviewAnswer?.choices?.find(c => c.id === choice.id);
        const isCorrect = reviewChoice?.is_correct ?? choice.is_correct ?? false;
        const isStudentChoice = reviewAnswer?.selected_choice_id === choice.id;
        const isSelected = (selectedChoiceId === choice.id) || (disabled && isStudentChoice);

        let rowBg = 'transparent';
        let borderCol = theme.border;
        let textCol = theme.text;
        let indicator = null;

        if (reviewAnswer) {
          if (isStudentChoice && isCorrect) {
            rowBg = 'rgba(52,211,153,0.12)';
            borderCol = '#34d399';
            textCol = '#34d399';
            indicator = '✓';
          } else if (isStudentChoice && !isCorrect) {
            rowBg = 'rgba(255,125,125,0.12)';
            borderCol = '#ff7d7d';
            textCol = '#ff7d7d';
            indicator = '✗';
          } else if (!isStudentChoice && isCorrect) {
            borderCol = '#34d399';
            textCol = '#34d399';
            indicator = '✓';
          }
        } else if (isSelected) {
          rowBg = theme.accentSoft;
          borderCol = theme.accent;
          textCol = theme.accent;
        }

        return (
          <Pressable
            key={choice.id}
            onPress={() => !disabled && onSelect(choice.id)}
            style={[styles.choiceRow, { backgroundColor: rowBg, borderColor: borderCol }]}
          >
            <View style={[styles.radioOuter, { borderColor: borderCol }]}>
              {isSelected && <View style={[styles.radioInner, { backgroundColor: borderCol }]} />}
            </View>
            <Text style={[styles.choiceText, { color: textCol }]} numberOfLines={0}>
              {choice.choice_text}
            </Text>
            {indicator ? (
              <Text style={[styles.choiceIndicator, { color: indicator === '✓' ? '#34d399' : '#ff7d7d' }]}>
                {indicator}
              </Text>
            ) : null}
          </Pressable>
        );
      })}
    </View>
  );
}

function EssayQuestion({ question, idx, value, onChange, disabled, theme }) {
  return (
    <View style={[styles.qCard, { backgroundColor: theme.card, borderColor: theme.border }]}>
      <View style={styles.qHeader}>
        <Text style={[styles.qNum, { color: theme.muted }]}>Question {idx + 1}</Text>
        <View style={[styles.ptsBadge, { backgroundColor: theme.accentSoft }]}>
          <Text style={[styles.ptsText, { color: theme.accent }]}>{question.points} pts</Text>
        </View>
      </View>
      <Text style={[styles.qText, { color: theme.text }]}>{question.question_text}</Text>
      <TextInput
        value={value}
        onChangeText={disabled ? undefined : onChange}
        editable={!disabled}
        placeholder={disabled ? '' : 'Write your answer here…'}
        placeholderTextColor={theme.muted}
        multiline
        style={[
          styles.essayInput,
          {
            borderColor: theme.border,
            color: theme.text,
            backgroundColor: disabled ? theme.background : theme.input,
          },
        ]}
      />
    </View>
  );
}

function StatusBadge({ status }) {
  const map = {
    not_submitted:   { label: 'Not Submitted',  bg: 'rgba(255,255,255,0.08)', color: '#aaa' },
    overdue:         { label: 'Overdue',         bg: 'rgba(255,125,125,0.15)', color: '#ff7d7d' },
    pending_grading: { label: 'Pending Grading', bg: 'rgba(245,166,35,0.15)',  color: '#f5a623' },
    submitted:       { label: 'Submitted',       bg: 'rgba(96,165,250,0.15)',  color: '#60a5fa' },
    graded:          { label: 'Graded',          bg: 'rgba(52,211,153,0.15)',  color: '#34d399' },
  };
  const cfg = map[status] ?? map.not_submitted;
  return (
    <View style={[styles.badge, { backgroundColor: cfg.bg }]}>
      <Text style={[styles.badgeText, { color: cfg.color }]}>{cfg.label}</Text>
    </View>
  );
}

function InfoRow({ label, value, theme }) {
  return (
    <View style={styles.infoRow}>
      <Text style={[styles.infoLabel, { color: theme.muted }]}>{label}</Text>
      <Text style={[styles.infoValue, { color: theme.text }]}>{value}</Text>
    </View>
  );
}

function formatDate(value) {
  if (!value) return 'No due date';
  const d = new Date(value);
  if (Number.isNaN(d.getTime())) return String(value);
  return d.toLocaleDateString(undefined, { year: 'numeric', month: 'short', day: 'numeric' });
}

// ─── Styles ───────────────────────────────────────────────────────────────────

const styles = StyleSheet.create({
  screen: { flex: 1 },
  container: { padding: 16, paddingBottom: 48 },

  headerSection: { marginBottom: 12 },
  backButton: { marginBottom: 8 },
  backButtonText: { fontSize: 16, fontWeight: '700' },
  title: { fontSize: 22, fontWeight: '900' },
  error: { marginTop: 8, fontSize: 14, color: '#ff7d7d' },

  centered: { alignItems: 'center', marginTop: 48, gap: 12 },
  mutedText: { fontSize: 14 },
  reviewLoader: { marginVertical: 10 },

  // Cards
  card: { borderRadius: 16, borderWidth: 1, padding: 16, marginBottom: 12 },
  sectionLabel: {
    fontSize: 12,
    fontWeight: '700',
    textTransform: 'uppercase',
    letterSpacing: 0.8,
    marginBottom: 10,
  },
  bodyText: { fontSize: 14, lineHeight: 21 },

  // Info rows
  infoRow: {
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'space-between',
    marginBottom: 8,
  },
  infoLabel: { fontSize: 13, fontWeight: '600' },
  infoValue: { fontSize: 14, flex: 1, textAlign: 'right' },
  scoreValue: { fontSize: 20, fontWeight: '900' },

  // Status badge
  badge: { paddingHorizontal: 10, paddingVertical: 4, borderRadius: 99 },
  badgeText: { fontSize: 12, fontWeight: '700' },

  // Success banners
  successCard: {
    borderRadius: 16,
    borderWidth: 1,
    borderColor: '#34d399',
    backgroundColor: 'rgba(52,211,153,0.1)',
    padding: 20,
    marginBottom: 12,
    alignItems: 'center',
    gap: 6,
  },
  successTitle: { fontSize: 18, fontWeight: '900', color: '#34d399' },
  successScore: { fontSize: 36, fontWeight: '900', color: '#34d399' },
  successSub: { fontSize: 14, color: '#86efac', textAlign: 'center' },

  pendingCard: {
    borderRadius: 16,
    borderWidth: 1,
    borderColor: '#f5a623',
    backgroundColor: 'rgba(245,166,35,0.1)',
    padding: 20,
    marginBottom: 12,
    alignItems: 'center',
    gap: 6,
  },
  pendingTitle: { fontSize: 18, fontWeight: '900', color: '#f5a623' },
  pendingSub: { fontSize: 14, color: '#fcd34d', textAlign: 'center' },

  // Question cards
  qCard: { borderRadius: 16, borderWidth: 1, padding: 16, marginBottom: 12 },
  qHeader: {
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'space-between',
    marginBottom: 10,
  },
  qNum: { fontSize: 12, fontWeight: '700', textTransform: 'uppercase', letterSpacing: 0.6 },
  ptsBadge: { paddingHorizontal: 10, paddingVertical: 4, borderRadius: 99 },
  ptsText: { fontSize: 12, fontWeight: '700' },
  qText: { fontSize: 16, fontWeight: '600', lineHeight: 23, marginBottom: 14 },

  // MC choices
  choiceRow: {
    flexDirection: 'row',
    alignItems: 'center',
    borderWidth: 1.5,
    borderRadius: 12,
    paddingVertical: 12,
    paddingHorizontal: 14,
    marginBottom: 8,
    gap: 12,
  },
  radioOuter: {
    width: 20,
    height: 20,
    borderRadius: 10,
    borderWidth: 2,
    alignItems: 'center',
    justifyContent: 'center',
    flexShrink: 0,
  },
  radioInner: { width: 10, height: 10, borderRadius: 5 },
  choiceText: { flex: 1, fontSize: 14, lineHeight: 20 },
  choiceIndicator: { fontSize: 16, fontWeight: '900', flexShrink: 0 },

  // Essay input
  essayInput: {
    borderWidth: 1,
    borderRadius: 12,
    padding: 12,
    minHeight: 110,
    textAlignVertical: 'top',
    fontSize: 14,
    lineHeight: 21,
    marginTop: 4,
  },

  // Legacy tabs
  tabRow: { flexDirection: 'row', marginVertical: 10, gap: 4 },
  tab: { flex: 1, padding: 10, alignItems: 'center', borderWidth: 1, borderRadius: 10 },
  tabText: { fontWeight: '700', fontSize: 13 },

  textArea: {
    height: 120,
    borderWidth: 1,
    borderRadius: 12,
    padding: 12,
    marginBottom: 12,
    textAlignVertical: 'top',
  },
  input: { borderWidth: 1, borderRadius: 12, padding: 12, marginBottom: 12 },
  fileCard: { marginBottom: 12, gap: 10 },
  secondaryButton: { padding: 12, alignItems: 'center', borderRadius: 12 },
  secondaryButtonText: { fontWeight: '700' },

  // Submit
  submitButton: { padding: 15, alignItems: 'center', borderRadius: 14, marginTop: 16 },
  submitButtonDisabled: { opacity: 0.45 },
  submitButtonText: { fontWeight: '800', fontSize: 16 },
});
