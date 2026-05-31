import { spawn } from 'node:child_process';

const expoArgs = process.argv.slice(2);

const env = { ...process.env, EXPO_DEV: '1' };
delete env.CI;

const child = spawn('npx', ['expo', 'start', ...expoArgs], {
  stdio: 'inherit',
  shell: true,
  env,
});

child.on('exit', (code) => {
  process.exit(code ?? 0);
});
