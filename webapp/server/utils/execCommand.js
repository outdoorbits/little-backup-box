import { exec, execSync } from 'child_process';
import { promisify } from 'util';
import { shouldMock, mockCommand } from './mockSystem.js';

const execAsync = promisify(exec);

// SECURITY NOTE: This function executes shell commands. Commands should be validated and sanitized
// before being passed here. Consider using parameterized commands or Node.js libraries where possible.
// Some commands that could be replaced with Node.js libraries:
// - File operations: Use fs module instead of shell commands
// - Process management: Use child_process.spawn with proper options
// - System info: Use os module or systeminformation library
// - Network operations: Use http/https modules or axios
export async function execCommand(command, options = {}) {
  const { logger, logCommand = true, logLevel = 10 } = options;
  
  if (shouldMock()) {
    return mockCommand(command, options);
  }
  
  if (logCommand && logger) {
    logger.info(`Executing: ${command}`);
  }

  try {
    const { stdout, stderr } = await execAsync(command, {
      maxBuffer: 10 * 1024 * 1024,
      ...options.execOptions,
    });
    
    if (stderr && logger) {
      logger.warn(`Command stderr: ${stderr}`);
    }
    
    return { success: true, stdout, stderr };
  } catch (error) {
    if (logger) {
      logger.error(`Command failed: ${command}`, { error: error.message });
    }
    return { success: false, error: error.message, stdout: error.stdout, stderr: error.stderr };
  }
}

export function execCommandSync(command, options = {}) {
  const { logger, logCommand = true } = options;
  
  if (shouldMock()) {
    return mockCommand(command, options);
  }
  
  if (logCommand && logger) {
    logger.info(`Executing (sync): ${command}`);
  }

  try {
    const stdout = execSync(command, {
      maxBuffer: 10 * 1024 * 1024,
      encoding: 'utf-8',
      ...options.execOptions,
    });
    return { success: true, stdout };
  } catch (error) {
    if (logger) {
      logger.error(`Command failed (sync): ${command}`, { error: error.message });
    }
    return { success: false, error: error.message, stdout: error.stdout };
  }
}

