import express from 'express';
import { readFileSync, writeFileSync } from 'fs';
import { parse, stringify } from 'ini';
import path from 'path';
import { execCommand } from '../utils/execCommand.js';

const router = express.Router();

router.get('/', (req, res) => {
  try {
    res.json({ config: req.config, constants: req.constants });
  } catch (error) {
    req.logger.error('Failed to get config', { error: error.message });
    res.status(500).json({ error: 'Failed to load config' });
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

    writeFileSync(configPath, stringify(currentConfig), 'utf-8');
    
    req.logger.info('Config saved');
    res.json({ success: true });
  } catch (error) {
    req.logger.error('Failed to save config', { error: error.message });
    res.status(500).json({ error: 'Failed to save config' });
  }
});

export default router;


