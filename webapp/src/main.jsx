import React from 'react';
import ReactDOM from 'react-dom/client';
import { BrowserRouter } from 'react-router-dom';
import { ThemeProvider, CssBaseline, useMediaQuery } from '@mui/material';
import App from './App';
import { ConfigProvider, useConfig } from './contexts/ConfigContext';
import { LanguageProvider } from './contexts/LanguageContext';
import { DrawerProvider } from './contexts/DrawerContext';
import { createAppTheme } from './theme';
import './index.css';

function Root() {
  const [themeMode, setThemeMode] = React.useState(() => {
    const savedTheme = localStorage.getItem('lbb-theme');
    if (savedTheme === 'system' || savedTheme === 'light' || savedTheme === 'dark') {
      return savedTheme;
    }
    return 'system';
  });

  const prefersDarkMode = useMediaQuery('(prefers-color-scheme: dark)');
  
  React.useEffect(() => {
    const handleStorageChange = (e) => {
      if (e.key === 'lbb-theme') {
        const newTheme = e.newValue;
        if (newTheme === 'system' || newTheme === 'light' || newTheme === 'dark') {
          setThemeMode(newTheme);
        }
      }
    };

    window.addEventListener('storage', handleStorageChange);
    return () => window.removeEventListener('storage', handleStorageChange);
  }, []);

  React.useEffect(() => {
    const handleCustomStorageChange = () => {
      const savedTheme = localStorage.getItem('lbb-theme');
      if (savedTheme === 'system' || savedTheme === 'light' || savedTheme === 'dark') {
        setThemeMode(savedTheme);
      }
    };

    window.addEventListener('themeChange', handleCustomStorageChange);
    return () => window.removeEventListener('themeChange', handleCustomStorageChange);
  }, []);
  
  const effectiveMode = React.useMemo(() => {
    if (themeMode === 'system') {
      return prefersDarkMode ? 'dark' : 'light';
    }
    return themeMode;
  }, [themeMode, prefersDarkMode]);

  const theme = React.useMemo(() => createAppTheme(effectiveMode), [effectiveMode]);

  React.useEffect(() => {
    document.documentElement.setAttribute('data-theme', effectiveMode);
  }, [effectiveMode]);

  return (
    <ThemeProvider theme={theme}>
      <CssBaseline />
      <ConfigProvider>
        <ThemeSync setThemeMode={setThemeMode} />
        <LanguageProvider>
          <DrawerProvider>
            <App />
          </DrawerProvider>
        </LanguageProvider>
      </ConfigProvider>
    </ThemeProvider>
  );
}

function ThemeSync({ setThemeMode }) {
  const { config } = useConfig();
  const [hasInitialized, setHasInitialized] = React.useState(false);
  
  React.useEffect(() => {
    if (hasInitialized) return;
    
    const savedTheme = localStorage.getItem('lbb-theme');
    
    if (!savedTheme && config?.conf_THEME) {
      const configTheme = config.conf_THEME;
      if (configTheme === 'system' || configTheme === 'light' || configTheme === 'dark') {
        setThemeMode(configTheme);
        localStorage.setItem('lbb-theme', configTheme);
      }
    } else if (!savedTheme) {
      const defaultTheme = 'system';
      setThemeMode(defaultTheme);
      localStorage.setItem('lbb-theme', defaultTheme);
    }
    
    setHasInitialized(true);
  }, [config, setThemeMode, hasInitialized]);

  return null;
}

const basename = import.meta.env.BASE_URL || '/';

ReactDOM.createRoot(document.getElementById('root')).render(
  <React.StrictMode>
    <BrowserRouter basename={basename}>
      <Root />
    </BrowserRouter>
  </React.StrictMode>
);

