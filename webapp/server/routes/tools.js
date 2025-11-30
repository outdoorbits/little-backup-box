import express from 'express';
import { execCommand } from '../utils/execCommand.js';
import path from 'path';

const router = express.Router();

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

async function getCloudServices(req) {
  try {
    const rcloneConfig = path.join(req.constants.const_MEDIA_DIR, req.constants.const_RCLONE_CONFIG_FILE);
    const command = `sudo rclone config show --config "${rcloneConfig}" | grep '^\\[.*\\]$' | sed 's/^\\[//' | sed 's/\\]$//'`;
    const result = await execCommand(command, { logger: req.logger });
    
    if (!result.success) return [];
    
    return result.stdout
      .trim()
      .split('\n')
      .filter(line => line.trim())
      .map(line => line.replace(/^\[|\]$/g, ''));
  } catch (error) {
    return [];
  }
}

router.get('/mounts', async (req, res) => {
  try {
    const command = `sudo python3 ${req.WORKING_DIR}/lib_storage.py --Action get_mounts_list`;
    const result = await execCommand(command, { logger: req.logger });
    
    if (!result.success) {
      return res.status(500).json({ error: 'Failed to get mounts' });
    }
    
    res.json({ mountsList: result.stdout });
  } catch (error) {
    req.logger.error('Failed to get mounts', { error: error.message });
    res.status(500).json({ error: 'Failed to get mounts' });
  }
});

router.get('/devices', async (req, res) => {
  try {
    const { storageType = '', listPartitions = true, skipMounted = false, ignoreFs = false } = req.query;
    
    const storageTypeArg = storageType ? `--StorageName ${storageType}` : '';
    const action = listPartitions === 'true' ? 'get_available_partitions' : 'get_available_devices';
    const skipMountedArg = skipMounted === 'true' ? 'True' : 'False';
    const ignoreFsArg = ignoreFs === 'true' ? 'True' : 'False';
    
    const command = `sudo python3 ${req.WORKING_DIR}/lib_storage.py --Action ${action} ${storageTypeArg} --skipMounted ${skipMountedArg} --ignore-fs ${ignoreFsArg}`;
    const result = await execCommand(command, { logger: req.logger });
    
    if (!result.success) {
      return res.status(500).json({ error: 'Failed to get devices' });
    }
    
    const devices = result.stdout
      .trim()
      .split('\n')
      .filter(line => line.trim())
      .map(line => {
        if (line.includes(':')) {
          const [lum, ...identifierParts] = line.split(': ');
          return {
            lum: lum.replace('/dev/', ''),
            identifier: identifierParts.join(': '),
          };
        }
        return {
          lum: line.replace('/dev/', ''),
          identifier: '',
        };
      });
    
    res.json({ devices });
  } catch (error) {
    req.logger.error('Failed to get devices', { error: error.message });
    res.status(500).json({ error: 'Failed to get devices' });
  }
});

router.post('/mount', async (req, res) => {
  try {
    const { role, storage, deviceIdentifierPreset } = req.body;
    
    const command = `sudo python3 ${req.WORKING_DIR}/lib_storage.py --Action mount --StorageName ${storage} --Role ${role} --DeviceIdentifierPresetThis '${deviceIdentifierPreset || ''}'`;
    
    await execCommand(`python3 ${req.WORKING_DIR}/lib_log.py 'execute' '' "${command}" '1'`, { logger: req.logger });
    
    req.logger.info(`Mount: ${role} ${storage}`);
    res.json({ success: true });
  } catch (error) {
    req.logger.error('Failed to mount', { error: error.message });
    res.status(500).json({ error: 'Failed to mount' });
  }
});

router.post('/umount', async (req, res) => {
  try {
    const { role, storage } = req.body;
    
    const command = `sudo python3 ${req.WORKING_DIR}/lib_storage.py --Action umount --StorageName ${storage} --Role ${role}`;
    
    await execCommand(`python3 ${req.WORKING_DIR}/lib_log.py 'execute' '' '${command}' '1'`, { logger: req.logger });
    
    req.logger.info(`Umount: ${role} ${storage}`);
    res.json({ success: true });
  } catch (error) {
    req.logger.error('Failed to umount', { error: error.message });
    res.status(500).json({ error: 'Failed to umount' });
  }
});

router.get('/mountable-storages', async (req, res) => {
  try {
    const NVMe_available = await checkNVMeAvailable(req);
    const CloudServices = await getCloudServices(req);
    
    const LocalServices = ['usb'];
    if (NVMe_available) {
      LocalServices.push('nvme');
    }
    
    const CloudServices_marked = CloudServices.map(s => `cloud:${s}`);
    const MountableStorages = [...LocalServices, ...CloudServices_marked];
    
    res.json({ 
      mountableStorages: MountableStorages,
      nvmeAvailable: NVMe_available,
      cloudServices: CloudServices
    });
  } catch (error) {
    req.logger.error('Failed to get mountable storages', { error: error.message });
    res.status(500).json({ error: 'Failed to get mountable storages' });
  }
});

