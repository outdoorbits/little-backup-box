import express from 'express';
import cors from 'cors';
import path from 'path';
import { fileURLToPath } from 'url';
import { existsSync } from 'fs';
import { createLogger } from './utils/logger.js';
import configRoutes from './routes/config.js';
import backupRoutes from './routes/backup.js';
import setupRoutes from './routes/setup.js';
import viewRoutes from './routes/view.js';
import toolsRoutes from './routes/tools.js';
import sysinfoRoutes from './routes/sysinfo.js';
import cmdRoutes from './routes/cmd.js';
import logRoutes from './routes/log.js';
import displayRoutes from './routes/display.js';
import systemRoutes from './routes/system.js';
import { loadConfig, loadConstants } from './utils/configLoader.js';
import { isLinux, shouldUseMocks } from './utils/systemDetector.js';

const __filename = fileURLToPath(import.meta.url);
const __dirname = path.dirname(__filename);

const app = express();
const PORT = process.env.PORT || 3000;

const logger = createLogger();

app.use(cors());
app.use(express.json());
app.use(express.urlencoded({ extended: true }));

const WORKING_DIR = path.resolve(__dirname, '../..', 'scripts');
const config = loadConfig(WORKING_DIR);
const constants = loadConstants(WORKING_DIR);

app.use((req, res, next) => {
  req.WORKING_DIR = WORKING_DIR;
  req.config = config;
  req.constants = constants;
  req.logger = logger;
  next();
});

app.use('/api/config', configRoutes);
app.use('/api/backup', backupRoutes);
app.use('/api/setup', setupRoutes);
app.use('/api/view', viewRoutes);
app.use('/api/tools', toolsRoutes);
app.use('/api/sysinfo', sysinfoRoutes);
app.use('/api/cmd', cmdRoutes);
app.use('/api/log', logRoutes);
app.use('/api/display', displayRoutes);
app.use('/api/system', systemRoutes);

const isDev = process.env.NODE_ENV !== 'production';

app.use('/css', express.static(path.join(WORKING_DIR, 'css')));
app.use('/js', express.static(path.join(WORKING_DIR, 'js')));
app.use('/img', express.static(path.join(WORKING_DIR, 'img')));
const WEBAPP_LANG_DIR = path.resolve(__dirname, '../public/lang');
app.use('/lang', express.static(WEBAPP_LANG_DIR));
app.use('/favicon.ico', express.static(path.join(WORKING_DIR, 'favicon.ico')));

if (!isDev) {
  const distPath = path.join(__dirname, '../dist');
  app.use(express.static(distPath));
  
  app.get('*', (req, res, next) => {
    if (req.path.startsWith('/api')) {
      return next();
    }
    const indexPath = path.join(distPath, 'index.html');
    if (existsSync(indexPath)) {
      res.sendFile(indexPath);
    } else {
      res.status(404).send('Frontend not built. Run "npm run build" first.');
    }
  });
} else {
  app.get('*', (req, res, next) => {
    if (req.path.startsWith('/api')) {
      return next();
    }
    res.status(200).send(`
      <!DOCTYPE html>
      <html>
        <head>
          <title>Little Backup Box - Development Mode</title>
          <style>
            body {
              font-family: system-ui, sans-serif;
              max-width: 600px;
              margin: 50px auto;
              padding: 20px;
              background: #f5f5f5;
            }
            .card {
              background: white;
              padding: 30px;
              border-radius: 8px;
              box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            }
            h1 { color: #333; }
            .info { color: #666; margin: 20px 0; }
            .link {
              display: inline-block;
              margin-top: 20px;
              padding: 12px 24px;
              background: #007bff;
              color: white;
              text-decoration: none;
              border-radius: 4px;
            }
            .link:hover { background: #0056b3; }
          </style>
        </head>
        <body>
          <div class="card">
            <h1>Little Backup Box - Development Mode</h1>
            <p class="info">
              You're accessing the backend server directly. In development mode, 
              please access the frontend through the Vite dev server.
            </p>
            <p class="info">
              <strong>Backend API:</strong> http://localhost:${PORT}<br>
              <strong>Frontend (Vite):</strong> <a href="http://localhost:5173">http://localhost:5173</a> 
              (or check the terminal for the actual port)
            </p>
            <a href="http://localhost:5173" class="link">Go to Frontend →</a>
          </div>
        </body>
      </html>
    `);
  });
}

app.listen(PORT, () => {
  logger.info(`Server running on port ${PORT}`);
  logger.info(`Platform: ${process.platform}, Linux: ${isLinux()}, Using mocks: ${shouldUseMocks()}`);
  if (shouldUseMocks()) {
    logger.info('Running in MOCK mode - system commands will be mocked');
  }
});

