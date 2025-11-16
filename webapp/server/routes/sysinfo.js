import express from 'express';
import { execCommand } from '../utils/execCommand.js';

const router = express.Router();

// TODO: Could replace with Node.js libraries:
// - Temperature: Use fs.readFileSync to read /sys/class/thermal/thermal_zone*/temp directly
// - CPU/Memory: Use os module (os.cpus(), os.totalmem(), os.freemem()) or systeminformation library
// - System model: Parse /proc/cpuinfo or use systeminformation library
router.get('/system', async (req, res) => {
  try {
    const modelResult = await execCommand(
      `sudo python3 ${req.WORKING_DIR}/lib_system.py --get_pi_model`,
      { logger: req.logger }
    );
    
    // TODO: Replace with fs.readFileSync('/sys/class/thermal/thermal_zone0/temp')
    const tempResult = await execCommand(
      'cat /sys/class/thermal/thermal_zone*/temp',
      { logger: req.logger }
    );
    
    // TODO: Replace with os module or systeminformation library
    const cpuResult = await execCommand(
      "vmstat | tail -1 | awk '{print $15}'",
      { logger: req.logger }
    );
    
    // TODO: Replace with os.totalmem() and os.freemem()
    const memRamResult = await execCommand(
      "free | grep Mem | awk '{print $3/$2 * 100.0}'",
      { logger: req.logger }
    );
    
    const memRamAllResult = await execCommand(
      "free | grep Mem | awk '{print $2 / 1024}'",
      { logger: req.logger }
    );
    
    const memSwapResult = await execCommand(
      "free | grep Swap | awk '{print $3/$2 * 100.0}'",
      { logger: req.logger }
    );
    
    const memSwapAllResult = await execCommand(
      "free | grep Swap | awk '{print $2 / 1024}'",
      { logger: req.logger }
    );
    
    const abnormalResult = await execCommand(
      `sudo python3 ${req.WORKING_DIR}/lib_system.py --get_abnormal_system_conditions`,
      { logger: req.logger }
    );
    
    const model = modelResult.success ? modelResult.stdout.trim() : 'Unknown';
    const temp = tempResult.success ? Math.round(parseFloat(tempResult.stdout) / 1000 * 10) / 10 : null;
    const cpuusage = cpuResult.success ? 100 - parseFloat(cpuResult.stdout.trim()) : null;
    const memRamFrac = memRamResult.success ? parseFloat(memRamResult.stdout.trim()) : null;
    const memRamAll = memRamAllResult.success ? Math.round(parseFloat(memRamAllResult.stdout.trim())) : null;
    const memSwapFrac = memSwapResult.success ? parseFloat(memSwapResult.stdout.trim()) : null;
    const memSwapAll = memSwapAllResult.success ? Math.round(parseFloat(memSwapAllResult.stdout.trim())) : null;
    const abnormalConditions = abnormalResult.success ? abnormalResult.stdout.trim() : '';
    
    res.json({
      model,
      temp,
      cpuusage,
      memRam: memRamFrac !== null && memRamAll !== null ? `${Math.round(memRamFrac * 10) / 10} % * ${memRamAll} MB` : null,
      memSwap: memSwapFrac !== null && memSwapAll !== null ? `${Math.round(memSwapFrac * 10) / 10} % * ${memSwapAll} MB` : null,
      abnormalConditions,
    });
  } catch (error) {
    req.logger.error('Failed to get system info', { error: error.message });
    res.status(500).json({ error: 'Failed to get system info' });
  }
});

router.get('/diskspace', async (req, res) => {
  try {
    const result = await execCommand(
      'sudo lsblk -P --output NAME,SIZE,FSAVAIL,FSUSED,FSUSE%,MOUNTPOINT',
      { logger: req.logger }
    );
    
    if (!result.success || !result.stdout) {
      return res.status(500).json({ error: 'Failed to get disk space' });
    }
    
    const lines = result.stdout.trim().split('\n').filter(line => line.trim());
    if (lines.length === 0) {
      const headers = ['NAME', 'SIZE', 'FSAVAIL', 'FSUSED', 'FSUSE%', 'MOUNTPOINT'];
      return res.json({ output: headers.join('\t') });
    }
    
    const headers = ['NAME', 'SIZE', 'FSAVAIL', 'FSUSED', 'FSUSE%', 'MOUNTPOINT'];
    const output = [headers.join('\t')];
    
    for (const line of lines) {
      const fields = {};
      const regex = /([A-Z%]+)="([^"]*)"/g;
      let match;
      while ((match = regex.exec(line)) !== null) {
        fields[match[1]] = match[2];
      }
      
      const row = headers.map(header => fields[header] || '').join('\t');
      output.push(row);
    }
    
    res.json({ output: output.join('\n') });
  } catch (error) {
    req.logger.error('Failed to get disk space', { error: error.message });
    res.status(500).json({ error: 'Failed to get disk space' });
  }
});

