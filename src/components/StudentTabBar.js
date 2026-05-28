import { useState } from 'react';
import { Image, Pressable, StyleSheet, Text, View } from 'react-native';

const tabs = [
  { key: 'dashboard', icon: 'dashboard' },
  { key: 'courses', icon: 'courses' },
  { key: 'tasks', icon: 'tasks' },
  { key: 'messages', icon: 'messages' },
  { key: 'grades', icon: 'grades' },
  { key: 'notifications', icon: 'notifications' },
];

export default function StudentTabBar({
  activeTab,
  onTabChange,
  onMenuPress,
  onSearchPress,
  onProfilePress,
  user,
  theme,
}) {
  const [layoutWidth, setLayoutWidth] = useState(0);

  const activeIndex = Math.max(
    0,
    tabs.findIndex((tab) => tab.key === activeTab)
  );
  const cellWidth = layoutWidth / tabs.length;
  const indicatorWidth = 28;
  const indicatorOffset = activeIndex * cellWidth + (cellWidth - indicatorWidth) / 2;

  return (
    <View style={[styles.chrome, { backgroundColor: theme.background, borderBottomColor: theme.border }]}>
      <View style={[styles.header, { backgroundColor: theme.header }]}>
        <Pressable
          onPress={onMenuPress}
          style={[styles.headerButton, { backgroundColor: theme.input, borderColor: theme.border }]}
          android_ripple={{ color: 'rgba(255,255,255,0.08)', borderless: true }}
        >
          <MenuIcon color={theme.text} />
        </Pressable>

        <View style={styles.brandWrap}>
          <View style={styles.brandMark}>
            <Image
              source={require('../../assets/images/scholaria-logo.png')}
              style={styles.logoImage}
              resizeMode="contain"
            />
          </View>
          <Text style={[styles.brandText, { color: theme.accent }]}>Scholaria</Text>
        </View>

        <Pressable
          onPress={onSearchPress}
          style={[styles.headerButton, { backgroundColor: theme.input, borderColor: theme.border }]}
          android_ripple={{ color: 'rgba(255,255,255,0.08)', borderless: true }}
        >
          <SearchIcon color={theme.text} />
        </Pressable>

        <Pressable
          onPress={onProfilePress}
          style={[styles.profileButton, { backgroundColor: theme.accentSoft, borderColor: theme.accent }]}
          android_ripple={{ color: 'rgba(255,255,255,0.08)', borderless: true }}
        >
          <Text style={[styles.profileButtonText, { color: theme.accent }]}>{getInitials(user?.name)}</Text>
        </Pressable>
      </View>

      <View style={[styles.navWrap, { backgroundColor: theme.background }]}>
        <View style={styles.navRow} onLayout={(e) => setLayoutWidth(e.nativeEvent.layout.width)}>
          {tabs.map((tab) => {
            const active = activeTab === tab.key;

            return (
              <Pressable
                key={tab.key}
                onPress={() => onTabChange(tab.key)}
                style={styles.navItem}
              >
                <TabIcon type={tab.icon} active={active} theme={theme} />
              </Pressable>
            );
          })}

          {layoutWidth > 0 && (
            <View
              pointerEvents="none"
              style={[
                styles.indicator,
                {
                  width: indicatorWidth,
                  transform: [{ translateX: indicatorOffset }],
                  backgroundColor: theme.accent,
                },
              ]}
            />
          )}
        </View>
      </View>
    </View>
  );
}

