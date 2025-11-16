import express from 'express';
import { execCommand } from '../utils/execCommand.js';

const router = express.Router();

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

export default router;


