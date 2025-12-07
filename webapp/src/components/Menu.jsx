import React from 'react';
import { useNavigate, useLocation } from 'react-router-dom';
import {
  AppBar,
  Toolbar,
  Box,
  useMediaQuery,
  IconButton,
  Drawer,
  List,
  ListItem,
  ListItemButton,
  ListItemIcon,
  ListItemText,
  useTheme,
  Menu as MuiMenu,
  MenuItem,
  Tooltip,
  Divider,
  Typography,
  Dialog,
  DialogTitle,
  DialogContent,
  DialogContentText,
  DialogActions,
  Button,
} from '@mui/material';
import MenuIcon from '@mui/icons-material/Menu';
import ChevronLeftIcon from '@mui/icons-material/ChevronLeft';
import ChevronRightIcon from '@mui/icons-material/ChevronRight';
import Brightness4Icon from '@mui/icons-material/Brightness4';
import Brightness7Icon from '@mui/icons-material/Brightness7';
import SettingsBrightnessIcon from '@mui/icons-material/SettingsBrightness';
import LanguageIcon from '@mui/icons-material/Language';
import PowerSettingsNewIcon from '@mui/icons-material/PowerSettingsNew';
import ArchiveIcon from '@mui/icons-material/Archive';
import PaletteIcon from '@mui/icons-material/Palette';
import ViewListIcon from '@mui/icons-material/ViewList';
import StorageIcon from '@mui/icons-material/Storage';
import InfoIcon from '@mui/icons-material/Info';
import BuildCircleIcon from '@mui/icons-material/BuildCircle';
import LinkIcon from '@mui/icons-material/Link';
import PublicIcon from '@mui/icons-material/Public';
import PhotoLibraryIcon from '@mui/icons-material/PhotoLibrary';
import FolderOpenIcon from '@mui/icons-material/FolderOpen';
import OpenInNewIcon from '@mui/icons-material/OpenInNew';
import RouterIcon from '@mui/icons-material/Router';
import AppsIcon from '@mui/icons-material/Apps';
import InfoOutlinedIcon from '@mui/icons-material/InfoOutlined';
import { useLanguage } from '../contexts/LanguageContext';
import { useConfig } from '../contexts/ConfigContext';
import { useDrawer } from '../contexts/DrawerContext';
import api from '../utils/api';

export const drawerWidth = 240;
export const drawerCollapsedWidth = 64;

