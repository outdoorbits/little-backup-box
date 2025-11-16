import express from 'express';
import { execCommand } from '../utils/execCommand.js';
import path from 'path';
import { readFileSync, writeFileSync, existsSync, mkdirSync } from 'fs';
import { 
  shouldUseSimulatedBackups, 
  startSimulatedBackup, 
  getRunningSimulatedBackups,
  stopSimulatedBackupById,
  stopAllSimulatedBackups as stopAllSimulated
} from '../utils/simulatedBackups.js';
import { getTempDir } from '../utils/paths.js';

const router = express.Router();

router.get('/services', async (req, res) => {
  try {
    const { execCommandSync } = await import('../utils/execCommand.js');
    const { execSync } = await import('child_process');
    
    const NVMe_available = await checkNVMeAvailable(req);
    const CloudServices = await getCloudServices(req);
    
    const USBDevices = ['anyusb', 'usb', 'camera'];
    
    const InternalStorage = ['internal'];
    if (NVMe_available) {
      InternalStorage.push('nvme');
    }
    
    const CloudServices_marked = CloudServices.map(s => `cloud:${s}`);
    
    const rsync_configurated = !(
      !req.config.conf_RSYNC_SERVER ||
      !req.config.conf_RSYNC_PORT ||
      !req.config.conf_RSYNC_USER ||
      !req.config.conf_RSYNC_PASSWORD ||
      !req.config.conf_RSYNC_SERVER_MODULE
    );
    
    if (rsync_configurated) {
      CloudServices_marked.unshift('cloud_rsync');
    }
    
    const SocialServices = await getSocialServices(req);
    const SocialServices_marked = SocialServices.map(s => `social:${s}`);
    
    const SourceServices = {
      usb: USBDevices,
      internal: [...InternalStorage, 'ftp'],
      cloud: CloudServices_marked,
    };
    
    const TargetServices = {
      internal: InternalStorage,
      usb: ['usb'],
      cloud: CloudServices_marked,
      social: SocialServices_marked,
    };
    
    res.json({
      sourceServices: SourceServices,
      targetServices: TargetServices,
      nvmeAvailable: NVMe_available,
    });
  } catch (error) {
    req.logger.error('Failed to get services', { error: error.message });
    res.status(500).json({ error: 'Failed to get services' });
  }
});

router.get('/partitions', async (req, res) => {
  try {
    const command = `sudo python3 ${req.WORKING_DIR}/lib_storage.py --Action get_available_partitions --skipMounted False`;
    const result = await execCommand(command, { logger: req.logger });
    
    if (!result.success) {
      req.logger.warn('Partitions command failed, returning empty array', { error: result.error, stderr: result.stderr });
      return res.json({ partitions: [] });
    }
    
    const partitions = result.stdout
      .trim()
      .split('\n')
      .filter(line => line.trim())
      .map(line => {
        const [lum, ...identifierParts] = line.split(': ');
        const identifier = identifierParts.join(': ');
        return {
          lum: lum.replace('/dev/', ''),
          identifier: identifier || '',
        };
      });
    
    res.json({ partitions });
  } catch (error) {
    req.logger.error('Failed to get partitions', { error: error.message });
    res.json({ partitions: [] });
  }
});

