import React, { useEffect, useState } from 'react';
import { ActivityIndicator, Alert, Modal, Pressable, ScrollView, StyleSheet, Text, TextInput, View } from 'react-native';
import { getStudentAssignmentSubmission, submitStudentAssignment } from '@/api/student';
import { createUploadFormData, pickDocumentAsset } from '@/utils/uploads';


export default function AssignmentSubmitScreen({ token, assignment, onBack, refreshTasks }) {
  const [detailLoading, setDetailLoading] = useState(false);
  const [assignmentDetail, setAssignmentDetail] = useState(null);
  const [submissionType, setSubmissionType] = useState('text');
  const [textContent, setTextContent] = useState('');
  const [linkContent, setLinkContent] = useState('');
  const [attachedFile, setAttachedFile] = useState(null);
  const [submitting, setSubmitting] = useState(false);
  const [error, setError] = useState('');

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
    if (assignment?.assignment_id) {
      loadAssignmentDetail(assignment.assignment_id);
    }
  }, [assignment]);

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
    if (!assignment?.assignment_id) return;
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
        assignment.assignment_id,
        payload.formData || {
          submission_type: payload.submission_type,
          text_content: payload.text_content,
          link_content: payload.link_content,
        }
      );
      await refreshTasks();
      onBack();
    } catch (err) {
      setError(err?.message || 'Unable to submit assignment.');
    } finally {
      setSubmitting(false);
    }
  };

  return (
    <ScrollView style={styles.screen} contentContainerStyle={styles.container}>
      <View style={styles.headerSection}>
        <Pressable onPress={onBack} style={styles.backButton}>
          <Text style={styles.backButtonText}>← Back</Text>
        </Pressable>
        <Text style={styles.title}>Assignment Details</Text>
        {error ? <Text style={styles.error}>{error}</Text> : null}
      </View>
      {detailLoading ? (
        <View style={styles.loadingCard}>
          <ActivityIndicator size="large" />
          <Text style={styles.loadingText}>Loading assignment...</Text>
        </View>
      ) : (
        <View style={styles.detailCard}>
          <Text style={styles.itemMeta}>{assignmentDetail?.assignment?.due_date || assignment.due_date || 'No due date'}</Text>
          <Text style={styles.itemMeta}>{assignmentDetail?.course?.title || assignment.course_title || assignment.course_name || ''}</Text>
          <Text style={styles.itemMeta}>Current status: {assignmentDetail?.submission ? 'Submitted' : 'Not submitted'}</Text>

          <View style={styles.submissionTabs}>
            {['text', 'link', 'file'].map((type) => {
              const active = submissionType === type;
              return (
                <Pressable key={type} onPress={() => setSubmissionType(type)} style={[styles.submissionTab, active && styles.submissionTabActive]}>
                  <Text style={[styles.submissionTabText, active && styles.submissionTabTextActive]}>{type}</Text>
                </Pressable>
              );
            })}
          </View>

          {submissionType === 'text' ? (
            <TextInput value={textContent} onChangeText={setTextContent} placeholder="Write your answer..." placeholderTextColor={colors.muted} multiline style={styles.textArea} />
          ) : submissionType === 'link' ? (
            <TextInput value={linkContent} onChangeText={setLinkContent} placeholder="Paste a link..." placeholderTextColor={colors.muted} autoCapitalize="none" keyboardType="url" style={styles.input} />
          ) : (
            <View style={styles.filePickerCard}>
              <Text style={styles.itemMeta}>{attachedFile?.name || assignmentDetail?.submission?.file_path || 'No file selected yet.'}</Text>
              <Pressable onPress={pickAssignmentFile} style={styles.secondaryButton}>
                <Text style={styles.secondaryButtonText}>{attachedFile ? 'Change file' : 'Choose file'}</Text>
              </Pressable>
            </View>
          )}

          <Pressable onPress={handleSubmitAssignment} style={styles.submitButton} disabled={submitting}>
            <Text style={styles.submitButtonText}>{submitting ? 'Submitting...' : 'Submit assignment'}</Text>
          </Pressable>
        </View>
      )}
    </ScrollView>
  );
}

const colors = {
  background: '#0b0b0b',
  accent: '#12d6ad',
  muted: '#9d9d9d',
  text: '#f4f4f4',
  border: '#3d3d3d',
};

const styles = StyleSheet.create({
  screen: { flex: 1, backgroundColor: colors.background },
  container: { padding: 16 },
  headerSection: { marginBottom: 12 },
  backButton: { marginBottom: 8 },
  backButtonText: { color: '#12d6ad', fontSize: 16 },
  title: { fontSize: 22, fontWeight: '900', color: colors.text },
  error: { color: '#ff7d7d' },
  loadingCard: { alignItems: 'center', marginTop: 20 },
  loadingText: { marginTop: 8, color: colors.text },
  detailCard: { backgroundColor: '#1c1c1c', padding: 12, borderRadius: 8 },
  itemMeta: { color: colors.text, marginBottom: 4 },
  submissionTabs: { flexDirection: 'row', marginVertical: 8 },
  submissionTab: { flex: 1, padding: 8, alignItems: 'center', borderWidth: 1, borderColor: colors.border },
  submissionTabActive: { backgroundColor: '#173d38' },
  submissionTabText: { color: colors.text },
  submissionTabTextActive: { color: '#12d6ad' },
  textArea: { height: 120, borderColor: colors.border, borderWidth: 1, borderRadius: 4, color: colors.text, padding: 8, marginBottom: 8 },
  input: { borderColor: colors.border, borderWidth: 1, borderRadius: 4, color: colors.text, padding: 8, marginBottom: 8 },
  filePickerCard: { marginBottom: 8 },
  secondaryButton: { backgroundColor: '#173d38', padding: 8, alignItems: 'center', borderRadius: 4 },
  secondaryButtonText: { color: colors.text },
  submitButton: { backgroundColor: '#12d6ad', padding: 10, alignItems: 'center', borderRadius: 4, marginTop: 12 },
  submitButtonText: { color: '#0b0b0b', fontWeight: '600' },
});
