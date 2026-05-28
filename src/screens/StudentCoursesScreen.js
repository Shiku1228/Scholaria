import { useCallback, useEffect, useMemo, useState } from 'react';
import {
  ActivityIndicator,
  Alert,
  Linking,
  Modal,
  Pressable,
  RefreshControl,
  ScrollView,
  StyleSheet,
  Text,
  View
} from 'react-native';

import { API_BASE_URL } from '@/api/client';
import {
  getStudentAnnouncements,
  getStudentCourse,
  getStudentCourses,
  getStudentTasks
} from '@/api/student';
import { darkTheme as colors } from '@/constants/colors';

const detailTabs = [
  { key: 'overview', label: 'Overview' },
  { key: 'tasks', label: 'Tasks' },
  { key: 'resources', label: 'Resources' },
  { key: 'announcements', label: 'Announcements' },
  { key: 'discussion', label: 'Discussion' },
  { key: 'live', label: 'Live Q&A' },
];

export default function StudentCoursesScreen({ token, theme, setActiveTab, setSelectedItem }) {
  const [loading, setLoading] = useState(true);
  const [refreshing, setRefreshing] = useState(false);
  const [error, setError] = useState('');
  const [debugInfo, setDebugInfo] = useState(null);
  const [courses, setCourses] = useState([]);
  const [selectedCourseId, setSelectedCourseId] = useState(null);
  const [selectedCourse, setSelectedCourse] = useState(null);
  const [courseAnnouncements, setCourseAnnouncements] = useState([]);
  const [announcementError, setAnnouncementError] = useState('');
  const [detailLoading, setDetailLoading] = useState(false);
  const [viewMode, setViewMode] = useState('list');
  const [detailTab, setDetailTab] = useState('overview');
  const [selectedDiscussion, setSelectedDiscussion] = useState(null);
  const [selectedAssignment, setSelectedAssignment] = useState(null);

  const selectedIndex = useMemo(() => {
    const index = courses.findIndex((course) => getCourseId(course) === selectedCourseId);
    return index >= 0 ? index : 0;
  }, [courses, selectedCourseId]);

  const selectedCourseBase = courses.find((course) => getCourseId(course) === selectedCourseId) || null;
  const currentCourse = selectedCourse || selectedCourseBase || {};
  const assignments = currentCourse.assignments || [];
  const resources = currentCourse.resources || [];
  const discussions = currentCourse.discussions || [];
  const totalTasks = assignments.length + (currentCourse.exams?.length || 0) + (currentCourse.quizzes?.length || 0);
  const enrollmentStatus = normalizeEnrollmentStatus(
    currentCourse.enrollment_status || courses[selectedIndex]?.enrollment_status || ''
  );

  const loadCourses = useCallback(async () => {
    setError('');
    setDebugInfo(null);
    setLoading(true);

    try {
      const response = await getStudentCourses(token);
      const nextCourses = response?.data || [];
      setCourses(nextCourses);

      if (nextCourses.length) {
        setSelectedCourseId((current) => {
          const validCurrent = nextCourses.some((course) => getCourseId(course) === current) ? current : null;
          return validCurrent || getCourseId(nextCourses[0]);
        });
      }
    } catch (err) {
      setError(err?.message || 'Unable to load courses.');
      setDebugInfo(extractApiDebug(err));
    } finally {
      setLoading(false);
    }
  }, [token]);

  const loadSelectedCourse = useCallback(async (courseId, courseList = courses) => {
    if (!courseId) {
      setSelectedCourse(null);
      setCourseAnnouncements([]);
      setAnnouncementError('');
      return;
    }

    setDetailLoading(true);
    setAnnouncementError('');
    setError('');
    setDebugInfo(null);

    const [courseResult, tasksResult, quizTasksResult, quizAliasResult, announcementsResult] = await Promise.allSettled([
      getStudentCourse(token, courseId),
      getStudentTasks(token, { course_id: courseId }),
      getStudentTasks(token, { course_id: courseId, tab: 'quizzes' }),
      getStudentTasks(token, { course_id: courseId, tab: 'quiz' }),
      getStudentAnnouncements(token, courseId),
    ]);

    const courseData = courseResult.status === 'fulfilled' ? courseResult.value?.data || null : null;
    const tasksData = tasksResult.status === 'fulfilled' ? tasksResult.value?.data || {} : {};
    const quizTasksData = quizTasksResult.status === 'fulfilled' ? quizTasksResult.value?.data || {} : {};
    const quizAliasData = quizAliasResult.status === 'fulfilled' ? quizAliasResult.value?.data || {} : {};
    const baseCourse = courseList.find((course) => matchesAnyCourseId(course, [courseId])) || {};
    const courseIds = collectCourseIds(courseId, baseCourse, courseData);

    const mergedCourse = {
      ...baseCourse,
      ...(courseData || {}),
      assignments: mergeTasksForCourse(
        [
          ...extractTaskItems(courseData, ['assignments', 'assignment_list', 'tasks']),
          ...extractTaskItems(tasksData, ['assignments', 'assignment_list', 'tasks']),
        ],
        courseIds
      ),
      exams: mergeTasksForCourse(
        [
          ...extractTaskItems(courseData, ['exams', 'exam_list']),
          ...extractTaskItems(tasksData, ['exams', 'exam_list']),
        ],
        courseIds
      ),
      quizzes: mergeTasksForCourse(
        [
          ...extractTaskItems(courseData, ['quizzes', 'quiz_list', 'assessments']),
          ...extractTaskItems(tasksData, ['quizzes', 'quiz_list', 'assessments']),
          ...extractTaskItems(quizTasksData, ['quizzes', 'quiz_list', 'assessments']),
          ...extractTaskItems(quizAliasData, ['quizzes', 'quiz_list', 'assessments']),
        ],
        courseIds
      ),
      resources: mergeTasksForCourse(
        [
          ...extractTaskItems(courseData, ['resources', 'files', 'materials']),
          ...extractTaskItems(tasksData, ['resources', 'files', 'materials']),
        ],
        courseIds
      ),
      discussions: mergeTasksForCourse(
        [
          ...extractTaskItems(courseData, ['discussions', 'discussion', 'posts', 'messages']),
          ...extractTaskItems(tasksData, ['discussions', 'discussion', 'posts', 'messages']),
        ],
        courseIds
      ),
    };

    setSelectedCourse(mergedCourse);

    if (announcementsResult.status === 'fulfilled') {
      setCourseAnnouncements(announcementsResult.value?.data?.announcements || []);
      setAnnouncementError('');
    } else {
      setCourseAnnouncements([]);
      setAnnouncementError(announcementsResult.reason?.message || 'Unable to load announcements.');
    }

    const courseError = courseResult.status === 'rejected' ? courseResult.reason : null;
    const tasksError = tasksResult.status === 'rejected' ? tasksResult.reason : null;
    const ignoredCourseError = isEnrollmentError(courseError);
    if ((courseError && !ignoredCourseError) || tasksError) {
      setError((!ignoredCourseError && courseError?.message) || tasksError?.message || 'Unable to load course details.');
      setDebugInfo(extractApiDebug(courseError) || extractApiDebug(tasksError));
    }

    setDetailLoading(false);
  }, [courses, token]);

  useEffect(() => {
    const timer = setTimeout(() => {
      loadCourses();
    }, 0);

    return () => clearTimeout(timer);
  }, [loadCourses]);

  useEffect(() => {
    const timer = setTimeout(() => {
      if (selectedCourseId) {
        loadSelectedCourse(selectedCourseId);
      }
    }, 0);

    return () => clearTimeout(timer);
  }, [loadSelectedCourse, selectedCourseId]);

  const handleRefresh = async () => {
    setRefreshing(true);
    try {
      const response = await getStudentCourses(token);
      const nextCourses = response?.data || [];
      setCourses(nextCourses);

      const validSelectedCourseId = nextCourses.some((course) => getCourseId(course) === selectedCourseId)
        ? selectedCourseId
        : getCourseId(nextCourses[0]);

      if (validSelectedCourseId) {
        setSelectedCourseId(validSelectedCourseId);
        await loadSelectedCourse(validSelectedCourseId, nextCourses);
      }
    } catch (err) {
      setError(err?.message || 'Unable to refresh courses.');
      setDebugInfo(extractApiDebug(err));
    } finally {
      setRefreshing(false);
    }
  };

  const openCourse = (course) => {
    const nextCourseId = getCourseId(course);
    if (!nextCourseId) {
      setError('Unable to open this course because it does not have a valid ID yet.');
      setDebugInfo({
        reason: 'invalid_course_id',
        message: 'The selected course did not include a valid course ID.',
        course,
      });
      return;
    }

    setSelectedCourseId(nextCourseId);
    setDetailTab('overview');
    setViewMode('detail');
  };

  const closeDetail = () => {
    setViewMode('list');
    setSelectedDiscussion(null);
    setSelectedAssignment(null);
  };

  const openResource = async (resource) => {
    const rawPath = resource?.file_path || '';
    if (!rawPath) {
      Alert.alert('Resource', 'No file link available for this resource.');
      return;
    }

    const normalizedPath = rawPath.startsWith('http')
      ? rawPath
      : `${API_BASE_URL.replace(/\/api$/, '')}/${rawPath.replace(/^\/+/, '')}`;

    const canOpen = await Linking.canOpenURL(normalizedPath);
    if (canOpen) {
      await Linking.openURL(normalizedPath);
    } else {
      Alert.alert('Resource', normalizedPath);
    }
  };

  const discussionReplies = selectedDiscussion?.replies || [];

  return (
    <View style={[styles.screen, { backgroundColor: theme.background }]}>
      <ScrollView
        style={styles.scroll}
        contentContainerStyle={styles.container}
        showsVerticalScrollIndicator={false}
        refreshControl={<RefreshControl refreshing={refreshing} onRefresh={handleRefresh} tintColor={theme.accent} />}
      >
        <View style={styles.headerContainer}>
          <Text style={[styles.kicker, { color: theme.text }]}>{viewMode === 'detail' ? 'COURSE DETAIL' : 'MY COURSES'}</Text>
          <Text style={[styles.subtitle, { color: theme.muted }]}>
            {viewMode === 'detail' ? 'Tap a section below to explore the course' : 'Courses you are enrolled in'}
          </Text>
        </View>

        {!!error && <Text style={[styles.error, { color: theme.danger }]}>{error}</Text>}

        {debugInfo ? (
          <View style={styles.debugCard}>
            <Text style={styles.debugTitle}>Enrollment Debug</Text>
            <Text style={styles.debugLine}>selectedCourseId: {String(selectedCourseId ?? 'n/a')}</Text>
            <Text style={styles.debugLine}>resolvedCourseId: {String(getCourseId(currentCourse) ?? 'n/a')}</Text>
            <Text style={styles.debugLine}>student_id: {String(debugInfo?.meta?.student_id ?? debugInfo?.student_id ?? 'n/a')}</Text>
            <Text style={styles.debugLine}>course_id: {String(debugInfo?.meta?.course_id ?? debugInfo?.course_id ?? 'n/a')}</Text>
            <Text style={styles.debugLine}>
              enrollment_count: {String(debugInfo?.student_enrollment_count ?? 'n/a')}
            </Text>
            <Text style={styles.debugLine}>
              enrolled_courses: {formatDebugList(debugInfo?.student_enrollment_course_ids)}
            </Text>
            <Text style={styles.debugLine}>
              reason: {String(debugInfo?.reason || debugInfo?.meta?.reason || 'n/a')}
            </Text>
            <Text style={styles.debugLine}>
              message: {String(debugInfo?.message || 'n/a')}
            </Text>
            {debugInfo?.matching_enrollment_row ? (
              <Text style={styles.debugJson}>
                row: {JSON.stringify(debugInfo.matching_enrollment_row)}
              </Text>
            ) : null}
          </View>
        ) : null}

        {loading ? (
          <View style={[styles.loadingCard, { backgroundColor: theme.card, borderColor: theme.border }]}>
            <ActivityIndicator size="large" color={theme.accent} />
            <Text style={[styles.loadingText, { color: theme.muted }]}>Loading courses...</Text>
          </View>
        ) : viewMode === 'list' ? (
          <View style={styles.sectionBlock}>
            <Text style={[styles.sectionTitle, { color: theme.text }]}>Tap a course</Text>

            {courses.length === 0 ? (
              <Text style={[styles.emptyText, { color: theme.muted }]}>No enrolled courses found.</Text>
            ) : (
              courses.map((course, index) => {
                const courseId = getCourseId(course);
                const active = selectedCourseId === courseId;
                const courseStatus = normalizeEnrollmentStatus(course.enrollment_status);

                return (
                  <Pressable
                    key={courseId || course.course_number || course.course_name || `course-${index}`}
                    onPress={() => openCourse(course)}
                    style={[styles.courseCard, { backgroundColor: theme.card, borderColor: theme.border }, active && { borderColor: theme.accent }]}
                  >
                    <View style={[styles.coursePreview, { backgroundColor: theme.card }]}>
                      <View style={styles.courseTopRow}>
                        <View style={styles.courseTextWrap}>
                          <Text style={[styles.courseTitle, { color: theme.text }]} numberOfLines={2}>
                            {course.course_name || 'Course'}
                          </Text>
                          <Text style={[styles.courseMeta, { color: theme.muted }]}>
                            {course.course_number || 'No course number'} - {course.teacher_name || 'No teacher'}
                          </Text>
                        </View>

                        <View style={[styles.badge, { backgroundColor: theme.accentSoftAlt }, active && { backgroundColor: theme.accent }]}>
                          <Text style={[styles.badgeText, { color: theme.text }, active && { color: theme.background }]}>
                            {course.progress ?? 0}%
                          </Text>
                        </View>
                      </View>
                    </View>

                    <View style={[styles.courseBody, { backgroundColor: theme.accentSoftAlt }]}>
                      <View style={[styles.progressTrack, { backgroundColor: theme.border }]}>
                        <View
                          style={[
                            styles.progressFill,
                            { width: `${Math.max(0, Math.min(100, course.progress ?? 0))}%`, backgroundColor: theme.accentSoftAlt },
                          ]}
                        />
                      </View>

                      {courseStatus ? (
                        <View style={styles.courseStatusRow}>
                          <View
                            style={[
                              styles.courseStatusPill,
                              courseStatus === 'active' && styles.courseStatusPillActive,
                            ]}
                          >
                            <Text style={[styles.courseStatusText, { color: theme.text }, courseStatus === 'active' && { color: theme.accentSoftAlt }]}>
                              {formatEnrollmentStatus(courseStatus)}
                            </Text>
                          </View>
                        </View>
                      ) : null}

                      <Text style={[styles.courseHint, { color: theme.accentSoftAlt }]}>Tap to open course activity</Text>
                    </View>
                  </Pressable>
                );
              })
            )}
          </View>
        ) : (
          <View style={[styles.detailPanel, { backgroundColor: theme.card, borderColor: theme.border }]}>
            <Pressable onPress={closeDetail} style={[styles.backButton, { backgroundColor: theme.accentSoft, borderColor: theme.border }]}>
              <Text style={[styles.backButtonText, { color: theme.accent }]}>Back to courses</Text>
            </Pressable>

            {detailLoading ? (
              <View style={styles.loadingCard}>
                <ActivityIndicator color={theme.accent} />
                <Text style={[styles.loadingText, { color: theme.muted }]}>Loading selected course...</Text>
              </View>
            ) : (
              <>
                <View style={[styles.heroCard, { backgroundColor: theme.accentSoftAlt }]}>
                  <View style={styles.heroTextBlock}>
                    <Text style={[styles.heroTitle, { color: '#FFFFFF' }]}>
                      {currentCourse.course?.title || currentCourse.course_name || 'Course'}
                    </Text>
                    <Text style={[styles.heroSubtitle, { color: '#FFFFFF', opacity: 0.8 }]}>
                      {currentCourse.course?.course_number || currentCourse.course_number || 'No course number'} •{' '}
                      {courses[selectedIndex]?.progress ?? 0}% complete
                    </Text>
                  </View>

                  <View style={[styles.heroBadge, { backgroundColor: theme.card }]}>
                    <Text style={[styles.heroBadgeText, { color: theme.accent }]}>{courses[selectedIndex]?.progress ?? 0}%</Text>
                  </View>
                </View>

                {enrollmentStatus ? (
                  <View style={styles.statusPillRow}>
                    <View style={[styles.statusPill, { backgroundColor: theme.border }, enrollmentStatus === 'active' && { backgroundColor: theme.accentSoftAlt, borderColor: theme.accent, borderWidth: 1 }]}>
                      <Text style={[styles.statusPillText, { color: theme.text }, enrollmentStatus === 'active' && { color: theme.accent }]}>
                        {formatEnrollmentStatus(enrollmentStatus)}
                      </Text>
                    </View>
                  </View>
                ) : null}

                <View style={[styles.tabCard, { backgroundColor: theme.card, borderColor: theme.border }]}>
                  <ScrollView horizontal showsHorizontalScrollIndicator={false} contentContainerStyle={styles.tabRow}>
                    {detailTabs.map((tab) => {
                      const active = detailTab === tab.key;
                      return (
                        <Pressable
                          key={tab.key}
                          onPress={() => setDetailTab(tab.key)}
                          style={[styles.tabPill, { backgroundColor: theme.input, borderColor: theme.border }, active && { backgroundColor: theme.accentSoftAlt, borderColor: theme.accent }]}
                        >
                          <Text style={[styles.tabText, { color: theme.muted }, active && { color: theme.accent }]}>{tab.label}</Text>
                          {active ? <View style={styles.tabUnderline} /> : null}
                        </Pressable>
                      );
                    })}
                  </ScrollView>
                </View>

                {detailTab === 'overview' && (
                  <View style={styles.detailSection}>
                    <Text style={[styles.sectionTitle, { color: theme.text }]}>Course Overview</Text>
                    <View style={styles.summaryGrid}>
                      <SummaryChip theme={theme} label="Resources" value={resources.length} />
                      <SummaryChip theme={theme} label="Discussions" value={discussions.length} />
                      <SummaryChip theme={theme} label="Assignments" value={assignments.length} />
                      <SummaryChip theme={theme} label="Exams" value={currentCourse.exams?.length || 0} />
                      <SummaryChip theme={theme} label="Quizzes" value={currentCourse.quizzes?.length || 0} />
                      <SummaryChip theme={theme} label="Tasks" value={totalTasks} />
                    </View>
                  </View>
                )}

                {detailTab === 'tasks' && (
                  <View style={styles.detailSection}>
                    <Text style={[styles.sectionTitle, { color: theme.text }]}>Tasks Overview</Text>
                    <View style={styles.summaryGrid}>
                      <SummaryChip theme={theme} label="Assignments" value={assignments.length} />
                      <SummaryChip theme={theme} label="Exams" value={currentCourse.exams?.length || 0} />
                      <SummaryChip theme={theme} label="Quizzes" value={currentCourse.quizzes?.length || 0} />
                      <SummaryChip theme={theme} label="Total" value={totalTasks} />
                    </View>

                    <View style={styles.sectionBlockInner}>
                      <Text style={[styles.sectionTitle, { color: theme.text }]}>Assignments</Text>
                      {assignments.length > 0 ? (
                        assignments.map((assignment, index) =>
                          assignment ? (
                            <Pressable
                              key={getItemKey(assignment, ['assignment_id', 'id'], `assignment-${index}`)}
                              onPress={() => {
                                setSelectedItem(assignment);
                                setActiveTab('assignmentSubmit');
                              }}
                              style={[styles.activityCard, { backgroundColor: theme.input, borderColor: theme.border }]}
                            >
                              <View style={styles.activityTopRow}>
                                <View style={styles.activityTextWrap}>
                                  <Text style={[styles.activityTitle, { color: theme.text }]}>
                                    {assignment.title}
                                  </Text>
                                  <Text style={[styles.activityMeta, { color: theme.muted }]}>
                                    {assignment.due_date || 'No due date'} •{' '}
                                    {assignment.submission_id ? 'Submitted' : 'Pending'}
                                  </Text>
                                </View>

                                <View style={[styles.submitButtonSmall, { backgroundColor: theme.accentSoftAlt }]}
                                >
                                  <Text style={[styles.submitButtonSmallText, { color: '#FFFFFF' }]}>
                                    {assignment.submission_id ? 'Resubmit' : 'Open'}
                                  </Text>
                                </View>
                              </View>
                            </Pressable>
                          ) : null
                        )
                      ) : (
                        <Text style={[styles.emptyText, { color: theme.muted }]}>No assignments yet.</Text>
                      )}
                    </View>

                    <View style={styles.sectionBlockInner}>
                      <Text style={[styles.sectionTitle, { color: theme.text }]}>Exams</Text>
                      {currentCourse.exams?.length > 0 ? (
                        currentCourse.exams.map((exam, index) =>
                          exam ? (
                            <Pressable
                              key={getItemKey(exam, ['id', 'exam_id'], `exam-${index}`)}
                              onPress={() => {
                                setSelectedItem(exam);
                                setActiveTab('exam');
                              }}
                              style={[styles.activityCard, { backgroundColor: theme.input, borderColor: theme.border }]}
                            >
                              <View style={styles.activityTopRow}>
                                <View style={styles.activityTextWrap}>
                                  <Text style={[styles.activityTitle, { color: theme.text }]}>{exam.title}</Text>
                                  <Text style={[styles.activityMeta, { color: theme.muted }]}>
                                    {exam.exam_date || exam.due_date || 'No date'} • {exam.attempts?.length ? 'Has attempts' : 'No attempt yet'}
                                  </Text>
                                </View>
                                <View style={[styles.linkPill, { backgroundColor: theme.accentSoftAlt }]}>
                                  <Text style={[styles.linkPillText, { color: theme.accent }]}>Exam</Text>
                                </View>
                              </View>
                            </Pressable>
                          ) : null
                        )
                      ) : (
                        <Text style={[styles.emptyText, { color: theme.muted }]}>No exams yet.</Text>
                      )}
                    </View>

                    <View style={styles.sectionBlockInner}>
                      <Text style={[styles.sectionTitle, { color: theme.text }]}>Quizzes</Text>

                      {currentCourse.quizzes?.length > 0 ? (
                        currentCourse.quizzes.map((quiz, index) =>
                          quiz ? (
                            <Pressable
                              key={getItemKey(quiz, ['id', 'quiz_id'], `quiz-${index}`)}
                              onPress={() => {
                                setSelectedItem(quiz);
                                setActiveTab('quiz');
                              }}
                              style={[styles.activityCard, { backgroundColor: theme.input, borderColor: theme.border }]}
                            >
                              <View style={styles.activityTopRow}>
                                <View style={styles.activityTextWrap}>
                                  <Text style={[styles.activityTitle, { color: theme.text }]}>{quiz.title}</Text>
                                  <Text style={[styles.activityMeta, { color: theme.muted }]}>
                                    {quiz.due_date || quiz.start_date || 'No date'} •{' '}
                                    {quiz.attempts?.length ? 'Has attempts' : 'No attempt yet'}
                                  </Text>
                                </View>
                                <View style={[styles.linkPill, { backgroundColor: theme.accentSoftAlt }]}>
                                  <Text style={[styles.linkPillText, { color: theme.accent }]}>Quiz</Text>
                                </View>
                              </View>
                            </Pressable>
                          ) : null
                        )
                      ) : (
                        <Text style={[styles.emptyText, { color: theme.muted }]}>No quizzes yet.</Text>
                      )}
                    </View>
                  </View>
                )}

                {detailTab === 'resources' && (
                  <View style={styles.detailSection}>
                    <Text style={[styles.sectionTitle, { color: theme.text }]}>Resources</Text>
                    {resources.length ? (
                      resources.map((resource, index) => (
                        <Pressable
                          key={`resource-${index}-${getItemKey(resource, ['resource_id', 'id'], 'no-id')}`}
                          onPress={() => openResource(resource)}
                          style={[styles.activityCard, { backgroundColor: theme.input, borderColor: theme.border }]}
                        >
                          <View style={styles.activityTopRow}>
                            <View style={styles.activityTextWrap}>
                              <Text style={[styles.activityTitle, { color: theme.text }]} numberOfLines={1}>
                                {resource.title || resource.file_name || 'Resource'}
                              </Text>
                              <Text style={[styles.activityMeta, { color: theme.muted }]} numberOfLines={1}>
                                {resource.mime_type || 'File'} • {prettyBytes(resource.file_size)}
                              </Text>
                            </View>
                            <View style={[styles.linkPill, { backgroundColor: theme.accentSoftAlt }]}>
                              <Text style={[styles.linkPillText, { color: theme.accent }]}>Open</Text>
                            </View>
                          </View>
                        </Pressable>
                      ))
                    ) : (
                      <Text style={[styles.emptyText, { color: theme.muted }]}>No resources yet.</Text>
                    )}
                  </View>
                )}

                {detailTab === 'announcements' && (
                  <View style={styles.detailSection}>
                    <Text style={[styles.sectionTitle, { color: theme.text }]}>Announcements</Text>
                    {!!announcementError && <Text style={styles.warningText}>{announcementError}</Text>}
                    {courseAnnouncements.length ? (
                      courseAnnouncements.map((announcement, index) => (
                        <View
                          key={getItemKey(announcement, ['announcement_id', 'id'], `announcement-${index}`)}
                          style={[styles.activityCard, { backgroundColor: theme.input, borderColor: theme.border }]}
                        >
                          <Text style={[styles.activityTitle, { color: theme.text }]}>{announcement.title}</Text>
                          <Text style={[styles.activityMeta, { color: theme.muted }]}>
                            {announcement.content || 'No content provided.'}
                          </Text>
                          <Text style={[styles.activityDate, { color: theme.accent }]}>{announcement.created_at || ''}</Text>
                        </View>
                      ))
                    ) : (
                      <Text style={[styles.emptyText, { color: theme.muted }]}>No announcements yet.</Text>
                    )}
                  </View>
                )}

                {detailTab === 'discussion' && (
                  <View style={styles.detailSection}>
                    <Text style={[styles.sectionTitle, { color: theme.text }]}>Discussion</Text>
                    {discussions.length ? (
                      discussions.map((discussion, index) => (
                        <Pressable
                          key={getItemKey(discussion, ['id', 'discussion_id'], `discussion-${index}`)}
                          onPress={() => setSelectedDiscussion(discussion)}
                          style={[styles.activityCard, { backgroundColor: theme.input, borderColor: theme.border }]}
                        >
                          <View style={styles.activityTopRow}>
                            <View style={styles.activityTextWrap}>
                              <Text style={[styles.activityTitle, { color: theme.text }]}>{discussion.user?.name || 'Student'}</Text>
                              <Text style={[styles.activityMeta, { color: theme.muted }]} numberOfLines={2}>
                                {discussion.content}
                              </Text>
                            </View>
                            <View style={[styles.linkPill, { backgroundColor: theme.accentSoftAlt }]}>
                              <Text style={[styles.linkPillText, { color: theme.accent }]}>{discussion.replies?.length || 0} replies</Text>
                            </View>
                          </View>
                        </Pressable>
                      ))
                    ) : (
                      <Text style={[styles.emptyText, { color: theme.muted }]}>No discussions yet.</Text>
                    )}
                  </View>
                )}

                {detailTab === 'live' && (
                  <View style={styles.detailSection}>
                    <Text style={[styles.sectionTitle, { color: theme.text }]}>Live Q&A</Text>
                    <View style={[styles.calloutCard, { backgroundColor: theme.card, borderColor: theme.border }]}>
                      <Text style={[styles.activityTitle, { color: theme.text }]}>Ask a question</Text>
                      <Text style={[styles.activityMeta, { color: theme.muted }]}>
                        This will reuse your discussion system, so it stays aligned with the current web app.
                      </Text>
                    </View>

                    {selectedDiscussion ? (
                      <View style={[styles.calloutCard, { backgroundColor: theme.card, borderColor: theme.border }]}>
                        <Text style={[styles.activityTitle, { color: theme.text }]}>Selected thread</Text>
                        <Text style={[styles.activityMeta, { color: theme.muted }]}>{selectedDiscussion.content}</Text>
                      </View>
                    ) : (
                      <Text style={[styles.emptyText, { color: theme.muted }]}>Tap a discussion to inspect the thread here.</Text>
                    )}
                  </View>
                )}
              </>
            )}
          </View>
        )}
      </ScrollView>

      <Modal visible={selectedDiscussion !== null} transparent animationType="fade" onRequestClose={() => setSelectedDiscussion(null)}>
        <View style={styles.modalBackdrop}>
          <Pressable style={StyleSheet.absoluteFill} onPress={() => setSelectedDiscussion(null)} />
          <View style={[styles.modalCard, { backgroundColor: theme.card, borderColor: theme.border }]}>
            <Text style={[styles.modalTitle, { color: theme.text }]}>Discussion thread</Text>
            <Text style={[styles.modalBody, { color: theme.text }]}>{selectedDiscussion?.content || ''}</Text>
            <Text style={[styles.modalSub, { color: theme.muted }]}>Replies</Text>
            <ScrollView style={styles.modalScroll} contentContainerStyle={styles.modalGap}>
              {discussionReplies.length ? (
                discussionReplies.map((reply, index) => (
                  <View key={getItemKey(reply, ['id', 'reply_id'], `reply-${index}`)} style={[styles.replyCard, { backgroundColor: theme.input, borderColor: theme.border }]}>
                    <Text style={[styles.replyName, { color: theme.accent }]}>{reply.user?.name || 'Student'}</Text>
                    <Text style={[styles.replyBody, { color: theme.text }]}>{reply.content}</Text>
                  </View>
                ))
              ) : (
                <Text style={styles.emptyText}>No replies yet.</Text>
              )}
            </ScrollView>
            <Pressable onPress={() => setSelectedDiscussion(null)} style={[styles.modalCloseButton, { backgroundColor: theme.accentSoft, borderColor: theme.border }]}>
              <Text style={[styles.modalCloseText, { color: theme.accent }]}>Close</Text>
            </Pressable>
          </View>
        </View>
      </Modal>
    </View>
  );
}

