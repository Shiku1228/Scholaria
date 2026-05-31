import { ImageBackground, Pressable, StyleSheet, Text, View } from 'react-native';

/**
 * Layered dark scrim so course title/meta stay readable on light or bright cover images.
 */
function CourseCardImageOverlay({ hasImage }) {
  return (
    <View style={styles.overlayWrap} pointerEvents="none">
      <View
        style={[
          styles.overlayLayer,
          { flex: 2, backgroundColor: hasImage ? 'rgba(0,0,0,0.18)' : 'rgba(0,0,0,0.08)' },
        ]}
      />
      <View
        style={[
          styles.overlayLayer,
          { flex: 2, backgroundColor: hasImage ? 'rgba(0,0,0,0.32)' : 'rgba(0,0,0,0.14)' },
        ]}
      />
      <View
        style={[
          styles.overlayLayer,
          { flex: 3, backgroundColor: hasImage ? 'rgba(0,0,0,0.55)' : 'rgba(0,0,0,0.28)' },
        ]}
      />
    </View>
  );
}

export default function StudentCourseListCard({
  course,
  coverImageUri,
  theme,
  active,
  onPress,
  formatEnrollmentStatus,
  normalizeEnrollmentStatus,
}) {
  const progress = Math.max(0, Math.min(100, Number(course.progress) || 0));
  const hasImage = Boolean(coverImageUri);
  const courseStatus = normalizeEnrollmentStatus(course.enrollment_status);

  const previewContent = (
    <>
      <CourseCardImageOverlay hasImage={hasImage} />
      <View style={styles.previewContent}>
        <View style={styles.textScrim}>
          <Text style={styles.courseTitle} numberOfLines={2}>
            {course.course_name || 'Course'}
          </Text>
          <Text style={styles.courseMeta} numberOfLines={1}>
            {course.course_number || 'No course number'} · {course.teacher_name || 'No teacher'}
          </Text>
        </View>
        <View
          style={[
            styles.progressBadge,
            active ? { backgroundColor: theme.accent, borderColor: theme.accent } : styles.progressBadgeDefault,
          ]}
        >
          <Text style={[styles.progressBadgeText, active && { color: theme.background }]}>{progress}%</Text>
        </View>
      </View>
    </>
  );

  return (
    <Pressable
      onPress={onPress}
      style={({ pressed }) => [
        styles.card,
        {
          backgroundColor: theme.card,
          borderColor: active ? theme.accent : theme.border,
          shadowColor: theme.accent,
        },
        pressed && styles.cardPressed,
      ]}
    >
      <View style={styles.previewFrame}>
        {hasImage ? (
          <ImageBackground
            source={{ uri: coverImageUri }}
            style={styles.preview}
            imageStyle={styles.previewImage}
            resizeMode="cover"
          >
            {previewContent}
          </ImageBackground>
        ) : (
          <View style={[styles.preview, styles.previewFallback, { backgroundColor: theme.accentSoft }]}>
            {previewContent}
          </View>
        )}
      </View>

      <View style={[styles.body, { backgroundColor: theme.accentSoftAlt, borderTopColor: theme.border }]}>
        <View style={styles.progressRow}>
          <View style={[styles.progressTrack, { backgroundColor: theme.border }]}>
            <View style={[styles.progressFill, { width: `${progress}%`, backgroundColor: theme.accent }]} />
          </View>
          <Text style={[styles.progressLabel, { color: theme.muted }]}>{progress}%</Text>
        </View>

        {courseStatus ? (
          <View style={styles.statusRow}>
            <View
              style={[
                styles.statusPill,
                { backgroundColor: theme.background, borderColor: theme.border },
                courseStatus === 'active' && { borderColor: theme.accent, backgroundColor: theme.accentSoft },
              ]}
            >
              <Text
                style={[
                  styles.statusPillText,
                  { color: theme.muted },
                  courseStatus === 'active' && { color: theme.accent },
                ]}
              >
                {formatEnrollmentStatus(courseStatus)}
              </Text>
            </View>
          </View>
        ) : null}

        <Text style={[styles.hint, { color: theme.accent }]}>Tap to open course activity</Text>
      </View>
    </Pressable>
  );
}

