import { createTheme } from '@mui/material/styles';

export const createAppTheme = (themeMode = 'dark') => {
  return createTheme({
    palette: {
      mode: themeMode,
    },
  });
};
