import express from 'express';
import { readFileSync } from 'fs';
import { getDisplayContentPath } from '../utils/paths.js';

const router = express.Router();

router.get('/status', (req, res) => {
  try {
    const displayContentPath = getDisplayContentPath(req.WORKING_DIR, req.constants);
    
    try {
      const content = readFileSync(displayContentPath, 'utf-8');
      res.json({ status: content.trim() });
    } catch (error) {
      res.json({ status: '' });
    }
  } catch (error) {
    req.logger.error('Failed to get display status', { error: error.message });
    res.json({ status: '' });
  }
});

export default router;

