import { readFileSync, existsSync } from 'fs';
import { parse } from 'ini';
import path from 'path';

function getDefaultConfig() {
  return {
    conf_LANGUAGE: 'en',
    conf_THEME: 'dark',
    conf_BACKGROUND_IMAGE: '',
    conf_LOGLEVEL: '1',
    conf_POPUP_MESSAGES: 'true',
    conf_BACKUP_MOVE_FILES: 'false',
    conf_BACKUP_RENAME_FILES: 'false',
    conf_BACKUP_GENERATE_THUMBNAILS: 'false',
    conf_BACKUP_UPDATE_EXIF: 'false',
    conf_BACKUP_CHECKSUM: 'false',
    conf_POWER_OFF: 'false',
    conf_TIME_ZONE: 'UTC',
    conf_WIFI_COUNTRY: 'GB',
    conf_RSYNC_SERVER: '',
    conf_RSYNC_PORT: '873',
    conf_RSYNC_USER: '',
    conf_RSYNC_PASSWORD: '',
    conf_RSYNC_SERVER_MODULE: 'little-backup-box',
    conf_MAIL_NOTIFICATIONS: 'false',
    conf_MAIL_IP: 'false',
    conf_SMTP_SERVER: '',
    conf_SMTP_PORT: '465',
    conf_MAIL_SECURITY: 'STARTTLS',
    conf_MAIL_USER: '',
    conf_MAIL_PASSWORD: '',
    conf_MAIL_FROM: '',
    conf_MAIL_TO: '',
    conf_PASSWORD: '',
    conf_PASSWORD_ENCRYPTION: 'plain',
    conf_VPN_TYPE_RSYNC: 'none',
    conf_VPN_TYPE_CLOUD: 'none',
    conf_VPN_TIMEOUT: '20',
    conf_SOCIAL_TELEGRAM_TOKEN: '',
    conf_SOCIAL_TELEGRAM_CHAT_IDENTIFIER: '',
    conf_SOCIAL_MATRIX_HOMESERVER: '',
    conf_SOCIAL_MATRIX_TOKEN: '',
    conf_SOCIAL_MATRIX_ROOM_IDENTIFIER: '',
    conf_SOCIAL_PUBLISH_DATE: 'true',
  };
}

export function loadConfig(workingDir) {
  const configPath = path.join(workingDir, 'config.cfg');
  
  if (!existsSync(configPath)) {
    console.warn(`Config file not found at ${configPath}, using default values`);
    return getDefaultConfig();
  }
  
  try {
    const content = readFileSync(configPath, 'utf-8');
    const loadedConfig = parse(content);
    const defaultConfig = getDefaultConfig();
    
    return { ...defaultConfig, ...loadedConfig };
  } catch (err) {
    console.warn(`Failed to load config.cfg: ${err.message}, using default values`);
    return getDefaultConfig();
  }
}

function getDefaultConstants() {
  return {
    const_MEDIA_DIR: '/media',
    const_MOUNTPOINT_USB_TARGET: 'usb_target',
    const_MOUNTPOINT_USB_SOURCE: 'usb_source',
    const_MOUNTPOINT_NVME_TARGET: 'nvme_target',
    const_MOUNTPOINT_NVME_SOURCE: 'nvme_source',
    const_MOUNTPOINT_CLOUD_TARGET: 'cloud_target',
    const_MOUNTPOINT_CLOUD_SOURCE: 'cloud_source',
    const_INTERNAL_BACKUP_DIR: 'internal',
    const_BACKGROUND_IMAGES_DIR: 'backgroundimages',
    const_WEB_ROOT_LBB: '/var/www/little-backup-box',
    const_LOGFILE: '/var/www/little-backup-box/tmp/little-backup-box.log',
    const_DISPLAY_CONTENT_PATH: '/var/www/little-backup-box/tmp/display-content',
    const_RCLONE_CONFIG_FILE: 'rclone.conf',
    const_BUTTONS_CONFIG_FILE: 'buttons.cfg',
    const_BUTTONS_PRIVATE_CONFIG_FILE: 'buttons.private.cfg',
    const_STORAGE_EXT_MASK: 'sd',
    const_STORAGE_NVME_MASK: 'nvme',
    const_CMD_LOGFILE: '/var/www/little-backup-box/tmp/cmd.log',
    const_CMD_RUNNER_LOCKFILE: '/var/www/little-backup-box/tmp/cmd-runner.lock',
    const_IMAGE_DATABASE_FILENAME: 'images.db',
    const_IDLETIME_LOCKFILE: '/var/www/little-backup-box/tmp/idletime.lock',
    const_FILE_EXTENSIONS_LIST_WEB_IMAGES: 'jpg;jpeg;gif;png;webp',
    const_FILE_EXTENSIONS_LIST_RAW: '3fr;arw;dcr;dng;cr2;cr3;crw;fff;gpr;j6i;k25;kc2;kdc;mdc;mrw;nef;nrw;orf;pef;raw;raf;rw2;rwl;sr2;srf;srw;x3f',
    const_FILE_EXTENSIONS_LIST_VIDEO: 'avi;lrv;mp4',
    const_FILE_EXTENSIONS_LIST_AUDIO: 'mp3;wav;m4a',
    const_FILE_EXTENSIONS_LIST_TEXT: 'txt;md;html',
    const_VIEW_GRID_COLUMNS: '3',
    const_VIEW_RATING_STANDARD_VALUE: '0',
    const_VPN_DIR_OpenVPN: '/etc/openvpn',
    const_VPN_DIR_WireGuard: '/etc/wireguard',
    const_VPN_FILENAME_OpenVPN: 'openvpn.conf',
    const_VPN_FILENAME_WireGuard: 'wg0.conf',
  };
}

export function loadConstants(workingDir) {
  const constantsPath = path.join(workingDir, 'constants.sh');
  
  if (!existsSync(constantsPath)) {
    console.warn(`Constants file not found at ${constantsPath}, using default values`);
    return getDefaultConstants();
  }
  
  try {
    const content = readFileSync(constantsPath, 'utf-8');
    const constants = {};
    const lines = content.split('\n');
    for (const line of lines) {
      const match = line.match(/^const_(\w+)="?([^"]+)"?$/);
      if (match) {
        constants[`const_${match[1]}`] = match[2];
      }
    }
    
    const defaultConstants = getDefaultConstants();
    return { ...defaultConstants, ...constants };
  } catch (err) {
    console.warn(`Failed to load constants.sh: ${err.message}, using default values`);
    return getDefaultConstants();
  }
}

