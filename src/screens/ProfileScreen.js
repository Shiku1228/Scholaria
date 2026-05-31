import {
  Dimensions,
  Pressable,
  ScrollView,
  StyleSheet,
  Text,
  View
} from 'react-native';

import { darkTheme as colors } from '@/constants/colors';

const { width } = Dimensions.get('window');

export default function ProfileScreen({ user, onLogout, setActiveTab, theme }) {
  const initials = String(user?.name || 'S')
    .split(' ')
    .map((n) => n[0])
    .join('')
    .toUpperCase()
    .slice(0, 2);

  return (
    <ScrollView style={[styles.screen, { backgroundColor: theme.background }]} showsVerticalScrollIndicator={false}>
      {/* Header Section (Facebook Style) */}
      <View style={styles.headerContainer}>
        {/* Cover Photo Placeholder */}
        <View style={styles.coverPhoto}>
          <View style={[styles.coverPlaceholder, { backgroundColor: theme.accentSoft }]} />
        </View>

        {/* Profile Picture Overlap */}
        <View style={styles.avatarContainer}>
          <View style={[styles.avatarBorder, { backgroundColor: theme.background }]}>
            <View style={[styles.avatarMain, { backgroundColor: theme.accentSoft, borderColor: theme.accent }]}>
              <Text style={[styles.avatarText, { color: theme.accent }]}>{initials}</Text>
            </View>
          </View>
        </View>
      </View>

      {/* Name and Bio */}
      <View style={styles.nameSection}>
        <Text style={[styles.nameText, { color: theme.text }]}>{user?.name || 'Student Name'}</Text>
        <Text style={[styles.bioText, { color: theme.muted }]}>Scholaria Student • Active Learner</Text>

        <View style={styles.actionRow}>
          <Pressable style={[styles.primaryButton, { backgroundColor: theme.accent }]}>
            <Text style={[styles.primaryButtonText, { color: theme.background }]}>Edit Profile</Text>
          </Pressable>
          <Pressable 
            style={[styles.secondaryButton, { backgroundColor: theme.border }]}
            onPress={() => setActiveTab('dashboard')}
          >
            <Text style={[styles.secondaryButtonText, { color: theme.text }]}>Dashboard</Text>
          </Pressable>
        </View>
      </View>

      <View style={[styles.divider, { backgroundColor: theme.border }]} />

      {/* Info Sections */}
      <View style={styles.contentPadding}>
        <Text style={[styles.sectionTitle, { color: theme.text }]}>Intro</Text>
        
        <InfoRow theme={theme} icon={<EmailIcon color={theme.accent} />} label="Email" value={user?.email || 'N/A'} />
        <InfoRow theme={theme} icon={<IDIcon color={theme.accent} />} label="Student ID" value={user?.id ? `SCH-${user.id}` : 'N/A'} />
        <InfoRow theme={theme} icon={<SchoolIcon color={theme.accent} />} label="School" value="Scholaria University" />
        <InfoRow theme={theme} icon={<CalendarIcon color={theme.accent} />} label="Joined" value="2024" />

          <View style={styles.statsCard}>
          <Text style={[styles.sectionTitle, { color: theme.text }]}>Account Actions</Text>
          <Pressable style={[styles.logoutButton, { backgroundColor: theme.accentSoft, borderColor: theme.danger }]} onPress={onLogout}>
            <Text style={[styles.logoutButtonText, { color: theme.danger }]}>Log Out</Text>
          </Pressable>
        </View>
      </View>
    </ScrollView>
  );
}

function EmailIcon({ color }) {
  return (
    <View style={styles.emailContainer}>
      <View style={[styles.emailBox, { borderColor: color }]} />
      <View style={[styles.emailLine, { borderLeftColor: color, borderTopColor: color }]} />
    </View>
  );
}

function IDIcon({ color }) {
  return (
    <View style={[styles.idCard, { borderColor: color }]}>
      <View style={[styles.idPhoto, { backgroundColor: color }]} />
      <View style={styles.idLines}>
        <View style={[styles.idLine, { backgroundColor: color, width: 10 }]} />
        <View style={[styles.idLine, { backgroundColor: color, width: 6 }]} />
      </View>
    </View>
  );
}

function SchoolIcon({ color }) {
  return (
    <View style={styles.mortarIcon}>
      <View style={[styles.mortarTop, { backgroundColor: color }]} />
      <View style={[styles.mortarBottom, { borderColor: color }]} />
      <View style={[styles.mortarTassel, { backgroundColor: color }]} />
    </View>
  );
}

function CalendarIcon({ color }) {
  return (
    <View style={[styles.calBox, { borderColor: color }]}>
      <View style={[styles.calHeader, { backgroundColor: color }]} />
      <View style={styles.calGrid}>
        <View style={[styles.calDot, { backgroundColor: color }]} />
        <View style={[styles.calDot, { backgroundColor: color }]} />
      </View>
    </View>
  );
}

