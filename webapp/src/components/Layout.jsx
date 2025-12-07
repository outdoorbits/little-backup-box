import React, { useEffect } from 'react';
import { Box, Container, Toolbar } from '@mui/material';
import { useLocation } from 'react-router-dom';
import { useConfig } from '../contexts/ConfigContext';
import { useDrawer } from '../contexts/DrawerContext';
import Menu, { drawerWidth, drawerCollapsedWidth } from './Menu';
import DisplayStatus from './DisplayStatus';

function Layout({ children }) {
  const { config, constants } = useConfig();
  const { desktopOpen } = useDrawer();
  const location = useLocation();
  const [background, setBackground] = React.useState('');
  const currentDrawerWidth = desktopOpen ? drawerWidth : drawerCollapsedWidth;

  useEffect(() => {
    if (config?.conf_BACKGROUND_IMAGE && constants?.const_MEDIA_DIR) {
      const bgPath = `${constants.const_MEDIA_DIR}/${constants.const_BACKGROUND_IMAGES_DIR}/${config.conf_BACKGROUND_IMAGE}`;
      setBackground(bgPath);
    }
  }, [config, constants]);

  useEffect(() => {
    document.documentElement.setAttribute('lang', config?.conf_LANGUAGE || 'en');
  }, [config]);

  return (
    <Box
      sx={{
        display: 'flex',
        minHeight: '100vh',
        margin: 0,
        padding: 0,
        backgroundImage: background ? `url(${background})` : 'none',
        backgroundSize: 'cover',
        backgroundRepeat: 'no-repeat',
        backgroundAttachment: 'fixed',
      }}
    >
      <Menu />
      <Box
        component="main"
        sx={{
          flexGrow: 1,
          width: { md: `calc(100% - ${currentDrawerWidth}px)` },
          display: 'flex',
          flexDirection: 'column',
          transition: (theme) =>
            theme.transitions.create('width', {
              easing: theme.transitions.easing.sharp,
              duration: theme.transitions.duration.enteringScreen,
            }),
        }}
      >
        <Toolbar />
        <DisplayStatus />
        <Container maxWidth="xl" sx={{ flex: 1, py: 3 }}>
          {children}
        </Container>
      </Box>
    </Box>
  );
}

export default Layout;
