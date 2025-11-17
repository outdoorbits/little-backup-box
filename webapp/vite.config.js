import { defineConfig } from 'vite';
import react from '@vitejs/plugin-react';
import path from 'path';
import fs from 'fs';
import { fileURLToPath } from 'url';

const __filename = fileURLToPath(import.meta.url);
const __dirname = path.dirname(__filename);
const repoScrapeDir = path.resolve(__dirname, '../scrape');
const bundledScrapeDir = path.resolve(__dirname, './scrape-data');

const resolveScrapeSource = () => {
  if (fs.existsSync(repoScrapeDir)) return repoScrapeDir;
  if (fs.existsSync(bundledScrapeDir)) return bundledScrapeDir;
  return null;
};

const scrapeSourceDir = resolveScrapeSource();
const manifestFileName = 'scrape-manifest.json';
const phpStaticMap = new Map();

const ensureWithinDir = (targetPath, rootDir) => {
  const normalized = path.normalize(targetPath);
  return normalized.startsWith(rootDir);
};

const ensureTrailingSlash = (value = '/') => (value.endsWith('/') ? value : `${value}/`);
const normalizePosixPath = (value) => value.replace(/\\/g, '/');
const isPhpRelativePath = (relativePath) => {
  const [pathWithoutQuery] = normalizePosixPath(relativePath).split('?');
  return pathWithoutQuery.toLowerCase().endsWith('.php');
};

const sanitizeQuerySegment = (value = '') =>
  value
    .replace(/[^a-z0-9]+/gi, '-')
    .replace(/-+/g, '-')
    .replace(/^-|-$/g, '')
    .toLowerCase();

const buildStaticHtmlPath = (pathWithoutQuery, queryPart = '') => {
  const dir = path.posix.dirname(pathWithoutQuery);
  const baseName = path.posix.basename(pathWithoutQuery, '.php');
  const suffix = queryPart ? `-${sanitizeQuerySegment(queryPart)}` : '';
  const fileName = `${baseName}${suffix}.html`;
  return dir === '.' ? fileName : `${dir}/${fileName}`;
};

const getStaticHtmlPathForFile = (relativePath) => {
  const posixPath = normalizePosixPath(relativePath);
  const [pathWithoutQuery, queryPart = ''] = posixPath.split('?');
  if (!pathWithoutQuery.toLowerCase().endsWith('.php')) {
    return null;
  }
  return buildStaticHtmlPath(pathWithoutQuery, queryPart);
};

const buildPhpStaticMap = () => {
  phpStaticMap.clear();
  if (!scrapeSourceDir || !fs.existsSync(scrapeSourceDir)) {
    return;
  }

  const siteDirs = fs
    .readdirSync(scrapeSourceDir, { withFileTypes: true })
    .filter((dirent) => dirent.isDirectory());

  for (const dirent of siteDirs) {
    const siteId = dirent.name;
    const sitePath = path.join(scrapeSourceDir, siteId);

    const walk = (currentRelative) => {
      const currentPath = currentRelative ? path.join(sitePath, currentRelative) : sitePath;
      const entries = fs.readdirSync(currentPath, { withFileTypes: true });

      for (const entry of entries) {
        const entryRelative = currentRelative
          ? path.join(currentRelative, entry.name)
          : entry.name;

        if (entry.isDirectory()) {
          walk(entryRelative);
          continue;
        }

        const relativePosix = normalizePosixPath(entryRelative);
        const staticRelativeWithinSite = getStaticHtmlPathForFile(relativePosix);
        if (!staticRelativeWithinSite) {
          continue;
        }
        const mapKey = normalizePosixPath(`${siteId}/${staticRelativeWithinSite}`);
        const originalPath = normalizePosixPath(`${siteId}/${relativePosix}`);
        phpStaticMap.set(mapKey, originalPath);
      }
    };

    walk('');
  }
};

buildPhpStaticMap();