function InfoRow({ icon, label, value, theme }) {
  return (
    <View style={styles.infoRow}>
      <View style={styles.iconWrapper}>
        {icon}
      </View>
      <View>
        <Text style={[styles.infoLabel, { color: theme.muted }]}>{label}</Text>
        <Text style={[styles.infoValue, { color: theme.text }]}>{value}</Text>
      </View>
    </View>
  );
}

const styles = StyleSheet.create({
  screen: {
    flex: 1,
  },
  headerContainer: {
    height: 240,
    width: '100%',
  },
  coverPhoto: {
    height: 180,
    width: '100%',
    backgroundColor: '#1c1c1c',
  },
  coverPlaceholder: {
    flex: 1,
  },
  avatarContainer: {
    position: 'absolute',
    bottom: 0,
    left: 20,
  },
  avatarBorder: {
    padding: 4,
    borderRadius: 85,
  },
  avatarMain: {
    width: 150,
    height: 150,
    borderRadius: 75,
    backgroundColor: '#173b47',
    alignItems: 'center',
    justifyContent: 'center',
    borderWidth: 2,
  },
  avatarText: {
    fontSize: 48,
    fontWeight: '900',
  },
  nameSection: {
    paddingHorizontal: 20,
    marginTop: 10,
  },
  nameText: {
    fontSize: 28,
    fontWeight: '800',
  },
  bioText: {
    fontSize: 15,
    marginTop: 4,
  },
  actionRow: {
    flexDirection: 'row',
    gap: 10,
    marginTop: 20,
  },
  primaryButton: {
    flex: 1,
    paddingVertical: 10,
    borderRadius: 8,
    alignItems: 'center',
  },
  primaryButtonText: {
    fontWeight: '700',
  },
  secondaryButton: {
    flex: 1,
    backgroundColor: '#2a2a2a',
    paddingVertical: 10,
    borderRadius: 8,
    alignItems: 'center',
  },
  secondaryButtonText: {
    fontWeight: '700',
  },
  divider: {
    height: 1,
    marginVertical: 20,
    marginHorizontal: 20,
  },
  contentPadding: {
    paddingHorizontal: 20,
    paddingBottom: 40,
  },
  sectionTitle: {
    fontSize: 20,
    fontWeight: '800',
    marginBottom: 15,
  },
  infoRow: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 15,
    marginBottom: 18,
  },
  iconWrapper: {
    width: 32,
    height: 32,
    alignItems: 'center',
    justifyContent: 'center',
  },
  emailContainer: {
    width: 22,
    height: 16,
    justifyContent: 'center',
    alignItems: 'center',
  },
  emailBox: {
    width: 20,
    height: 14,
    borderWidth: 2,
    borderRadius: 2,
  },
  emailLine: {
    position: 'absolute',
    top: 2,
    width: 10,
    height: 10,
    borderLeftWidth: 2,
    borderTopWidth: 2,
    transform: [{ rotate: '225deg' }],
  },
  idCard: {
    width: 22,
    height: 16,
    borderWidth: 2,
    borderRadius: 3,
    flexDirection: 'row',
    padding: 2,
    alignItems: 'center',
    gap: 3,
  },
  idPhoto: {
    width: 5,
    height: 6,
    borderRadius: 1,
  },
  idLines: {
    gap: 2,
  },
  idLine: {
    height: 1.5,
    borderRadius: 1,
  },
  mortarIcon: {
    width: 22,
    height: 18,
    alignItems: 'center',
    justifyContent: 'center',
  },
  mortarTop: {
    width: 18,
    height: 9,
    transform: [{ rotate: '45deg' }, { scaleX: 1.4 }],
    borderRadius: 1,
  },
  mortarBottom: {
    width: 10,
    height: 5,
    borderBottomWidth: 2,
    borderLeftWidth: 2,
    borderRightWidth: 2,
    marginTop: -2,
    borderBottomLeftRadius: 3,
    borderBottomRightRadius: 3,
  },
  mortarTassel: {
    position: 'absolute',
    right: 1,
    top: 7,
    width: 1.5,
    height: 5,
  },
  calBox: {
    width: 18,
    height: 18,
    borderWidth: 2,
    borderRadius: 3,
    overflow: 'hidden',
  },
  calHeader: {
    height: 4,
    width: '100%',
  },
  calGrid: {
    flexDirection: 'row',
    gap: 2,
    padding: 2,
  },
  calDot: { width: 3, height: 3, borderRadius: 1 },
  infoLabel: { color: colors.muted, fontSize: 12 },
  infoValue: { color: colors.text, fontSize: 16, fontWeight: '600' },
  statsCard: { marginTop: 20, gap: 10 },
  logoutButton: {
    backgroundColor: '#3b1c1c',
    paddingVertical: 12,
    borderRadius: 10,
    alignItems: 'center',
    borderWidth: 1,
    borderColor: colors.danger,
  },
  logoutButtonText: { color: colors.danger, fontWeight: '800' },
});