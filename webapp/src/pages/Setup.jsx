import React, { useState, useEffect, useRef } from 'react';
import {
  Box,
  Typography,
  Select,
  MenuItem,
  FormControl,
  InputLabel,
  Checkbox,
  FormControlLabel,
  Snackbar,
  CircularProgress,
  Grid,
  Stack,
  Alert,
} from '@mui/material';
import { useLanguage } from '../contexts/LanguageContext';
import { useConfig } from '../contexts/ConfigContext';
import DisplayConfig from '../components/DisplayConfig';

function UserInterface() {
  const { t } = useLanguage();
  const { config, updateConfig, constants } = useConfig();
  const [formData, setFormData] = useState({});
  const [message, setMessage] = useState('');
  const isInitialMount = useRef(true);
  const saveTimeoutRef = useRef(null);
  const lastSavedConfig = useRef(null);
  const isSaving = useRef(false);

  useEffect(() => {
    if (config) {
      const configString = JSON.stringify(config);
      if (lastSavedConfig.current !== configString) {
        setFormData(config);
        lastSavedConfig.current = configString;
        isInitialMount.current = true;
        isSaving.current = false;
      }
    }
  }, [config]);

  useEffect(() => {
    if (isInitialMount.current) {
      isInitialMount.current = false;
      return;
    }

    if (isSaving.current) {
      return;
    }

    if (Object.keys(formData).length === 0) {
      return;
    }

    const formDataString = JSON.stringify(formData);
    if (lastSavedConfig.current === formDataString) {
      return;
    }

    if (saveTimeoutRef.current) {
      clearTimeout(saveTimeoutRef.current);
    }

    saveTimeoutRef.current = setTimeout(async () => {
      isSaving.current = true;
      try {
        await updateConfig(formData);
        lastSavedConfig.current = JSON.stringify(formData);
        setMessage(t('config.message_settings_saved') || 'Settings saved');
      } catch (error) {
        console.error('Failed to save settings:', error);
        setMessage('Error saving settings');
      } finally {
        isSaving.current = false;
      }
    }, 500);

    return () => {
      if (saveTimeoutRef.current) {
        clearTimeout(saveTimeoutRef.current);
      }
    };
  }, [formData, updateConfig, t]);

  if (!config) {
    return (
      <Box display="flex" justifyContent="center" alignItems="center" minHeight="400px">
        <CircularProgress />
      </Box>
    );
  }

  return (
    <Box>
      <Grid container spacing={3}>
        <Grid item xs={12}>
          <Box>
            <Typography variant="h5" gutterBottom>
              {t('config.backup.general_settings_header') || 'Defaults'}
            </Typography>
            <Typography variant="body2" color="text.secondary" sx={{ mb: 3 }}>
              {t('config.view_section_desc')}
            </Typography>
            <Stack spacing={3}>
              <FormControl sx={{ maxWidth: 400 }}>
                <InputLabel>{t('config.lang_header') || 'Language'}</InputLabel>
                <Select
                  value={formData.conf_LANGUAGE || 'en'}
                  onChange={(e) => {
                    setFormData({ ...formData, conf_LANGUAGE: e.target.value });
                  }}
                  label={t('config.lang_header') || 'Language'}
                >
                  <MenuItem value="en">English</MenuItem>
                  <MenuItem value="de">Deutsch</MenuItem>
                  <MenuItem value="es">Español</MenuItem>
                  <MenuItem value="fr">Français</MenuItem>
                  <MenuItem value="fi">Suomi</MenuItem>
                </Select>
              </FormControl>

              <FormControl sx={{ maxWidth: 400 }}>
                <InputLabel>{t('config.view_theme_header') || 'Theme'}</InputLabel>
                <Select
                  value={formData.conf_THEME || 'system'}
                  onChange={(e) => {
                    setFormData({ ...formData, conf_THEME: e.target.value });
                  }}
                  label={t('config.view_theme_header') || 'Theme'}
                >
                  <MenuItem value="light">Light</MenuItem>
                  <MenuItem value="dark">Dark</MenuItem>
                  <MenuItem value="system">System</MenuItem>
                </Select>
              </FormControl>

              <Box>
                <Typography variant="h6" gutterBottom>
                  {t('config.screen.virtual_keyboard_enable_header') || 'Virtual keyboard'}
                </Typography>
                <FormControlLabel
                  control={
                    <Checkbox
                      checked={formData.conf_VIRTUAL_KEYBOARD_ENABLED === '1' || formData.conf_VIRTUAL_KEYBOARD_ENABLED === true}
                      onChange={(e) => {
                        setFormData({ ...formData, conf_VIRTUAL_KEYBOARD_ENABLED: e.target.checked ? '1' : '0' });
                      }}
                    />
                  }
                  label={t('config.screen.virtual_keyboard_enable_label') || 'Enable virtual keyboard'}
                />
              </Box>
            </Stack>
          </Box>
        </Grid>

        <Grid item xs={12}>
          <Typography variant="h5" gutterBottom>
            {t('config.display.section') || 'Display'}
          </Typography>
          <DisplayConfig />
        </Grid>
      </Grid>

      <Snackbar
        open={!!message}
        autoHideDuration={3000}
        onClose={() => setMessage('')}
        anchorOrigin={{ vertical: 'bottom', horizontal: 'right' }}
      >
        <Alert 
          onClose={() => setMessage('')} 
          severity={message.includes('Error') ? 'error' : 'success'}
          sx={{ width: '100%' }}
        >
          {message}
        </Alert>
      </Snackbar>
    </Box>
  );
}

export default UserInterface;
