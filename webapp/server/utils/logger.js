import winston from 'winston';
import path from 'path';
import { fileURLToPath } from 'url';
import { loadConfig, loadConstants } from './configLoader.js';
import { getLogFile } from './paths.js';

const __filename = fileURLToPath(import.meta.url);
const __dirname = path.dirname(__filename);

const WORKING_DIR = path.resolve(__dirname, '../../../scripts');

let config, constants;
try {
  config = loadConfig(WORKING_DIR);
  constants = loadConstants(WORKING_DIR);
} catch (err) {
  console.error('Failed to load config for logger:', err);
}

const passwordFields = ['conf_PASSWORD', 'conf_MAIL_PASSWORD', 'conf_RSYNC_PASSWORD'];

const passwordFilter = winston.format((info) => {
  if (config && constants) {
    passwordFields.forEach((field) => {
      try {
        const password = Buffer.from(config[field] || '', 'base64').toString('utf-8');
        if (password && info.message) {
          info.message = info.message.replace(new RegExp(password.replace(/[.*+?^${}()|[\]\\]/g, '\\$&'), 'g'), '***');
        }
      } catch (err) {
      }
    });
  }
  return info;
});

export function createLogger() {
  const logFile = getLogFile(WORKING_DIR, constants);
  const logLevel = config?.conf_LOGLEVEL || 10;

  return winston.createLogger({
    level: 'info',
    format: winston.format.combine(
      passwordFilter(),
      winston.format.timestamp(),
      winston.format.errors({ stack: true }),
      winston.format.printf(({ timestamp, level, message, ...meta }) => {
        return `${timestamp} [${level.toUpperCase()}]: ${message} ${Object.keys(meta).length ? JSON.stringify(meta) : ''}`;
      })
    ),
    transports: [
      new winston.transports.File({ filename: logFile }),
      new winston.transports.Console({
        format: winston.format.combine(
          passwordFilter(),
          winston.format.colorize(),
          winston.format.simple()
        ),
      }),
    ],
  });
}