const getSitePath = (siteId) => {
  if (!scrapeSourceDir) return null;
  const sitePath = path.join(scrapeSourceDir, siteId);
  return fs.existsSync(sitePath) ? sitePath : null;
};

const getSiteStaticOptions = (siteId) => {
  const sitePath = getSitePath(siteId);
  if (!sitePath) return {};
  const logFile = path.join(sitePath, 'tmp', 'little-backup-box.log');
  let logContent = null;
  if (fs.existsSync(logFile)) {
    try {
      logContent = fs.readFileSync(logFile, 'utf-8');
    } catch {
      logContent = null;
    }
  }
  return { logContent };
};

const getMimeType = (filePath) => {
  const ext = path.extname(filePath).toLowerCase();
  switch (ext) {
    case '.html':
    case '.php':
      return 'text/html';
    case '.css':
      return 'text/css';
    case '.js':
      return 'application/javascript';
    case '.json':
      return 'application/json';
    case '.png':
      return 'image/png';
    case '.jpg':
    case '.jpeg':
      return 'image/jpeg';
    case '.svg':
      return 'image/svg+xml';
    case '.gif':
      return 'image/gif';
    case '.ico':
      return 'image/x-icon';
    case '.log':
      return 'text/plain';
    default:
      return 'application/octet-stream';
  }
};

const convertPhpReferenceToStatic = (value, siteBasePath) => {
  const base = ensureTrailingSlash(siteBasePath || '/');
  let rewritten = value;
  let prefix = '';

  if (/^(?:[a-z]+:)?\/\//i.test(rewritten)) {
    if (!rewritten.startsWith(base)) {
      return value;
    }
    prefix = base;
    rewritten = rewritten.slice(base.length);
  } else if (rewritten.startsWith(base)) {
    prefix = base;
    rewritten = rewritten.slice(base.length);
  }

  const hashIndex = rewritten.indexOf('#');
  let hashFragment = '';
  if (hashIndex !== -1) {
    hashFragment = rewritten.slice(hashIndex);
    rewritten = rewritten.slice(0, hashIndex);
  }

  const [pathWithoutQuery, queryPart = ''] = rewritten.split('?');
  if (!pathWithoutQuery.toLowerCase().endsWith('.php')) {
    return value;
  }

  const staticPath = buildStaticHtmlPath(pathWithoutQuery, queryPart);
  return `${prefix}${staticPath}${hashFragment}`;
};

const rewriteResourceUrl = (value, { siteBasePath, sanitizePhpLinks }) => {
  if (!value) return value;
  const trimmed = value.trim();
  if (
    !trimmed ||
    trimmed.startsWith('#') ||
    trimmed.startsWith('javascript:') ||
    trimmed.startsWith('data:') ||
    trimmed.startsWith('mailto:')
  ) {
    return value;
  }

  const base = ensureTrailingSlash(siteBasePath || '/');
  const localHostRegex = /^https?:\/\/(?:lbb\.local|localhost)(?::\d+)?\//i;

  let rewritten = trimmed;

  if (localHostRegex.test(rewritten)) {
    rewritten = rewritten.replace(localHostRegex, base);
  } else if (rewritten.startsWith('//')) {
    return value;
  } else if (rewritten.startsWith('/')) {
    rewritten = base + rewritten.slice(1);
  }

  if (sanitizePhpLinks) {
    rewritten = convertPhpReferenceToStatic(rewritten, base);
  }

  return rewritten;
};

