import React, { useState, useEffect, useRef, useCallback } from 'react';
import {
  Box,
  Typography,
  TextField,
  Button,
  Checkbox,
  Stack,
  Table,
  TableBody,
  TableCell,
  TableContainer,
  TableHead,
  TableRow,
  Paper,
  Radio,
  CircularProgress,
  Alert,
} from '@mui/material';
import SaveIcon from '@mui/icons-material/Save';
import RefreshIcon from '@mui/icons-material/Refresh';
import { useLanguage } from '../contexts/LanguageContext';
import { useConfig } from '../contexts/ConfigContext';
import api from '../utils/api';

function CloudConfig({ onSavedStateChange, isSticky = false, drawerWidth = 0 }) {
  const { t } = useLanguage();
  const { config, updateConfig, constants } = useConfig();
  const [cloudServices, setCloudServices] = useState([]);
  const [formData, setFormData] = useState({});
  const [message, setMessage] = useState('');
  const [loading, setLoading] = useState(false);
  const lastSavedConfig = useRef(null);

  useEffect(() => {
    loadCloudServices();
    if (config) {
      loadFormData();
    }
  }, [config]);

  const loadCloudServices = async () => {
    try {
      const response = await api.get('/cloud/services');
      setCloudServices(response.data.services || []);
    } catch (error) {
      console.error('Failed to load cloud services:', error);
    }
  };

  const handleSave = useCallback(async () => {
    try {
      const basedirsArray = Object.entries(formData.basedirs || {})
        .filter(([_, value]) => value)
        .map(([service, basedir]) => `${service}|=|${basedir}`);
      const syncMethodsArray = Object.entries(formData.syncMethods || {})
        .map(([service, method]) => `${service}|=|${method || 'rclone'}`);
      const filesStayInPlaceArray = Object.entries(formData.filesStayInPlace || {})
        .map(([service, value]) => `${service}|=|${value ? 'true' : 'false'}`);

      const configToSave = {
        conf_BACKUP_CLOUDS_TARGET_BASEDIR: basedirsArray.join('|;|'),
        conf_BACKUP_SYNC_METHOD_CLOUDS: syncMethodsArray.join('|;|'),
        conf_BACKUP_CLOUDS_TARGET_FILES_STAY_IN_PLACE: filesStayInPlaceArray.join('|;|'),
      };

      await updateConfig(configToSave);
      lastSavedConfig.current = JSON.stringify(formData);
      if (onSavedStateChange) {
        onSavedStateChange(true, handleSave);
      }
      setMessage(t('config.message_settings_saved') || 'Settings saved');
    } catch (error) {
      console.error('Failed to save cloud settings:', error);
      setMessage('Error saving cloud settings');
    }
  }, [formData, updateConfig, t, onSavedStateChange]);

  const loadFormData = useCallback(() => {
    const basedirsRaw = (config.conf_BACKUP_CLOUDS_TARGET_BASEDIR || '').split('|;|');
    const syncMethodsRaw = (config.conf_BACKUP_SYNC_METHOD_CLOUDS || '').split('|;|');
    const filesStayInPlaceRaw = (config.conf_BACKUP_CLOUDS_TARGET_FILES_STAY_IN_PLACE || '').split('|;|');

    const basedirs = {};
    const syncMethods = {};
    const filesStayInPlace = {};

    cloudServices.forEach((service) => {
      basedirs[service] = '';
      syncMethods[service] = 'rclone';
      filesStayInPlace[service] = true;
    });

    basedirsRaw.forEach((item) => {
      if (item) {
        const [service, basedir] = item.split('|=|');
        if (service) basedirs[service] = basedir || '';
      }
    });

    syncMethodsRaw.forEach((item) => {
      if (item) {
        const [service, method] = item.split('|=|');
        if (service) syncMethods[service] = method || 'rclone';
      }
    });

    filesStayInPlaceRaw.forEach((item) => {
      if (item) {
        const [service, value] = item.split('|=|');
        if (service) filesStayInPlace[service] = value === 'true';
      }
    });

    const newFormData = {
      basedirs,
      syncMethods,
      filesStayInPlace,
    };
    setFormData(newFormData);
    lastSavedConfig.current = JSON.stringify(newFormData);
    if (onSavedStateChange) {
      onSavedStateChange(true, handleSave);
    }
  }, [config, cloudServices, onSavedStateChange, handleSave]);

  useEffect(() => {
    if (cloudServices.length > 0 && config) {
      loadFormData();
    }
  }, [cloudServices, config, loadFormData]);

  // Track saved state
  useEffect(() => {
    if (Object.keys(formData).length === 0 || !formData.basedirs) {
      return;
    }
    const formDataString = JSON.stringify(formData);
    const isSaved = lastSavedConfig.current === formDataString;
    if (onSavedStateChange) {
      onSavedStateChange(isSaved, handleSave);
    }
  }, [formData, onSavedStateChange, handleSave]);

  const handleRestartRcloneGui = async () => {
    setLoading(true);
    try {
      await api.post('/cloud/rclone-gui/restart');
      setMessage(t('config.cloud.rclone.gui.restarted') || 'Rclone GUI restarted');
    } catch (error) {
      setMessage('Error restarting rclone GUI');
    } finally {
      setLoading(false);
    }
  };

  if (!config || !formData.basedirs) {
    return (
      <Box display="flex" justifyContent="center" alignItems="center" minHeight="200px">
        <CircularProgress />
      </Box>
    );
  }

  return (
    <Stack spacing={3} sx={{ pb: isSticky ? 10 : 0 }}>
          {cloudServices.length === 0 && (
            <Alert severity="info">
              {t('integrations.cloud_services.no_services') || 'No cloud services configured. Please configure rclone first.'}
            </Alert>
          )}

          {cloudServices.length > 0 && (
            <>
              <Typography variant="h2">
                {t('config.cloud.target_basedir_header') || 'Base folder on cloud servers'}
              </Typography>
              <Typography variant="body2" color="text.secondary">
                {t('config.cloud.target_basedir_label') || 'The backup on the cloud server is carried out under this directory.'}
              </Typography>
              <Stack spacing={2}>
                {cloudServices.map((service) => (
                  <TextField
                    key={service}
                    label={`${service}`}
                    value={formData.basedirs[service] || ''}
                    onChange={(e) => {
                      setFormData({
                        ...formData,
                        basedirs: {
                          ...formData.basedirs,
                          [service]: e.target.value,
                        },
                      });
                    }}
                    sx={{ maxWidth: 500 }}
                  />
                ))}
              </Stack>

              <Typography variant="h2">
                {t('config.cloud.sync_parameter_header') || 'Sync Parameters'}
              </Typography>
              <TableContainer component={Paper}>
                <Table>
                  <TableHead>
                    <TableRow>
                      <TableCell></TableCell>
                      <TableCell align="center" colSpan={2}>
                        {t('config.cloud.sync_method_header') || 'Sync Method'}
                      </TableCell>
                      <TableCell align="center">
                        {t('config.cloud.files_stay_in_place_header') || 'Files Stay In Place'}
                      </TableCell>
                    </TableRow>
                    <TableRow>
                      <TableCell></TableCell>
                      <TableCell align="center">rclone</TableCell>
                      <TableCell align="center">rsync</TableCell>
                      <TableCell align="center"></TableCell>
                    </TableRow>
                  </TableHead>
                  <TableBody>
                    {cloudServices.map((service) => (
                      <TableRow key={service}>
                        <TableCell component="th" scope="row">
                          {service}:
                        </TableCell>
                        <TableCell align="center">
                          <Radio
                            checked={(formData.syncMethods[service] || 'rclone') !== 'rsync'}
                            onChange={() => {
                              setFormData({
                                ...formData,
                                syncMethods: {
                                  ...formData.syncMethods,
                                  [service]: 'rclone',
                                },
                              });
                            }}
                            value="rclone"
                            name={`sync-${service}`}
                          />
                        </TableCell>
                        <TableCell align="center">
                          <Radio
                            checked={(formData.syncMethods[service] || 'rclone') === 'rsync'}
                            onChange={() => {
                              setFormData({
                                ...formData,
                                syncMethods: {
                                  ...formData.syncMethods,
                                  [service]: 'rsync',
                                },
                              });
                            }}
                            value="rsync"
                            name={`sync-${service}`}
                          />
                        </TableCell>
                        <TableCell align="center">
                          <Checkbox
                            checked={formData.filesStayInPlace[service] !== false}
                            onChange={(e) => {
                              setFormData({
                                ...formData,
                                filesStayInPlace: {
                                  ...formData.filesStayInPlace,
                                  [service]: e.target.checked,
                                },
                              });
                            }}
                          />
                        </TableCell>
                      </TableRow>
                    ))}
                  </TableBody>
                </Table>
              </TableContainer>
            </>
          )}

          <Typography variant="h2">
            {t('config.cloud.rclone.gui.header') || 'rclone GUI'}
          </Typography>
          <Typography variant="body2" color="text.secondary" sx={{ mb: 2 }}>
            {t('config.cloud.rclone.desc') || 'Depending on your cloud service, this configuration may only be valid for a limited time.'}
          </Typography>
          <Button
            variant="outlined"
            startIcon={<RefreshIcon />}
            onClick={handleRestartRcloneGui}
            disabled={loading}
            sx={{ alignSelf: 'flex-start' }}
          >
            {t('config.cloud.rclone.gui.restart_label') || 'Update and restart rclone GUI'}
          </Button>

          <Box
            sx={{
              ...(isSticky && {
                position: 'fixed',
                bottom: 0,
                left: { md: `${drawerWidth}px` },
                right: 0,
                zIndex: 1000,
                p: 2,
                backgroundColor: 'background.paper',
                borderTop: 1,
                borderColor: 'divider',
                display: 'flex',
                justifyContent: 'center',
                transition: (theme) =>
                  theme.transitions.create('left', {
                    easing: theme.transitions.easing.sharp,
                    duration: theme.transitions.duration.enteringScreen,
                  }),
              }),
            }}
          >
            <Button
              variant="contained"
              startIcon={<SaveIcon />}
              onClick={handleSave}
              disabled={cloudServices.length === 0 || lastSavedConfig.current === JSON.stringify(formData)}
              sx={{ 
                alignSelf: 'flex-start',
              }}
              size={isSticky ? 'large' : 'medium'}
            >
              {t('config.save_button') || 'Save'}
            </Button>
          </Box>
        </Stack>
  );
}

export default CloudConfig;