function TabIcon({ type, active, theme }) {
  const color = active ? theme.accent : theme.muted;

  if (type === 'dashboard') {
    return (
      <View style={styles.dashIcon}>
        <View style={[styles.dashMain, { borderColor: color }]} />
        <View style={styles.dashSide}>
          <View style={[styles.dashSmall, { backgroundColor: color }]} />
          <View style={[styles.dashSmall, { backgroundColor: color }]} />
        </View>
      </View>
    );
  }

  if (type === 'courses') {
    return (
      <View style={styles.mortarIcon}>
        <View style={[styles.mortarTop, { backgroundColor: color }]} />
        <View style={[styles.mortarBottom, { borderColor: color }]} />
        <View style={[styles.mortarTassel, { backgroundColor: color }]} />
      </View>
    );
  }

  if (type === 'tasks') {
    return (
      <View style={styles.taskIcon}>
        <View style={[styles.taskLine, { backgroundColor: color, width: 14 }]} />
        <View style={[styles.taskLine, { backgroundColor: color, width: 10 }]} />
        <View style={[styles.taskCheck, { borderColor: color }]} />
      </View>
    );
  }

  if (type === 'messages') {
    return (
      <View style={[styles.bubbleIcon, { borderColor: color }]}>
        <View style={[styles.bubbleTail, { borderTopColor: color }]} />
        <View style={[styles.bubbleDot, { backgroundColor: color }]} />
      </View>
    );
  }

  if (type === 'grades') {
    return (
      <View style={styles.statsIcon}>
        <View style={[styles.statsBar, { height: 8, backgroundColor: color }]} />
        <View style={[styles.statsBar, { height: 14, backgroundColor: color }]} />
        <View style={[styles.statsBar, { height: 11, backgroundColor: color }]} />
      </View>
    );
  }

  return (
    <View style={styles.bellIcon}>
      <View style={[styles.bellTop, { backgroundColor: color }]} />
      <View style={[styles.bellBody, { borderColor: color }]} />
      <View style={[styles.bellClapper, { backgroundColor: color }]} />
    </View>
  );
}

function MenuIcon({ color }) {
  return (
    <View style={styles.menuIcon}>
      <View style={[styles.menuLine, { backgroundColor: color, width: 18 }]} />
      <View style={[styles.menuLine, { backgroundColor: color, width: 12 }]} />
      <View style={[styles.menuLine, { backgroundColor: color, width: 16 }]} />
    </View>
  );
}

function SearchIcon({ color }) {
  return (
    <View style={styles.searchIcon}>
      <View style={[styles.searchRing, { borderColor: color }]} />
      <View style={[styles.searchHandle, { backgroundColor: color }]} />
    </View>
  );
}

function getInitials(name) {
  const parts = String(name || '')
    .trim()
    .split(/\s+/)
    .filter(Boolean);

  if (!parts.length) {
    return 'U';
  }

  return parts
    .slice(0, 2)
    .map((part) => part[0]?.toUpperCase() || '')
    .join('');
}

const stylesValues = {
  page: '#0b0b0b',
  header: '#111111',
  border: '#2d2d2d',
  icon: '#f4f4f4',
  accent: '#15c9a7',
};