router.get('/devices', async (req, res) => {
  try {
    const result = await execCommand(
      'sudo lsblk -P --output NAME,FSTYPE,UUID,MODEL',
      { logger: req.logger }
    );
    
    if (!result.success || !result.stdout) {
      return res.status(500).json({ error: 'Failed to get devices' });
    }
    
    const lines = result.stdout.trim().split('\n').filter(line => line.trim());
    if (lines.length === 0) {
      const headers = ['NAME', 'FSTYPE', 'UUID', 'MODEL'];
      return res.json({ output: headers.join('\t') });
    }
    
    const headers = ['NAME', 'FSTYPE', 'UUID', 'MODEL'];
    const output = [headers.join('\t')];
    
    for (const line of lines) {
      const fields = {};
      const regex = /([A-Z]+)="([^"]*)"/g;
      let match;
      while ((match = regex.exec(line)) !== null) {
        fields[match[1]] = match[2];
      }
      
      const row = headers.map(header => fields[header] || '').join('\t');
      output.push(row);
    }
    
    res.json({ output: output.join('\n') });
  } catch (error) {
    req.logger.error('Failed to get devices', { error: error.message });
    res.status(500).json({ error: 'Failed to get devices' });
  }
});

router.get('/device-states', async (req, res) => {
  try {
    const partitionsResult = await execCommand(
      `sudo python3 ${req.WORKING_DIR}/lib_storage.py --Action get_available_partitions --skipMounted False --ignore-fs True`,
      { logger: req.logger }
    );
    
    if (!partitionsResult.success) {
      return res.status(500).json({ error: 'Failed to get partitions' });
    }
    
    const partitions = partitionsResult.stdout
      .trim()
      .split('\n')
      .filter(line => line.trim());
    
    const deviceStates = [];
    
    for (const partition of partitions) {
      const [lum, ...identifierParts] = partition.split(': ');
      const identifier = identifierParts.join(': ');
      
      if (lum) {
        const smartResult = await execCommand(
          `sudo smartctl -a ${lum}`,
          { logger: req.logger }
        );
        
        if (smartResult.success) {
          const lines = smartResult.stdout.split('\n');
          const states = [];
          
          for (const line of lines) {
            if (line.includes(': ')) {
              const [value, ...measuredParts] = line.split(':');
              const measured = measuredParts.join(':').trim();
              states.push({
                value: value.trim() + ':',
                measured,
              });
            }
          }
          
          deviceStates.push({
            lum,
            identifier,
            states,
          });
        }
      }
    }
    
    res.json({ deviceStates });
  } catch (error) {
    req.logger.error('Failed to get device states', { error: error.message });
    res.status(500).json({ error: 'Failed to get device states' });
  }
});

router.get('/cameras', async (req, res) => {
  try {
    const detectResult = await execCommand(
      'sudo gphoto2 --auto-detect',
      { logger: req.logger }
    );
    
    if (!detectResult.success) {
      return res.json({ cameras: [] });
    }
    
    const lines = detectResult.stdout.trim().split('\n');
    if (lines.length <= 2) {
      return res.json({ cameras: [] });
    }
    
    const firstColumnLength = lines[0].indexOf('Port');
    const cameras = [];
    
    for (let i = 2; i < lines.length; i++) {
      const line = lines[i];
      const model = line.substring(0, firstColumnLength).trim();
      const port = line.substring(firstColumnLength).trim();
      
      const summaryResult = await execCommand(
        `sudo gphoto2 --camera '${model}' --port '${port}' --summary | grep 'Model' | cut -d: -f2`,
        { logger: req.logger }
      );
      
      const serialResult = await execCommand(
        `sudo gphoto2 --camera '${model}' --port '${port}' --summary | grep 'Serial Number' | cut -d: -f2 | tr -d ' '`,
        { logger: req.logger }
      );
      
      const storageResult = await execCommand(
        `sudo gphoto2 --camera '${model}' --port '${port}' --storage-info | grep 'basedir' | cut -d= -f2 | tr -d ' '`,
        { logger: req.logger }
      );
      
      const modelName = summaryResult.success && summaryResult.stdout.trim()
        ? summaryResult.stdout.trim().replace(/[^a-zA-Z0-9-]/g, '_')
        : model;
      
      const serial = serialResult.success && serialResult.stdout.trim()
        ? serialResult.stdout.trim().replace(/[^a-zA-Z0-9-_.]/g, '_').replace(/^0+/, '')
        : '';
      
      const storages = storageResult.success
        ? storageResult.stdout.trim().split('\n').filter(s => s.trim())
        : [];
      
      cameras.push({
        model: modelName,
        port,
        serial,
        storages,
      });
    }
    
    res.json({ cameras });
  } catch (error) {
    req.logger.error('Failed to get cameras', { error: error.message });
    res.status(500).json({ error: 'Failed to get cameras' });
  }
});

router.get('/wifi', async (req, res) => {
  try {
    const interfacesResult = await execCommand(
      "iw dev | awk '$1==\"Interface\"{print $2}'",
      { logger: req.logger }
    );
    
    if (!interfacesResult.success) {
      return res.json({ wifi: [] });
    }
    
    const interfaces = interfacesResult.stdout
      .trim()
      .split('\n')
      .filter(line => line.trim())
      .sort();
    
    const wifiInfo = [];
    
    for (const iface of interfaces) {
      const result = await execCommand(
        `iwconfig ${iface}`,
        { logger: req.logger }
      );
      
      if (result.success) {
        wifiInfo.push({
          interface: iface,
          info: result.stdout,
        });
      }
    }
    
    res.json({ wifi: wifiInfo });
  } catch (error) {
    req.logger.error('Failed to get WiFi info', { error: error.message });
    res.status(500).json({ error: 'Failed to get WiFi info' });
  }
});

export default router;

