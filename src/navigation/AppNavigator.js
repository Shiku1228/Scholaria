import { StatusBar } from 'expo-status-bar';
import { useEffect, useState } from 'react';
import { ActivityIndicator, Alert, Modal, Pressable, ScrollView, StyleSheet, Text, View } from 'react-native';
import { SafeAreaView } from 'react-native-safe-area-context';

import { login, logout, restoreSession } from '@/api/auth';
import { isStudentUser, STUDENT_ACCESS_DENIED_MESSAGE } from '@/utils/userRole';
import { clearSession } from '@/utils/session';
import StudentTabBar from '@/components/StudentTabBar';
import { darkTheme, lightTheme } from '@/constants/colors';
import AssignmentSubmitScreen from '@/screens/AssignmentSubmitScreen';
import ChatScreen from '@/screens/ChatScreen';
import DashboardScreen from '@/screens/DashboardScreen';
import ExamScreen from '@/screens/ExamScreen';
import LoginScreen from '@/screens/LoginScreen';
import MessageCourseScreen from '@/screens/MessageCourseScreen';
import ProfileScreen from '@/screens/ProfileScreen';
import QuizScreen from '@/screens/QuizScreen';
import SettingsScreen from '@/screens/SettingsScreen';
import StudentCoursesScreen from '@/screens/StudentCoursesScreen';
import StudentGradesScreen from '@/screens/StudentGradesScreen';
import StudentNotificationsScreen from '@/screens/StudentNotificationsScreen';
import StudentTasksScreen from '@/screens/StudentTasksScreen';

