import React, { useState, useEffect, useRef } from 'react';
import {
  Box,
  Typography,
  Select,
  MenuItem,
  FormControl,
  InputLabel,
  Button,
  Checkbox,
  FormControlLabel,
  Alert,
  Snackbar,
  CircularProgress,
  Stack,
  TextField,
  RadioGroup,
  Radio,
  FormLabel,
  InputAdornment,
  Tabs,
  Tab,
} from '@mui/material';
import EmailIcon from '@mui/icons-material/Email';
import SaveIcon from '@mui/icons-material/Save';
import ServerIcon from '@mui/icons-material/Dns';
import PortIcon from '@mui/icons-material/Numbers';
import LockIcon from '@mui/icons-material/Lock';
import PersonIcon from '@mui/icons-material/Person';
import MailOutlineIcon from '@mui/icons-material/MailOutline';
import { useLanguage } from '../contexts/LanguageContext';
import { useConfig } from '../contexts/ConfigContext';
import { useDrawer } from '../contexts/DrawerContext';
import { drawerWidth, drawerCollapsedWidth } from '../components/Menu';
import api from '../utils/api';
import SocialMediaConfig from '../components/SocialMediaConfig';
import CloudConfig from '../components/CloudConfig';

function ServiceConnections() {
  const { t } = useLanguage();
  const { config, updateConfig } = useConfig();
  const { desktopOpen } = useDrawer();
  const currentDrawerWidth = desktopOpen ? drawerWidth : drawerCollapsedWidth;
  const [mailFormData, setMailFormData] = useState({});
  const [passwordError, setPasswordError] = useState('');
  const [rsyncFormData, setRsyncFormData] = useState({});
  const [rsyncPasswordError, setRsyncPasswordError] = useState('');
  const [rsyncSaving, setRsyncSaving] = useState(false);
  const rsyncSaveTimeoutRef = useRef(null);
  const rsyncLastSavedConfig = useRef(null);
  const rsyncIsSaving = useRef(false);
  const [message, setMessage] = useState('');
  const [currentTab, setCurrentTab] = useState(0);
  const mailLastSavedConfig = useRef(null);
  const [mailIsSaved, setMailIsSaved] = useState(true);
  const [rsyncIsSaved, setRsyncIsSaved] = useState(true);
  const socialMediaConfigRef = useRef({ isSaved: true, handleSave: null });
  const cloudConfigRef = useRef({ isSaved: true, handleSave: null });

  useEffect(() => {
    // Load selected tab from localStorage
    const savedTab = localStorage.getItem('integrations-tab');
    if (savedTab !== null) {
      try {
        const tabIndex = parseInt(savedTab, 10);
        if (tabIndex >= 0 && tabIndex <= 3) {
          setCurrentTab(tabIndex);
        }
      } catch (e) {
        console.error('Failed to parse saved tab:', e);
      }
    }
    if (config) {
      const mailConfig = {
        conf_MAIL_IP: config.conf_MAIL_IP || '0',
        conf_MAIL_HTML: config.conf_MAIL_HTML || '0',
        conf_SMTP_SERVER: config.conf_SMTP_SERVER || '',
        conf_SMTP_PORT: config.conf_SMTP_PORT || '',
        conf_MAIL_SECURITY: config.conf_MAIL_SECURITY || 'STARTTLS',
        conf_MAIL_USER: config.conf_MAIL_USER || '',
        conf_MAIL_PASSWORD: config.conf_MAIL_PASSWORD && config.conf_MAIL_PASSWORD.trim() ? (() => {
          try {
            return atob(config.conf_MAIL_PASSWORD);
          } catch (e) {
            return '';
          }
        })() : '',
        conf_MAIL_FROM: config.conf_MAIL_FROM || '',
        conf_MAIL_TO: config.conf_MAIL_TO || '',
        conf_MAIL_TIMEOUT_SEC: config.conf_MAIL_TIMEOUT_SEC || '30',
      };
      setMailFormData(mailConfig);
      mailLastSavedConfig.current = JSON.stringify(mailConfig);
      setMailIsSaved(true);

      const rsyncConfig = {
        conf_RSYNC_SERVER: config.conf_RSYNC_SERVER || '',
        conf_RSYNC_PORT: config.conf_RSYNC_PORT || '',
        conf_RSYNC_USER: config.conf_RSYNC_USER || '',
        conf_RSYNC_PASSWORD: config.conf_RSYNC_PASSWORD && config.conf_RSYNC_PASSWORD.trim() ? (() => {
          try {
            return atob(config.conf_RSYNC_PASSWORD);
          } catch (e) {
            return '';
          }
        })() : '',
        conf_RSYNC_SERVER_MODULE: config.conf_RSYNC_SERVER_MODULE || '',
      };
      setRsyncFormData(rsyncConfig);
      rsyncLastSavedConfig.current = JSON.stringify(rsyncConfig);
      setRsyncIsSaved(true);
    }
  }, [config]);

  useEffect(() => {
    if (rsyncIsSaving.current) {
      return;
    }

    if (Object.keys(rsyncFormData).length === 0) {
      return;
    }

    const formDataString = JSON.stringify(rsyncFormData);
    const isSaved = rsyncLastSavedConfig.current === formDataString;
    setRsyncIsSaved(isSaved);

    if (isSaved) {
      return;
    }

    if (rsyncSaveTimeoutRef.current) {
      clearTimeout(rsyncSaveTimeoutRef.current);
    }

    rsyncSaveTimeoutRef.current = setTimeout(async () => {
      rsyncIsSaving.current = true;
      setRsyncSaving(true);
      try {
        const rsyncConfigToSave = {
          conf_RSYNC_SERVER: rsyncFormData.conf_RSYNC_SERVER || '',
          conf_RSYNC_PORT: rsyncFormData.conf_RSYNC_PORT || '',
          conf_RSYNC_USER: rsyncFormData.conf_RSYNC_USER || '',
          conf_RSYNC_PASSWORD: rsyncFormData.conf_RSYNC_PASSWORD ? btoa(rsyncFormData.conf_RSYNC_PASSWORD) : '',
          conf_RSYNC_SERVER_MODULE: rsyncFormData.conf_RSYNC_SERVER_MODULE || '',
        };
        await updateConfig(rsyncConfigToSave);
        rsyncLastSavedConfig.current = JSON.stringify(rsyncFormData);
        setRsyncIsSaved(true);
        setMessage(t('config.message_settings_saved') || 'Settings saved');
      } catch (error) {
        console.error('Failed to save rsync settings:', error);
        setMessage('Error saving rsync settings');
      } finally {
        rsyncIsSaving.current = false;
        setRsyncSaving(false);
      }
    }, 500);

    return () => {
      if (rsyncSaveTimeoutRef.current) {
        clearTimeout(rsyncSaveTimeoutRef.current);
      }
    };
  }, [rsyncFormData, updateConfig, t]);

  // Track mail saved state
  useEffect(() => {
    if (Object.keys(mailFormData).length === 0) {
      return;
    }
    const formDataString = JSON.stringify(mailFormData);
    const isSaved = mailLastSavedConfig.current === formDataString;
    setMailIsSaved(isSaved);
  }, [mailFormData]);

  const validatePassword = (password) => {
    if (!password) {
      setPasswordError('');
      return true;
    }
    if (password.length < 5) {
      setPasswordError(t('config.alert_password_too_short') || 'Password must be at least 5 characters long.');
      return false;
    }
    if (/[\\'" ]/.test(password)) {
      setPasswordError('Password cannot contain backslash, single quote, double quote, or space.');
      return false;
    }
    setPasswordError('');
    return true;
  };

  const validateRsyncPassword = (password) => {
    if (!password) {
      setRsyncPasswordError('');
      return true;
    }
    if (password.length < 5) {
      setRsyncPasswordError(t('config.alert_password_too_short') || 'Password must be at least 5 characters long.');
      return false;
    }
    if (/[\\'" ]/.test(password)) {
      setRsyncPasswordError('Password cannot contain backslash, single quote, double quote, or space.');
      return false;
    }
    setRsyncPasswordError('');
    return true;
  };

  const handleTabChange = (event, newValue) => {
    setCurrentTab(newValue);
    localStorage.setItem('integrations-tab', newValue.toString());
  };

  const areAllMailFieldsFilled = () => {
    return !!(
      mailFormData.conf_SMTP_SERVER?.trim() &&
      mailFormData.conf_SMTP_PORT?.trim() &&
      mailFormData.conf_MAIL_SECURITY &&
      mailFormData.conf_MAIL_USER?.trim() &&
      mailFormData.conf_MAIL_PASSWORD?.trim() &&
      mailFormData.conf_MAIL_FROM?.trim() &&
      mailFormData.conf_MAIL_TO?.trim()
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

  const handleSaveMail = async () => {
    if (mailFormData.conf_MAIL_PASSWORD && !validatePassword(mailFormData.conf_MAIL_PASSWORD)) {
      return;
    }

    try {
      const mailConfigToSave = {
        conf_MAIL_IP: mailFormData.conf_MAIL_IP || '0',
        conf_MAIL_HTML: mailFormData.conf_MAIL_HTML || '0',
        conf_SMTP_SERVER: mailFormData.conf_SMTP_SERVER || '',
        conf_SMTP_PORT: mailFormData.conf_SMTP_PORT || '',
        conf_MAIL_SECURITY: mailFormData.conf_MAIL_SECURITY || 'STARTTLS',
        conf_MAIL_USER: mailFormData.conf_MAIL_USER || '',
        conf_MAIL_PASSWORD: mailFormData.conf_MAIL_PASSWORD ? btoa(mailFormData.conf_MAIL_PASSWORD) : '',
        conf_MAIL_FROM: mailFormData.conf_MAIL_FROM || '',
        conf_MAIL_TO: mailFormData.conf_MAIL_TO || '',
        conf_MAIL_TIMEOUT_SEC: mailFormData.conf_MAIL_TIMEOUT_SEC || '30',
      };
      
      await updateConfig(mailConfigToSave);
      mailLastSavedConfig.current = JSON.stringify(mailFormData);
      setMailIsSaved(true);
      setMessage(t('config.message_settings_saved') || 'Settings saved');
    } catch (error) {
      console.error('Failed to save mail settings:', error);
      setMessage('Error saving mail settings');
    }
  };

  const handleTestMail = async () => {
    try {
      await api.post('/setup/test-mail');
      setMessage(t('config.mail.testmail_sent') || 'Test mail sent');
    } catch (error) {
      console.error('Failed to send test mail:', error);
      const errorMessage = error.response?.data?.error || error.message || t('config.mail.testmail_error') || 'Failed to send test mail';
      setMessage(errorMessage);
    }
  };

  const handleSaveRsync = async () => {
    if (rsyncFormData.conf_RSYNC_PASSWORD && !validateRsyncPassword(rsyncFormData.conf_RSYNC_PASSWORD)) {
      return;
    }

    // Clear any pending auto-save timeout
    if (rsyncSaveTimeoutRef.current) {
      clearTimeout(rsyncSaveTimeoutRef.current);
      rsyncSaveTimeoutRef.current = null;
    }

    rsyncIsSaving.current = true;
    setRsyncSaving(true);
    try {
      const rsyncConfigToSave = {
        conf_RSYNC_SERVER: rsyncFormData.conf_RSYNC_SERVER || '',
        conf_RSYNC_PORT: rsyncFormData.conf_RSYNC_PORT || '',
        conf_RSYNC_USER: rsyncFormData.conf_RSYNC_USER || '',
        conf_RSYNC_PASSWORD: rsyncFormData.conf_RSYNC_PASSWORD ? btoa(rsyncFormData.conf_RSYNC_PASSWORD) : '',
        conf_RSYNC_SERVER_MODULE: rsyncFormData.conf_RSYNC_SERVER_MODULE || '',
      };
      await updateConfig(rsyncConfigToSave);
      rsyncLastSavedConfig.current = JSON.stringify(rsyncFormData);
      setRsyncIsSaved(true);
      setMessage(t('config.message_settings_saved') || 'Settings saved');
    } catch (error) {
      console.error('Failed to save rsync settings:', error);
      setMessage('Error saving rsync settings');
    } finally {
      rsyncIsSaving.current = false;
      setRsyncSaving(false);
    }
  };


  if (!config) {
    return (
      <Box display="flex" justifyContent="center" alignItems="center" minHeight="400px">
        <CircularProgress />
      </Box>
    );
  }

  function TabPanel({ children, value, index, ...other }) {
    // Add bottom padding when save button is sticky (tabs 0, 1, 2, 3)
    const needsBottomPadding = value === index && (index === 0 || index === 1 || index === 2 || index === 3);
    return (
      <div
        role="tabpanel"
        hidden={value !== index}
        id={`integrations-tabpanel-${index}`}
        aria-labelledby={`integrations-tab-${index}`}
        {...other}
      >
        {value === index && <Box sx={{ pt: 3, pb: needsBottomPadding ? 10 : 0 }}>{children}</Box>}
      </div>
    );
  }

  return (
    <Box>
      <Box sx={{ borderBottom: 1, borderColor: 'divider', mb: 2 }}>
        <Tabs value={currentTab} onChange={handleTabChange} aria-label="service connections tabs">
          <Tab 
            label={t('config.mail.section') || 'Email'} 
            id="integrations-tab-0"
            aria-controls="integrations-tabpanel-0"
          />
          <Tab 
            label={t('integrations.social_media.title') || 'Social Media Integration'} 
            id="integrations-tab-1"
            aria-controls="integrations-tabpanel-1"
          />
          <Tab 
            label={t('integrations.cloud_services.title') || 'Cloud Services Configuration'} 
            id="integrations-tab-2"
            aria-controls="integrations-tabpanel-2"
          />
          <Tab 
            label={t('network.rsync_config.title') || 'rsync Server Configuration'} 
            id="integrations-tab-3"
            aria-controls="integrations-tabpanel-3"
          />
        </Tabs>
      </Box>

      <TabPanel value={currentTab} index={0}>
              {hasMissingEmailServerConfig() && (
                <Alert severity="warning" sx={{ mb: 2 }}>
                  {t('config.mail.server_settings_notice') || 'Server settings must be configured before email notifications can be sent.'}
                </Alert>
              )}
              <Stack spacing={1.5} sx={{ mt: 3, mb: 4 }}>
                    <FormControlLabel
                      control={
                        <Checkbox
                          checked={mailFormData.conf_MAIL_IP === '1'}
                          onChange={(e) => setMailFormData({ ...mailFormData, conf_MAIL_IP: e.target.checked ? '1' : '0' })}
                        />
                      }
                      label={t('config.mail.notify_ip_label') || 'If possible, send the current links of this Little Backup Box via email?'}
                    />
                    <FormControlLabel
                      control={
                        <Checkbox
                          checked={mailFormData.conf_MAIL_HTML === '1'}
                          onChange={(e) => setMailFormData({ ...mailFormData, conf_MAIL_HTML: e.target.checked ? '1' : '0' })}
                        />
                      }
                      label={t('config.mail.html_label') || 'Format emails in HTML?'}
                    />
                  </Stack>
                  
                  <Typography variant="h6" gutterBottom sx={{ mt: 3, mb: 2 }}>
                    {t('config.mail.smtp_header') || 'SMTP Configuration'}
                  </Typography>
                  <Stack spacing={3} sx={{ mb: 4 }}>
                    <TextField
                      required
                      variant="outlined"
                      label={t('config.mail.smtp_label') || 'Address of the SMTP-mailserver'}
                      value={mailFormData.conf_SMTP_SERVER || ''}
                      onChange={(e) => setMailFormData({ ...mailFormData, conf_SMTP_SERVER: e.target.value })}
                      sx={{ maxWidth: 500 }}
                      InputProps={{
                        startAdornment: (
                          <InputAdornment position="start">
                            <ServerIcon sx={{ color: 'text.secondary' }} />
                          </InputAdornment>
                        ),
                      }}
                    />

                    <TextField
                      required
                      variant="outlined"
                      type="number"
                      label={t('config.mail.port_label') || 'Port of the SMTP-Mailserver'}
                      value={mailFormData.conf_SMTP_PORT || ''}
                      onChange={(e) => setMailFormData({ ...mailFormData, conf_SMTP_PORT: e.target.value })}
                      sx={{ maxWidth: 200 }}
                      InputProps={{
                        startAdornment: (
                          <InputAdornment position="start">
                            <PortIcon sx={{ color: 'text.secondary' }} />
                          </InputAdornment>
                        ),
                      }}
                    />

                    <FormControl component="fieldset">
                      <FormLabel component="legend">{t('config.mail.security_header') || 'Connection security'}</FormLabel>
                      <RadioGroup
                        row
                        value={mailFormData.conf_MAIL_SECURITY || 'STARTTLS'}
                        onChange={(e) => setMailFormData({ ...mailFormData, conf_MAIL_SECURITY: e.target.value })}
                      >
                        <FormControlLabel value="STARTTLS" control={<Radio />} label="STARTTLS" />
                        <FormControlLabel value="SSL" control={<Radio />} label="SSL" />
                      </RadioGroup>
                    </FormControl>

                    <TextField
                      required
                      variant="outlined"
                      label={t('config.mail.user_label') || 'Username for the mailserver'}
                      value={mailFormData.conf_MAIL_USER || ''}
                      onChange={(e) => setMailFormData({ ...mailFormData, conf_MAIL_USER: e.target.value })}
                      sx={{ maxWidth: 400 }}
                      InputProps={{
                        startAdornment: (
                          <InputAdornment position="start">
                            <PersonIcon sx={{ color: 'text.secondary' }} />
                          </InputAdornment>
                        ),
                      }}
                    />

                    <TextField
                      required
                      variant="outlined"
                      type="password"
                      label={t('config.mail.password_label') || 'Password for the mailserver'}
                      value={mailFormData.conf_MAIL_PASSWORD || ''}
                      onChange={(e) => {
                        setMailFormData({ ...mailFormData, conf_MAIL_PASSWORD: e.target.value });
                        validatePassword(e.target.value);
                      }}
                      error={!!passwordError}
                      helperText={passwordError || ''}
                      sx={{ maxWidth: 400 }}
                      InputProps={{
                        startAdornment: (
                          <InputAdornment position="start">
                            <LockIcon sx={{ color: 'text.secondary' }} />
                          </InputAdornment>
                        ),
                      }}
                    />

                    <TextField
                      required
                      variant="outlined"
                      type="email"
                      label={t('config.mail.sender_label') || 'Mailaddress of the sender'}
                      value={mailFormData.conf_MAIL_FROM || ''}
                      onChange={(e) => setMailFormData({ ...mailFormData, conf_MAIL_FROM: e.target.value })}
                      sx={{ maxWidth: 400 }}
                      InputProps={{
                        startAdornment: (
                          <InputAdornment position="start">
                            <MailOutlineIcon sx={{ color: 'text.secondary' }} />
                          </InputAdornment>
                        ),
                      }}
                    />

                    <TextField
                      required
                      variant="outlined"
                      type="email"
                      label={t('config.mail.recipient_label') || 'Mailaddress of the recipient'}
                      value={mailFormData.conf_MAIL_TO || ''}
                      onChange={(e) => setMailFormData({ ...mailFormData, conf_MAIL_TO: e.target.value })}
                      sx={{ maxWidth: 400 }}
                      InputProps={{
                        startAdornment: (
                          <InputAdornment position="start">
                            <MailOutlineIcon sx={{ color: 'text.secondary' }} />
                          </InputAdornment>
                        ),
                      }}
                    />

                    <FormControl sx={{ maxWidth: 400 }}>
                      <InputLabel>{t('config.mail.timeout_header') || 'Mail timeout'}</InputLabel>
                      <Select
                        value={mailFormData.conf_MAIL_TIMEOUT_SEC || '30'}
                        onChange={(e) => setMailFormData({ ...mailFormData, conf_MAIL_TIMEOUT_SEC: e.target.value })}
                        label={t('config.mail.timeout_header') || 'Mail timeout'}
                      >
                        <MenuItem value="5">5 {t('seconds_short') || 's'}</MenuItem>
                        <MenuItem value="10">10 {t('seconds_short') || 's'}</MenuItem>
                        <MenuItem value="20">20 {t('seconds_short') || 's'}</MenuItem>
                        <MenuItem value="30">30 {t('seconds_short') || 's'}</MenuItem>
                        <MenuItem value="40">40 {t('seconds_short') || 's'}</MenuItem>
                        <MenuItem value="50">50 {t('seconds_short') || 's'}</MenuItem>
                        <MenuItem value="60">60 {t('seconds_short') || 's'}</MenuItem>
                        <MenuItem value="90">90 {t('seconds_short') || 's'}</MenuItem>
                        <MenuItem value="120">120 {t('seconds_short') || 's'}</MenuItem>
                        <MenuItem value="300">300 {t('seconds_short') || 's'}</MenuItem>
                        <MenuItem value="600">600 {t('seconds_short') || 's'}</MenuItem>
                      </Select>
                    </FormControl>
                  </Stack>

                  <Stack 
                    direction="row" 
                    spacing={2} 
                    sx={{ 
                      mt: 4,
                      ...(currentTab === 0 && {
                        position: 'fixed',
                        bottom: 0,
                        left: { md: `${currentDrawerWidth}px` },
                        right: 0,
                        zIndex: 1000,
                        p: 2,
                        backgroundColor: 'background.paper',
                        borderTop: 1,
                        borderColor: 'divider',
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
                      onClick={handleSaveMail}
                      disabled={mailIsSaved}
                      size={currentTab === 0 ? 'large' : 'medium'}
                    >
                      {t('config.save_button') || 'Save'}
                    </Button>
                    <Button
                      variant="outlined"
                      startIcon={<EmailIcon />}
                      onClick={handleTestMail}
                      disabled={!areAllMailFieldsFilled()}
                      size={currentTab === 0 ? 'large' : 'medium'}
                    >
                      {t('config.mail.testmail_header') || 'Send Test Mail'}
                    </Button>
                  </Stack>
      </TabPanel>

      <TabPanel value={currentTab} index={1}>
              <SocialMediaConfig 
                onSavedStateChange={(isSaved, handleSave) => {
                  socialMediaConfigRef.current = { isSaved, handleSave };
                }}
                isSticky={currentTab === 1}
                drawerWidth={currentDrawerWidth}
              />
      </TabPanel>

      <TabPanel value={currentTab} index={2}>
              <CloudConfig 
                onSavedStateChange={(isSaved, handleSave) => {
                  cloudConfigRef.current = { isSaved, handleSave };
                }}
                isSticky={currentTab === 2}
                drawerWidth={currentDrawerWidth}
              />
      </TabPanel>

      <TabPanel value={currentTab} index={3}>
              <Stack spacing={3}>
                    <TextField
                      variant="outlined"
                      label={t('config.rsync_server_label') || 'Address of the rsync-server'}
                      value={rsyncFormData.conf_RSYNC_SERVER || ''}
                      onChange={(e) => setRsyncFormData({ ...rsyncFormData, conf_RSYNC_SERVER: e.target.value })}
                      sx={{ maxWidth: 500 }}
                      InputProps={{
                        startAdornment: (
                          <InputAdornment position="start">
                            <ServerIcon sx={{ color: 'text.secondary' }} />
                          </InputAdornment>
                        ),
                      }}
                    />

                    <TextField
                      variant="outlined"
                      type="number"
                      label={t('config.rsync_port_label') || 'Port of the rsync-server'}
                      value={rsyncFormData.conf_RSYNC_PORT || ''}
                      onChange={(e) => setRsyncFormData({ ...rsyncFormData, conf_RSYNC_PORT: e.target.value })}
                      sx={{ maxWidth: 200 }}
                      InputProps={{
                        startAdornment: (
                          <InputAdornment position="start">
                            <PortIcon sx={{ color: 'text.secondary' }} />
                          </InputAdornment>
                        ),
                      }}
                    />

                    <TextField
                      variant="outlined"
                      label={t('config.rsync_user_label') || 'Username for the rsync-server'}
                      value={rsyncFormData.conf_RSYNC_USER || ''}
                      onChange={(e) => setRsyncFormData({ ...rsyncFormData, conf_RSYNC_USER: e.target.value })}
                      sx={{ maxWidth: 400 }}
                      InputProps={{
                        startAdornment: (
                          <InputAdornment position="start">
                            <PersonIcon sx={{ color: 'text.secondary' }} />
                          </InputAdornment>
                        ),
                      }}
                    />

                    <TextField
                      variant="outlined"
                      type="password"
                      label={t('config.rsync_password_label') || 'Password for the rsync-server'}
                      value={rsyncFormData.conf_RSYNC_PASSWORD || ''}
                      onChange={(e) => {
                        setRsyncFormData({ ...rsyncFormData, conf_RSYNC_PASSWORD: e.target.value });
                        validateRsyncPassword(e.target.value);
                      }}
                      error={!!rsyncPasswordError}
                      helperText={rsyncPasswordError || ''}
                      sx={{ maxWidth: 400 }}
                      InputProps={{
                        startAdornment: (
                          <InputAdornment position="start">
                            <LockIcon sx={{ color: 'text.secondary' }} />
                          </InputAdornment>
                        ),
                      }}
                    />

                    <TextField
                      variant="outlined"
                      label={t('config.rsync_module_label1') || 'Module name on the rsync server'}
                      value={rsyncFormData.conf_RSYNC_SERVER_MODULE || ''}
                      onChange={(e) => setRsyncFormData({ ...rsyncFormData, conf_RSYNC_SERVER_MODULE: e.target.value })}
                      sx={{ maxWidth: 400 }}
                    />

                    <Stack 
                      direction="row" 
                      spacing={2} 
                      sx={{ 
                        mt: 2,
                        ...(currentTab === 3 && {
                          position: 'fixed',
                          bottom: 0,
                          left: { md: `${currentDrawerWidth}px` },
                          right: 0,
                          zIndex: 1000,
                          p: 2,
                          backgroundColor: 'background.paper',
                          borderTop: 1,
                          borderColor: 'divider',
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
                        startIcon={rsyncSaving ? <CircularProgress size={16} /> : <SaveIcon />}
                        onClick={handleSaveRsync}
                        disabled={rsyncIsSaved || rsyncSaving}
                        size={currentTab === 3 ? 'large' : 'medium'}
                      >
                        {t('config.save_button') || 'Save'}
                      </Button>
                    </Stack>
              </Stack>
      </TabPanel>

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

export default ServiceConnections;

