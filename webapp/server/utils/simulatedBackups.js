import { shouldUseMocks } from './systemDetector.js';

const simulatedBackups = new Map();
let nextPid = 10000;

function generatePid() {
  return nextPid++;
}

function createSimulatedBackup(backupConfig) {
  const pid = generatePid();
  const startTime = Date.now();
  
  const duration = 10000 + Math.random() * 20000;
  
  const backup = {
    pid,
    startTime,
    duration,
    sourceDevice: backupConfig.sourceDevice,
    targetDevice: backupConfig.targetDevice,
    presetSource: backupConfig.presetSource || '',
    presetTarget: backupConfig.presetTarget || '',
    moveFiles: backupConfig.moveFiles || false,
    renameFiles: backupConfig.renameFiles || false,
    generateThumbnails: backupConfig.generateThumbnails || false,
    updateExif: backupConfig.updateExif || false,
    checksum: backupConfig.checksum || false,
    powerOff: backupConfig.powerOff || false,
    secSourceName: backupConfig.secSourceName,
    secTargetName: backupConfig.secTargetName,
    telegramChatId: backupConfig.telegramChatId,
  };
  
  simulatedBackups.set(pid, backup);
  
  setTimeout(() => {
    if (simulatedBackups.has(pid)) {
      simulatedBackups.delete(pid);
    }
  }, duration);
  
  return backup;
}

function getSimulatedBackups() {
  const now = Date.now();
  const activeBackups = [];
  
  for (const [pid, backup] of simulatedBackups.entries()) {
    const elapsed = now - backup.startTime;
    if (elapsed < backup.duration) {
      activeBackups.push({
        pid: backup.pid,
        sourceDevice: backup.sourceDevice,
        targetDevice: backup.targetDevice,
        presetSource: backup.presetSource,
        presetTarget: backup.presetTarget,
        moveFiles: backup.moveFiles,
        renameFiles: backup.renameFiles,
        generateThumbnails: backup.generateThumbnails,
        updateExif: backup.updateExif,
        checksum: backup.checksum,
        powerOff: backup.powerOff,
      });
    } else {
      simulatedBackups.delete(pid);
    }
  }
  
  return activeBackups;
}

function stopSimulatedBackup(pid) {
  if (simulatedBackups.has(pid)) {
    simulatedBackups.delete(pid);
    return true;
  }
  return false;
}

function clearAllSimulatedBackups() {
  const count = simulatedBackups.size;
  simulatedBackups.clear();
  return count;
}

function findMatchingBackup(backupConfig) {
  for (const backup of simulatedBackups.values()) {
    if (
      backup.sourceDevice === backupConfig.sourceDevice &&
      backup.targetDevice === backupConfig.targetDevice &&
      (backup.presetSource || '') === (backupConfig.presetSource || '') &&
      (backup.presetTarget || '') === (backupConfig.presetTarget || '') &&
      backup.moveFiles === backupConfig.moveFiles &&
      backup.renameFiles === backupConfig.renameFiles &&
      backup.generateThumbnails === backupConfig.generateThumbnails &&
      backup.updateExif === backupConfig.updateExif &&
      backup.checksum === backupConfig.checksum &&
      backup.powerOff === backupConfig.powerOff
    ) {
      return backup;
    }
  }
  return null;
}

export function shouldUseSimulatedBackups() {
  return shouldUseMocks();
}

export function startSimulatedBackup(backupConfig) {
  if (!shouldUseSimulatedBackups()) {
    return null;
  }
  
  const existing = findMatchingBackup(backupConfig);
  if (existing) {
    return { alreadyRunning: true, backup: existing };
  }
  
  return { backup: createSimulatedBackup(backupConfig) };
}

export function getRunningSimulatedBackups() {
  if (!shouldUseSimulatedBackups()) {
    return [];
  }
  return getSimulatedBackups();
}

export function stopSimulatedBackupById(pid) {
  if (!shouldUseSimulatedBackups()) {
    return false;
  }
  return stopSimulatedBackup(pid);
}

export function stopAllSimulatedBackups() {
  if (!shouldUseSimulatedBackups()) {
    return 0;
  }
  return clearAllSimulatedBackups();
}

export function formatSimulatedBackupForPs(backup) {
  const timeStr = new Date(backup.startTime).toLocaleTimeString('en-US', { hour12: false });
  return `root ${backup.pid} 1 0 ${timeStr} ? 00:00:00 python3 /path/to/scripts/backup.py --SourceName '${backup.sourceDevice}' --TargetName '${backup.targetDevice}' --move-files ${backup.moveFiles ? 'True' : 'False'} --rename-files ${backup.renameFiles ? 'True' : 'False'} --force-sync-database False --generate-thumbnails ${backup.generateThumbnails ? 'True' : 'False'} --update-exif ${backup.updateExif ? 'True' : 'False'} --checksum ${backup.checksum ? 'True' : 'False'} --device-identifier-preset-source '${backup.presetSource}' --device-identifier-preset-target '${backup.presetTarget}' --power-off ${backup.powerOff ? 'True' : 'False'}`;
}

