import React, { useState, useEffect, useRef } from 'react';
import {
  Box,
  Typography,
  Card,
  CardContent,
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
  Divider,
  Alert,
} from '@mui/material';
import { useLanguage } from '../contexts/LanguageContext';
import { useConfig } from '../contexts/ConfigContext';

function Setup() {
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
          <Card>
            <CardContent>
              <Typography variant="h5" gutterBottom>
                {t('config.backup.general_settings_header') || 'Defaults'}
              </Typography>
              <Typography variant="body2" color="text.secondary" sx={{ mb: 2 }}>
                {t('config.view_section_desc')}
              </Typography>
              <Stack spacing={3} sx={{ mb: 4 }}>
                <FormControl fullWidth>
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
                  </Select>
                </FormControl>

                <FormControl fullWidth>
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
              </Stack>

            </CardContent>
          </Card>
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

export default Setup;