router.post('/start', async (req, res) => {
  try {
    const {
      sourceDevice,
      targetDevice,
      moveFiles,
      renameFiles,
      generateThumbnails,
      updateExif,
      checksum,
      powerOff,
      presetSource,
      presetTarget,
      secSourceName,
      secTargetName,
      telegramChatId,
    } = req.body;
    
    if (!sourceDevice || !targetDevice) {
      return res.status(400).json({ error: 'Source and target devices are required' });
    }
    
    if (shouldUseSimulatedBackups()) {
      const backupConfig = {
        sourceDevice,
        targetDevice,
        moveFiles,
        renameFiles,
        generateThumbnails,
        updateExif,
        checksum,
        powerOff,
        presetSource,
        presetTarget,
        secSourceName,
        secTargetName,
        telegramChatId,
      };
      
      const result = startSimulatedBackup(backupConfig);
      
      if (result.alreadyRunning) {
        return res.status(409).json({ 
          error: 'A backup with this configuration is already running',
          alreadyRunning: true 
        });
      }
      
      const historyEntry = {
        sourceDevice,
        targetDevice,
        moveFiles,
        renameFiles,
        generateThumbnails,
        updateExif,
        checksum,
        powerOff,
        presetSource: presetSource || '',
        presetTarget: presetTarget || '',
        timestamp: new Date().toISOString(),
      };
      
      const history = loadBackupHistory(req);
      history.unshift(historyEntry);
      if (history.length > 100) {
        history.splice(100);
      }
      saveBackupHistory(req, history);
      
      req.logger.info(`[SIMULATED] Backup started: ${sourceDevice} -> ${targetDevice}`);
      res.json({ success: true, message: `Backup ${sourceDevice} to ${targetDevice} initiated (simulated)` });
      return;
    }
    
    const runningBackupsResult = await execCommand(
      `ps -ef | grep "${req.WORKING_DIR}/backup.py" | grep -v grep`,
      { logger: req.logger }
    );
    
    if (runningBackupsResult.success && runningBackupsResult.stdout.trim()) {
      const lines = runningBackupsResult.stdout.trim().split('\n');
      for (const line of lines) {
        const fullCommand = line.substring(line.indexOf('backup.py'));
        const extractArg = (cmd, argName) => {
          const regex = new RegExp(`--${argName}\\s+(?:'([^']*)'|"([^"]*)"|([^\\s]+))`);
          const match = cmd.match(regex);
          if (match) {
            return match[1] || match[2] || match[3] || null;
          }
          return null;
        };
        
        const runningSource = extractArg(fullCommand, 'SourceName');
        const runningTarget = extractArg(fullCommand, 'TargetName');
        const runningPresetSource = extractArg(fullCommand, 'device-identifier-preset-source') || '';
        const runningPresetTarget = extractArg(fullCommand, 'device-identifier-preset-target') || '';
        const runningMoveFiles = extractArg(fullCommand, 'move-files') === 'True';
        const runningRenameFiles = extractArg(fullCommand, 'rename-files') === 'True';
        const runningGenerateThumbnails = extractArg(fullCommand, 'generate-thumbnails') === 'True';
        const runningUpdateExif = extractArg(fullCommand, 'update-exif') === 'True';
        const runningChecksum = extractArg(fullCommand, 'checksum') === 'True';
        const runningPowerOff = extractArg(fullCommand, 'power-off') === 'True';
        
        if (
          runningSource === sourceDevice &&
          runningTarget === targetDevice &&
          (runningPresetSource || '') === (presetSource || '') &&
          (runningPresetTarget || '') === (presetTarget || '') &&
          runningMoveFiles === moveFiles &&
          runningRenameFiles === renameFiles &&
          runningGenerateThumbnails === generateThumbnails &&
          runningUpdateExif === updateExif &&
          runningChecksum === checksum &&
          runningPowerOff === powerOff
        ) {
          return res.status(409).json({ 
            error: 'A backup with this configuration is already running',
            alreadyRunning: true 
          });
        }
      }
    }
    
    let command = `sudo python3 ${req.WORKING_DIR}/backup.py `;
    command += `--SourceName ${escapeShellArg(sourceDevice)} `;
    command += `--TargetName ${escapeShellArg(targetDevice)} `;
    command += `--move-files ${moveFiles ? 'True' : 'False'} `;
    command += `--rename-files ${renameFiles ? 'True' : 'False'} `;
    command += `--force-sync-database False `;
    command += `--generate-thumbnails ${generateThumbnails ? 'True' : 'False'} `;
    command += `--update-exif ${updateExif ? 'True' : 'False'} `;
    command += `--checksum ${checksum ? 'True' : 'False'} `;
    command += `--device-identifier-preset-source ${escapeShellArg(presetSource || '')} `;
    command += `--device-identifier-preset-target ${escapeShellArg(presetTarget || '')} `;
    command += `--power-off ${powerOff ? 'True' : 'False'} `;
    
    if (telegramChatId) {
      command += `--telegram-chat-id ${escapeShellArg(telegramChatId)} `;
    }
    
    if (secSourceName && secTargetName && secSourceName !== 'none' && secTargetName !== 'none') {
      command += `--SecSourceName ${escapeShellArg(secSourceName)} `;
      command += `--SecTargetName ${escapeShellArg(secTargetName)} `;
    }
    
    command += '> /dev/null 2>&1 &';
    
    await execCommand(command, { logger: req.logger });
    
    const backupConfig = {
      sourceDevice,
      targetDevice,
      moveFiles,
      renameFiles,
      generateThumbnails,
      updateExif,
      checksum,
      powerOff,
      presetSource: presetSource || '',
      presetTarget: presetTarget || '',
      timestamp: new Date().toISOString(),
    };
    
    const history = loadBackupHistory(req);
    history.unshift(backupConfig);
    if (history.length > 100) {
      history.splice(100);
    }
    saveBackupHistory(req, history);
    
    req.logger.info(`Backup started: ${sourceDevice} -> ${targetDevice}`);
    res.json({ success: true, message: `Backup ${sourceDevice} to ${targetDevice} initiated` });
  } catch (error) {
    req.logger.error('Failed to start backup', { error: error.message });
    res.status(500).json({ error: 'Failed to start backup' });
  }
});

