import express from 'express';
import { readFileSync, writeFileSync, existsSync, renameSync, createReadStream, unlinkSync } from 'fs';
import { parse, stringify } from 'ini';
import path from 'path';
import { fileURLToPath } from 'url';
import { execCommand } from '../utils/execCommand.js';
import { getTempDir, getIdletimeLockfile } from '../utils/paths.js';
import multer from 'multer';

const __filename = fileURLToPath(import.meta.url);
const __dirname = path.dirname(__filename);

const router = express.Router();

const isDev = process.env.NODE_ENV !== 'production';
const uploadDir = isDev ? path.join(__dirname, '../../tmp') : '/tmp';
const upload = multer({ dest: uploadDir });

router.get('/wifi-country', async (req, res) => {
  try {
    const result = await execCommand('raspi-config nonint get_wifi_country', { logger: req.logger });
    const country = result.success ? result.stdout.trim() : '';
    res.json({ country });
  } catch (error) {
    req.logger.error('Failed to get WiFi country', { error: error.message });
    res.status(500).json({ error: 'Failed to get WiFi country' });
  }
});

router.get('/wifi-countries', async (req, res) => {
  try {
    const result = await execCommand("sed '/^#/d' /usr/share/zoneinfo/iso3166.tab", { logger: req.logger });
    
    if (!result.success) {
      return res.json({ countries: [] });
    }
    
    const countries = result.stdout
      .trim()
      .split('\n')
      .map(line => {
        const parts = line.split('\t');
        return {
          code: parts[0]?.trim() || '',
          name: parts[1]?.trim() || '',
        };
      })
      .filter(c => c.code && c.name);
    
    res.json({ countries });
  } catch (error) {
    req.logger.error('Failed to get WiFi countries', { error: error.message });
    res.status(500).json({ error: 'Failed to get WiFi countries' });
  }
});

router.post('/save', async (req, res) => {
  try {
    const configPath = path.join(req.WORKING_DIR, 'config.cfg');
    const currentConfig = parse(readFileSync(configPath, 'utf-8'));
    
    Object.keys(req.body).forEach((key) => {
      if (key.startsWith('conf_')) {
        currentConfig[key] = req.body[key];
      }
    });
    
    if (req.body.conf_TIME_ZONE && req.body.conf_TIME_ZONE !== currentConfig.conf_TIME_ZONE) {
      await execCommand(`sudo raspi-config nonint do_change_timezone ${req.body.conf_TIME_ZONE}`, { logger: req.logger });
    }
    
    if (req.body.conf_WIFI_COUNTRY && req.body.conf_WIFI_COUNTRY !== currentConfig.conf_WIFI_COUNTRY) {
      await execCommand(`sudo raspi-config nonint do_wifi_country ${req.body.conf_WIFI_COUNTRY}`, { logger: req.logger });
    }
    
    writeFileSync(configPath, stringify(currentConfig), 'utf-8');
    
    req.logger.info('Setup config saved');
    res.json({ success: true });
  } catch (error) {
    req.logger.error('Failed to save setup config', { error: error.message });
    res.status(500).json({ error: 'Failed to save setup config' });
  }
});

router.post('/test-mail', async (req, res) => {
  try {
    const command = `sudo python3 ${req.WORKING_DIR}/lib_mail.py 'Test mail' 'This is a test mail from Little Backup Box' /dev/null 2>&1`;
    const result = await execCommand(command, { logger: req.logger });
    
    if (result.success) {
      res.json({ success: true, message: 'Test mail sent' });
    } else {
      const errorMessage = result.stderr || result.error || 'Failed to send test mail';
      req.logger.error('Failed to send test mail', { error: errorMessage, stderr: result.stderr });
      res.status(500).json({ error: errorMessage });
    }
  } catch (error) {
    req.logger.error('Failed to send test mail', { error: error.message });
    res.status(500).json({ error: error.message || 'Failed to send test mail' });
  }
});

router.post('/restart-rclone-gui', async (req, res) => {
  try {
    const command = `sudo python3 ${req.WORKING_DIR}/start-rclone-gui.py True > /dev/null 2>&1 &`;
    await execCommand(command, { logger: req.logger });
    
    res.json({ success: true, message: 'Rclone GUI restarted' });
  } catch (error) {
    req.logger.error('Failed to restart rclone GUI', { error: error.message });
    res.status(500).json({ error: 'Failed to restart rclone GUI' });
  }
});

