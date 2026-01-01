import React, { useState, useEffect, useRef } from 'react';
import {
  Box,
  Typography,
  FormControl,
  FormControlLabel,
  Button,
  Grid,
  Alert,
  Accordion,
  AccordionSummary,
  AccordionDetails,
  Checkbox,
  Select,
  MenuItem,
  FormLabel,
  CircularProgress,
  Stack,
  List,
  ListItem,
  ListItemText,
  IconButton,
  Chip,
  ListSubheader,
  Snackbar,
} from '@mui/material';
import ExpandMoreIcon from '@mui/icons-material/ExpandMore';
import StopIcon from '@mui/icons-material/Stop';
import PlayArrowIcon from '@mui/icons-material/PlayArrow';
import ReplayIcon from '@mui/icons-material/Replay';
import { useLanguage } from '../contexts/LanguageContext';
import { useConfig } from '../contexts/ConfigContext';
import api from '../utils/api';

function Backup() {
  const { t } = useLanguage();
  const { config, updateConfig } = useConfig();
  const [services, setServices] = useState({ sourceServices: {}, targetServices: {}, nvmeAvailable: false });
  const [partitions, setPartitions] = useState([]);
  const [runningBackups, setRunningBackups] = useState([]);
  const [backupHistory, setBackupHistory] = useState([]);
  const [formData, setFormData] = useState({
    sourceDevice: 'anyusb',
    targetDevice: '',
    moveFiles: config?.conf_BACKUP_MOVE_FILES === 'true' ? 'move' : 'copy',
    renameFiles: config?.conf_BACKUP_RENAME_FILES === 'true',
    generateThumbnails: config?.conf_BACKUP_GENERATE_THUMBNAILS === 'true',
    updateExif: config?.conf_BACKUP_UPDATE_EXIF === 'true',
    checksum: config?.conf_BACKUP_CHECKSUM === 'true' ? 'checksum' : 'file_size_timestamp',
    powerOff: config?.conf_POWER_OFF === 'true',
    presetSource: '',
    presetTarget: '',
    secSourceName: 'none',
    secTargetName: 'none',
  });
  const [message, setMessage] = useState('');
  const [toastMessage, setToastMessage] = useState('');
  const [toastSeverity, setToastSeverity] = useState('success');
  const [previousRunningBackups, setPreviousRunningBackups] = useState([]);
  const [loading, setLoading] = useState(true);
  const [defaultBackupConfig, setDefaultBackupConfig] = useState({
    conf_BACKUP_MOVE_FILES: 'false',
    conf_BACKUP_RENAME_FILES: 'false',
    conf_BACKUP_GENERATE_THUMBNAILS: 'false',
    conf_BACKUP_UPDATE_EXIF: 'false',
    conf_BACKUP_CHECKSUM: 'false',
    conf_POWER_OFF: 'false',
    conf_MAIL_NOTIFICATIONS: '0',
  });
  const isInitialMount = useRef(true);
  const saveTimeoutRef = useRef(null);
  const lastSavedConfig = useRef(null);
  const isSaving = useRef(false);
  const configInitialized = useRef(false);
  const pendingSaveConfig = useRef(null);
  const isSyncingFromServer = useRef(false);
  const defaultBackupConfigRef = useRef({
    conf_BACKUP_MOVE_FILES: 'false',
    conf_BACKUP_RENAME_FILES: 'false',
    conf_BACKUP_GENERATE_THUMBNAILS: 'false',
    conf_BACKUP_UPDATE_EXIF: 'false',
    conf_BACKUP_CHECKSUM: 'false',
    conf_POWER_OFF: 'false',
    conf_MAIL_NOTIFICATIONS: '0',
  });
  const [optionsAccordionExpanded, setOptionsAccordionExpanded] = useState(false);

  useEffect(() => {
    // Load accordion state from localStorage
    const savedState = localStorage.getItem('accordion-home-options');
    if (savedState !== null) {
      setOptionsAccordionExpanded(JSON.parse(savedState));
    }
    
    loadServices();
    loadPartitions();
    loadRunningBackups();
    loadBackupHistory();
    
    const interval = setInterval(() => {
      loadRunningBackups();
    }, 2000);
    
    return () => clearInterval(interval);
  }, []);

  const handleOptionsAccordionChange = (event, isExpanded) => {
    setOptionsAccordionExpanded(isExpanded);
    localStorage.setItem('accordion-home-options', JSON.stringify(isExpanded));
  };

  useEffect(() => {
    defaultBackupConfigRef.current = defaultBackupConfig;
  }, [defaultBackupConfig]);

  useEffect(() => {
    if (config && !configInitialized.current) {
      const newConfig = {
        conf_BACKUP_MOVE_FILES: config.conf_BACKUP_MOVE_FILES || 'false',
        conf_BACKUP_RENAME_FILES: config.conf_BACKUP_RENAME_FILES || 'false',
        conf_BACKUP_GENERATE_THUMBNAILS: config.conf_BACKUP_GENERATE_THUMBNAILS || 'false',
        conf_BACKUP_UPDATE_EXIF: config.conf_BACKUP_UPDATE_EXIF || 'false',
        conf_BACKUP_CHECKSUM: config.conf_BACKUP_CHECKSUM || 'false',
        conf_POWER_OFF: config.conf_POWER_OFF || 'false',
        conf_MAIL_NOTIFICATIONS: config.conf_MAIL_NOTIFICATIONS || '0',
      };
      const configString = JSON.stringify(newConfig);
      setDefaultBackupConfig(newConfig);
      lastSavedConfig.current = configString;
      configInitialized.current = true;
      isSaving.current = false;
      
      setFormData(prev => ({
        ...prev,
        moveFiles: newConfig.conf_BACKUP_MOVE_FILES === 'true' ? 'move' : 'copy',
        renameFiles: newConfig.conf_BACKUP_RENAME_FILES === 'true',
        generateThumbnails: newConfig.conf_BACKUP_GENERATE_THUMBNAILS === 'true',
        updateExif: newConfig.conf_BACKUP_UPDATE_EXIF === 'true',
        checksum: newConfig.conf_BACKUP_CHECKSUM === 'true' ? 'checksum' : 'file_size_timestamp',
        powerOff: newConfig.conf_POWER_OFF === 'true',
      }));
    } else if (config && configInitialized.current && !isSaving.current) {
      const newConfig = {
        conf_BACKUP_MOVE_FILES: config.conf_BACKUP_MOVE_FILES || 'false',
        conf_BACKUP_RENAME_FILES: config.conf_BACKUP_RENAME_FILES || 'false',
        conf_BACKUP_GENERATE_THUMBNAILS: config.conf_BACKUP_GENERATE_THUMBNAILS || 'false',
        conf_BACKUP_UPDATE_EXIF: config.conf_BACKUP_UPDATE_EXIF || 'false',
        conf_BACKUP_CHECKSUM: config.conf_BACKUP_CHECKSUM || 'false',
        conf_POWER_OFF: config.conf_POWER_OFF || 'false',
        conf_MAIL_NOTIFICATIONS: config.conf_MAIL_NOTIFICATIONS || '0',
      };
      const configString = JSON.stringify(newConfig);
      
      if (pendingSaveConfig.current) {
        if (pendingSaveConfig.current === configString) {
          isSyncingFromServer.current = true;
          setDefaultBackupConfig(newConfig);
          lastSavedConfig.current = configString;
          pendingSaveConfig.current = null;
          setTimeout(() => {
            isSyncingFromServer.current = false;
          }, 0);
        }
      } else {
        const currentConfigString = JSON.stringify(defaultBackupConfig);
        if (currentConfigString !== configString) {
          isSyncingFromServer.current = true;
          setDefaultBackupConfig(newConfig);
          lastSavedConfig.current = configString;
          setTimeout(() => {
            isSyncingFromServer.current = false;
          }, 0);
        }
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

    if (isSyncingFromServer.current) {
      return;
    }

    if (!config || !configInitialized.current) {
      return;
    }

    const configString = JSON.stringify(defaultBackupConfig);
    if (lastSavedConfig.current === configString) {
      return;
    }
    
    if (saveTimeoutRef.current) {
      clearTimeout(saveTimeoutRef.current);
    }

    saveTimeoutRef.current = setTimeout(async () => {
      isSaving.current = true;
      const configToSave = JSON.stringify(defaultBackupConfig);
      pendingSaveConfig.current = configToSave;
      try {
        await updateConfig(defaultBackupConfig);
        lastSavedConfig.current = configToSave;
      } catch (error) {
        console.error('Failed to save default backup config:', error);
        pendingSaveConfig.current = null;
      } finally {
        isSaving.current = false;
      }
    }, 500);

    return () => {
      if (saveTimeoutRef.current) {
        clearTimeout(saveTimeoutRef.current);
      }
    };
  }, [defaultBackupConfig, updateConfig, config]);

  const loadServices = async () => {
    try {
      const response = await api.get('/backup/services');
      if (response.data && response.data.sourceServices && response.data.targetServices) {
        setServices(response.data);
      } else {
        console.error('Invalid services response:', response.data);
        setServices({ sourceServices: {}, targetServices: {}, nvmeAvailable: false });
      }
    } catch (error) {
      console.error('Failed to load services:', error);
      setServices({ sourceServices: {}, targetServices: {}, nvmeAvailable: false });
    } finally {
      setLoading(false);
    }
  };

  const loadPartitions = async () => {
    try {
      const response = await api.get('/backup/partitions');
      setPartitions(response.data?.partitions || []);
    } catch (error) {
      console.error('Failed to load partitions:', error);
      setPartitions([]);
    }
  };

  const loadRunningBackups = async () => {
    try {
      const response = await api.get('/backup/running');
      const currentBackups = response.data?.backups || [];
      
      if (previousRunningBackups.length > 0 && currentBackups.length < previousRunningBackups.length) {
        const finishedBackups = previousRunningBackups.filter(
          prev => !currentBackups.some(curr => curr.pid === prev.pid)
        );
        if (finishedBackups.length > 0) {
          setToastSeverity('success');
          setToastMessage(t('box.backup.complete') || 'Backup complete');
        }
      }
      
      setPreviousRunningBackups(currentBackups);
      setRunningBackups(currentBackups);
    } catch (error) {
      console.error('Failed to load running backups:', error);
      setRunningBackups([]);
      setPreviousRunningBackups([]);
    }
  };

  const loadBackupHistory = async () => {
    try {
      const response = await api.get('/backup/history');
      setBackupHistory(response.data?.history || []);
    } catch (error) {
      console.error('Failed to load backup history:', error);
      setBackupHistory([]);
    }
  };

  const handleStartBackup = async () => {
    if (!formData.sourceDevice || !formData.targetDevice) {
      setMessage(t('main.backup_error') || 'Please select source and target');
      return;
    }
    
    if (isDisallowedCombination(formData.sourceDevice, formData.targetDevice)) {
      setMessage(t('main.backup_error') || 'Invalid source/target combination');
      return;
    }
    
    try {
      const backupData = {
        ...formData,
        moveFiles: formData.moveFiles === 'move',
        checksum: formData.checksum === 'checksum',
        presetSource: formData.presetSource || '',
        presetTarget: formData.presetTarget || '',
      };
      await api.post('/backup/start', backupData);
      setToastSeverity('success');
      setToastMessage(t('main.backup.initiated'));
      setTimeout(() => {
        loadRunningBackups();
        loadBackupHistory();
      }, 1000);
    } catch (error) {
      console.error('Failed to start backup:', error);
      if (error.response?.status === 409) {
        setMessage(t('main.backup_error') || 'A backup with this configuration is already running');
      } else {
        setMessage(t('main.backup_error') || 'Backup error');
      }
    }
  };

  const handleStopBackup = async (pid) => {
    try {
      await api.post('/backup/stop', pid ? { pid } : {});
      setToastSeverity('info');
      setToastMessage(t('main.stopbackup_m'));
      setTimeout(() => loadRunningBackups(), 500);
    } catch (error) {
      console.error('Failed to stop backup:', error);
      setToastSeverity('error');
      setToastMessage(t('main.backup_error') || 'Failed to stop backup');
    }
  };

  const handleSaveAsDefaults = async () => {
    const newDefaults = {
      conf_BACKUP_MOVE_FILES: formData.moveFiles === 'move' ? 'true' : 'false',
      conf_BACKUP_RENAME_FILES: formData.renameFiles ? 'true' : 'false',
      conf_BACKUP_GENERATE_THUMBNAILS: formData.generateThumbnails ? 'true' : 'false',
      conf_BACKUP_UPDATE_EXIF: formData.updateExif ? 'true' : 'false',
      conf_BACKUP_CHECKSUM: formData.checksum === 'checksum' ? 'true' : 'false',
      conf_POWER_OFF: formData.powerOff ? 'true' : 'false',
      conf_MAIL_NOTIFICATIONS: defaultBackupConfig.conf_MAIL_NOTIFICATIONS || '0',
    };
    
    try {
      await updateConfig(newDefaults);
      setDefaultBackupConfig(newDefaults);
      setToastSeverity('success');
      setToastMessage(t('main.backup.defaults_saved') || 'Defaults saved successfully');
    } catch (error) {
      console.error('Failed to save defaults:', error);
      setToastSeverity('error');
      setToastMessage(t('main.backup.defaults_save_error') || 'Failed to save defaults');
    }
  };

  const isBackupRunning = () => {
    return runningBackups.some(backup => 
      backup.sourceDevice === formData.sourceDevice &&
      backup.targetDevice === formData.targetDevice &&
      (backup.presetSource || '') === (formData.presetSource || '') &&
      (backup.presetTarget || '') === (formData.presetTarget || '') &&
      backup.moveFiles === (formData.moveFiles === 'move') &&
      backup.renameFiles === formData.renameFiles &&
      backup.generateThumbnails === formData.generateThumbnails &&
      backup.updateExif === formData.updateExif &&
      backup.checksum === (formData.checksum === 'checksum') &&
      backup.powerOff === formData.powerOff
    );
  };

  const hasMissingEmailServerConfig = () => {
    if (!config) return true;
    const password = config.conf_MAIL_PASSWORD?.trim() || '';
    let decodedPassword = '';
    if (password) {
      try {
        decodedPassword = atob(password);
      } catch (e) {
        decodedPassword = '';
      }
    }
    return !(
      config.conf_SMTP_SERVER?.trim() &&
      config.conf_SMTP_PORT?.trim() &&
      config.conf_MAIL_SECURITY &&
      config.conf_MAIL_USER?.trim() &&
      decodedPassword?.trim() &&
      config.conf_MAIL_FROM?.trim() &&
      config.conf_MAIL_TO?.trim()
    );
  };

  const getServiceColor = (service) => {
    if (service.includes('usb')) return 'primary';
    if (service.includes('camera')) return 'warning';
    if (service.includes('cloud')) return 'info';
    if (service.includes('social')) return 'secondary';
    if (service.includes('anyusb')) return 'warning';
    return 'primary';
  };

  const hasServices = services && services.sourceServices && services.targetServices && 
    Object.keys(services.sourceServices).length > 0 && Object.keys(services.targetServices).length > 0;
  
  if (loading || !hasServices) {
    return (
      <Box display="flex" justifyContent="center" alignItems="center" minHeight="400px">
        <CircularProgress />
      </Box>
    );
  }

  return (
    <Box>
      {message && (
        <Alert 
          severity={message.includes('error') ? 'error' : 'info'} 
          sx={{ mb: 3 }}
          onClose={() => setMessage('')}
        >
          {message}
        </Alert>
      )}

      <Box>
        <Typography variant="h2" gutterBottom>
          {(() => {
            const translation = t('main.backup.configuration');
            return translation !== 'main.backup.configuration' ? translation : 'Backup Configuration';
          })()}
        </Typography>
        
        <Grid container spacing={3} sx={{ mt: 1 }}>
            <Grid item xs={12} md={6}>
              <FormControl sx={{ maxWidth: 400 }}>
                <FormLabel>{t('main.source')}</FormLabel>
                <Select
                  value={formData.sourceDevice}
                  onChange={(e) => setFormData({ ...formData, sourceDevice: e.target.value })}
                >
                  {Object.entries(services.sourceServices || {}).flatMap(([group, items]) => {
                    const filteredItems = items.filter(service => 
                      !service.startsWith('cloud:') && 
                      service !== 'cloud_rsync' &&
                      !service.startsWith('social:')
                    );
                    if (filteredItems.length === 0) {
                      return [];
                    }
                    return [
                      <ListSubheader key={`header-${group}`}>
                        {getGroupLabel(group, t)}
                      </ListSubheader>,
                      ...filteredItems.map((service) => (
                        <MenuItem key={service} value={service}>
                          {getServiceLabel(service, t)}
                        </MenuItem>
                      ))
                    ];
                  })}
                  <ListSubheader>
                    {t('main.backup.partitions') || 'Partitions'}
                  </ListSubheader>
                  <MenuItem value="selected_partition">
                    {t('main.backup.selected_partition') || 'Selected partition'}
                  </MenuItem>
                </Select>
              </FormControl>
              {formData.sourceDevice === 'selected_partition' && (
                <FormControl sx={{ maxWidth: 400, mt: 2 }}>
                  <FormLabel>{t('main.backup.preset_source_label')}</FormLabel>
                  <Select
                    value={formData.presetSource || ''}
                    onChange={(e) => setFormData({ ...formData, presetSource: e.target.value })}
                  >
                    {partitions.map((p, i) => (
                      <MenuItem key={i} value={p.identifier || ''}>
                        {p.lum} {p.identifier ? `(${p.identifier})` : ''}
                      </MenuItem>
                    ))}
                  </Select>
                </FormControl>
              )}
            </Grid>

            <Grid item xs={12} md={6}>
              <FormControl sx={{ maxWidth: 400 }}>
                <FormLabel>{t('main.target')}</FormLabel>
                <Select
                  value={formData.targetDevice}
                  onChange={(e) => setFormData({ ...formData, targetDevice: e.target.value })}
                >
                  {(() => {
                    const localStorageItems = [];
                    const otherItems = [];
                    
                    Object.entries(services.targetServices || {}).forEach(([group, items]) => {
                      const filteredItems = items.filter(service => 
                        !service.startsWith('cloud:') && 
                        service !== 'cloud_rsync' &&
                        !service.startsWith('social:')
                      );
                      
                      filteredItems.forEach((service) => {
                        if (isLocalStorageTarget(service)) {
                          localStorageItems.push({ service, group });
                        } else {
                          otherItems.push({ service, group });
                        }
                      });
                    });
                    
                    const result = [];
                    
                    if (localStorageItems.length > 0) {
                      result.push(
                        <ListSubheader key="header-local">
                          {t('main.backup.local_storage') || 'Local Storage'}
                        </ListSubheader>
                      );
                      localStorageItems.forEach(({ service }) => {
                        const isDisabled = isDisallowedCombination(formData.sourceDevice, service);
                        result.push(
                          <MenuItem key={service} value={service} disabled={isDisabled}>
                            {getServiceLabel(service, t)}
                          </MenuItem>
                        );
                      });
                      const isPartitionDisabled = isDisallowedCombination(formData.sourceDevice, 'selected_partition');
                      result.push(
                        <MenuItem key="selected_partition" value="selected_partition" disabled={isPartitionDisabled}>
                          {t('main.backup.selected_partition') || 'Selected partition'}
                        </MenuItem>
                      );
                    }
                    
                    const groupedOther = {};
                    otherItems.forEach(({ service, group }) => {
                      if (!groupedOther[group]) {
                        groupedOther[group] = [];
                      }
                      groupedOther[group].push(service);
                    });
                    
                    Object.entries(groupedOther).forEach(([group, items]) => {
                      result.push(
                        <ListSubheader key={`header-${group}`}>
                          {getGroupLabel(group, t)}
                        </ListSubheader>
                      );
                      items.forEach((service) => {
                        const isDisabled = isDisallowedCombination(formData.sourceDevice, service);
                        result.push(
                          <MenuItem key={service} value={service} disabled={isDisabled}>
                            {getServiceLabel(service, t)}
                          </MenuItem>
                        );
                      });
                    });
                    
                    return result;
                  })()}
                </Select>
              </FormControl>
              {formData.targetDevice === 'selected_partition' && (
                <FormControl sx={{ maxWidth: 400, mt: 2 }}>
                  <FormLabel>{t('main.backup.preset_target_label')}</FormLabel>
                  <Select
                    value={formData.presetTarget || ''}
                    onChange={(e) => setFormData({ ...formData, presetTarget: e.target.value })}
                  >
                    {partitions.map((p, i) => (
                      <MenuItem key={i} value={p.identifier || ''}>
                        {p.lum} {p.identifier ? `(${p.identifier})` : ''}
                      </MenuItem>
                    ))}
                  </Select>
                </FormControl>
              )}
            </Grid>
          </Grid>

          <Accordion 
            sx={{ mt: 3 }}
            expanded={optionsAccordionExpanded}
            onChange={handleOptionsAccordionChange}
          >
            <AccordionSummary expandIcon={<ExpandMoreIcon />}>
              <Typography variant="h6">
                {(() => {
                  const translation = t('main.backup.options');
                  return translation !== 'main.backup.options' ? translation : 'Options';
                })()}
              </Typography>
            </AccordionSummary>
            <AccordionDetails>
              <Stack spacing={3}>
                <Box>
                  <Typography variant="h3" gutterBottom>
                    {t('main.backup.general')}
                  </Typography>
                  <Box sx={{ mt: 1 }}>
                    <FormControlLabel
                      control={
                        <Checkbox
                          checked={formData.powerOff}
                          onChange={(e) => setFormData({ ...formData, powerOff: e.target.checked })}
                        />
                      }
                      label={t('main.backup.power_off_checkbox_label')}
                    />
                  </Box>
                </Box>

                <Box>
                  <Typography variant="h3" gutterBottom>
                    {t('main.backup.primary')}
                  </Typography>
                  <Stack spacing={1} sx={{ mt: 1 }}>
                    <FormControl sx={{ maxWidth: 400 }}>
                      <FormLabel>{t('config.backup.file_operation_label') || 'File operation'}</FormLabel>
                      <Select
                        value={formData.moveFiles}
                        onChange={(e) => setFormData({ ...formData, moveFiles: e.target.value })}
                      >
                        <MenuItem value="copy">
                          {t('config.backup.file_operation_copy') || 'Copy files'}
                        </MenuItem>
                        <MenuItem value="move">
                          {t('config.backup.file_operation_move') || 'Move files'}
                        </MenuItem>
                      </Select>
                    </FormControl>
                    {isLocalStorageTarget(formData.targetDevice) && (
                      <>
                        <FormControlLabel
                          control={
                            <Checkbox
                              checked={formData.renameFiles}
                              onChange={(e) => setFormData({ ...formData, renameFiles: e.target.checked })}
                            />
                          }
                          label={t('main.backup.rename_checkbox_label')}
                        />
                        <FormControlLabel
                          control={
                            <Checkbox
                              checked={formData.generateThumbnails}
                              onChange={(e) => setFormData({ ...formData, generateThumbnails: e.target.checked })}
                            />
                          }
                          label={t('main.backup.generate_thumbnails_checkbox_label')}
                        />
                        <FormControlLabel
                          control={
                            <Checkbox
                              checked={formData.updateExif}
                              onChange={(e) => setFormData({ ...formData, updateExif: e.target.checked })}
                            />
                          }
                          label={t('main.backup.update_exif_checkbox_label')}
                        />
                      </>
                    )}
                    <FormControl sx={{ maxWidth: 400 }}>
                      <FormLabel>{t('main.backup.comparison_method_label') || 'Comparison method'}</FormLabel>
                      <Select
                        value={formData.checksum}
                        onChange={(e) => setFormData({ ...formData, checksum: e.target.value })}
                      >
                        <MenuItem value="file_size_timestamp">
                          {t('main.backup.comparison_file_size_timestamp') || 'File size and timestamp'}
                        </MenuItem>
                        <MenuItem value="checksum">
                          {t('main.backup.comparison_checksum') || 'Checksum'}
                        </MenuItem>
                      </Select>
                    </FormControl>
                  </Stack>

                </Box>

                <Box>
                  <Typography variant="h3" gutterBottom>
                    {t('main.backup.secondary')}
                  </Typography>
                  <Box sx={{ mt: 1 }}>
                    <FormControl sx={{ maxWidth: 400 }}>
                    <FormLabel>{t('main.backup.secondary_label')}</FormLabel>
                    <Select
                      value={`${formData.secSourceName} ${formData.secTargetName}`}
                      onChange={(e) => {
                        const parts = e.target.value.split(' ');
                        setFormData({
                          ...formData,
                          secSourceName: parts[0] || 'none',
                          secTargetName: parts[1] || 'none'
                        });
                      }}
                    >
                      <MenuItem value="none none">
                        {t('config.backup.none') || 'no automatic backup'}
                      </MenuItem>
                      {services.targetServices?.cloud?.includes('cloud_rsync') && (
                        <>
                          <ListSubheader>
                            → {t('main.rsync_button') || 'rsync server'}
                          </ListSubheader>
                          <MenuItem value="usb cloud_rsync">
                            {t('main.usb_button') || 'USB storage'} → {t('main.rsync_button') || 'rsync server'}
                          </MenuItem>
                          {services.nvmeAvailable && (
                            <MenuItem value="nvme cloud_rsync">
                              {t('main.nvme_button') || 'NVMe SSD'} → {t('main.rsync_button') || 'rsync server'}
                            </MenuItem>
                          )}
                          <MenuItem value="internal cloud_rsync">
                            {t('main.internal_button') || 'Int. storage'} → {t('main.rsync_button') || 'rsync server'}
                          </MenuItem>
                        </>
                      )}
                      {services.targetServices?.cloud
                        ?.filter(service => service !== 'cloud_rsync' && service.startsWith('cloud:'))
                        .map((cloudService) => {
                          const serviceName = cloudService.replace('cloud:', '');
                          return (
                            <React.Fragment key={cloudService}>
                              <ListSubheader>
                                → {serviceName}
                              </ListSubheader>
                              <MenuItem value={`usb ${cloudService}`}>
                                {t('main.usb_button') || 'USB storage'} → {serviceName}
                              </MenuItem>
                              {services.nvmeAvailable && (
                                <MenuItem value={`nvme ${cloudService}`}>
                                  {t('main.nvme_button') || 'NVMe SSD'} → {serviceName}
                                </MenuItem>
                              )}
                              <MenuItem value={`internal ${cloudService}`}>
                                {t('main.internal_button') || 'Int. storage'} → {serviceName}
                              </MenuItem>
                            </React.Fragment>
                          );
                        })}
                    </Select>
                  </FormControl>
                  </Box>
                </Box>

                <Box>
                  <Typography variant="h3" gutterBottom>
                    {t('config.mail.section') || 'Email'}
                  </Typography>
                  <Box sx={{ mt: 1 }}>
                    {hasMissingEmailServerConfig() && (
                    <Alert severity="warning" sx={{ mt: 2, mb: 2 }}>
                      {t('config.mail.server_settings_notice') || 'Server settings must be configured before email notifications can be sent.'}
                    </Alert>
                  )}
                  <Stack spacing={1.5}>
                    <FormControlLabel
                      control={
                        <Checkbox
                          checked={defaultBackupConfig.conf_MAIL_NOTIFICATIONS === '1'}
                          onChange={(e) => setDefaultBackupConfig({ ...defaultBackupConfig, conf_MAIL_NOTIFICATIONS: e.target.checked ? '1' : '0' })}
                        />
                      }
                      label={t('config.mail.notify_backup_label') || 'If possible, send backup reports via email?'}
                    />
                  </Stack>
                  </Box>
                </Box>
              </Stack>
              <Box sx={{ mt: 3, display: 'flex', justifyContent: 'flex-end' }}>
                <Button
                  variant="outlined"
                  onClick={handleSaveAsDefaults}
                >
                  {t('main.backup.save_as_defaults') || 'Save as defaults'}
                </Button>
              </Box>
            </AccordionDetails>
          </Accordion>

          <Box sx={{ mt: 3, display: 'flex', justifyContent: 'center' }}>
            <Button
              variant="contained"
              color="primary"
              size="large"
              startIcon={<PlayArrowIcon />}
              onClick={handleStartBackup}
              disabled={!formData.sourceDevice || !formData.targetDevice || isBackupRunning()}
            >
              {t('main.backup.start') || 'Start Backup'}
            </Button>
          </Box>

          {backupHistory.length > 0 && (
            <Box sx={{ mt: 3 }}>
              <Typography variant="h2" gutterBottom>
                {t('main.backup.previous_runs') || 'Previous Backup Runs'}
              </Typography>
              <List>
                {backupHistory.map((backup, index) => (
                  <ListItem
                    key={index}
                    secondaryAction={
                      <Button
                        variant="outlined"
                        size="small"
                        startIcon={<ReplayIcon />}
                        onClick={async () => {
                          const rerunData = {
                            ...formData,
                            sourceDevice: backup.sourceDevice,
                            targetDevice: backup.targetDevice,
                            moveFiles: backup.moveFiles ? 'move' : 'copy',
                            renameFiles: backup.renameFiles || false,
                            generateThumbnails: backup.generateThumbnails || false,
                            updateExif: backup.updateExif || false,
                            checksum: backup.checksum ? 'checksum' : 'file_size_timestamp',
                            powerOff: backup.powerOff || false,
                            presetSource: backup.presetSource || '',
                            presetTarget: backup.presetTarget || '',
                          };
                          setFormData(rerunData);
                          
                          try {
                            const backupData = {
                              ...rerunData,
                              moveFiles: rerunData.moveFiles === 'move',
                              checksum: rerunData.checksum === 'checksum',
                              presetSource: rerunData.presetSource || '',
                              presetTarget: rerunData.presetTarget || '',
                            };
                            await api.post('/backup/start', backupData);
                            setToastSeverity('success');
                            setToastMessage(t('main.backup.initiated'));
                            setTimeout(() => {
                              loadRunningBackups();
                              loadBackupHistory();
                            }, 1000);
                          } catch (error) {
                            console.error('Failed to start backup:', error);
                            if (error.response?.status === 409) {
                              setMessage(t('main.backup_error') || 'A backup with this configuration is already running');
                            } else {
                              setMessage(t('main.backup_error') || 'Backup error');
                            }
                          }
                        }}
                      >
                        {t('main.backup.rerun') || 'Rerun'}
                      </Button>
                    }
                  >
                    <ListItemText
                      primary={
                        <Box sx={{ display: 'flex', alignItems: 'center', gap: 1, flexWrap: 'wrap' }}>
                          <Typography variant="body2">
                            {getServiceLabel(backup.sourceDevice, t)} → {getServiceLabel(backup.targetDevice, t)}
                          </Typography>
                          {backup.timestamp && (
                            <Typography variant="caption" color="text.secondary">
                              {new Date(backup.timestamp).toLocaleString()}
                            </Typography>
                          )}
                        </Box>
                      }
                      secondary={
                        <Stack direction="row" spacing={0.5} sx={{ mt: 0.5, flexWrap: 'wrap' }}>
                          {backup.presetSource && (
                            <Chip label={`Source: ${backup.presetSource}`} size="small" variant="outlined" />
                          )}
                          {backup.presetTarget && (
                            <Chip label={`Target: ${backup.presetTarget}`} size="small" variant="outlined" />
                          )}
                          {backup.moveFiles && <Chip label="Move" size="small" variant="outlined" />}
                          {backup.renameFiles && <Chip label="Rename" size="small" variant="outlined" />}
                          {backup.generateThumbnails && <Chip label="Thumbnails" size="small" variant="outlined" />}
                          {backup.updateExif && <Chip label="EXIF" size="small" variant="outlined" />}
                          {backup.checksum && <Chip label={t('main.backup.comparison_checksum') || 'Checksum'} size="small" variant="outlined" />}
                          {backup.powerOff && <Chip label="Power Off" size="small" variant="outlined" color="warning" />}
                        </Stack>
                      }
                    />
                  </ListItem>
                ))}
              </List>
            </Box>
          )}
      </Box>

      {runningBackups.length > 0 && (
        <Box sx={{ mt: 4 }}>
          <Typography variant="h2" gutterBottom>
            {t('main.backup.running') || 'Running Backups'}
          </Typography>
          <Box sx={{ mt: 2 }}>
            <List>
              {runningBackups.map((backup) => (
                <ListItem
                  key={backup.pid}
                  secondaryAction={
                    <IconButton
                      edge="end"
                      aria-label="stop"
                      onClick={() => handleStopBackup(backup.pid)}
                      color="error"
                    >
                      <StopIcon />
                    </IconButton>
                  }
                >
                  <ListItemText
                    primary={
                      <Box sx={{ display: 'flex', alignItems: 'center', gap: 1 }}>
                        <Typography variant="body1">
                          {getServiceLabel(backup.sourceDevice, t)} → {getServiceLabel(backup.targetDevice, t)}
                        </Typography>
                        <Chip label={`PID: ${backup.pid}`} size="small" />
                      </Box>
                    }
                    secondary={
                      <Stack direction="row" spacing={1} sx={{ mt: 1, flexWrap: 'wrap' }}>
                        {backup.presetSource && (
                          <Chip label={`Source: ${backup.presetSource}`} size="small" variant="outlined" />
                        )}
                        {backup.presetTarget && (
                          <Chip label={`Target: ${backup.presetTarget}`} size="small" variant="outlined" />
                        )}
                        {backup.moveFiles && <Chip label="Move" size="small" variant="outlined" />}
                        {backup.renameFiles && <Chip label="Rename" size="small" variant="outlined" />}
                        {backup.generateThumbnails && <Chip label="Thumbnails" size="small" variant="outlined" />}
                        {backup.updateExif && <Chip label="EXIF" size="small" variant="outlined" />}
                        {backup.checksum && <Chip label={t('main.backup.comparison_checksum') || 'Checksum'} size="small" variant="outlined" />}
                        {!backup.checksum && <Chip label={t('main.backup.comparison_file_size_timestamp') || 'File size and timestamp'} size="small" variant="outlined" />}
                        {backup.powerOff && <Chip label="Power Off" size="small" variant="outlined" color="warning" />}
                      </Stack>
                    }
                  />
                </ListItem>
              ))}
            </List>
          </Box>
        </Box>
      )}


      <Snackbar
        open={!!toastMessage}
        autoHideDuration={3000}
        onClose={() => setToastMessage('')}
        anchorOrigin={{ vertical: 'bottom', horizontal: 'right' }}
      >
        <Alert 
          onClose={() => setToastMessage('')} 
          severity={toastSeverity}
          sx={{ width: '100%' }}
        >
          {toastMessage}
        </Alert>
      </Snackbar>
    </Box>
  );
}

function getGroupLabel(group, t) {
  const labels = {
    usb: t('main.backup.usb_devices') || 'USB Devices',
    internal: t('main.backup.internal_storage') || 'Internal Storage',
    cloud: t('box.backup.mode.cloud') || 'Cloud',
    social: t('box.backup.mode.social') || 'SoMe',
  };
  
  return labels[group] || group;
}

function getServiceLabel(service, t) {
  if (!service) return '';
  
  if (service === 'selected_partition') {
    return t('main.backup.selected_partition') || 'Selected partition';
  }
  
  const parts = service.split(':');
  const type = parts[0];
  const name = parts[parts.length - 1];
  
  if (type === 'cloud' && parts.length > 1) {
    return parts.slice(1).join(':');
  }
  
  if (type === 'social' && parts.length > 1) {
    const socialName = parts[1];
    return t(`box.backup.mode.${socialName}`) || socialName;
  }
  
  const labels = {
    anyusb: t('box.backup.mode.anyusb') || 'any USB',
    usb: t('box.backup.mode.usb') || 'USB storage',
    internal: t('box.backup.mode.internal') || 'Int. storage',
    nvme: t('box.backup.mode.nvme') || 'NVMe SSD',
    camera: t('box.backup.mode.cameras') || 'Cameras',
    cloud_rsync: t('box.backup.mode.cloud_rsync') || 'rsync server',
    ftp: t('box.backup.mode.ftp') || 'LBBs FTP server',
  };
  
  return labels[name] || name;
}

function isLocalStorageTarget(target) {
  if (!target) return false;
  const localStorageTargets = ['usb', 'internal', 'nvme', 'selected_partition'];
  return localStorageTargets.includes(target);
}

function isDisallowedCombination(source, target) {
  if (target === source && target !== 'usb' && target !== 'selected_partition') return true;
  if (source === 'anyusb' && target === 'cloud_rsync') return true;
  if (source === 'camera' && target === 'cloud_rsync') return true;
  if (source === 'ftp' && target === 'cloud_rsync') return true;
  if (source === 'anyusb' && target.startsWith('social')) return true;
  if (source === 'camera' && target.startsWith('social')) return true;
  if (source.startsWith('cloud') && target.startsWith('social')) return true;
  if (source === 'ftp' && target.startsWith('social')) return true;
  return false;
}

export default Backup;