const styles = StyleSheet.create({
  card: {
    overflow: 'hidden',
    borderRadius: 20,
    borderWidth: 1,
    marginBottom: 14,
    shadowOffset: { width: 0, height: 6 },
    shadowOpacity: 0.22,
    shadowRadius: 10,
    elevation: 6,
  },
  cardPressed: {
    opacity: 0.92,
    transform: [{ scale: 0.995 }],
  },
  previewFrame: {
    overflow: 'hidden',
  },
  preview: {
    height: 132,
    justifyContent: 'flex-end',
  },
  previewImage: {
    borderTopLeftRadius: 19,
    borderTopRightRadius: 19,
  },
  previewFallback: {
    borderTopLeftRadius: 19,
    borderTopRightRadius: 19,
  },
  overlayWrap: {
    ...StyleSheet.absoluteFillObject,
    flexDirection: 'column',
  },
  overlayLayer: {
    width: '100%',
  },
  previewContent: {
    flex: 1,
    flexDirection: 'row',
    alignItems: 'flex-end',
    justifyContent: 'space-between',
    gap: 10,
    paddingHorizontal: 14,
    paddingBottom: 12,
    paddingTop: 14,
  },
  textScrim: {
    flex: 1,
    paddingHorizontal: 10,
    paddingVertical: 8,
    borderRadius: 12,
    backgroundColor: 'rgba(0,0,0,0.38)',
    borderWidth: 1,
    borderColor: 'rgba(255,255,255,0.08)',
  },
  courseTitle: {
    color: '#FFFFFF',
    fontSize: 17,
    fontWeight: '900',
    lineHeight: 22,
    textShadowColor: 'rgba(0,0,0,0.85)',
    textShadowOffset: { width: 0, height: 1 },
    textShadowRadius: 6,
  },
  courseMeta: {
    color: 'rgba(255,255,255,0.92)',
    fontSize: 12,
    marginTop: 4,
    fontWeight: '600',
    textShadowColor: 'rgba(0,0,0,0.75)',
    textShadowOffset: { width: 0, height: 1 },
    textShadowRadius: 4,
  },
  progressBadge: {
    minWidth: 54,
    height: 32,
    paddingHorizontal: 12,
    borderRadius: 999,
    alignItems: 'center',
    justifyContent: 'center',
    borderWidth: 1,
  },
  progressBadgeDefault: {
    backgroundColor: 'rgba(0,0,0,0.55)',
    borderColor: 'rgba(21, 201, 167, 0.45)',
  },
  progressBadgeText: {
    color: '#FFFFFF',
    fontWeight: '900',
    fontSize: 12,
  },
  body: {
    paddingHorizontal: 14,
    paddingVertical: 12,
    gap: 8,
    borderTopWidth: 1,
  },
  progressRow: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 10,
  },
  progressTrack: {
    flex: 1,
    height: 6,
    borderRadius: 999,
    overflow: 'hidden',
  },
  progressFill: {
    height: '100%',
    borderRadius: 999,
  },
  progressLabel: {
    fontSize: 11,
    fontWeight: '800',
    minWidth: 32,
    textAlign: 'right',
  },
  statusRow: {
    alignItems: 'flex-start',
  },
  statusPill: {
    paddingHorizontal: 12,
    paddingVertical: 6,
    borderRadius: 999,
    borderWidth: 1,
  },
  statusPillText: {
    fontSize: 11,
    fontWeight: '800',
    textTransform: 'capitalize',
  },
  hint: {
    fontSize: 11,
    fontWeight: '800',
    letterSpacing: 0.3,
  },
});
