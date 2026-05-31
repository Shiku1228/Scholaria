/**
 * Local phone dev (EXPO_DEV=1) omits EAS projectId so Expo CLI does not
 * require login. EAS builds keep projectId from app.json.
 */
module.exports = ({ config }) => {
  if (process.env.EXPO_DEV !== '1') {
    return config;
  }

  const extra = { ...config.extra };
  delete extra.eas;

  return {
    ...config,
    extra,
  };
};