export default function AppNavigator() {
  const [booting, setBooting] = useState(true);
  const [session, setSession] = useState(null);
  const [activeTab, setActiveTab] = useState('dashboard');
  const [drawerOpen, setDrawerOpen] = useState(false);
  const [selectedItem, setSelectedItem] = useState(null);
  const [isDarkMode, setIsDarkMode] = useState(true);

  const theme = isDarkMode ? darkTheme : lightTheme;
  const toggleTheme = () => setIsDarkMode(!isDarkMode);

  useEffect(() => {
    let mounted = true;

    (async () => {
      try {
        const stored = await restoreSession();
        if (!mounted) return;

        if (stored?.token && !isStudentUser(stored.user)) {
          await clearSession();
          setSession(null);
          return;
        }

        setSession(stored);
      } finally {
        if (mounted) {
          setBooting(false);
        }
      }
    })();

    return () => {
      mounted = false;
    };
  }, []);

  const handleLoginSuccess = (sessionData) => {
    if (!sessionData?.token || !isStudentUser(sessionData.user)) {
      Alert.alert('Access denied', STUDENT_ACCESS_DENIED_MESSAGE);
      clearSession();
      setSession(null);
      return;
    }
    setSession(sessionData);
  };

  const handleLogin = async (email, password) => {
    // This function now returns the result, which could be an MFA requirement or the full session
    return await login(email, password);
  };

  const handleLogout = async () => {
    await logout(session?.token);
    setSession(null);
    setActiveTab('dashboard');
    setDrawerOpen(false);
  };

  const openTabFromDrawer = (tab) => {
    setActiveTab(tab);
    setDrawerOpen(false);
  };

  if (booting) {
    return (
      <SafeAreaView style={[styles.loadingScreen, { backgroundColor: theme.background }]}>
        <ActivityIndicator size="large" />
        <Text style={[styles.loadingText, { color: theme.text }]}>Starting Scholaria...</Text>
      </SafeAreaView>
    );
  }

  if (!session?.token) {
    return (
      <View style={[styles.flex, { backgroundColor: theme.background }]}>
        <LoginScreen onLogin={handleLogin} onLoginSuccess={handleLoginSuccess} theme={theme} />
      </View>
    );
  }

  const sharedProps = {
    token: session.token,
    user: session.user,
    theme: theme,
  };

  const renderContent = () => {
    switch (activeTab) {
      case 'settings':
        return <SettingsScreen {...sharedProps} isDarkMode={isDarkMode} onToggleTheme={toggleTheme} setActiveTab={setActiveTab} />;
      case 'profile':
        return <ProfileScreen {...sharedProps} onLogout={handleLogout} setActiveTab={setActiveTab} />;
      case 'courses':
        return <StudentCoursesScreen {...sharedProps} setActiveTab={setActiveTab} setSelectedItem={setSelectedItem} />;
      case 'tasks':
        return <StudentTasksScreen {...sharedProps} setActiveTab={setActiveTab} setSelectedItem={setSelectedItem} />;
      case 'messages':
        return <MessageCourseScreen {...sharedProps} setActiveTab={setActiveTab} setSelectedItem={setSelectedItem} />;
      case 'chat':
        return <ChatScreen {...sharedProps} item={selectedItem} setActiveTab={setActiveTab} />;
      case 'grades':
        return <StudentGradesScreen {...sharedProps} />;
      case 'notifications':
        return <StudentNotificationsScreen {...sharedProps} />;
      case 'assignmentSubmit':
        return <AssignmentSubmitScreen {...sharedProps} item={selectedItem} setActiveTab={setActiveTab} />;
      case 'quiz':
        return <QuizScreen {...sharedProps} item={selectedItem} setActiveTab={setActiveTab} />;
      case 'exam':
        return <ExamScreen {...sharedProps} item={selectedItem} setActiveTab={setActiveTab} />;
      default:
        return <DashboardScreen {...sharedProps} onNavigate={setActiveTab} onLogout={handleLogout} />;
    }
  };

  const isFullscreenAssessment = activeTab === 'quiz' || activeTab === 'exam' || activeTab === 'chat' || activeTab === 'profile' || activeTab === 'settings';

  return (
    <SafeAreaView style={[styles.appShell, { backgroundColor: theme.background }]}>
      <StatusBar style="light" backgroundColor={isDarkMode ? theme.header : theme.accent} translucent={false} />

      {!isFullscreenAssessment ? (
        <StudentTabBar
          activeTab={activeTab}
          user={session.user}
          theme={theme}
          onTabChange={(tab) => {
            setActiveTab(tab);
            setDrawerOpen(false);
          }}
          onMenuPress={() => setDrawerOpen(true)}
          onSearchPress={() => Alert.alert('Search', 'Search is not wired yet.')}
          onProfilePress={() => setActiveTab('profile')}
        />
      ) : null}

      <View style={styles.content}>{renderContent()}</View>

      {!isFullscreenAssessment ? (
        <Modal visible={drawerOpen} animationType="fade" transparent onRequestClose={() => setDrawerOpen(false)}>
          <View style={styles.drawerBackdrop}>
            <Pressable style={StyleSheet.absoluteFill} onPress={() => setDrawerOpen(false)} />
            <Pressable style={[styles.drawerPanel, { backgroundColor: theme.card, borderRightColor: theme.border }]} onPress={() => {}}>
              <View style={[styles.drawerHeader, { borderBottomColor: theme.border }]}>
                <View style={[styles.drawerAvatar, { backgroundColor: theme.accentSoft, borderColor: theme.accent }]}>
                  <Text style={[styles.drawerAvatarText, { color: theme.accent }]}>
                    {getInitials(session?.user?.name)}
                  </Text>
                </View>
                <View style={styles.drawerHeaderInfo}>
                  <Text style={[styles.drawerTitle, { color: theme.text }]} numberOfLines={1}>
                    {session?.user?.name || 'Student'}
                  </Text>
                  <Text style={[styles.drawerSubtitle, { color: theme.muted }]} numberOfLines={1}>
                    {session?.user?.email || 'Student Account'}
                  </Text>
                </View>
              </View>

              <ScrollView style={styles.drawerScroll} showsVerticalScrollIndicator={false}>
                <DrawerLink theme={theme} label="Dashboard" icon="D" onPress={() => openTabFromDrawer('dashboard')} />
                <DrawerLink theme={theme} label="Courses" icon="C" onPress={() => openTabFromDrawer('courses')} />
                <DrawerLink theme={theme} label="Tasks" icon="T" onPress={() => openTabFromDrawer('tasks')} />
                <DrawerLink theme={theme} label="Messages" icon="M" onPress={() => openTabFromDrawer('messages')} />
                <DrawerLink theme={theme} label="Grades" icon="G" onPress={() => openTabFromDrawer('grades')} />
                <DrawerLink theme={theme} label="Notifications" icon="N" onPress={() => openTabFromDrawer('notifications')} />
                <DrawerLink theme={theme} label="Profile" icon="P" onPress={() => openTabFromDrawer('profile')} />
                <DrawerLink theme={theme} label="Settings" icon="S" onPress={() => openTabFromDrawer('settings')} />
              </ScrollView>

              <View style={[styles.drawerDivider, { backgroundColor: theme.border }]} />
              <DrawerLink
                theme={theme}
                label="Logout"
                danger
                icon="L"
                onPress={async () => {
                  await handleLogout();
                }}
              />
            </Pressable>
          </View>
        </Modal>
      ) : null}
    </SafeAreaView>
  );
}