function SummaryChip({ label, value, theme }) {
  return (
    <View style={[styles.summaryChip, { backgroundColor: theme.background, borderColor: theme.border }]}>
      <Text style={[styles.summaryLabel, { color: theme.muted }]}>{label}</Text>
      <Text style={[styles.summaryValue, { color: theme.text }]}>{value}</Text>
    </View>
  );
}

function prettyBytes(value) {
  const size = Number(value);
  if (isNaN(size)) return 'Unknown size';
  if (size === 0) return '0 B';
  if (size >= 1024 * 1024) return `${(size / (1024 * 1024)).toFixed(1)} MB`;
  if (size >= 1024) return `${Math.round(size / 1024)} KB`;
  return `${size} B`;
}

function normalizeEnrollmentStatus(value) {
  return String(value || '').trim().toLowerCase();
}

function formatEnrollmentStatus(value) {
  const normalized = normalizeEnrollmentStatus(value);
  if (!normalized) {
    return '';
  }

  return normalized.charAt(0).toUpperCase() + normalized.slice(1);
}

function getItemKey(item, fields, fallback) {
  for (const field of fields) {
    const value = item?.[field];
    if (value !== null && value !== undefined && value !== '') {
      return String(value);
    }
  }

  return fallback;
}

function getCourseId(course) {
  return pickPositiveId(
    course?.id,
    course?.course_id,
    course?.course?.id,
    course?.course?.course_id,
    course?.course?.course?.id
  );
}