const escapeHtml = (value = '') =>
  value
    .replace(/&/g, '&amp;')
    .replace(/</g, '&lt;')
    .replace(/>/g, '&gt;')
    .replace(/"/g, '&quot;');

const rewriteHtmlContent = (html, siteBasePath, options = {}) => {
  if (!siteBasePath) {
    return html;
  }
  const { sanitizePhpLinks = false, logContent = null } = options;
  const attrRegex = /\b(href|src|action)(\s*=\s*)("([^"]*)"|'([^']*)')/gi;

  let rewrittenHtml = html.replace(
    attrRegex,
    (fullMatch, attrName, separator, quotedValue, doubleQuoted, singleQuoted) => {
      const quoteChar = quotedValue[0];
      const currentValue = quoteChar === '"' ? doubleQuoted ?? '' : singleQuoted ?? '';
      const updatedValue = rewriteResourceUrl(currentValue, {
        siteBasePath,
        sanitizePhpLinks,
      });

      if (updatedValue === currentValue) {
        return fullMatch;
      }

      return `${attrName}${separator}${quoteChar}${updatedValue}${quoteChar}`;
    }
  );

  if (logContent !== null) {
    const escapedLog = escapeHtml(logContent);
    const logContainer = `
<div class="logmonitor-static-wrapper" style="margin-top: 1rem;">
  <h2>Log monitor</h2>
  <pre class="logmonitor-static" style="background:#111;color:#0f0;padding:1em;overflow:auto;">${escapedLog}</pre>
  <p class="logmonitor-note" style="font-style:italic;color:#888;margin-top:0.5rem;">(Static log snapshot – live updates disabled in archived view)</p>
</div>
`;
    const iframeRegex = /<iframe\b[^>]*id=["']logmonitor["'][\s\S]*?<\/iframe>/i;
    if (iframeRegex.test(rewrittenHtml)) {
      rewrittenHtml = rewrittenHtml.replace(iframeRegex, logContainer);
    } else {
      const logCardRegex =
        /<div[^>]*class=["'][^"']*card[^"']*["'][^>]*>\s*<h2[^>]*>[^<]*Log monitor[^<]*<\/h2>[\s\S]*?<\/div>/i;
      rewrittenHtml = rewrittenHtml.replace(logCardRegex, logContainer);
    }

    const downloadLinkRegex = /<a\b[^>]*href=["'][^"']*little-backup-box\.log[^"']*["'][^>]*>[\s\S]*?<\/a>/gi;
    rewrittenHtml = rewrittenHtml.replace(
      downloadLinkRegex,
      '<span class="logmonitor-download-static" style="font-size:0.9em;color:#888;">Download unavailable in static archive</span>'
    );

    const refreshButtonRegex =
      /<button\b[^>]*onclick=["'][^"']*refreshLogMonitor[^"']*["'][^>]*>[\s\S]*?<\/button>/gi;
    rewrittenHtml = rewrittenHtml.replace(
      refreshButtonRegex,
      '<span class="logmonitor-refresh-static" style="font-size:0.9em;color:#888;">Auto-refresh disabled</span>'
    );
  }

  return rewrittenHtml;
};

const rewriteCssContent = (css, siteBasePath) => {
  if (!siteBasePath) {
    return css;
  }
  const base = ensureTrailingSlash(siteBasePath || '/');
  const localHostRegex = /^https?:\/\/(?:lbb\.local|localhost)(?::\d+)?\//i;

  return css.replace(
    /@import\s+(?:url\()?["']([^"']+)["'](?:\))?/gi,
    (match, url) => {
      const trimmed = url.trim();
      if (localHostRegex.test(trimmed)) {
        const rewritten = trimmed.replace(localHostRegex, base);
        return match.replace(url, rewritten);
      }
      if (trimmed.startsWith('/')) {
        const rewritten = base + trimmed.slice(1);
        return match.replace(url, rewritten);
      }
      return match;
    }
  );
};

const gatherScrapeSites = () => {
  if (!scrapeSourceDir || !fs.existsSync(scrapeSourceDir)) {
    return [];
  }

  return fs
    .readdirSync(scrapeSourceDir, { withFileTypes: true })
    .filter((dirent) => dirent.isDirectory())
    .map((dirent) => {
      const sitePath = path.join(scrapeSourceDir, dirent.name);
      const indexFile = fs.existsSync(path.join(sitePath, 'index.html')) ? 'index.html' : null;
      const stats = fs.statSync(sitePath);
      return {
        id: dirent.name,
        name: dirent.name.replace(/[-_]/g, ' '),
        index: indexFile ? `scrape/${dirent.name}/${indexFile}` : `scrape/${dirent.name}/`,
        lastModified: stats.mtime.toISOString(),
      };
    })
    .sort((a, b) => a.name.localeCompare(b.name));
};

const buildScrapeManifest = () => ({
  generatedAt: new Date().toISOString(),
  sites: gatherScrapeSites(),
});

const copySiteDirectory = (siteId, sourceDir, destinationDir, basePath) => {
  if (!fs.existsSync(sourceDir)) {
    return;
  }

  const siteBasePath = `${ensureTrailingSlash(basePath)}scrape/${siteId}/`;

  const siteStaticOptions = getSiteStaticOptions(siteId);

  const writeFile = (relativePath) => {
    const absolutePath = path.join(sourceDir, relativePath);
    const relativePosix = normalizePosixPath(relativePath);
    const destRelativeForPhp = getStaticHtmlPathForFile(relativePosix);
    const extension = path.extname(relativePosix).toLowerCase();
    const isHtmlFile = extension === '.html' || extension === '.htm';
    const isCssFile = extension === '.css';
    const targetRelative = destRelativeForPhp || relativePosix;
    const targetAbsolute = path.join(destinationDir, targetRelative.split('/').join(path.sep));

    fs.mkdirSync(path.dirname(targetAbsolute), { recursive: true });

    if (destRelativeForPhp || isHtmlFile) {
      const content = fs.readFileSync(absolutePath, 'utf-8');
      const rewritten = rewriteHtmlContent(content, siteBasePath, {
        sanitizePhpLinks: true,
        ...siteStaticOptions,
      });
      fs.writeFileSync(targetAbsolute, rewritten, 'utf-8');
      return;
    }

    if (isCssFile) {
      const content = fs.readFileSync(absolutePath, 'utf-8');
      const rewritten = rewriteCssContent(content, siteBasePath);
      fs.writeFileSync(targetAbsolute, rewritten, 'utf-8');
      return;
    }

    fs.copyFileSync(absolutePath, targetAbsolute);
  };

  const walk = (currentRelative) => {
    const absolutePath = currentRelative
      ? path.join(sourceDir, currentRelative)
      : sourceDir;
    const entries = fs.readdirSync(absolutePath, { withFileTypes: true });

    for (const entry of entries) {
      const entryRelative = currentRelative
        ? path.join(currentRelative, entry.name)
        : entry.name;

      if (entry.isDirectory()) {
        walk(entryRelative);
        continue;
      }

      if (isPhpRelativePath(entryRelative)) {
        // The content will be written using the sanitized .html path
        writeFile(entryRelative);
        continue;
      }

      writeFile(entryRelative);
    }
  };

  walk('');
};

const scrapeAssetsPlugin = () => {
  let resolvedConfig;
  let normalizedBase = '/';

  return {
    name: 'scrape-assets-plugin',
    configResolved(config) {
      resolvedConfig = config;
      normalizedBase = ensureTrailingSlash(config.base || '/');
    },
    configureServer(server) {
      const base = normalizedBase;
      server.middlewares.use((req, res, next) => {
        if (!req.url) {
          return next();
        }
        const urlWithoutQuery = req.url.split('?')[0];
        const manifestPath = `${base}${manifestFileName}`;
        if (urlWithoutQuery === manifestPath) {
          const manifest = buildScrapeManifest();
          res.setHeader('Content-Type', 'application/json');
          res.end(JSON.stringify(manifest));
          return;
        }

        if (!scrapeSourceDir || !fs.existsSync(scrapeSourceDir)) {
          return next();
        }

        const scrapePrefix = `${base}scrape/`;
        if (!urlWithoutQuery.startsWith(scrapePrefix)) {
          return next();
        }

        const relative = urlWithoutQuery.slice(scrapePrefix.length);
        const relativePosix = normalizePosixPath(relative);
        let requestedPath = path.join(scrapeSourceDir, relative);

        if (!fs.existsSync(requestedPath) && phpStaticMap.has(relativePosix)) {
          const mappedRelative = phpStaticMap.get(relativePosix);
          requestedPath = path.join(scrapeSourceDir, mappedRelative.split('/').join(path.sep));
        }

        if (!ensureWithinDir(requestedPath, scrapeSourceDir) || !fs.existsSync(requestedPath)) {
          res.statusCode = 404;
          res.end('Not found');
          return;
        }

        let finalPath = requestedPath;
        const stats = fs.statSync(finalPath);
        if (stats.isDirectory()) {
          const indexPath = path.join(finalPath, 'index.html');
          if (fs.existsSync(indexPath)) {
            finalPath = indexPath;
          } else {
            res.statusCode = 403;
            res.end('Directory listing is not available');
            return;
          }
        }

        const mimeType = getMimeType(finalPath);
        res.setHeader('Content-Type', mimeType);

        if (mimeType === 'text/html') {
          const siteId = relativePosix.split('/')[0];
          if (!siteId) {
            res.statusCode = 404;
            res.end('Not found');
            return;
          }
          const siteBasePath = `${base}scrape/${siteId}/`;
          const siteOptions = getSiteStaticOptions(siteId);
          const htmlContent = fs.readFileSync(finalPath, 'utf-8');
          const rewritten = rewriteHtmlContent(htmlContent, siteBasePath, {
            sanitizePhpLinks: true,
            ...siteOptions,
          });
          res.end(rewritten);
          return;
        }

        const stream = fs.createReadStream(finalPath);
        stream.on('error', () => {
          res.statusCode = 500;
          res.end('Failed to read file');
        });
        stream.pipe(res);
      });
    },
    closeBundle() {
      if (!resolvedConfig) return;
      const outDir = path.resolve(resolvedConfig.root, resolvedConfig.build.outDir);
      const manifest = buildScrapeManifest();
      const manifestPath = path.join(outDir, manifestFileName);
      fs.writeFileSync(manifestPath, JSON.stringify(manifest, null, 2), 'utf-8');
      if (!scrapeSourceDir || !fs.existsSync(scrapeSourceDir) || manifest.sites.length === 0) {
        return;
      }
      const targetDir = path.join(outDir, 'scrape');
      for (const site of manifest.sites) {
        const srcDir = path.join(scrapeSourceDir, site.id);
        const destDir = path.join(targetDir, site.id);
        copySiteDirectory(site.id, srcDir, destDir, normalizedBase);
      }
    },
  };
};

export default defineConfig(() => {
  const basePath = process.env.VITE_BASE_PATH || '/';

  return {
    base: basePath,
    plugins: [react(), scrapeAssetsPlugin()],
    server: {
      port: 5173,
      proxy: {
        '/api': {
          target: 'http://localhost:3000',
          changeOrigin: true,
        },
        '/css': {
          target: 'http://localhost:3000',
          changeOrigin: true,
        },
        '/js': {
          target: 'http://localhost:3000',
          changeOrigin: true,
        },
        '/img': {
          target: 'http://localhost:3000',
          changeOrigin: true,
        },
        '/lang': {
          target: 'http://localhost:3000',
          changeOrigin: true,
        },
        '/favicon.ico': {
          target: 'http://localhost:3000',
          changeOrigin: true,
        },
      },
    },
    build: {
      outDir: 'dist',
      assetsDir: 'assets',
    },
    resolve: {
      alias: {
        '@': path.resolve(__dirname, './src'),
      },
    },
  };
});

