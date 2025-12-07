import express from 'express';
import { readFileSync, writeFileSync, existsSync, unlinkSync } from 'fs';
import { parse, stringify } from 'ini';
import path from 'path';
import { execCommand } from '../utils/execCommand.js';
import multer from 'multer';

const router = express.Router();

import { fileURLToPath } from 'url';

const __filename = fileURLToPath(import.meta.url);
const __dirname = path.dirname(__filename);

const isDev = process.env.NODE_ENV !== 'production';
const uploadDir = isDev ? path.join(__dirname, '../../tmp') : '/tmp';
const upload = multer({ dest: uploadDir });

router.get('/config', async (req, res) => {
  try {
    const configPath = path.join(req.WORKING_DIR, 'config.cfg');
    const config = parse(readFileSync(configPath, 'utf-8'));
    
    res.json({
      config: {
        conf_VPN_TYPE_RSYNC: config.conf_VPN_TYPE_RSYNC || 'none',
        conf_VPN_TYPE_CLOUD: config.conf_VPN_TYPE_CLOUD || 'none',
        conf_VPN_TIMEOUT: config.conf_VPN_TIMEOUT || 20,
      },
    });
  } catch (error) {
    req.logger.error('Failed to get VPN config', { error: error.message });
    res.status(500).json({ error: 'Failed to get VPN config' });
  }
});

router.get('/status', async (req, res) => {
  try {
    const vpnTypes = ['OpenVPN', 'WireGuard'];
    const status = {};
    
    for (const vpnType of vpnTypes) {
      const vpnDir = req.constants[`const_VPN_DIR_${vpnType}`];
      const vpnFilename = req.constants[`const_VPN_FILENAME_${vpnType}`];
      
      if (vpnDir && vpnFilename) {
        const vpnConfigFile = path.join(vpnDir, vpnFilename);
        const fileExists = existsSync(vpnConfigFile);
        
        status[vpnType] = {
          fileExists,
          filePath: vpnConfigFile,
        };
      } else {
        status[vpnType] = {
          fileExists: false,
          filePath: null,
        };
      }
    }
    
    res.json(status);
  } catch (error) {
    req.logger.error('Failed to get VPN status', { error: error.message });
    res.status(500).json({ error: 'Failed to get VPN status' });
  }
});

router.post('/upload', upload.single('file'), async (req, res) => {
  try {
    if (!req.file) {
      return res.status(400).json({ error: 'No file uploaded' });
    }
    
    const { type } = req.body;
    
    if (!type || type === 'none' || !['OpenVPN', 'WireGuard'].includes(type)) {
      unlinkSync(req.file.path);
      return res.status(400).json({ error: 'Invalid VPN type' });
    }
    
    const vpnDir = req.constants[`const_VPN_DIR_${type}`];
    const vpnFilename = req.constants[`const_VPN_FILENAME_${type}`];
    
    if (!vpnDir || !vpnFilename) {
      unlinkSync(req.file.path);
      return res.status(500).json({ error: 'VPN directory or filename not configured' });
    }
    
    await execCommand(`sudo mkdir -p "${vpnDir}"`, { logger: req.logger });
    
    const targetPath = path.join(vpnDir, vpnFilename);
    
    if (req.file.originalname.endsWith('.zip')) {
      await execCommand(`cd "${uploadDir}" && unzip -o "${req.file.filename}"`, { logger: req.logger });
      const extractedFiles = await execCommand(`cd "${uploadDir}" && find . -name "*.conf" -o -name "*.ovpn" -o -name "*.wg" | head -1`, { logger: req.logger });
      if (extractedFiles.success && extractedFiles.stdout.trim()) {
        const extractedFile = path.join(uploadDir, extractedFiles.stdout.trim().replace(/^\.\//, ''));
        await execCommand(`sudo mv "${extractedFile}" "${targetPath}"`, { logger: req.logger });
      } else {
        unlinkSync(req.file.path);
        return res.status(400).json({ error: 'No valid VPN config file found in ZIP' });
      }
    } else {
      await execCommand(`sudo mv "${req.file.path}" "${targetPath}"`, { logger: req.logger });
    }
    
    await execCommand(`sudo chown root:root "${targetPath}"`, { logger: req.logger });
    await execCommand(`sudo chmod 700 "${targetPath}"`, { logger: req.logger });
    await execCommand(`sudo dos2unix "${targetPath}"`, { logger: req.logger });
    
    res.json({ success: true, message: 'VPN config file uploaded' });
  } catch (error) {
    req.logger.error('Failed to upload VPN config file', { error: error.message });
    if (req.file && existsSync(req.file.path)) {
      try {
        unlinkSync(req.file.path);
      } catch (e) {
      }
    }
    res.status(500).json({ error: 'Failed to upload VPN config file' });
  }
});

router.post('/remove', async (req, res) => {
  try {
    const { type } = req.body;
    
    if (!type || !['OpenVPN', 'WireGuard'].includes(type)) {
      return res.status(400).json({ error: 'Invalid VPN type' });
    }
    
    const vpnDir = req.constants[`const_VPN_DIR_${type}`];
    const vpnFilename = req.constants[`const_VPN_FILENAME_${type}`];
    
    if (!vpnDir || !vpnFilename) {
      return res.status(500).json({ error: 'VPN directory or filename not configured' });
    }
    
    const vpnConfigFile = path.join(vpnDir, vpnFilename);
    
    if (!existsSync(vpnConfigFile)) {
      return res.status(404).json({ error: 'VPN config file not found' });
    }
    
    await execCommand(`sudo rm "${vpnConfigFile}"`, { logger: req.logger });
    
    res.json({ success: true, message: 'VPN config file removed' });
  } catch (error) {
    req.logger.error('Failed to remove VPN config file', { error: error.message });
    res.status(500).json({ error: 'Failed to remove VPN config file' });
  }
});

export default router;