function DrawerLink({ label, onPress, icon, theme, danger = false }) {
  return (
    <Pressable onPress={onPress} style={({ pressed }) => [styles.drawerLink, pressed && styles.drawerLinkPressed]}>
      <View style={[styles.drawerLinkIcon, { backgroundColor: theme.background, borderColor: theme.border }, danger && styles.drawerLinkIconDanger]}>
        <Text style={[styles.drawerLinkIconText, { color: theme.accent }, danger && styles.drawerLinkTextDanger]}>{icon}</Text>
      </View>
      <Text style={[styles.drawerLinkText, { color: theme.text }, danger && styles.drawerLinkTextDanger]}>{label}</Text>
    </Pressable>
  );
}

function getInitials(name) {
  const parts = String(name || '').trim().split(/\s+/).filter(Boolean);
  if (!parts.length) return 'S';
  return parts.slice(0, 2).map(p => p[0].toUpperCase()).join('');
}

function LogoutIcon({ color }) {
  return (
    <View style={{ width: 16, height: 16, borderLeftWidth: 2, borderBottomWidth: 2, borderTopWidth: 2, borderColor: color }}>
      <View style={{ position: 'absolute', right: -4, top: 6, width: 8, height: 2, backgroundColor: color }} />
    </View>
  );
}

const stylesValues = {
  teal: '#15c9a7',
};

const styles = StyleSheet.create({
  flex: {
    flex: 1,
  },
  appShell: {
    flex: 1,
  },
  content: {
    flex: 1,
  },
  drawerBackdrop: {
    flex: 1,
    backgroundColor: 'rgba(0,0,0,0.45)',
    justifyContent: 'flex-start',
  },
  drawerPanel: {
    width: '78%',
    maxWidth: 320,
    minHeight: '100%',
    backgroundColor: '#111111',
    paddingHorizontal: 18,
    paddingTop: 28,
    borderRightWidth: 1,
    borderRightColor: '#2d2d2d',
  },
  drawerHeader: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 12,
    paddingBottom: 20,
    marginBottom: 10,
    borderBottomWidth: 1,
    borderBottomColor: '#222',
  },
  drawerAvatar: {
    width: 54,
    height: 54,
    borderRadius: 27,
    backgroundColor: '#173b47',
    alignItems: 'center',
    justifyContent: 'center',
    borderWidth: 1.5,
    borderColor: '#15c9a7',
  },
  drawerAvatarText: {
    color: '#15c9a7',
    fontSize: 20,
    fontWeight: '900',
  },
  drawerHeaderInfo: {
    flex: 1,
  },
  drawerTitle: {
    color: '#f4f4f4',
    fontSize: 18,
    fontWeight: '800',
  },
  drawerSubtitle: {
    color: '#B0B4BA',
    fontSize: 12,
    marginTop: 2,
  },
  drawerScroll: {
    flex: 1,
  },
  drawerLink: {
    paddingVertical: 14,
    flexDirection: 'row',
    alignItems: 'center',
    gap: 14,
  },
  drawerLinkPressed: {
    opacity: 0.6,
  },
  drawerLinkIcon: {
    width: 32,
    height: 32,
    borderRadius: 8,
    backgroundColor: '#161616',
    alignItems: 'center',
    justifyContent: 'center',
    borderWidth: 1,
    borderColor: '#2d2d2d',
  },
  drawerLinkIconDanger: {
    borderColor: '#ff7d7d22',
    backgroundColor: '#3b1c1c22',
  },
  drawerLinkIconText: {
    color: '#15c9a7',
    fontSize: 12,
    fontWeight: '900',
  },
  drawerLinkText: {
    color: '#f4f4f4',
    fontSize: 16,
    fontWeight: '700',
  },
  drawerLinkTextDanger: {
    color: '#ff7d7d',
  },
  drawerDivider: {
    height: 1,
    backgroundColor: '#2d2d2d',
    marginVertical: 12,
  },
  loadingScreen: {
    flex: 1,
    alignItems: 'center',
    justifyContent: 'center',
    gap: 12,
  },
  loadingText: {
    fontSize: 16,
    fontWeight: '600',
  },
});
