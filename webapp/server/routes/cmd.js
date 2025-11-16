import express from 'express';
import { execCommand } from '../utils/execCommand.js';
import { writeFileSync, unlinkSync } from 'fs';
import { getCmdLogFile, getCmdRunnerLockfile } from '../utils/paths.js';

const router = express.Router();

// SECURITY NOTE: This endpoint executes system commands. Ensure proper authentication and authorization.
// Commands are whitelisted but parameters come from user input - validate and sanitize all parameters.
// Consider implementing rate limiting and command execution timeouts.
router.post('/execute', async (req, res) => {
  try {
    const { cmd, param1, param2, password, mailResult } = req.body;
    
    // SECURITY NOTE: Validate password if required - do not log or expose in responses
    if (!cmd) {
      return res.status(400).json({ error: 'Command required' });
    }
    
    let commandLine = '';
    
    switch (cmd) {
      case 'update':
        commandLine = `sudo python3 ${req.WORKING_DIR}/lib_display.py ':' 'Update started...' && `;
        commandLine += `sudo python3 ${req.WORKING_DIR}/lib_git.py --update && `;
        commandLine += `sudo python3 ${req.WORKING_DIR}/lib_display.py ':' 'Update complete'`;
        break;
        
      case 'update_development':
        commandLine = `sudo python3 ${req.WORKING_DIR}/lib_git.py --update-development`;
        break;
        
      case 'update_libraw':
        commandLine = `sudo bash ${req.WORKING_DIR}/mods/update_libraw.sh`;
        break;
        
      case 'fsck':
        if (param2 === 'repair') {
          commandLine = `sudo fsck -y ${param1}`;
        } else {
          commandLine = `sudo fsck -n ${param1}`;
        }
        break;
        
      case 'format':
        // SECURITY NOTE: Formatting is a destructive operation. Ensure proper authorization.
        // SECURITY NOTE: Validate param1 (device path) to prevent path traversal attacks
        if (!param1 || !param2 || param1 === '-' || param2 === '-') {
          return res.status(400).json({ error: 'Invalid parameters' });
        }
        
        // SECURITY NOTE: Validate device path - should start with /dev/ and not contain .. or other dangerous patterns
        if (!param1.startsWith('/dev/') || param1.includes('..')) {
          return res.status(400).json({ error: 'Invalid device path' });
        }
        
        // TODO: Could use Node.js library for filesystem operations, but mkfs commands require root
        let formatCommand = '';
        if (param2 === 'FAT32') {
          formatCommand = `sudo mkfs.vfat -F 32 ${param1}`;
        } else if (param2 === 'exFAT') {
          formatCommand = `sudo mkfs.exfat ${param1}`;
        } else if (param2.startsWith('NTFS')) {
          const compression = param2.includes('compression enabled');
          formatCommand = `sudo mkfs.ntfs ${compression ? '-C' : ''} ${param1}`;
        } else if (param2 === 'Ext4') {
          formatCommand = `sudo mkfs.ext4 ${param1}`;
        } else if (param2 === 'Ext3') {
          formatCommand = `sudo mkfs.ext3 ${param1}`;
        } else if (param2 === 'HFS Plus') {
          formatCommand = `sudo mkfs.hfsplus ${param1}`;
        } else if (param2 === 'HFS') {
          formatCommand = `sudo mkfs.hfs ${param1}`;
        }
        
        if (!formatCommand) {
          return res.status(400).json({ error: 'Invalid format type' });
        }
        
        commandLine = formatCommand;
        break;
        
      case 'f3':
        if (!param1 || !param2 || param1 === '-' || param2 === '-') {
          return res.status(400).json({ error: 'Invalid parameters' });
        }
        
        if (param2 === 'f3probe_non_destructive') {
          commandLine = `sudo f3probe --destructive=false ${param1}`;
        } else if (param2 === 'f3probe_destructive') {
          commandLine = `sudo f3probe --destructive=true ${param1}`;
        }
        break;
        
      case 'comitup_reset':
        const mainCommand = `${req.WORKING_DIR}/comitup-reset.sh`;
        commandLine = `cat ${mainCommand} | grep '^[^# ]' && echo '' && sudo ${mainCommand} && echo '' && echo 'FINISHED.' && sudo python3 ${req.WORKING_DIR}/lib_display.py ':' 'Comitup reset'`;
        break;
        
      default:
        return res.status(400).json({ error: 'Unknown command' });
    }
    
    if (!commandLine) {
      return res.status(400).json({ error: 'Command not implemented' });
    }
    
    const lockfile = getCmdRunnerLockfile(req.WORKING_DIR, req.constants);
    const logfile = getCmdLogFile(req.WORKING_DIR, req.constants);
    
    writeFileSync(lockfile, commandLine, 'utf-8');
    
    const fullCommand = `sh -c "${commandLine}" > ${logfile} 2>&1 & echo $!`;
    const result = await execCommand(fullCommand, { logger: req.logger });
    
    if (mailResult && result.success) {
      const pid = parseInt(result.stdout.trim());
      if (pid > 0) {
        while (true) {
          try {
            const { execSync } = await import('child_process');
            execSync(`ps -p ${pid} > /dev/null 2>&1`);
            await new Promise(resolve => setTimeout(resolve, 500));
          } catch (err) {
            break;
          }
        }
        
        const { readFileSync } = await import('fs');
        const logContent = readFileSync(logfile, 'utf-8');
        
        await execCommand(
          `sudo python3 ${req.WORKING_DIR}/lib_mail.py "${cmd} ${param1 || ''} ${param2 || ''}" "${logContent}"`,
          { logger: req.logger }
        );
      }
    }
    
    unlinkSync(lockfile);
    
    req.logger.info(`Command executed: ${cmd}`);
    res.json({ success: true, message: 'Command executed' });
  } catch (error) {
    req.logger.error('Failed to execute command', { error: error.message });
    res.status(500).json({ error: 'Failed to execute command' });
  }
});

export default router;