function pickPositiveId(...values) {
  for (const value of values) {
    const numeric = Number(value);
    if (Number.isFinite(numeric) && numeric > 0) {
      return numeric;
    }
  }

  return null;
}

function collectCourseIds(...sources) {
  const ids = [];

  const pushId = (value) => {
    if (value === null || value === undefined || value === '') {
      return;
    }

    ids.push(String(value));
  };

  sources.forEach((source) => {
    if (!source) {
      return;
    }

    if (Array.isArray(source)) {
      source.forEach(pushId);
      return;
    }

    if (typeof source === 'object') {
      pushId(source.id);
      pushId(source.course_id);
      pushId(source?.course?.id);
      pushId(source?.course?.course_id);
      pushId(source?.course?.course?.id);
      return;
    }

    pushId(source);
  });

  return [...new Set(ids)];
}

function matchesAnyCourseId(course, courseIds = []) {
  const candidates = collectCourseIds(course);
  const targetIds = new Set(collectCourseIds(courseIds));
  return candidates.some((id) => targetIds.has(id));
}

function mergeTasksForCourse(items, courseIds) {
  if (!Array.isArray(items)) {
    return [];
  }

  const targetIds = new Set(collectCourseIds(courseIds));

  const filtered = items.filter((item) => {
    const cid = item?.course_id || item?.course?.id;
    return !cid || targetIds.has(String(cid));
  });

  const seen = new Set();
  const deduped = [];
  for (const itm of filtered) {
    const itmId =
      itm?.id ??
      itm?.assignment_id ??
      itm?.exam_id ??
      itm?.quiz_id ??
      itm?.resource_id ??
      itm?.discussion_id;

    const key = itmId ? String(itmId) : `${itm?.title || itm?.name || 'no-id'}-${deduped.length}`;
    if (!seen.has(key)) {
      seen.add(key);
      deduped.push(itm);
    }
  }

  return deduped;
}