function Menu() {
  const location = useLocation();
  const navigate = useNavigate();
  const { t } = useLanguage();
  const { config } = useConfig();
  const theme = useTheme();
  const isMobile = useMediaQuery(theme.breakpoints.down('md'));
  const [mobileOpen, setMobileOpen] = React.useState(false);
  const { desktopOpen, toggleDrawer } = useDrawer();
  const [themeMenuAnchor, setThemeMenuAnchor] = React.useState(null);
  const [languageMenuAnchor, setLanguageMenuAnchor] = React.useState(null);
  const [powerMenuAnchor, setPowerMenuAnchor] = React.useState(null);
  const [stopLbbDialogOpen, setStopLbbDialogOpen] = React.useState(false);
  const [hasRunningBackups, setHasRunningBackups] = React.useState(false);
  const [rebootDialogOpen, setRebootDialogOpen] = React.useState(false);
  const [shutdownDialogOpen, setShutdownDialogOpen] = React.useState(false);
  const [rebootHasRunningBackups, setRebootHasRunningBackups] = React.useState(false);
  const [shutdownHasRunningBackups, setShutdownHasRunningBackups] = React.useState(false);
  const [currentTheme, setCurrentTheme] = React.useState(() => {
    const saved = localStorage.getItem('lbb-theme');
    return saved && (saved === 'system' || saved === 'light' || saved === 'dark') 
      ? saved 
      : (config?.conf_THEME || 'system');
  });
  const [currentLanguage, setCurrentLanguage] = React.useState(() => {
    const saved = localStorage.getItem('lbb-language');
    if (saved === null) {
      return config?.conf_LANGUAGE || '';
    }
    if (saved === '') {
      return '';
    }
    return saved || (config?.conf_LANGUAGE || 'en');
  });
  const [rcloneGuiInfo, setRcloneGuiInfo] = React.useState(null);

  const isActive = (path) => location.pathname === path;

  const menuItems = [
    { path: '/', key: 'main', icon: <ArchiveIcon /> },
    { path: '/tools', key: 'filesystem', icon: <StorageIcon /> },
    { path: '/integrations', key: 'integrations', icon: <LinkIcon /> },
    { path: '/maintenance', key: 'maintenance', icon: <BuildCircleIcon /> },
    { path: '/sysinfo', key: 'sysinfo', icon: <InfoIcon /> },
    { path: '/network', key: 'network', icon: <RouterIcon /> },
    { path: '/setup', key: 'config', icon: <PaletteIcon /> },
    { path: '/view.php', key: 'gallery', icon: <PhotoLibraryIcon />, external: true },
    { path: '/files', key: 'filebrowser', icon: <FolderOpenIcon />, external: true },
    { path: '/frame.php?page=rclone_gui', key: 'rclone_gui', icon: <AppsIcon />, external: true, hasInfo: true },
    { path: '/scrape', key: 'scrape', icon: <PublicIcon />},
  ];

  const handleDrawerToggle = () => {
    setMobileOpen(!mobileOpen);
  };

  const handleDesktopDrawerToggle = () => {
    toggleDrawer();
  };

  const handleNavigation = (path, external) => {
    if (external) {
      window.open(path, '_blank', 'noopener,noreferrer');
    } else {
      navigate(path);
    }
    if (isMobile) {
      setMobileOpen(false);
    }
  };

  const handleThemeMenuOpen = (event) => {
    setThemeMenuAnchor(event.currentTarget);
  };

  const handleThemeMenuClose = () => {
    setThemeMenuAnchor(null);
  };

  const handleThemeChange = (themeMode) => {
    localStorage.setItem('lbb-theme', themeMode);
    setCurrentTheme(themeMode);
    window.dispatchEvent(new Event('themeChange'));
    handleThemeMenuClose();
  };

  const handleLanguageMenuOpen = (event) => {
    setLanguageMenuAnchor(event.currentTarget);
  };

  const handleLanguageMenuClose = () => {
    setLanguageMenuAnchor(null);
  };

  const handleLanguageChange = (languageCode) => {
    localStorage.setItem('lbb-language', languageCode);
    setCurrentLanguage(languageCode);
    window.dispatchEvent(new CustomEvent('languageChange', { detail: languageCode }));
    handleLanguageMenuClose();
  };

  const handlePowerMenuOpen = (event) => {
    setPowerMenuAnchor(event.currentTarget);
  };

  const handlePowerMenuClose = () => {
    setPowerMenuAnchor(null);
  };

  const handleReboot = async () => {
    handlePowerMenuClose();
    
    try {
      const response = await fetch('/api/backup/running');
      if (response.ok) {
        const data = await response.json();
        const runningBackups = data.backups || [];
        const hasBackups = runningBackups.length > 0;
        setRebootHasRunningBackups(hasBackups);
        setRebootDialogOpen(true);
      } else {
        setRebootHasRunningBackups(false);
        setRebootDialogOpen(true);
      }
    } catch (error) {
      console.error('Error checking running backups:', error);
      setRebootHasRunningBackups(false);
      setRebootDialogOpen(true);
    }
  };

  const handleRebootConfirm = async () => {
    setRebootDialogOpen(false);
    try {
      const response = await fetch('/api/system/reboot', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
        },
      });
      if (response.ok) {
        await response.json();
        alert(t('main.reboot_m') || 'The device reboots. Refresh this page in a few moments.');
      } else {
        alert('Failed to reboot');
      }
    } catch (error) {
      console.error('Error rebooting:', error);
      alert('Failed to reboot');
    }
  };

  const handleRebootCancel = () => {
    setRebootDialogOpen(false);
  };

  const handleShutdown = async () => {
    handlePowerMenuClose();
    
    try {
      const response = await fetch('/api/backup/running');
      if (response.ok) {
        const data = await response.json();
        const runningBackups = data.backups || [];
        const hasBackups = runningBackups.length > 0;
        setShutdownHasRunningBackups(hasBackups);
        setShutdownDialogOpen(true);
      } else {
        setShutdownHasRunningBackups(false);
        setShutdownDialogOpen(true);
      }
    } catch (error) {
      console.error('Error checking running backups:', error);
      setShutdownHasRunningBackups(false);
      setShutdownDialogOpen(true);
    }
  };

  const handleShutdownConfirm = async () => {
    setShutdownDialogOpen(false);
    try {
      const response = await fetch('/api/system/shutdown', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
        },
      });
      if (response.ok) {
        await response.json();
        alert(t('main.shutdown_m') || 'The device is powered off. You can close this page.');
      } else {
        alert('Failed to shutdown');
      }
    } catch (error) {
      console.error('Error shutting down:', error);
      alert('Failed to shutdown');
    }
  };

  const handleShutdownCancel = () => {
    setShutdownDialogOpen(false);
  };

  const handleLogout = () => {
    handlePowerMenuClose();
    const protocol = window.location.protocol;
    const host = window.location.host;
    window.location.href = `${protocol}//logout@${host}`;
  };

  const handleStopLbb = async () => {
    handlePowerMenuClose();
    
    try {
      const response = await fetch('/api/backup/running');
      if (response.ok) {
        const data = await response.json();
        const runningBackups = data.backups || [];
        const hasBackups = runningBackups.length > 0;
        setHasRunningBackups(hasBackups);
        setStopLbbDialogOpen(true);
      } else {
        setHasRunningBackups(false);
        setStopLbbDialogOpen(true);
      }
    } catch (error) {
      console.error('Error checking running backups:', error);
      setHasRunningBackups(false);
      setStopLbbDialogOpen(true);
    }
  };

  const handleStopLbbConfirm = async () => {
    setStopLbbDialogOpen(false);
    try {
      const response = await fetch('/api/setup/exit-lbb', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
        },
        body: JSON.stringify({
          stopBackup: hasRunningBackups,
        }),
      });
      if (response.ok) {
        await response.json();
      } else {
        alert('Failed to stop LBB');
      }
    } catch (error) {
      console.error('Error stopping LBB:', error);
      alert('Failed to stop LBB');
    }
  };

  const handleStopLbbCancel = () => {
    setStopLbbDialogOpen(false);
  };

  React.useEffect(() => {
    const loadRcloneGuiInfo = async () => {
      try {
        const response = await api.get('/cloud/rclone-gui/info');
        setRcloneGuiInfo(response.data);
      } catch (error) {
        console.error('Failed to load rclone GUI info:', error);
      }
    };
    loadRcloneGuiInfo();
  }, []);

  React.useEffect(() => {
    const handleThemeChange = () => {
      const saved = localStorage.getItem('lbb-theme');
      if (saved && (saved === 'system' || saved === 'light' || saved === 'dark')) {
        setCurrentTheme(saved);
      }
    };
    const handleLanguageChange = () => {
      const saved = localStorage.getItem('lbb-language');
      if (saved !== null) {
        setCurrentLanguage(saved);
      } else {
        setCurrentLanguage('');
      }
    };

    window.addEventListener('themeChange', handleThemeChange);
    window.addEventListener('languageChange', handleLanguageChange);
    return () => {
      window.removeEventListener('themeChange', handleThemeChange);
      window.removeEventListener('languageChange', handleLanguageChange);
    };
  }, []);

  React.useEffect(() => {
    if (config) {
      if (!localStorage.getItem('lbb-theme') && config.conf_THEME) {
        setCurrentTheme(config.conf_THEME);
      }
      if (!localStorage.getItem('lbb-language') && config.conf_LANGUAGE) {
        setCurrentLanguage(config.conf_LANGUAGE);
      }
    }
  }, [config]);

  React.useEffect(() => {
    const getScrollbarWidth = () => {
      const outer = document.createElement('div');
      outer.style.visibility = 'hidden';
      outer.style.overflow = 'scroll';
      outer.style.msOverflowStyle = 'scrollbar';
      document.body.appendChild(outer);
      const inner = document.createElement('div');
      outer.appendChild(inner);
      const scrollbarWidth = outer.offsetWidth - inner.offsetWidth;
      outer.parentNode?.removeChild(outer);
      return scrollbarWidth;
    };

    const scrollbarWidth = getScrollbarWidth();
    let isPaddingApplied = false;

    const observer = new MutationObserver(() => {
      const bodyOverflow = window.getComputedStyle(document.body).overflow;
      
      if (bodyOverflow === 'hidden' && !isPaddingApplied) {
        document.body.style.paddingRight = `${scrollbarWidth}px`;
        isPaddingApplied = true;
      } else if (bodyOverflow !== 'hidden' && isPaddingApplied) {
        document.body.style.paddingRight = '';
        isPaddingApplied = false;
      }
    });

    observer.observe(document.body, {
      attributes: true,
      attributeFilter: ['style'],
    });

    return () => {
      observer.disconnect();
      if (isPaddingApplied) {
        document.body.style.paddingRight = '';
      }
    };
  }, []);
  const isThemeMenuOpen = Boolean(themeMenuAnchor);
  const isLanguageMenuOpen = Boolean(languageMenuAnchor);
  const isPowerMenuOpen = Boolean(powerMenuAnchor);

  const languages = [
    { code: '', name: t('config.lang_browser_detect') || 'Auto-detect' },
    { code: 'en', name: 'English' },
    { code: 'de', name: 'Deutsch' },
    { code: 'es', name: 'Español' },
    { code: 'fr', name: 'Français' },
    { code: 'fi', name: 'Suomi' },
  ];
  
  const getThemeIcon = () => {
    if (currentTheme === 'system') {
      return <SettingsBrightnessIcon />;
    }
    return currentTheme === 'dark' ? <Brightness4Icon /> : <Brightness7Icon />;
  };

  const getPageTitle = () => {
    const routeMap = {
      '/': { key: 'mainmenue.main', fallback: 'Backup' },
      '/setup': { key: 'mainmenue.config', fallback: 'User Interface' },
      '/view': { key: 'mainmenue.view', fallback: 'View' },
      '/integrations': { key: 'mainmenue.integrations', fallback: 'Service Connections' },
      '/tools': { key: 'mainmenue.filesystem', fallback: 'Filesystem' },
      '/sysinfo': { key: 'mainmenue.sysinfo', fallback: 'System' },
      '/network': { key: 'mainmenue.network', fallback: 'Network' },
      '/maintenance': { key: 'mainmenue.maintenance', fallback: 'Maintenance' },
      '/scrape': { key: 'mainmenue.scrape', fallback: 'Scraped UI' },
    };
    const routeInfo = routeMap[location.pathname] || routeMap['/'];
    const translation = t(routeInfo.key);
    return translation !== routeInfo.key ? translation : routeInfo.fallback;
  };

  const currentDrawerWidth = desktopOpen ? drawerWidth : drawerCollapsedWidth;

  const drawer = (
    <Box>
      <Toolbar
        sx={{
          display: 'flex',
          alignItems: 'center',
          justifyContent: desktopOpen ? 'flex-end' : 'center',
          px: 1,
        }}
      >
        <IconButton onClick={handleDesktopDrawerToggle} sx={{ display: { xs: 'none', md: 'flex' } }}>
          {desktopOpen ? <ChevronLeftIcon /> : <ChevronRightIcon />}
        </IconButton>
      </Toolbar>
      <Divider />
      <List>
        {menuItems.map((item) => {
          const getInfoTooltipText = () => {
            if (item.hasInfo && rcloneGuiInfo) {
              const username = rcloneGuiInfo.username || 'lbb';
              return `${t('config.username') || 'Username'}: ${username}\n${t('config.cloud.rclone.gui.password_hint') || 'Use the global password set in the Password section'}`;
            }
            return '';
          };

          return (
            <ListItem key={item.path} disablePadding>
              <Tooltip title={!desktopOpen ? t(`mainmenue.${item.key}`) : ''} placement="right">
                <ListItemButton
                  selected={!item.external && isActive(item.path)}
                  onClick={() => handleNavigation(item.path, item.external)}
                  sx={{
                    minHeight: 48,
                    justifyContent: desktopOpen ? 'initial' : 'center',
                    px: 2.5,
                  }}
                >
                  <ListItemIcon
                    sx={{
                      minWidth: 0,
                      mr: desktopOpen ? 3 : 'auto',
                      justifyContent: 'center',
                    }}
                  >
                    {item.icon}
                  </ListItemIcon>
                  <ListItemText
                    primary={t(`mainmenue.${item.key}`)}
                    sx={{
                      opacity: desktopOpen ? 1 : 0,
                      display: { md: desktopOpen ? 'block' : 'none' },
                    }}
                  />
                  {desktopOpen && item.hasInfo && (
                    <Tooltip
                      title={getInfoTooltipText()}
                      placement="left"
                      arrow
                    >
                      <IconButton
                        size="small"
                        onClick={(e) => {
                          e.stopPropagation();
                        }}
                        sx={{
                          ml: 1,
                          opacity: 0.6,
                          '&:hover': {
                            opacity: 1,
                          },
                        }}
                      >
                        <InfoOutlinedIcon fontSize="small" />
                      </IconButton>
                    </Tooltip>
                  )}
                  {item.external && (
                    <OpenInNewIcon
                      sx={{
                        fontSize: 16,
                        opacity: 0.6,
                        ml: item.hasInfo ? 0.5 : 1,
                        display: { md: desktopOpen ? 'block' : 'none' },
                      }}
                    />
                  )}
                </ListItemButton>
              </Tooltip>
            </ListItem>
          );
        })}
      </List>
    </Box>
  );

  return (
    <Box sx={{ display: 'flex', margin: 0, padding: 0 }}>
      <AppBar
        position="fixed"
        sx={{
          top: 0,
          width: { md: `calc(100% - ${currentDrawerWidth}px)` },
          ml: { md: `${currentDrawerWidth}px` },
          zIndex: (theme) => theme.zIndex.drawer + 1,
          transition: theme.transitions.create(['width', 'margin'], {
            easing: theme.transitions.easing.sharp,
            duration: theme.transitions.duration.enteringScreen,
          }),
        }}
      >
        <Toolbar>
          <IconButton
            color="inherit"
            aria-label="open drawer"
            edge="start"
            onClick={handleDrawerToggle}
            sx={{ mr: 2, display: { md: 'none' } }}
          >
            <MenuIcon />
          </IconButton>
          <Typography variant="h6" component="div" sx={{ flexGrow: 0, mr: 2 }}>
            {getPageTitle()}
          </Typography>
          <Box sx={{ flexGrow: 1 }} />
          <Box sx={{ display: 'flex', alignItems: 'center', gap: 0.5 }}>
            <Tooltip title={t('config.lang_header') || 'Language'}>
              <IconButton
                color="inherit"
                onClick={handleLanguageMenuOpen}
                aria-label="language selection"
                aria-controls={isLanguageMenuOpen ? 'language-menu' : undefined}
                aria-haspopup="true"
                aria-expanded={isLanguageMenuOpen ? 'true' : undefined}
              >
                <LanguageIcon />
              </IconButton>
            </Tooltip>
            <MuiMenu
              id="language-menu"
              anchorEl={languageMenuAnchor}
              open={isLanguageMenuOpen}
              onClose={handleLanguageMenuClose}
              disableScrollLock
              MenuListProps={{
                'aria-labelledby': 'language-button',
              }}
            >
              {languages.map((lang) => (
                <MenuItem
                  key={lang.code || 'auto'}
                  onClick={() => handleLanguageChange(lang.code)}
                  selected={currentLanguage === lang.code}
                >
                  {lang.name}
                </MenuItem>
              ))}
            </MuiMenu>
            <Tooltip title={t('config.view_theme_header') || 'Theme'}>
              <IconButton
                color="inherit"
                onClick={handleThemeMenuOpen}
                aria-label="theme selection"
                aria-controls={isThemeMenuOpen ? 'theme-menu' : undefined}
                aria-haspopup="true"
                aria-expanded={isThemeMenuOpen ? 'true' : undefined}
              >
                {getThemeIcon()}
              </IconButton>
            </Tooltip>
            <MuiMenu
              id="theme-menu"
              anchorEl={themeMenuAnchor}
              open={isThemeMenuOpen}
              onClose={handleThemeMenuClose}
              disableScrollLock
              MenuListProps={{
                'aria-labelledby': 'theme-button',
              }}
            >
              <MenuItem
                onClick={() => handleThemeChange('light')}
                selected={currentTheme === 'light'}
              >
                {t('config.view_theme_light') || 'Light'}
              </MenuItem>
              <MenuItem
                onClick={() => handleThemeChange('dark')}
                selected={currentTheme === 'dark'}
              >
                {t('config.view_theme_dark') || 'Dark'}
              </MenuItem>
              <MenuItem
                onClick={() => handleThemeChange('system')}
                selected={currentTheme === 'system'}
              >
                {t('config.view_theme_system') || 'System'}
              </MenuItem>
            </MuiMenu>
            <Tooltip title="Power">
              <IconButton
                color="inherit"
                onClick={handlePowerMenuOpen}
                aria-label="power menu"
                aria-controls={isPowerMenuOpen ? 'power-menu' : undefined}
                aria-haspopup="true"
                aria-expanded={isPowerMenuOpen ? 'true' : undefined}
              >
                <PowerSettingsNewIcon />
              </IconButton>
            </Tooltip>
            <MuiMenu
              id="power-menu"
              anchorEl={powerMenuAnchor}
              open={isPowerMenuOpen}
              onClose={handlePowerMenuClose}
              disableScrollLock
              MenuListProps={{
                'aria-labelledby': 'power-button',
              }}
            >
              <MenuItem onClick={handleStopLbb}>
                {t('main.stop_lbb_button') || 'Stop LBB'}
              </MenuItem>
              <MenuItem onClick={handleLogout}>
                {t('main.logout_button') || 'Logout'}
              </MenuItem>
              <Divider />
              <MenuItem onClick={handleReboot}>
                {t('main.reboot_button') || 'Reboot'}
              </MenuItem>
              <MenuItem onClick={handleShutdown}>
                {t('main.shutdown_button') || 'Power off'}
              </MenuItem>
            </MuiMenu>
            <Dialog
              open={stopLbbDialogOpen}
              onClose={handleStopLbbCancel}
              aria-labelledby="stop-lbb-dialog-title"
              aria-describedby="stop-lbb-dialog-description"
            >
              <DialogTitle id="stop-lbb-dialog-title">
                {t('main.stop_lbb_confirm_title') || 'Stop Little Backup Box?'}
              </DialogTitle>
              <DialogContent>
                <DialogContentText id="stop-lbb-dialog-description">
                  {hasRunningBackups
                    ? (t('main.stop_lbb_confirm_message') || 'Backup processes are currently running. Do you want to stop them and terminate Little Backup Box?')
                    : (t('main.stop_lbb_confirm_message_no_backup') || 'Are you sure you want to terminate Little Backup Box?')}
                </DialogContentText>
              </DialogContent>
              <DialogActions>
                <Button onClick={handleStopLbbCancel}>
                  Cancel
                </Button>
                <Button onClick={handleStopLbbConfirm} color="primary" autoFocus>
                  {t('main.stop_lbb_button') || 'Stop LBB'}
                </Button>
              </DialogActions>
            </Dialog>
            <Dialog
              open={rebootDialogOpen}
              onClose={handleRebootCancel}
              aria-labelledby="reboot-dialog-title"
              aria-describedby="reboot-dialog-description"
            >
              <DialogTitle id="reboot-dialog-title">
                {t('main.reboot_confirm_title') || 'Reboot the device?'}
              </DialogTitle>
              <DialogContent>
                <DialogContentText id="reboot-dialog-description">
                  {rebootHasRunningBackups
                    ? (t('main.reboot_confirm_message') || 'Warning: Backup processes are currently running. They will be interrupted if you proceed. Are you sure you want to reboot?')
                    : (t('main.reboot_confirm_message_no_backup') || 'Are you sure you want to reboot the device?')}
                </DialogContentText>
              </DialogContent>
              <DialogActions>
                <Button onClick={handleRebootCancel}>
                  {t('main.cancel_button') || 'Cancel'}
                </Button>
                <Button onClick={handleRebootConfirm} color="primary" autoFocus>
                  {t('main.reboot_button') || 'Reboot'}
                </Button>
              </DialogActions>
            </Dialog>
            <Dialog
              open={shutdownDialogOpen}
              onClose={handleShutdownCancel}
              aria-labelledby="shutdown-dialog-title"
              aria-describedby="shutdown-dialog-description"
            >
              <DialogTitle id="shutdown-dialog-title">
                {t('main.shutdown_confirm_title') || 'Shutdown the device?'}
              </DialogTitle>
              <DialogContent>
                <DialogContentText id="shutdown-dialog-description">
                  {shutdownHasRunningBackups
                    ? (t('main.shutdown_confirm_message') || 'Warning: Backup processes are currently running. They will be interrupted if you proceed. Are you sure you want to shutdown?')
                    : (t('main.shutdown_confirm_message_no_backup') || 'Are you sure you want to shutdown the device?')}
                </DialogContentText>
              </DialogContent>
              <DialogActions>
                <Button onClick={handleShutdownCancel}>
                  {t('main.cancel_button') || 'Cancel'}
                </Button>
                <Button onClick={handleShutdownConfirm} color="primary" autoFocus>
                  {t('main.shutdown_button') || 'Power off'}
                </Button>
              </DialogActions>
            </Dialog>
          </Box>
        </Toolbar>
      </AppBar>
      <Box
        component="nav"
        sx={{
          width: { md: currentDrawerWidth },
          flexShrink: { md: 0 },
          transition: theme.transitions.create('width', {
            easing: theme.transitions.easing.sharp,
            duration: theme.transitions.duration.enteringScreen,
          }),
        }}
      >
        <Drawer
          variant="temporary"
          open={mobileOpen}
          onClose={handleDrawerToggle}
          ModalProps={{
            keepMounted: true,
            disableScrollLock: true,
          }}
          sx={{
            display: { xs: 'block', md: 'none' },
            '& .MuiDrawer-paper': {
              boxSizing: 'border-box',
              width: drawerWidth,
              top: 0,
            },
          }}
        >
          {drawer}
        </Drawer>
        <Drawer
          variant="permanent"
          open={desktopOpen}
          sx={{
            display: { xs: 'none', md: 'block' },
            '& .MuiDrawer-paper': {
              boxSizing: 'border-box',
              width: currentDrawerWidth,
              top: 0,
              transition: theme.transitions.create('width', {
                easing: theme.transitions.easing.sharp,
                duration: theme.transitions.duration.enteringScreen,
              }),
              overflowX: 'hidden',
            },
          }}
        >
          {drawer}
        </Drawer>
      </Box>
    </Box>
  );
}

export default Menu;