const styles = StyleSheet.create({
  chrome: {
    backgroundColor: stylesValues.page,
    borderBottomWidth: 1,
    borderBottomColor: stylesValues.border,
    shadowColor: '#000',
    shadowOpacity: 0.25,
    shadowRadius: 12,
    shadowOffset: { width: 0, height: 4 },
    elevation: 12,
  },
  header: {
    minHeight: 56,
    flexDirection: 'row',
    alignItems: 'center',
    paddingHorizontal: 12,
    backgroundColor: stylesValues.header,
  },
  headerButton: {
    width: 44,
    height: 44,
    borderRadius: 12,
    backgroundColor: '#1a1a1a',
    alignItems: 'center',
    justifyContent: 'center',
    borderWidth: 1,
    borderColor: '#2d2d2d',
  },
  profileButton: {
    width: 44,
    height: 44,
    borderRadius: 22,
    backgroundColor: '#173b47',
    alignItems: 'center',
    justifyContent: 'center',
    marginLeft: 8,
    borderWidth: 1,
    borderColor: stylesValues.accent,
  },
  profileButtonText: {
    color: stylesValues.accent,
    fontSize: 14,
    fontWeight: '900',
    letterSpacing: 0.3,
  },
  brandWrap: {
    flex: 1,
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'center',
    gap: 10,
  },
  brandMark: {
    width: 30,
    height: 30,
    borderRadius: 15,
    alignItems: 'center',
    justifyContent: 'center',
  },
  logoImage: {
    width: 24,
    height: 24,
  },
  brandMarkText: {
    color: stylesValues.accent,
    fontSize: 18,
    fontWeight: '900',
  },
  brandText: {
    color: stylesValues.accent,
    fontSize: 22,
    fontWeight: '900',
    letterSpacing: -0.3,
  },
  navWrap: {
    backgroundColor: stylesValues.page,
    paddingHorizontal: 6,
    paddingTop: 4,
  },
  navRow: {
    flexDirection: 'row',
    alignItems: 'center',
    height: 48,
    position: 'relative',
  },
  navItem: {
    flex: 1,
    alignItems: 'center',
    justifyContent: 'center',
    height: 48,
  },
  indicator: {
    position: 'absolute',
    left: 0,
    bottom: 0,
    height: 3,
    borderRadius: 999,
    backgroundColor: stylesValues.accent,
  },
  dashIcon: {
    width: 24,
    height: 24,
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'space-between',
    padding: 2,
  },
  dashMain: {
    width: 12,
    height: 18,
    borderWidth: 2,
    borderRadius: 2,
  },
  dashSide: {
    gap: 4,
  },
  dashSmall: {
    width: 6,
    height: 7,
    borderRadius: 1,
  },
  mortarIcon: {
    width: 22,
    height: 20,
    alignItems: 'center',
    justifyContent: 'center',
  },
  mortarTop: {
    width: 20,
    height: 10,
    transform: [{ rotate: '45deg' }, { scaleX: 1.4 }],
    borderRadius: 1,
  },
  mortarBottom: {
    width: 12,
    height: 6,
    borderBottomWidth: 2,
    borderLeftWidth: 2,
    borderRightWidth: 2,
    marginTop: -2,
    borderBottomLeftRadius: 4,
    borderBottomRightRadius: 4,
  },
  mortarTassel: {
    position: 'absolute',
    right: 0,
    top: 8,
    width: 2,
    height: 6,
  },
  taskIcon: {
    width: 24,
    height: 24,
    justifyContent: 'center',
    gap: 4,
  },
  taskLine: {
    height: 2,
    borderRadius: 1,
  },
  taskCheck: {
    position: 'absolute',
    right: 0,
    top: 4,
    width: 8,
    height: 4,
    borderLeftWidth: 2,
    borderBottomWidth: 2,
    transform: [{ rotate: '-45deg' }],
  },
  bubbleIcon: {
    width: 22,
    height: 18,
    borderWidth: 2,
    borderRadius: 6,
    alignItems: 'center',
    justifyContent: 'center',
  },
  bubbleDot: {
    width: 4,
    height: 4,
    borderRadius: 2,
  },
  bubbleTail: {
    position: 'absolute',
    bottom: -6,
    left: 6,
    width: 0,
    height: 0,
    borderLeftWidth: 3,
    borderRightWidth: 3,
    borderTopWidth: 5,
    borderLeftColor: 'transparent',
    borderRightColor: 'transparent',
  },
  statsIcon: {
    width: 24,
    height: 24,
    flexDirection: 'row',
    alignItems: 'flex-end',
    justifyContent: 'space-between',
    paddingHorizontal: 2,
  },
  statsBar: {
    width: 4,
    borderRadius: 2,
  },
  bellIcon: {
    width: 22,
    height: 22,
    alignItems: 'center',
    justifyContent: 'center',
  },
  bellTop: {
    width: 4,
    height: 4,
    borderRadius: 2,
    marginBottom: -2,
  },
  bellBody: {
    width: 14,
    height: 12,
    borderWidth: 2,
    borderTopLeftRadius: 7,
    borderTopRightRadius: 7,
    borderBottomWidth: 3,
  },
  bellClapper: {
    width: 4,
    height: 4,
    borderRadius: 2,
    marginTop: -1,
  },
  menuIcon: {
    width: 18,
    height: 14,
    justifyContent: 'space-between',
  },
  menuLine: {
    width: 18,
    height: 2,
    borderRadius: 999,
  },
  searchIcon: {
    width: 18,
    height: 18,
    alignItems: 'center',
    justifyContent: 'center',
  },
  searchRing: {
    width: 14,
    height: 14,
    borderWidth: 2,
    borderRadius: 99,
  },
  searchHandle: {
    position: 'absolute',
    right: 0,
    bottom: 1,
    width: 7,
    height: 2,
    borderRadius: 999,
    transform: [{ rotate: '45deg' }],
  },
});
