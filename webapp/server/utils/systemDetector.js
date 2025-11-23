import { platform } from 'os';

export function isLinux() {
  return platform() === 'linux';
}

export function shouldUseMocks() {
  const forceMocks = process.env.USE_MOCKS === 'true';
  const forceReal = process.env.USE_MOCKS === 'false';
  
  if (forceReal) return false;
  if (forceMocks) return true;
  
  return !isLinux();
}