router.post('/function', async (req, res) => {
  try {
    const { function: func, target, presetSource, presetTarget, powerOff } = req.body;
    
    const [functionType, targetType] = func.split('_');
    
    let syncDatabase = 'False';
    let renameFiles = 'False';
    let generateThumbnails = 'False';
    let updateExif = 'False';
    
    if (functionType === 'thumbnails') {
      generateThumbnails = 'True';
    } else if (functionType === 'database') {
      syncDatabase = 'True';
    } else if (functionType === 'exif') {
      updateExif = 'True';
    } else if (functionType === 'rename') {
      renameFiles = 'True';
    }
    
    let command = `sudo ${req.WORKING_DIR}/stop_backup.sh && `;
    command += `sudo python3 ${req.WORKING_DIR}/backup.py `;
    command += `--SourceName ${functionType} `;
    command += `--TargetName ${target} `;
    command += `--rename-files ${renameFiles} `;
    command += `--force-sync-database ${syncDatabase} `;
    command += `--generate-thumbnails ${generateThumbnails} `;
    command += `--update-exif ${updateExif} `;
    command += `--device-identifier-preset-source ${escapeShellArg(presetSource || '')} `;
    command += `--device-identifier-preset-target ${escapeShellArg(presetTarget || '')} `;
    command += `--power-off ${powerOff ? 'True' : 'False'} `;
    command += '> /dev/null 2>&1 &';
    
    await execCommand(command, { logger: req.logger });
    
    req.logger.info(`Backup function started: ${functionType} on ${target}`);
    res.json({ success: true, message: `${functionType} on ${target} initiated` });
  } catch (error) {
    req.logger.error('Failed to start backup function', { error: error.message });
    res.status(500).json({ error: 'Failed to start backup function' });
  }
});

router.get('/running', async (req, res) => {
  try {
    if (shouldUseSimulatedBackups()) {
      const backups = getRunningSimulatedBackups();
      return res.json({ backups });
    }
    
    const command = `ps -ef | grep "${req.WORKING_DIR}/backup.py" | grep -v grep`;
    const result = await execCommand(command, { logger: req.logger });
    
    if (!result.success || !result.stdout.trim()) {
      return res.json({ backups: [] });
    }
    
    const lines = result.stdout.trim().split('\n').filter(line => line.trim());
    const backups = lines.map(line => {
      const parts = line.trim().split(/\s+/);
      const pid = parts[1];
      const fullCommand = line.substring(line.indexOf('backup.py'));
      
      const extractArg = (cmd, argName) => {
        const regex = new RegExp(`--${argName}\\s+(?:'([^']*)'|"([^"]*)"|([^\\s]+))`);
        const match = cmd.match(regex);
        if (match) {
          return match[1] || match[2] || match[3] || null;
        }
        return null;
      };
      
      const sourceDevice = extractArg(fullCommand, 'SourceName');
      const targetDevice = extractArg(fullCommand, 'TargetName');
      const presetSource = extractArg(fullCommand, 'device-identifier-preset-source') || '';
      const presetTarget = extractArg(fullCommand, 'device-identifier-preset-target') || '';
      const moveFiles = extractArg(fullCommand, 'move-files') === 'True';
      const renameFiles = extractArg(fullCommand, 'rename-files') === 'True';
      const generateThumbnails = extractArg(fullCommand, 'generate-thumbnails') === 'True';
      const updateExif = extractArg(fullCommand, 'update-exif') === 'True';
      const checksum = extractArg(fullCommand, 'checksum') === 'True';
      const powerOff = extractArg(fullCommand, 'power-off') === 'True';
      
      return {
        pid: parseInt(pid, 10),
        sourceDevice,
        targetDevice,
        presetSource,
        presetTarget,
        moveFiles,
        renameFiles,
        generateThumbnails,
        updateExif,
        checksum,
        powerOff,
      };
    }).filter(backup => backup.sourceDevice && backup.targetDevice);
    
    res.json({ backups });
  } catch (error) {
    req.logger.error('Failed to get running backups', { error: error.message });
    res.json({ backups: [] });
  }
});

