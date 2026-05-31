import { Pressable, StyleSheet, Switch, Text, View } from 'react-native';

export default function SettingsScreen({ theme, isDarkMode, onToggleTheme, setActiveTab }) {
  return (
    <View style={[styles.screen, { backgroundColor: theme.background }]}>
      <View style={styles.header}>
        <Pressable onPress={() => setActiveTab('dashboard')} style={styles.backButton}>
          <Text style={{ color: theme.accent, fontWeight: '700' }}>← Back</Text>
        </Pressable>
        <Text style={[styles.title, { color: theme.text }]}>Settings</Text>
      </View>

      <View style={[styles.section, { backgroundColor: theme.card, borderColor: theme.border }]}>
        <View style={styles.row}>
          <View>
            <Text style={[styles.label, { color: theme.text }]}>Dark Mode</Text>
            <Text style={[styles.subLabel, { color: theme.muted }]}>
              Switch between light and dark themes
            </Text>
          </View>
          <Switch
            value={isDarkMode}
            onValueChange={onToggleTheme}
            trackColor={{ false: '#767577', true: theme.accentSoft }}
            thumbColor={isDarkMode ? theme.accent : '#f4f3f4'}
          />
        </View>
      </View>

      <Text style={[styles.footer, { color: theme.muted }]}>
        Scholaria Mobile v1.0.0
      </Text>
    </View>
  );
}

const styles = StyleSheet.create({
  screen: {
    flex: 1,
    padding: 20,
  },
  header: {
    marginTop: 10,
    marginBottom: 30,
    gap: 10,
  },
  backButton: {
    alignSelf: 'flex-start',
  },
  title: {
    fontSize: 32,
    fontWeight: '900',
  },
  section: {
    borderRadius: 20,
    padding: 20,
    borderWidth: 1,
  },
  row: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    alignItems: 'center',
  },
  label: {
    fontSize: 18,
    fontWeight: '700',
  },
  subLabel: {
    fontSize: 14,
    marginTop: 2,
  },
  footer: {
    textAlign: 'center',
    marginTop: 'auto',
    fontSize: 12,
  },
});