router.get('/update-check', async (req, res) => {
  try {
    const command = `sudo python3 ${req.WORKING_DIR}/lib_git.py --write-available --update-available`;
    const result = await execCommand(command, { logger: req.logger });
    
    const updateAvailable = result.success && result.stdout.trim() === 'True';
    
    res.json({ updateAvailable });
  } catch (error) {
    req.logger.error('Failed to check for updates', { error: error.message });
    res.status(500).json({ error: 'Failed to check for updates' });
  }
});

router.get('/update/status', async (req, res) => {
  try {
    const configPath = path.join(req.WORKING_DIR, 'config.cfg');
    const config = parse(readFileSync(configPath, 'utf-8'));
    
    const branch = req.constants?.const_SOFTWARE_BRANCH || 'main';
    const installedVersion = config.conf_SOFTWARE_DATE_INSTALLED || '';
    const availableVersion = config.conf_SOFTWARE_DATE_AVAILABLE || '';
    
    res.json({
      branch,
      installedVersion,
      availableVersion,
    });
  } catch (error) {
    req.logger.error('Failed to get update status', { error: error.message });
    res.status(500).json({ error: 'Failed to get update status' });
  }
});

router.post('/update/install', async (req, res) => {
  try {
    const branch = req.constants?.const_SOFTWARE_BRANCH || 'main';
    
    let command = `sudo python3 ${req.WORKING_DIR}/lib_display.py ':Update' ':started...'`;
    command += `;sudo -u pi curl -sSL https://raw.githubusercontent.com/outdoorbits/little-backup-box/${branch}/install-little-backup-box.sh -o ~pi/install-little-backup-box.sh`;
    command += `;sudo -u pi bash ~pi/install-little-backup-box.sh ${branch}`;
    command += ' > /dev/null 2>&1 &';
    
    await execCommand(command, { logger: req.logger });
    
    req.logger.info(`Update installation started for branch: ${branch}`);
    res.json({ success: true, message: 'Update installation started' });
  } catch (error) {
    req.logger.error('Failed to install update', { error: error.message });
    res.status(500).json({ error: 'Failed to install update' });
  }
});

