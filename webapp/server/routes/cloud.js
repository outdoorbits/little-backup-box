import express from 'express';
import { readFileSync } from 'fs';
import { parse } from 'ini';
import path from 'path';
import { execCommand } from '../utils/execCommand.js';

const router = express.Router();

router.get('/config', async (req, res) => {
  try {
    const configPath = path.join(req.WORKING_DIR, 'config.cfg');
    const config = parse(readFileSync(configPath, 'utf-8'));
    
    res.json({
      config: {
        conf_BACKUP_CLOUDS_TARGET_BASEDIR: config.conf_BACKUP_CLOUDS_TARGET_BASEDIR || '',
        conf_BACKUP_SYNC_METHOD_CLOUDS: config.conf_BACKUP_SYNC_METHOD_CLOUDS || '',
        conf_BACKUP_CLOUDS_TARGET_FILES_STAY_IN_PLACE: config.conf_BACKUP_CLOUDS_TARGET_FILES_STAY_IN_PLACE || '',
      },
    });
  } catch (error) {
    req.logger.error('Failed to get cloud config', { error: error.message });
    res.status(500).json({ error: 'Failed to get cloud config' });
  }
});

router.get('/services', async (req, res) => {
  try {
    const rcloneConfigPath = path.join(
      req.constants.const_MEDIA_DIR,
      req.constants.const_RCLONE_CONFIG_FILE
    );
    
    const command = `sudo rclone config show --config "${rcloneConfigPath}" | grep '^\\[.*\\]$' | sed 's/^\\[//' | sed 's/\\]$//'`;
    const result = await execCommand(command, { logger: req.logger });
    
    const services = result.success && result.stdout.trim()
      ? result.stdout.trim().split('\n').filter(s => s.trim()).map(s => s.replace(/^\[|\]$/g, ''))
      : [];
    
    res.json({ services });
  } catch (error) {
    req.logger.error('Failed to get cloud services', { error: error.message });
    res.status(500).json({ error: 'Failed to get cloud services' });
  }
});

router.post('/rclone-gui/restart', async (req, res) => {
  try {
    const command = `sudo python3 ${req.WORKING_DIR}/start-rclone-gui.py True > /dev/null 2>&1 &`;
    await execCommand(command, { logger: req.logger });
    
    res.json({ success: true, message: 'Rclone GUI restarted' });
  } catch (error) {
    req.logger.error('Failed to restart rclone GUI', { error: error.message });
    res.status(500).json({ error: 'Failed to restart rclone GUI' });
  }
});

router.get('/rclone-gui/info', async (req, res) => {
  try {
    const configPath = path.join(req.WORKING_DIR, 'config.cfg');
    const config = parse(readFileSync(configPath, 'utf-8'));
    
    const password = config.conf_PASSWORD?.trim() || '';
    let passwordNote = '';
    
    if (password) {
      try {
        const decodedPassword = Buffer.from(password, 'base64').toString('utf-8');
        if (decodedPassword) {
          passwordNote = `Password: as set in 'Password' section`;
        }
      } catch (e) {
      }
    } else {
      passwordNote = "Password: 'lbb'";
    }
    
    res.json({
      url: '/frame.php?page=rclone_gui',
      username: 'lbb',
      passwordNote,
    });
  } catch (error) {
    req.logger.error('Failed to get rclone GUI info', { error: error.message });
    res.status(500).json({ error: 'Failed to get rclone GUI info' });
  }
});

export default router;