function extractTaskItems(rawData, keys) {
  if (!rawData) {
    return [];
  }

  for (const key of keys) {
    const value = rawData?.[key];
    if (Array.isArray(value)) {
      return value;
    }
  }

  const data = rawData?.data;
  if (Array.isArray(data)) {
    return data;
  }

  const nested = rawData?.items || rawData?.list || rawData?.results;
  if (Array.isArray(nested)) {
    return nested;
  }

  return [];
}

function isEnrollmentError(error) {
  const message = String(error?.message || '').toLowerCase();
  return message.includes('not enrolled in this course');
}

function extractApiDebug(error) {
  return error?.data?.debug || error?.data || null;
}

function formatDebugList(values) {
  if (!Array.isArray(values) || values.length === 0) {
    return '[]';
  }

  return `[${values.join(', ')}]`;
}

const styles = StyleSheet.create({
  screen: {
    flex: 1,
  },
  scroll: {
    flex: 1,
  },
  container: {
    paddingBottom: 24,
  },
  headerContainer: {
    paddingHorizontal: 24,
    paddingTop: 24,
    paddingBottom: 8,
    gap: 4,
  },
  kicker: {
    fontSize: 32,
    fontWeight: '900',
    letterSpacing: -0.6,
  },
  subtitle: {
    color: colors.muted,
    fontSize: 14,
  },
  error: {
    fontWeight: '700',
    paddingHorizontal: 24,
  },
  debugCard: {
    marginHorizontal: 16,
    marginTop: 12,
    padding: 14,
    borderRadius: 16,
    borderWidth: 1,
    borderColor: '#4b6b9a',
    backgroundColor: '#0f1d33',
    gap: 6,
  },
  debugTitle: {
    fontSize: 13,
    fontWeight: '900',
    textTransform: 'uppercase',
    letterSpacing: 0.6,
  },
  debugLine: {
    fontSize: 12,
    lineHeight: 18,
  },
  debugJson: {
    fontSize: 11,
    lineHeight: 16,
  },
  warningText: {
    color: '#f5c451',
    fontWeight: '700',
  },
  loadingCard: {
    borderRadius: 20,
    padding: 20,
    marginHorizontal: 16,
    alignItems: 'center',
    gap: 10,
    borderWidth: 1,
  },
  loadingText: {
  },
  sectionBlock: {
    paddingHorizontal: 16,
    paddingTop: 12,
    gap: 10,
  },
  sectionTitle: {
    fontSize: 18,
    fontWeight: '800',
  },
  emptyText: {
    fontStyle: 'italic',
  },
  courseCard: {
    overflow: 'hidden',
    borderRadius: 28,
    borderWidth: 1,
  },
  courseCardActive: {
  },
  coursePreview: {
    padding: 16,
    height: 140,
  },
  courseBody: {
    paddingHorizontal: 16,
    paddingVertical: 14,
    gap: 8,
  },
  courseTopRow: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    gap: 12,
    alignItems: 'flex-start',
  },
  courseTextWrap: {
    flex: 1,
  },
  courseTitle: {
    fontSize: 18,
    fontWeight: '900',
  },
  courseMeta: {
    fontSize: 13,
    marginTop: 6,
  },
  courseHint: {
    opacity: 0.9,
    fontSize: 12,
    fontWeight: '800',
    marginTop: 4,
  },
  statusPillRow: {
    alignItems: 'flex-start',
  },
  statusPill: {
    alignSelf: 'flex-end',
    paddingHorizontal: 14,
    paddingVertical: 7,
    borderRadius: 999,
  },
  statusPillActive: {
  },
  statusPillText: {
    fontSize: 12,
    fontWeight: '900',
    textTransform: 'capitalize',
  },
  statusPillTextActive: {
  },
  courseStatusRow: {
    alignItems: 'flex-start',
  },
  courseStatusPill: {
    alignSelf: 'flex-end',
    paddingHorizontal: 14,
    paddingVertical: 7,
    borderRadius: 999,
  },
  courseStatusPillActive: {
  },
  courseStatusText: {
    fontSize: 12,
    fontWeight: '900',
    textTransform: 'capitalize',
  },
  courseStatusTextActive: {
  },
  badge: {
    minWidth: 52,
    paddingHorizontal: 10,
    height: 30,
    borderRadius: 999,
    alignItems: 'center',
    justifyContent: 'center',
  },
  badgeActive: {
  },
  badgeText: {
    fontWeight: '900',
    fontSize: 12,
  },
  badgeTextActive: {
  },
  progressTrack: {
    height: 8,
    borderRadius: 999,
    overflow: 'hidden',
  },
  progressFill: {
    height: '100%',
    borderRadius: 999,
  },
  detailPanel: {
    marginHorizontal: 16,
    borderRadius: 20,
    borderWidth: 1,
    padding: 14,
    gap: 14,
  },
  backButton: {
    alignSelf: 'flex-start',
    paddingHorizontal: 14,
    paddingVertical: 10,
    borderRadius: 14,
  },
  backButtonText: {
    fontWeight: '800',
  },
  heroCard: {
    borderRadius: 24,
    padding: 16,
    flexDirection: 'row',
    alignItems: 'flex-start',
    justifyContent: 'space-between',
    gap: 12,
  },
  heroTextBlock: {
    flex: 1,
  },
  heroTitle: {
    fontSize: 26,
    lineHeight: 30,
    fontWeight: '900',
    letterSpacing: -0.5,
  },
  heroSubtitle: {
    marginTop: 6,
    fontSize: 13,
    lineHeight: 19,
  },
  heroBadge: {
    borderRadius: 999,
    paddingHorizontal: 12,
    paddingVertical: 8,
  },
  heroBadgeText: {
    fontWeight: '900',
    fontSize: 12,
  },
  tabCard: {
    borderRadius: 20,
    borderWidth: 1,
    paddingVertical: 8,
    paddingHorizontal: 8,
  },
  tabRow: {
    gap: 8,
    alignItems: 'center',
  },
  tabPill: {
    minWidth: 100,
    paddingHorizontal: 14,
    paddingVertical: 11,
    borderRadius: 16,
    alignItems: 'center',
    justifyContent: 'center',
  },
  tabPillActive: {
  },
  tabText: {
    fontSize: 12,
    fontWeight: '800',
  },
  tabTextActive: {
  },
  tabUnderline: {
    width: '80%',
    height: 3,
    borderRadius: 999,
    marginTop: 6,
  },
  detailSection: {
    gap: 12,
  },
  sectionBlockInner: {
    gap: 10,
  },
  summaryGrid: {
    flexDirection: 'row',
    flexWrap: 'wrap',
    gap: 10,
  },
  summaryChip: {
    width: '48%',
    borderRadius: 16,
    padding: 12,
    borderWidth: 1,
    gap: 4,
  },
  summaryLabel: {
    fontSize: 12,
    fontWeight: '700',
  },
  summaryValue: {
    fontSize: 18,
    fontWeight: '900',
  },
  activityCard: {
    borderRadius: 14,
    padding: 12,
    borderWidth: 1,
  },
  activityTopRow: {
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'space-between',
    gap: 12,
  },
  activityTextWrap: {
    flex: 1,
  },
  activityTitle: {
    fontWeight: '800',
    fontSize: 15,
  },
  activityMeta: {
    fontSize: 12,
    marginTop: 3,
  },
  activityDate: {
    color: '#15803d',
    fontSize: 12,
    fontWeight: '700',
    marginTop: 4,
  },
  linkPill: {
    paddingHorizontal: 10,
    paddingVertical: 6,
    borderRadius: 999,
  },
  linkPillText: {
    fontSize: 11,
    fontWeight: '800',
  },
  calloutCard: {
    borderRadius: 16,
    padding: 14,
    borderWidth: 1,
    gap: 4,
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
  filePickerCard: {
    borderRadius: 14,
    borderWidth: 1,
    padding: 14,
    gap: 10,
  },
  submitButton: {
    paddingVertical: 13,
    borderRadius: 14,
    alignItems: 'center',
  },
  submitButtonText: {
    color: '#fff',
    fontWeight: '800',
  },
  submitButtonSmall: {
    paddingHorizontal: 12,
    paddingVertical: 9,
    borderRadius: 12,
  },
  submitButtonSmallText: {
    color: '#fff',
    fontWeight: '800',
    fontSize: 12,
  },
  modalBackdrop: {
    flex: 1,
    backgroundColor: 'rgba(0,0,0,0.55)',
    justifyContent: 'center',
    padding: 16,
  },
  modalCard: {
    borderRadius: 22,
    padding: 16,
    borderWidth: 1,
    gap: 12,
  },
  modalTitle: {
    fontSize: 18,
    fontWeight: '900',
  },
  modalBody: {
    lineHeight: 20,
  },
  modalSub: {
    fontSize: 12,
  },
  modalScroll: {
    maxHeight: 220,
  },
  modalGap: {
    gap: 10,
  },
  replyCard: {
    borderRadius: 14,
    padding: 12,
    borderWidth: 1,
    gap: 4,
  },
  replyName: {
    fontWeight: '800',
  },
  replyBody: {
  },
  modalCloseButton: {
    alignSelf: 'flex-start',
    paddingHorizontal: 14,
    paddingVertical: 10,
    borderRadius: 14,
  },
  modalCloseText: {
    fontWeight: '800',
  },
});
