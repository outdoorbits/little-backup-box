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
} from '@mui/material';
import MenuIcon from '@mui/icons-material/Menu';
import ChevronLeftIcon from '@mui/icons-material/ChevronLeft';
import ChevronRightIcon from '@mui/icons-material/ChevronRight';
import Brightness4Icon from '@mui/icons-material/Brightness4';
import Brightness7Icon from '@mui/icons-material/Brightness7';
import SettingsBrightnessIcon from '@mui/icons-material/SettingsBrightness';
import LanguageIcon from '@mui/icons-material/Language';
import PowerSettingsNewIcon from '@mui/icons-material/PowerSettingsNew';
import HomeIcon from '@mui/icons-material/Home';
import PaletteIcon from '@mui/icons-material/Palette';
import ViewListIcon from '@mui/icons-material/ViewList';
import StorageIcon from '@mui/icons-material/Storage';
import InfoIcon from '@mui/icons-material/Info';
import BuildCircleIcon from '@mui/icons-material/BuildCircle';
import LinkIcon from '@mui/icons-material/Link';
import { useLanguage } from '../contexts/LanguageContext';
import { useConfig } from '../contexts/ConfigContext';
import { useDrawer } from '../contexts/DrawerContext';

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
  const [currentTheme, setCurrentTheme] = React.useState(() => {
    const saved = localStorage.getItem('lbb-theme');
    return saved && (saved === 'system' || saved === 'light' || saved === 'dark') 
      ? saved 
      : (config?.conf_THEME || 'system');
  });
  const [currentLanguage, setCurrentLanguage] = React.useState(() => {
    const saved = localStorage.getItem('lbb-language');
    return saved || (config?.conf_LANGUAGE || 'en');
  });

  const isActive = (path) => location.pathname === path;

  const menuItems = [
    { path: '/', key: 'main', icon: <HomeIcon /> },
    { path: '/tools', key: 'filesystem', icon: <StorageIcon /> },
    { path: '/integrations', key: 'integrations', icon: <LinkIcon /> },
    { path: '/maintenance', key: 'maintenance', icon: <BuildCircleIcon /> },
    { path: '/sysinfo', key: 'sysinfo', icon: <InfoIcon /> },
    { path: '/setup', key: 'config', icon: <PaletteIcon /> },
  ];

  const handleDrawerToggle = () => {
    setMobileOpen(!mobileOpen);
  };

  const handleDesktopDrawerToggle = () => {
    toggleDrawer();
  };

  const handleNavigation = (path) => {
    navigate(path);
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
      const response = await fetch('/api/system/reboot', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
        },
      });
      if (response.ok) {
        await response.json();
        alert(t('main.reboot_m') || 'Little Backup Box reboots. Refresh this page in a few moments.');
      } else {
        alert('Failed to reboot');
      }
    } catch (error) {
      console.error('Error rebooting:', error);
      alert('Failed to reboot');
    }
  };

  const handleShutdown = async () => {
    handlePowerMenuClose();
    try {
      const response = await fetch('/api/system/shutdown', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
        },
      });
      if (response.ok) {
        await response.json();
        alert(t('main.shutdown_m') || 'Little Backup Box is powered off. You can close this page.');
      } else {
        alert('Failed to shutdown');
      }
    } catch (error) {
      console.error('Error shutting down:', error);
      alert('Failed to shutdown');
    }
  };

  const handleLogout = () => {
    handlePowerMenuClose();
    const protocol = window.location.protocol;
    const host = window.location.host;
    window.location.href = `${protocol}//logout@${host}`;
  };

  React.useEffect(() => {
    const handleThemeChange = () => {
      const saved = localStorage.getItem('lbb-theme');
      if (saved && (saved === 'system' || saved === 'light' || saved === 'dark')) {
        setCurrentTheme(saved);
      }
    };
    const handleLanguageChange = () => {
      const saved = localStorage.getItem('lbb-language');
      if (saved) {
        setCurrentLanguage(saved);
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
    { code: 'en', name: 'English' },
    { code: 'de', name: 'Deutsch' },
    { code: 'es', name: 'Español' },
    { code: 'fr', name: 'Français' },
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
      '/integrations': { key: 'mainmenue.integrations', fallback: 'Integrations' },
      '/tools': { key: 'mainmenue.filesystem', fallback: 'Filesystem' },
      '/sysinfo': { key: 'mainmenue.sysinfo', fallback: 'System' },
      '/maintenance': { key: 'mainmenue.maintenance', fallback: 'Maintenance' },
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
        {menuItems.map((item) => (
          <ListItem key={item.path} disablePadding>
            <Tooltip title={!desktopOpen ? t(`mainmenue.${item.key}`) : ''} placement="right">
              <ListItemButton
                selected={isActive(item.path)}
                onClick={() => handleNavigation(item.path)}
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
              </ListItemButton>
            </Tooltip>
          </ListItem>
        ))}
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
                  key={lang.code}
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
              <MenuItem onClick={handleReboot}>
                {t('main.reboot_button') || 'Reboot'}
              </MenuItem>
              <MenuItem onClick={handleShutdown}>
                {t('main.shutdown_button') || 'Power off'}
              </MenuItem>
              <MenuItem onClick={handleLogout}>
                {t('main.logout_button') || 'Logout'}
              </MenuItem>
            </MuiMenu>
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