router.get('/download-settings', async (req, res) => {
  try {
    const tempDir = getTempDir(req.WORKING_DIR, req.constants);
    const zipFileName = 'lbb-settings.zip';
    const zipFilePath = path.join(tempDir, zipFileName);
    
    const files = [];
    
    const configFile = path.join(req.WORKING_DIR, 'config.cfg');
    if (existsSync(configFile)) {
      files.push({ file: configFile, dir: '' });
    }
    
    const rcloneConfig = path.join(req.constants.const_MEDIA_DIR, req.constants.const_RCLONE_CONFIG_FILE);
    if (existsSync(rcloneConfig)) {
      files.push({ file: rcloneConfig, dir: '' });
    }
    
    const buttonsConfig = path.join(req.constants.const_MEDIA_DIR, req.constants.const_BUTTONS_PRIVATE_CONFIG_FILE);
    if (existsSync(buttonsConfig)) {
      files.push({ file: buttonsConfig, dir: '' });
    }
    
    const vpnTypes = ['OpenVPN', 'WireGuard'];
    for (const vpnType of vpnTypes) {
      const vpnDir = req.constants[`const_VPN_DIR_${vpnType}`];
      const vpnFilename = req.constants[`const_VPN_FILENAME_${vpnType}`];
      if (vpnDir && vpnFilename) {
        const vpnConfigFile = path.join(vpnDir, vpnFilename);
        if (existsSync(vpnConfigFile)) {
          await execCommand(`sudo chmod 755 "${vpnDir}"`, { logger: req.logger });
          await execCommand(`sudo chmod 755 "${vpnConfigFile}"`, { logger: req.logger });
          files.push({ file: vpnConfigFile, dir: '' });
        }
      }
    }
    
    const backgroundImagesDir = path.join(req.constants.const_MEDIA_DIR, req.constants.const_BACKGROUND_IMAGES_DIR);
    if (existsSync(backgroundImagesDir)) {
      const { readdirSync, statSync } = await import('fs');
      const backgroundImages = readdirSync(backgroundImagesDir);
      for (const bgImage of backgroundImages) {
        const bgImagePath = path.join(backgroundImagesDir, bgImage);
        if (statSync(bgImagePath).isFile()) {
          files.push({ file: bgImagePath, dir: 'bg-images' });
        }
      }
    }
    
    if (files.length === 0) {
      return res.status(404).json({ error: 'No settings files found' });
    }
    
    if (existsSync(zipFilePath)) {
      unlinkSync(zipFilePath);
    }
    
    for (const fileInfo of files) {
      let zipCommand;
      if (fileInfo.dir) {
        const targetPath = `${fileInfo.dir}/${path.basename(fileInfo.file)}`;
        zipCommand = `zip -q "${zipFilePath}" "${targetPath}" "${fileInfo.file}"`;
      } else {
        zipCommand = `cd "${path.dirname(fileInfo.file)}" && zip -q "${zipFilePath}" "${path.basename(fileInfo.file)}"`;
      }
      
      const result = await execCommand(zipCommand, { logger: req.logger });
      if (!result.success) {
        throw new Error('Failed to create zip archive');
      }
    }
    
    for (const vpnType of vpnTypes) {
      const vpnDir = req.constants[`const_VPN_DIR_${vpnType}`];
      const vpnFilename = req.constants[`const_VPN_FILENAME_${vpnType}`];
      if (vpnDir && vpnFilename) {
        const vpnConfigFile = path.join(vpnDir, vpnFilename);
        if (existsSync(vpnConfigFile)) {
          await execCommand(`sudo chmod 700 "${vpnConfigFile}"`, { logger: req.logger });
        }
      }
    }
    
    res.setHeader('Content-Type', 'application/zip');
    res.setHeader('Content-Disposition', `attachment; filename="${zipFileName}"`);
    
    const fileStream = createReadStream(zipFilePath);
    fileStream.pipe(res);
    
    fileStream.on('end', () => {
      unlinkSync(zipFilePath);
    });
    
    fileStream.on('error', (error) => {
      req.logger.error('Error streaming zip file', { error: error.message });
      if (existsSync(zipFilePath)) {
        unlinkSync(zipFilePath);
      }
      if (!res.headersSent) {
        res.status(500).json({ error: 'Failed to download settings' });
      }
    });
  } catch (error) {
    req.logger.error('Failed to download settings', { error: error.message });
    if (!res.headersSent) {
      res.status(500).json({ error: 'Failed to download settings' });
    }
  }
});

router.post('/upload-settings', upload.single('settings'), async (req, res) => {
  try {
    if (!req.file) {
      return res.status(400).json({ error: 'No file uploaded' });
    }
    
    const targetDir = getTempDir(req.WORKING_DIR, req.constants);
    const targetPath = path.join(targetDir, 'uploaded-settings.zip');
    
    renameSync(req.file.path, targetPath);
    
    await execCommand(`cd ${targetDir} && unzip -o uploaded-settings.zip`, { logger: req.logger });
    
    if (existsSync(path.join(targetDir, 'config.cfg'))) {
      await execCommand(`sudo dos2unix "${req.WORKING_DIR}/config.cfg"`, { logger: req.logger });
      await execCommand(`sudo cp "${targetDir}/config.cfg" "${req.WORKING_DIR}/config.cfg"`, { logger: req.logger });
    }
    
    res.json({ success: true, message: 'Settings uploaded' });
  } catch (error) {
    req.logger.error('Failed to upload settings', { error: error.message });
    res.status(500).json({ error: 'Failed to upload settings' });
  }
});

router.post('/exit-lbb', async (req, res) => {
  try {
    const { stopBackup } = req.body;
    
    const lockfile = getIdletimeLockfile(req.WORKING_DIR, req.constants);
    writeFileSync(lockfile, '', 'utf-8');
    
    if (stopBackup) {
      await execCommand(`sudo ${req.WORKING_DIR}/stop_backup.sh`, { logger: req.logger });
    }
    
    await execCommand('sudo pkill -f firefox', { logger: req.logger });
    
    res.json({ success: true });
  } catch (error) {
    req.logger.error('Failed to exit LBB', { error: error.message });
    res.status(500).json({ error: 'Failed to exit LBB' });
  }
});

export default router;