router.post('/stop', async (req, res) => {
  try {
    const { pid } = req.body;
    
    if (shouldUseSimulatedBackups()) {
      if (pid) {
        const stopped = stopSimulatedBackupById(pid);
        if (stopped) {
          req.logger.info(`[SIMULATED] Backup stopped (PID: ${pid})`);
          res.json({ success: true, message: `Backup stopped (PID: ${pid})` });
        } else {
          res.status(404).json({ error: 'Backup not found' });
        }
      } else {
        const count = stopAllSimulated();
        req.logger.info(`[SIMULATED] All backups stopped (${count} backups)`);
        res.json({ success: true, message: `All backups stopped (${count} backups)` });
      }
      return;
    }
    
    if (pid) {
      const command = `sudo kill ${pid}`;
      await execCommand(command, { logger: req.logger });
      req.logger.info(`Backup stopped (PID: ${pid})`);
      res.json({ success: true, message: `Backup stopped (PID: ${pid})` });
    } else {
      const command = `sudo ${req.WORKING_DIR}/stop_backup.sh`;
      await execCommand(command, { logger: req.logger });
      req.logger.info('All backups stopped');
      res.json({ success: true, message: 'All backups stopped' });
    }
  } catch (error) {
    req.logger.error('Failed to stop backup', { error: error.message });
    res.status(500).json({ error: 'Failed to stop backup' });
  }
});

function getBackupHistoryFile(req) {
  const tempDir = getTempDir(req.WORKING_DIR, req.constants);
  return path.join(tempDir, 'backup-history.json');
}

function loadBackupHistory(req) {
  try {
    const historyFile = getBackupHistoryFile(req);
    if (!existsSync(historyFile)) {
      return [];
    }
    const content = readFileSync(historyFile, 'utf-8');
    return JSON.parse(content);
  } catch (error) {
    req.logger?.warn('Failed to load backup history', { error: error.message });
    return [];
  }
}

function saveBackupHistory(req, history) {
  try {
    const historyFile = getBackupHistoryFile(req);
    const tempDir = path.dirname(historyFile);
    if (!existsSync(tempDir)) {
      mkdirSync(tempDir, { recursive: true });
    }
    writeFileSync(historyFile, JSON.stringify(history, null, 2), 'utf-8');
  } catch (error) {
    req.logger?.warn('Failed to save backup history', { error: error.message });
  }
}

function getBackupConfigKey(config) {
  return JSON.stringify({
    sourceDevice: config.sourceDevice,
    targetDevice: config.targetDevice,
    presetSource: config.presetSource || '',
    presetTarget: config.presetTarget || '',
    moveFiles: config.moveFiles || false,
    renameFiles: config.renameFiles || false,
    generateThumbnails: config.generateThumbnails || false,
    updateExif: config.updateExif || false,
    checksum: config.checksum || false,
    powerOff: config.powerOff || false,
  });
}

router.get('/history', async (req, res) => {
  try {
    const history = loadBackupHistory(req);
    const seen = new Set();
    const uniqueHistory = [];
    
    for (const entry of history) {
      const key = getBackupConfigKey(entry);
      if (!seen.has(key)) {
        seen.add(key);
        uniqueHistory.push(entry);
        if (uniqueHistory.length >= 5) {
          break;
        }
      }
    }
    
    res.json({ history: uniqueHistory });
  } catch (error) {
    req.logger.error('Failed to get backup history', { error: error.message });
    res.json({ history: [] });
  }
});

async function checkNVMeAvailable(req) {
  try {
    const command = `sudo python3 ${req.WORKING_DIR}/lib_storage.py --Action get_available_partitions --skipMounted False --ignore-fs False`;
    const result = await execCommand(command, { logger: req.logger });
    
    if (!result.success) return false;
    
    const partitions = result.stdout.trim().split('\n');
    return partitions.some(p => p.startsWith(`/dev/${req.constants.const_STORAGE_NVME_MASK}`));
  } catch (error) {
    return false;
  }
}

// TODO: Could replace with rclone Node.js library if available, or parse config file directly
// SECURITY NOTE: Command execution with user-controlled paths - ensure rcloneConfig is validated
async function getCloudServices(req) {
  try {
    const rcloneConfig = path.join(req.constants.const_MEDIA_DIR, req.constants.const_RCLONE_CONFIG_FILE);
    const command = `sudo rclone config show --config "${rcloneConfig}" | grep '^\\[.*\\]$' | sed 's/^\\[//' | sed 's/\\]$//'`;
    const result = await execCommand(command, { logger: req.logger });
    
    if (!result.success) return [];
    
    return result.stdout
      .trim()
      .split('\n')
      .filter(line => line.trim());
  } catch (error) {
    return [];
  }
}

async function getSocialServices(req) {
  try {
    const command = `python3 ${req.WORKING_DIR}/lib_socialmedia.py --action get_social_services_configured`;
    const result = await execCommand(command, { logger: req.logger });
    
    if (!result.success) return [];
    
    return result.stdout
      .trim()
      .split('\n')
      .filter(line => line.trim());
  } catch (error) {
    return [];
  }
}

function escapeShellArg(arg) {
  if (!arg) return "''";
  return `'${String(arg).replace(/'/g, "'\\''")}'`;
}

export default router;

