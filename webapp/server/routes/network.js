import express from 'express';
import { execCommand } from '../utils/execCommand.js';

const router = express.Router();

router.get('/ips', async (req, res) => {
  try {
    const command = `python3 ${req.WORKING_DIR}/lib_network.py --Mode ip`;
    const result = await execCommand(command, { logger: req.logger });
    
    if (!result.success) {
      return res.json({ ips: [] });
    }
    
    const ips = result.stdout
      .trim()
      .split('\n')
      .filter(ip => ip.trim())
      .filter(ip => !ip.startsWith('127.0.0.'));
    
    res.json({ ips });
  } catch (error) {
    req.logger.error('Failed to get IP addresses', { error: error.message });
    res.status(500).json({ error: 'Failed to get IP addresses' });
  }
});

router.get('/internet-status', async (req, res) => {
  try {
    const command = `python3 ${req.WORKING_DIR}/lib_network.py --Mode internet_status`;
    const result = await execCommand(command, { logger: req.logger });
    
    const online = result.success && result.stdout.trim() === 'True';
    
    res.json({ online });
  } catch (error) {
    req.logger.error('Failed to get internet status', { error: error.message });
    res.status(500).json({ error: 'Failed to get internet status' });
  }
});

router.get('/qr-links', async (req, res) => {
  try {
    const protocol = req.query.protocol || (req.headers['x-forwarded-proto'] || 'https').split(',')[0] || 'https';
    const command = `python3 ${req.WORKING_DIR}/lib_network.py --Mode qr_links --Protocol ${protocol}`;
    const result = await execCommand(command, { logger: req.logger });
    
    if (!result.success) {
      return res.json({ qrLinks: [] });
    }
    
    const qrLinksHtml = result.stdout.trim();
    
    if (!qrLinksHtml) {
      return res.json({ qrLinks: [] });
    }
    
    const qrLinks = qrLinksHtml.split(/(?=<img|<a)/).filter(link => link.trim());
    
    res.json({ qrLinks });
  } catch (error) {
    req.logger.error('Failed to get QR links', { error: error.message });
    res.status(500).json({ error: 'Failed to get QR links' });
  }
});

export default router;

