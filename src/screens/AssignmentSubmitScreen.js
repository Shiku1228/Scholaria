import { getStudentAssignmentSubmission, submitStudentAssignment } from '@/api/student';
import { createUploadFormData, pickDocumentAsset } from '@/utils/uploads';
import { useEffect, useState } from 'react';
import { ActivityIndicator, Alert, Pressable, ScrollView, StyleSheet, Text, TextInput, View } from 'react-native';

export default function AssignmentSubmitScreen({ token, item: assignment, setActiveTab, theme, refreshTasks }) {
  const assignmentId = assignment?.assignment_id ?? assignment?.id;

  const [detailLoading, setDetailLoading] = useState(false);
  const [assignmentDetail, setAssignmentDetail] = useState(null);
  const [submissionType, setSubmissionType] = useState('text');
  const [textContent, setTextContent] = useState('');
  const [linkContent, setLinkContent] = useState('');
  const [attachedFile, setAttachedFile] = useState(null);
  const [submitting, setSubmitting] = useState(false);
  const [error, setError] = useState('');

  const onBack = () => setActiveTab?.('courses');

  const loadAssignmentDetail = async (assignmentId) => {
    setDetailLoading(true);
    try {
      const response = await getStudentAssignmentSubmission(token, assignmentId);
      const data = response?.data || null;
      setAssignmentDetail(data);
      const existing = data?.submission || null;
      setSubmissionType(existing?.submission_type || 'text');
      setTextContent(existing?.content || '');
      setLinkContent(existing?.content || '');
      setAttachedFile(null);
    } catch (err) {
      setError(err?.message || 'Unable to load assignment details.');
    } finally {
      setDetailLoading(false);
    }
  };

  useEffect(() => {
    if (assignmentId) {
      loadAssignmentDetail(assignmentId);
    }
  }, [assignmentId]);

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

  const handleSubmitAssignment = async () => {
    if (!assignmentId) return;
    if (submissionType === 'file' && !attachedFile?.uri) {
      Alert.alert('Attach a file', 'Please choose a file before submitting.');
      return;
    }
    const payload = { submission_type: submissionType };
    if (submissionType === 'text') {
      payload.text_content = textContent;
    } else if (submissionType === 'link') {
      payload.link_content = linkContent;
    } else if (submissionType === 'file') {
      const fileForm = createUploadFormData({ submission_type: 'file' }, 'file', attachedFile);
      payload.formData = fileForm;
    }
    setSubmitting(true);
    setError('');
    try {
      await submitStudentAssignment(
        token,
        assignmentId,
        payload.formData || {
          submission_type: payload.submission_type,
          text_content: payload.text_content,
          link_content: payload.link_content,
        }
      );
      if (refreshTasks) await refreshTasks();
      onBack();
    } catch (err) {
      setError(err?.message || 'Unable to submit assignment.');
    } finally {
      setSubmitting(false);
    }
  };

  return (
    <ScrollView style={[styles.screen, { backgroundColor: theme.background }]} contentContainerStyle={styles.container}>
      <View style={styles.headerSection}>
        <Pressable onPress={onBack} style={styles.backButton}>
          <Text style={[styles.backButtonText, { color: theme.accent }]}>← Back</Text>
        </Pressable>
        <Text style={[styles.title, { color: theme.text }]}>Assignment Details</Text>
        {error ? <Text style={[styles.error, { color: theme.danger }]}>{error}</Text> : null}
      </View>

      {detailLoading ? (
        <View style={styles.loadingCard}>
          <ActivityIndicator size="large" color={theme.accent} />
          <Text style={[styles.loadingText, { color: theme.muted }]}>Loading assignment...</Text>
        </View>
      ) : (
        <View style={[styles.detailCard, { backgroundColor: theme.card, borderColor: theme.border }]}>
          <Text style={[styles.itemMeta, { color: theme.text }]}>
            Due: {assignmentDetail?.assignment?.due_date || assignment?.due_date || 'No due date'}
          </Text>
          <Text style={[styles.itemMeta, { color: theme.text }]}>
            Course: {assignmentDetail?.course?.title || assignment?.course_title || assignment?.course_name || ''}
          </Text>
          <Text style={[styles.itemMeta, { color: theme.text }]}>
            Status: {assignmentDetail?.submission ? 'Submitted' : 'Not submitted'}
          </Text>

          <View style={styles.submissionTabs}>
            {['text', 'link', 'file'].map((type) => {
              const active = submissionType === type;
              return (
                <Pressable
                  key={type}
                  onPress={() => setSubmissionType(type)}
                  style={[
                    styles.submissionTab,
                    { borderColor: theme.border },
                    active && { backgroundColor: theme.accentSoft, borderColor: theme.accent }
                  ]}
                >
                  <Text style={[styles.submissionTabText, { color: theme.text }, active && { color: theme.accent }]}>
                    {type}
                  </Text>
                </Pressable>
              );
            })}
          </View>

          {submissionType === 'text' ? (
            <TextInput
              value={textContent}
              onChangeText={setTextContent}
              placeholder="Write your answer..."
              placeholderTextColor={theme.muted}
              multiline
              style={[styles.textArea, { borderColor: theme.border, color: theme.text, backgroundColor: theme.input }]}
            />
          ) : submissionType === 'link' ? (
            <TextInput
              value={linkContent}
              onChangeText={setLinkContent}
              placeholder="Paste a link..."
              placeholderTextColor={theme.muted}
              autoCapitalize="none"
              keyboardType="url"
              style={[styles.input, { borderColor: theme.border, color: theme.text, backgroundColor: theme.input }]}
            />
          ) : (
            <View style={styles.filePickerCard}>
              <Text style={[styles.itemMeta, { color: theme.text }]}>
                {attachedFile?.name || assignmentDetail?.submission?.file_path || 'No file selected yet.'}
              </Text>
              <Pressable onPress={pickAssignmentFile} style={[styles.secondaryButton, { backgroundColor: theme.accentSoft }]}>
                <Text style={[styles.secondaryButtonText, { color: theme.accent }]}>
                  {attachedFile ? 'Change file' : 'Choose file'}
                </Text>
              </Pressable>
            </View>
          )}

          <Pressable
            onPress={handleSubmitAssignment}
            style={[styles.submitButton, { backgroundColor: theme.accent }]}
            disabled={submitting}
          >
            <Text style={[styles.submitButtonText, { color: theme.background }]}>
              {submitting ? 'Submitting...' : 'Submit assignment'}
            </Text>
          </Pressable>
        </View>
      )}
    </ScrollView>
  );
}

