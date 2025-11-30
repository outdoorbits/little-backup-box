import express from 'express';
import { readFileSync, unlinkSync } from 'fs';
import { getLogFile } from '../utils/paths.js';

const router = express.Router();

router.get('/', (req, res) => {
  try {
    const { file } = req.query;
    const logFile = file || getLogFile(req.WORKING_DIR, req.constants);
    
    try {
      const content = readFileSync(logFile, 'utf-8');
      res.send(content);
    } catch (error) {
      res.send('');
    }
  } catch (error) {
    req.logger.error('Failed to read log', { error: error.message });
    res.status(500).send('');
  }
});

router.post('/delete', (req, res) => {
  try {
    const { file } = req.body;
    const logFile = file || getLogFile(req.WORKING_DIR, req.constants);
    
    unlinkSync(logFile);
    
    req.logger.info('Log deleted');
    res.json({ success: true });
  } catch (error) {
    req.logger.error('Failed to delete log', { error: error.message });
    res.status(500).json({ error: 'Failed to delete log' });
  }
});

export default router;

