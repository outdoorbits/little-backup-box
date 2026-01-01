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
        
      case 'fsck': {
        const devicePath = param1.startsWith('/dev/') ? param1 : `/dev/${param1}`;
        
        const fsckDetectCommand = `sudo lsblk -p -P -o PATH,MOUNTPOINT,UUID,FSTYPE | grep ${devicePath}`;
        const fsckDetectResult = await execCommand(fsckDetectCommand, { logger: req.logger });
        
        let deviceFstype = '';
        if (fsckDetectResult.success && fsckDetectResult.stdout) {
          const fstypeMatch = fsckDetectResult.stdout.match(/FSTYPE="([^"]+)"/);
          if (fstypeMatch) {
            deviceFstype = fstypeMatch[1];
          }
        }
        
        if (!deviceFstype) {
          return res.status(400).json({ error: 'Could not detect filesystem type' });
        }
        
        let mainCommand = '';
        if (param2 === 'repair') {
          if (deviceFstype === 'exfat') {
            mainCommand = `fsck.${deviceFstype} -p ${devicePath}`;
          } else {
            mainCommand = `fsck.${deviceFstype} -f -p ${devicePath}`;
          }
        } else {
          mainCommand = `fsck.${deviceFstype} ${devicePath}`;
        }
        
        const startMsg1 = 'Start';
        const startMsg2 = 'file system';
        const stopMsg1 = 'Finished file';
        const stopMsg2 = 'system';
        
        commandLine = `sudo python3 ${req.WORKING_DIR}/lib_display.py ':' '${startMsg1}' ':' '${startMsg2}' ':' '${param2}' && `;
        commandLine += `echo 'sudo ${mainCommand}' && `;
        commandLine += `echo '' && `;
        commandLine += `sudo ${mainCommand} && `;
        commandLine += `echo '' && `;
        commandLine += `echo 'FINISHED.' && `;
        commandLine += `sudo python3 ${req.WORKING_DIR}/lib_display.py ':' '${stopMsg1}' ':' '${stopMsg2}' ':' '${param2}'`;
        break;
      }
        
      case 'format': {
        if (!param1 || !param2 || param1 === '-' || param2 === '-') {
          return res.status(400).json({ error: 'Invalid parameters' });
        }
        
        const formatDevicePath = param1.startsWith('/dev/') ? param1 : `/dev/${param1}`;
        
        if (!formatDevicePath.startsWith('/dev/') || formatDevicePath.includes('..')) {
          return res.status(400).json({ error: 'Invalid device path' });
        }
        
        const startMsg1 = 'Formatting';
        const startMsg2 = 'started...';
        const stopMsg1 = 'Formatting';
        const stopMsg2 = 'completed';
        
        let mainFormatCommand = '';
        let fsckCommand = 'fsck';
        
        if (param2 === 'FAT32') {
          mainFormatCommand = `mkfs.vfat -v -I -F32 ${formatDevicePath}`;
        } else if (param2 === 'exFAT') {
          mainFormatCommand = `mkfs.exfat ${formatDevicePath}`;
        } else if (param2 === 'NTFS (compression enabled)') {
          mainFormatCommand = `mkfs.ntfs --enable-compression --force --verbose ${formatDevicePath}`;
        } else if (param2 === 'NTFS (no compression)') {
          mainFormatCommand = `mkfs.ntfs --force --verbose ${formatDevicePath}`;
        } else if (param2 === 'Ext4') {
          mainFormatCommand = `mkfs.ext4 -v -F ${formatDevicePath}`;
        } else if (param2 === 'Ext3') {
          mainFormatCommand = `mkfs.ext3 -v -F ${formatDevicePath}`;
        } else if (param2 === 'HFS Plus') {
          mainFormatCommand = `mkfs.hfsplus ${formatDevicePath}`;
          fsckCommand = 'fsck.hfsplus';
        } else if (param2 === 'HFS') {
          mainFormatCommand = `mkfs.hfs ${formatDevicePath}`;
          fsckCommand = 'fsck.hfs';
        } else {
          return res.status(400).json({ error: 'Invalid format type' });
        }
        
        commandLine = `sudo python3 ${req.WORKING_DIR}/lib_display.py ':' '${startMsg1}' ':' '${param1}: ${param2}' ':' '${startMsg2}' && `;
        commandLine += `echo 'sudo ${mainFormatCommand}' && `;
        commandLine += `echo '' && `;
        commandLine += `sudo ${mainFormatCommand} && `;
        commandLine += `echo '' && `;
        commandLine += `sudo fdisk -l ${formatDevicePath} && `;
        commandLine += `echo '' && `;
        commandLine += `lsblk -f ${formatDevicePath} && `;
        commandLine += `echo '' && `;
        commandLine += `sudo ${fsckCommand} ${formatDevicePath} && `;
        commandLine += `echo '' && `;
        commandLine += `echo 'FINISHED.' && `;
        commandLine += `sudo python3 ${req.WORKING_DIR}/lib_display.py ':' '${stopMsg1}' ':' '${param1}: ${param2}' ':' '${stopMsg2}'`;
        break;
      }
        
      case 'f3': {
        if (!param1 || !param2 || param1 === '-' || param2 === '-') {
          return res.status(400).json({ error: 'Invalid parameters' });
        }
        
        const f3DevicePath = param1.startsWith('/dev/') ? param1 : `/dev/${param1}`;
        
        const probeStartMsg1 = 'Examination';
        const probeStartMsg2 = 'started...';
        const probeStopMsg1 = 'Examination';
        const probeStopMsg2 = 'completed';
        
        let mainF3Command = '';
        let description = '';
        
        if (param2 === 'f3probe_non_destructive') {
          mainF3Command = `f3probe --time-ops ${f3DevicePath}`;
          description = 'non destructive';
        } else if (param2 === 'f3probe_destructive') {
          mainF3Command = `f3probe --destructive --time-ops ${f3DevicePath}`;
          description = 'destructive';
        } else {
          return res.status(400).json({ error: 'Invalid f3 action' });
        }
        
        commandLine = `sudo python3 ${req.WORKING_DIR}/lib_display.py ':' '${probeStartMsg1}' ':' '${param1}: ${description}' ':' '${probeStartMsg2}' && `;
        commandLine += `echo 'sudo ${mainF3Command}' && `;
        commandLine += `echo '' && `;
        commandLine += `sudo ${mainF3Command} && `;
        commandLine += `echo '' && `;
        commandLine += `echo 'FINISHED.' && `;
        commandLine += `sudo python3 ${req.WORKING_DIR}/lib_display.py ':' '${probeStopMsg1}' ':' '${param1}: ${description}' ':' '${probeStopMsg2}'`;
        break;
      }
        
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

