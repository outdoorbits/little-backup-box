import express from 'express';
import { isLinux, shouldUseMocks } from '../utils/systemDetector.js';
import { execCommand } from '../utils/execCommand.js';

const router = express.Router();

router.get('/info', (req, res) => {
  res.json({
    platform: process.platform,
    isLinux: isLinux(),
    useMocks: shouldUseMocks(),
    nodeVersion: process.version,
  });
});

router.post('/reboot', async (req, res) => {
  try {
    const command = `sudo python3 ${req.WORKING_DIR}/lib_poweroff.py reboot`;
    await execCommand(command, { logger: req.logger });
    req.logger.info('Reboot initiated');
    res.json({ success: true, message: 'Reboot initiated' });
  } catch (error) {
    req.logger.error('Failed to reboot', { error: error.message });
    res.status(500).json({ error: 'Failed to reboot' });
  }
});

router.post('/shutdown', async (req, res) => {
  try {
    const command = `sudo python3 ${req.WORKING_DIR}/lib_poweroff.py poweroff`;
    await execCommand(command, { logger: req.logger });
    req.logger.info('Shutdown initiated');
    res.json({ success: true, message: 'Shutdown initiated' });
  } catch (error) {
    req.logger.error('Failed to shutdown', { error: error.message });
    res.status(500).json({ error: 'Failed to shutdown' });
  }
});

export default router;


