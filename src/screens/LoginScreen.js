import { verifyMfa } from '@/api/auth';
import { useState } from 'react';
import {
  ActivityIndicator,
  Image,
  KeyboardAvoidingView,
  Platform,
  Pressable,
  ScrollView,
  StyleSheet,
  Text,
  TextInput,
  View,
} from 'react-native';
import { SafeAreaView } from 'react-native-safe-area-context';

export default function LoginScreen({ onLogin, onLoginSuccess, theme }) {
  const [email, setEmail] = useState('');
  const [password, setPassword] = useState('');
  const [mfaCode, setMfaCode] = useState('');
  const [mfaRequired, setMfaRequired] = useState(false);
  const [tempToken, setTempToken] = useState(null);
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState('');

  const handleSubmit = async () => {
    if (!email.trim() || !password.trim()) {
      setError('Please enter your email and password.');
      return;
    }

    setLoading(true);
    setError('');

    try {
      const result = await onLogin(email.trim(), password);
      
      if (result?.requiresMfa) {
        setMfaRequired(true);
        setTempToken(result.tempToken);
      } else if (onLoginSuccess && result?.token) {
        onLoginSuccess(result);
      }
    } catch (err) {
      setError(err?.message || 'Unable to log in.');
    } finally {
      setLoading(false);
    }
  };

  const handleVerifyMfa = async () => {
    const cleanCode = mfaCode.replace(/\D/g, ''); // Siguraduhin na numbers lang ang ipapadala
    if (cleanCode.length !== 6) {
      setError('Please enter a valid 6-digit code.');
      return;
    }

    setLoading(true);
    setError('');
    try {
      const session = await verifyMfa(cleanCode, tempToken);
      if (onLoginSuccess) {
        onLoginSuccess(session);
      }
    } catch (err) {
      setError(err?.message || 'Verification failed.');
    } finally {
      setLoading(false);
    }
  };

  return (
    <SafeAreaView style={[styles.safeArea, { backgroundColor: theme.background }]}>
      <KeyboardAvoidingView
        behavior={Platform.OS === 'ios' ? 'padding' : undefined}
        style={styles.flex}
      >
        <ScrollView contentContainerStyle={styles.container} keyboardShouldPersistTaps="handled">
          <View style={styles.hero}>
            <Image
              source={require('../../assets/images/scholaria-logo.png')}
              style={styles.logo}
              resizeMode="contain"
            />
            <Text style={[styles.kicker, { color: theme.accent }]}>Scholaria Student</Text>
            <Text style={[styles.title, { color: theme.text }]}>Sign in to continue</Text>
            <Text style={[styles.subtitle, { color: theme.muted }]}>
              Student accounts only. Teachers and admins should use the Scholaria website.
            </Text>
          </View>

          <View style={[styles.card, { backgroundColor: theme.card, borderColor: theme.border }]}>
            {!mfaRequired ? (
              <>
                <Text style={[styles.label, { color: theme.text }]}>Email</Text>
                <TextInput
                  value={email}
                  onChangeText={setEmail}
                  placeholder="you@example.com"
                  placeholderTextColor={theme.muted}
                  autoCapitalize="none"
                  keyboardType="email-address"
                  textContentType="emailAddress"
                  style={[styles.input, { backgroundColor: theme.input, color: theme.text, borderColor: theme.border }]}
                />

                <Text style={[styles.label, styles.spacingTop, { color: theme.text }]}>Password</Text>
                <TextInput
                  value={password}
                  onChangeText={setPassword}
                  placeholder="Your password"
                  placeholderTextColor={theme.muted}
                  secureTextEntry
                  textContentType="password"
                  style={[styles.input, { backgroundColor: theme.input, color: theme.text, borderColor: theme.border }]}
                />
              </>
            ) : (
              <>
                <Text style={[styles.label, { color: theme.text }]}>Verification Code</Text>
                <Text style={[styles.subtitle, { color: theme.muted, fontSize: 13, marginBottom: 5 }]}>
                  Enter the 6-digit code from your authenticator app.
                </Text>
                <TextInput
                  value={mfaCode}
                  onChangeText={setMfaCode}
                  placeholder="000000"
                  placeholderTextColor={theme.muted}
                  keyboardType="number-pad"
                  maxLength={6}
                  style={[styles.input, { backgroundColor: theme.input, color: theme.text, borderColor: theme.border, textAlign: 'center', fontSize: 24, letterSpacing: 8 }]}
                />
              </>
            )}

            {!!error && <Text style={[styles.error, { color: theme.danger }]}>{error}</Text>}

            <Pressable
              onPress={mfaRequired ? handleVerifyMfa : handleSubmit}
              disabled={loading}
              style={({ pressed }) => [
                styles.button, { backgroundColor: theme.accent },
                pressed && !loading ? styles.buttonPressed : null,
                loading ? styles.buttonDisabled : null,
              ]}
            >
              {loading ? (
                <ActivityIndicator color={theme.background} />
              ) : (
                <Text style={[styles.buttonText, { color: theme.background }]}>
                  {mfaRequired ? 'Verify Code' : 'Log in'}
                </Text>
              )}
            </Pressable>

            {mfaRequired && (
              <Pressable onPress={() => { setMfaRequired(false); setError(''); }} style={{ marginTop: 10 }}>
                <Text style={{ color: theme.accent, textAlign: 'center', fontWeight: '600' }}>Cancel</Text>
              </Pressable>
            )}
          </View>

          <View style={styles.note}>
            <Text style={[styles.noteText, { color: theme.muted }]}>
              Only users with the student role can sign in here. If login fails, check your API URL
              and that your backend is running.
            </Text>
          </View>
        </ScrollView>
      </KeyboardAvoidingView>
    </SafeAreaView>
  );
}

const styles = StyleSheet.create({
  flex: {
    flex: 1,
  },
  safeArea: {
    flex: 1,
  },
  container: {
    flexGrow: 1,
    paddingHorizontal: 20,
    paddingVertical: 28,
    justifyContent: 'center',
    gap: 18,
  },
  hero: {
    gap: 10,
  },
  logo: {
    width: 150,
    height: 150,
    alignSelf: 'center',
    marginBottom: 10,
  },
  kicker: {
    textTransform: 'uppercase',
    letterSpacing: 1.5,
    fontSize: 12,
    fontWeight: '700',
  },
  title: {
    fontSize: 32,
    lineHeight: 38,
    fontWeight: '800',
  },
  subtitle: {
    fontSize: 15,
    lineHeight: 22,
  },
  card: {
    borderRadius: 24,
    padding: 20,
    borderWidth: 1,
    gap: 10,
  },
  label: {
    fontSize: 14,
    fontWeight: '600',
  },
  spacingTop: {
    marginTop: 8,
  },
  input: {
    borderRadius: 16,
    paddingHorizontal: 16,
    paddingVertical: 14,
    borderWidth: 1,
    fontSize: 16,
  },
  error: {
    fontSize: 14,
    marginTop: 6,
  },
  button: {
    marginTop: 10,
    paddingVertical: 15,
    borderRadius: 16,
    alignItems: 'center',
    justifyContent: 'center',
  },
  buttonPressed: {
    opacity: 0.85,
  },
  buttonDisabled: {
    opacity: 0.7,
  },
  buttonText: {
    fontWeight: '800',
    fontSize: 16,
  },
  note: {
    paddingHorizontal: 8,
  },
  noteText: {
    fontSize: 13,
    lineHeight: 19,
    textAlign: 'center',
  },
});