async function executeCmd(req, cmd, param1, param2) {
  const { writeFileSync, unlinkSync } = await import('fs');
  const { getCmdLogFile, getCmdRunnerLockfile } = await import('../utils/paths.js');
  
  const devicePath = param1 && param1.startsWith('/dev/') ? param1 : (param1 ? `/dev/${param1}` : '');
  
  let commandLine = '';
  
  switch (cmd) {
    case 'fsck':
      const fsckDetectCommand = `sudo lsblk -p -P -o PATH,MOUNTPOINT,UUID,FSTYPE | grep ${devicePath}`;
      const fsckDetectResult = await execCommand(fsckDetectCommand, { logger: req.logger });
      
      let deviceFstype = '';
      if (fsckDetectResult.success && fsckDetectResult.stdout) {
        const fstypeMatch = fsckDetectResult.stdout.match(/FSTYPE="([^"]+)"/);
        if (fstypeMatch) {
          deviceFstype = fstypeMatch[1];
        }
      }
      
      if (!deviceFstype) {
        throw new Error('Could not detect filesystem type');
      }
      
      let mainCommand = '';
      if (param2 === 'repair') {
        if (deviceFstype === 'exfat') {
          mainCommand = `fsck.${deviceFstype} -p ${devicePath}`;
        } else {
          mainCommand = `fsck.${deviceFstype} -f -p ${devicePath}`;
        }
      } else {
        mainCommand = `fsck.${deviceFstype} ${devicePath}`;
      }
      
      commandLine = `sudo python3 ${req.WORKING_DIR}/lib_display.py ':' 'Start' ':' 'file system' ':' '${param2}' && `;
      commandLine += `echo 'sudo ${mainCommand}' && `;
      commandLine += `echo '' && `;
      commandLine += `sudo ${mainCommand} && `;
      commandLine += `echo '' && `;
      commandLine += `echo 'FINISHED.' && `;
      commandLine += `sudo python3 ${req.WORKING_DIR}/lib_display.py ':' 'Finished file' ':' 'system' ':' '${param2}'`;
      break;
      
    case 'format':
      let mainFormatCommand = '';
      let fsckCommand = 'fsck';
      
      if (param2 === 'FAT32') {
        mainFormatCommand = `mkfs.vfat -v -I -F32 ${devicePath}`;
      } else if (param2 === 'exFAT') {
        mainFormatCommand = `mkfs.exfat ${devicePath}`;
      } else if (param2 === 'NTFS (compression enabled)') {
        mainFormatCommand = `mkfs.ntfs --enable-compression --force --verbose ${devicePath}`;
      } else if (param2 === 'NTFS (no compression)') {
        mainFormatCommand = `mkfs.ntfs --force --verbose ${devicePath}`;
      } else if (param2 === 'Ext4') {
        mainFormatCommand = `mkfs.ext4 -v -F ${devicePath}`;
      } else if (param2 === 'Ext3') {
        mainFormatCommand = `mkfs.ext3 -v -F ${devicePath}`;
      } else if (param2 === 'HFS Plus') {
        mainFormatCommand = `mkfs.hfsplus ${devicePath}`;
        fsckCommand = 'fsck.hfsplus';
      } else if (param2 === 'HFS') {
        mainFormatCommand = `mkfs.hfs ${devicePath}`;
        fsckCommand = 'fsck.hfs';
      } else {
        throw new Error('Invalid format type');
      }
      
      commandLine = `sudo python3 ${req.WORKING_DIR}/lib_display.py ':' 'Formatting' ':' '${param1}: ${param2}' ':' 'started...' && `;
      commandLine += `echo 'sudo ${mainFormatCommand}' && `;
      commandLine += `echo '' && `;
      commandLine += `sudo ${mainFormatCommand} && `;
      commandLine += `echo '' && `;
      commandLine += `sudo fdisk -l ${devicePath} && `;
      commandLine += `echo '' && `;
      commandLine += `lsblk -f ${devicePath} && `;
      commandLine += `echo '' && `;
      commandLine += `sudo ${fsckCommand} ${devicePath} && `;
      commandLine += `echo '' && `;
      commandLine += `echo 'FINISHED.' && `;
      commandLine += `sudo python3 ${req.WORKING_DIR}/lib_display.py ':' 'Formatting' ':' '${param1}: ${param2}' ':' 'completed'`;
      break;
      
    case 'f3':
      let mainF3Command = '';
      let description = '';
      
      if (param2 === 'f3probe_non_destructive') {
        mainF3Command = `f3probe --time-ops ${devicePath}`;
        description = 'non destructive';
      } else if (param2 === 'f3probe_destructive') {
        mainF3Command = `f3probe --destructive --time-ops ${devicePath}`;
        description = 'destructive';
      } else {
        throw new Error('Invalid f3 action');
      }
      
      commandLine = `sudo python3 ${req.WORKING_DIR}/lib_display.py ':' 'Examination' ':' '${param1}: ${description}' ':' 'started...' && `;
      commandLine += `echo 'sudo ${mainF3Command}' && `;
      commandLine += `echo '' && `;
      commandLine += `sudo ${mainF3Command} && `;
      commandLine += `echo '' && `;
      commandLine += `echo 'FINISHED.' && `;
      commandLine += `sudo python3 ${req.WORKING_DIR}/lib_display.py ':' 'Examination' ':' '${param1}: ${description}' ':' 'completed'`;
      break;
      
    default:
      throw new Error('Unknown command');
  }
  
  const lockfile = getCmdRunnerLockfile(req.WORKING_DIR, req.constants);
  const logfile = getCmdLogFile(req.WORKING_DIR, req.constants);
  
  writeFileSync(lockfile, commandLine, 'utf-8');
  
  const fullCommand = `sh -c "${commandLine}" > ${logfile} 2>&1 & echo $!`;
  await execCommand(fullCommand, { logger: req.logger });
  
  unlinkSync(lockfile);
}