const styles = StyleSheet.create({
  screen: { flex: 1 },
  container: { padding: 16 },
  headerSection: { marginBottom: 12 },
  backButton: { marginBottom: 8 },
  backButtonText: { fontSize: 16, fontWeight: '700' },
  title: { fontSize: 22, fontWeight: '900' },
  error: { color: '#ff7d7d' },
  loadingCard: { alignItems: 'center', marginTop: 20 },
  loadingText: { marginTop: 8 },
  detailCard: { padding: 16, borderRadius: 16, borderWidth: 1 },
  itemMeta: { marginBottom: 6, fontSize: 14 },
  submissionTabs: { flexDirection: 'row', marginVertical: 8 },
  submissionTab: { flex: 1, padding: 10, alignItems: 'center', borderWidth: 1, borderRadius: 8, marginRight: 4 },
  submissionTabText: { fontWeight: '700' },
  textArea: { height: 120, borderWidth: 1, borderRadius: 12, padding: 12, marginBottom: 12, textAlignVertical: 'top' },
  input: { borderWidth: 1, borderRadius: 12, padding: 12, marginBottom: 12 },
  filePickerCard: { marginBottom: 12, gap: 8 },
  secondaryButton: { padding: 12, alignItems: 'center', borderRadius: 12 },
  secondaryButtonText: { fontWeight: '700' },
  submitButton: { padding: 15, alignItems: 'center', borderRadius: 14, marginTop: 12 },
  submitButtonText: { fontWeight: '800', fontSize: 16 },
});
