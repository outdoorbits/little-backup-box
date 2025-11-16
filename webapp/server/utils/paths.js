import path from 'path';
import { fileURLToPath } from 'url';
import { mkdirSync, existsSync } from 'fs';

const __filename = fileURLToPath(import.meta.url);
const __dirname = path.dirname(__filename);

const isDev = process.env.NODE_ENV !== 'production';

export function getTempDir(workingDir, constants) {
  if (isDev) {
    const devTempDir = path.join(__dirname, '../../tmp');
    if (!existsSync(devTempDir)) {
      mkdirSync(devTempDir, { recursive: true });
    }
    return devTempDir;
  }
  
  const prodTempDir = constants?.const_LOGFILE 
    ? path.dirname(constants.const_LOGFILE)
    : path.join(workingDir, 'tmp');
  
  if (!existsSync(prodTempDir)) {
    try {
      mkdirSync(prodTempDir, { recursive: true });
    } catch (err) {
      console.warn(`Failed to create production temp dir ${prodTempDir}, using dev temp dir`);
      const fallbackTempDir = path.join(__dirname, '../../tmp');
      if (!existsSync(fallbackTempDir)) {
        mkdirSync(fallbackTempDir, { recursive: true });
      }
      return fallbackTempDir;
    }
  }
  
  return prodTempDir;
}

export function getLogFile(workingDir, constants) {
  const tempDir = getTempDir(workingDir, constants);
  return path.join(tempDir, 'little-backup-box.log');
}

export function getCmdLogFile(workingDir, constants) {
  const tempDir = getTempDir(workingDir, constants);
  return path.join(tempDir, 'cmd.log');
}

export function getCmdRunnerLockfile(workingDir, constants) {
  const tempDir = getTempDir(workingDir, constants);
  return path.join(tempDir, 'cmd-runner.lock');
}

export function getDisplayContentPath(workingDir, constants) {
  const tempDir = getTempDir(workingDir, constants);
  return path.join(tempDir, 'display-content');
}

export function getIdletimeLockfile(workingDir, constants) {
  const tempDir = getTempDir(workingDir, constants);
  return path.join(tempDir, 'idletime.lock');
}