router.post('/fsck/check', async (req, res) => {
  try {
    const { partition } = req.body;
    
    if (!partition || partition === '-') {
      return res.status(400).json({ error: 'Invalid partition' });
    }
    
    const devicePath = partition.startsWith('/dev/') ? partition : `/dev/${partition}`;
    
    await execCommand(`python3 ${req.WORKING_DIR}/lib_log.py 'message' "fsck ${partition} check" "1"`, { logger: req.logger });
    await executeCmd(req, 'fsck', devicePath, 'check');
    
    req.logger.info(`fsck check: ${partition}`);
    res.json({ success: true });
  } catch (error) {
    req.logger.error('Failed to execute fsck check', { error: error.message });
    res.status(500).json({ error: 'Failed to execute fsck check' });
  }
});

router.post('/fsck/repair', async (req, res) => {
  try {
    const { partition } = req.body;
    
    if (!partition || partition === '-') {
      return res.status(400).json({ error: 'Invalid partition' });
    }
    
    const devicePath = partition.startsWith('/dev/') ? partition : `/dev/${partition}`;
    
    await execCommand(`python3 ${req.WORKING_DIR}/lib_log.py 'message' "fsck ${partition} repair" "1"`, { logger: req.logger });
    await executeCmd(req, 'fsck', devicePath, 'repair');
    
    req.logger.info(`fsck repair: ${partition}`);
    res.json({ success: true });
  } catch (error) {
    req.logger.error('Failed to execute fsck repair', { error: error.message });
    res.status(500).json({ error: 'Failed to execute fsck repair' });
  }
});

router.post('/format', async (req, res) => {
  try {
    const { partition, fstype } = req.body;
    
    if (!partition || !fstype || partition === '-' || fstype === '-') {
      return res.status(400).json({ error: 'Invalid parameters' });
    }
    
    const devicePath = partition.startsWith('/dev/') ? partition : `/dev/${partition}`;
    
    await execCommand(`python3 ${req.WORKING_DIR}/lib_log.py 'message' "format ${partition} ${fstype}" "1"`, { logger: req.logger });
    await executeCmd(req, 'format', devicePath, fstype);
    
    req.logger.info(`format: ${partition} ${fstype}`);
    res.json({ success: true });
  } catch (error) {
    req.logger.error('Failed to execute format', { error: error.message });
    res.status(500).json({ error: 'Failed to execute format' });
  }
});

router.post('/f3', async (req, res) => {
  try {
    const { device, action } = req.body;
    
    if (!device || !action || device === '-' || action === '-') {
      return res.status(400).json({ error: 'Invalid parameters' });
    }
    
    const devicePath = device.startsWith('/dev/') ? device : `/dev/${device}`;
    
    await execCommand(`python3 ${req.WORKING_DIR}/lib_log.py 'message' "f3 ${device} ${action}" "1"`, { logger: req.logger });
    await executeCmd(req, 'f3', devicePath, action);
    
    req.logger.info(`f3: ${device} ${action}`);
    res.json({ success: true });
  } catch (error) {
    req.logger.error('Failed to execute f3', { error: error.message });
    res.status(500).json({ error: 'Failed to execute f3' });
  }
});

export default router;


