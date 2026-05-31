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
  const c = active ? theme.accent : theme.muted;

  if (type === 'dashboard') {
    return (
      <View style={{ width: 22, height: 22, flexDirection: 'row', flexWrap: 'wrap', gap: 3, padding: 1 }}>
        {[0, 1, 2, 3].map((i) => (
          <View key={i} style={{ width: 8, height: 8, borderRadius: 2, backgroundColor: c }} />
        ))}
      </View>
    );
  }

  if (type === 'courses') {
    return (
      <View style={{ width: 22, height: 20 }}>
        <View style={{ position: 'absolute', left: 0, top: 0, width: 9, height: 20, borderWidth: 2, borderColor: c, borderTopLeftRadius: 3, borderBottomLeftRadius: 3 }}>
          <View style={{ position: 'absolute', top: 4, left: 2, right: 1, height: 1.5, backgroundColor: c, borderRadius: 1 }} />
          <View style={{ position: 'absolute', top: 8, left: 2, right: 1, height: 1.5, backgroundColor: c, borderRadius: 1 }} />
          <View style={{ position: 'absolute', top: 12, left: 2, right: 1, height: 1.5, backgroundColor: c, borderRadius: 1 }} />
        </View>
        <View style={{ position: 'absolute', left: 9, top: 0, width: 4, height: 20, backgroundColor: c, borderRadius: 1 }} />
        <View style={{ position: 'absolute', right: 0, top: 0, width: 9, height: 20, borderWidth: 2, borderColor: c, borderTopRightRadius: 3, borderBottomRightRadius: 3 }}>
          <View style={{ position: 'absolute', top: 4, left: 1, right: 2, height: 1.5, backgroundColor: c, borderRadius: 1 }} />
          <View style={{ position: 'absolute', top: 8, left: 1, right: 2, height: 1.5, backgroundColor: c, borderRadius: 1 }} />
          <View style={{ position: 'absolute', top: 12, left: 1, right: 2, height: 1.5, backgroundColor: c, borderRadius: 1 }} />
        </View>
      </View>
    );
  }

  if (type === 'tasks') {
    return (
      <View style={{ width: 18, height: 22, borderWidth: 2, borderColor: c, borderRadius: 4 }}>
        <View style={{ position: 'absolute', top: 5, left: 3, right: 3, height: 2, backgroundColor: c, borderRadius: 1 }} />
        <View style={{ position: 'absolute', top: 10, left: 3, right: 3, height: 2, backgroundColor: c, borderRadius: 1 }} />
        <View style={{ position: 'absolute', top: 15, left: 3, right: 6, height: 2, backgroundColor: c, borderRadius: 1 }} />
      </View>
    );
  }

  if (type === 'messages') {
    return (
      <View style={{ width: 22, height: 22 }}>
        <View style={{ position: 'absolute', top: 0, left: 0, right: 0, height: 16, borderWidth: 2, borderColor: c, borderRadius: 8 }}>
          <View style={{ position: 'absolute', top: 4, left: 4, flexDirection: 'row', gap: 3 }}>
            <View style={{ width: 3, height: 3, borderRadius: 1.5, backgroundColor: c }} />
            <View style={{ width: 3, height: 3, borderRadius: 1.5, backgroundColor: c }} />
            <View style={{ width: 3, height: 3, borderRadius: 1.5, backgroundColor: c }} />
          </View>
        </View>
        <View style={{ position: 'absolute', bottom: 0, left: 5, width: 0, height: 0, borderLeftWidth: 4, borderRightWidth: 4, borderTopWidth: 6, borderLeftColor: 'transparent', borderRightColor: 'transparent', borderTopColor: c }} />
      </View>
    );
  }

  if (type === 'grades') {
    return (
      <View style={{ width: 22, height: 22, flexDirection: 'row', alignItems: 'flex-end', justifyContent: 'space-between' }}>
        <View style={{ width: 5, height: 10, backgroundColor: c, borderTopLeftRadius: 2, borderTopRightRadius: 2 }} />
        <View style={{ width: 5, height: 18, backgroundColor: c, borderTopLeftRadius: 2, borderTopRightRadius: 2 }} />
        <View style={{ width: 5, height: 14, backgroundColor: c, borderTopLeftRadius: 2, borderTopRightRadius: 2 }} />
        <View style={{ position: 'absolute', bottom: 0, left: 0, right: 0, height: 2, backgroundColor: c, borderRadius: 1 }} />
      </View>
    );
  }

  return (
    <View style={{ width: 22, height: 22, alignItems: 'center' }}>
      <View style={{ width: 4, height: 4, borderRadius: 2, backgroundColor: c }} />
      <View style={{ marginTop: -2, width: 16, height: 11, borderWidth: 2, borderColor: c, borderTopLeftRadius: 8, borderTopRightRadius: 8, borderBottomWidth: 0 }} />
      <View style={{ width: 18, height: 2, backgroundColor: c, borderRadius: 1 }} />
      <View style={{ marginTop: 1, width: 5, height: 5, borderRadius: 2.5, borderWidth: 2, borderColor: c }} />
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
