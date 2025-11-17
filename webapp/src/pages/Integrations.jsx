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
  Button,
  Checkbox,
  FormControlLabel,
  Alert,
  Snackbar,
  CircularProgress,
  Grid,
  Stack,
  Divider,
  TextField,
  RadioGroup,
  Radio,
  FormLabel,
  InputAdornment,
  Accordion,
  AccordionSummary,
  AccordionDetails,
} from '@mui/material';
import ExpandMoreIcon from '@mui/icons-material/ExpandMore';
import EmailIcon from '@mui/icons-material/Email';
import SaveIcon from '@mui/icons-material/Save';
import ServerIcon from '@mui/icons-material/Dns';
import PortIcon from '@mui/icons-material/Numbers';
import LockIcon from '@mui/icons-material/Lock';
import PersonIcon from '@mui/icons-material/Person';
import MailOutlineIcon from '@mui/icons-material/MailOutline';
import { useLanguage } from '../contexts/LanguageContext';
import { useConfig } from '../contexts/ConfigContext';
import api from '../utils/api';
import SocialMediaConfig from '../components/SocialMediaConfig';
import CloudConfig from '../components/CloudConfig';
import VPNConfig from '../components/VPNConfig';

function Integrations() {
  const { t } = useLanguage();
  const { config, updateConfig } = useConfig();
  const [mailFormData, setMailFormData] = useState({});
  const [passwordError, setPasswordError] = useState('');
  const [rsyncFormData, setRsyncFormData] = useState({});
  const [rsyncPasswordError, setRsyncPasswordError] = useState('');
  const [rsyncSaving, setRsyncSaving] = useState(false);
  const rsyncSaveTimeoutRef = useRef(null);
  const rsyncLastSavedConfig = useRef(null);
  const rsyncIsSaving = useRef(false);
  const [message, setMessage] = useState('');

  useEffect(() => {
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
    if (rsyncLastSavedConfig.current === formDataString) {
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
          <Accordion defaultExpanded>
            <AccordionSummary expandIcon={<ExpandMoreIcon />}>
              <Typography variant="h5">
                {t('config.mail.section') || 'Email'}
              </Typography>
            </AccordionSummary>
            <AccordionDetails>
              <Card>
                <CardContent>
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
                      fullWidth
                      required
                      variant="outlined"
                      label={t('config.mail.smtp_label') || 'Address of the SMTP-mailserver'}
                      value={mailFormData.conf_SMTP_SERVER || ''}
                      onChange={(e) => setMailFormData({ ...mailFormData, conf_SMTP_SERVER: e.target.value })}
                      InputProps={{
                        startAdornment: (
                          <InputAdornment position="start">
                            <ServerIcon sx={{ color: 'text.secondary' }} />
                          </InputAdornment>
                        ),
                      }}
                    />

                    <TextField
                      fullWidth
                      required
                      variant="outlined"
                      type="number"
                      label={t('config.mail.port_label') || 'Port of the SMTP-Mailserver'}
                      value={mailFormData.conf_SMTP_PORT || ''}
                      onChange={(e) => setMailFormData({ ...mailFormData, conf_SMTP_PORT: e.target.value })}
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
                      fullWidth
                      required
                      variant="outlined"
                      label={t('config.mail.user_label') || 'Username for the mailserver'}
                      value={mailFormData.conf_MAIL_USER || ''}
                      onChange={(e) => setMailFormData({ ...mailFormData, conf_MAIL_USER: e.target.value })}
                      InputProps={{
                        startAdornment: (
                          <InputAdornment position="start">
                            <PersonIcon sx={{ color: 'text.secondary' }} />
                          </InputAdornment>
                        ),
                      }}
                    />

                    <TextField
                      fullWidth
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
                      InputProps={{
                        startAdornment: (
                          <InputAdornment position="start">
                            <LockIcon sx={{ color: 'text.secondary' }} />
                          </InputAdornment>
                        ),
                      }}
                    />

                    <TextField
                      fullWidth
                      required
                      variant="outlined"
                      type="email"
                      label={t('config.mail.sender_label') || 'Mailaddress of the sender'}
                      value={mailFormData.conf_MAIL_FROM || ''}
                      onChange={(e) => setMailFormData({ ...mailFormData, conf_MAIL_FROM: e.target.value })}
                      InputProps={{
                        startAdornment: (
                          <InputAdornment position="start">
                            <MailOutlineIcon sx={{ color: 'text.secondary' }} />
                          </InputAdornment>
                        ),
                      }}
                    />

                    <TextField
                      fullWidth
                      required
                      variant="outlined"
                      type="email"
                      label={t('config.mail.recipient_label') || 'Mailaddress of the recipient'}
                      value={mailFormData.conf_MAIL_TO || ''}
                      onChange={(e) => setMailFormData({ ...mailFormData, conf_MAIL_TO: e.target.value })}
                      InputProps={{
                        startAdornment: (
                          <InputAdornment position="start">
                            <MailOutlineIcon sx={{ color: 'text.secondary' }} />
                          </InputAdornment>
                        ),
                      }}
                    />

                    <FormControl fullWidth>
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

                  <Divider sx={{ my: 2 }} />

                  <Stack direction="row" spacing={2} sx={{ mt: 3 }}>
                    <Button
                      variant="contained"
                      startIcon={<SaveIcon />}
                      onClick={handleSaveMail}
                    >
                      {t('config.save_button') || 'Save'}
                    </Button>
                    <Button
                      variant="outlined"
                      startIcon={<EmailIcon />}
                      onClick={handleTestMail}
                      disabled={!areAllMailFieldsFilled()}
                    >
                      {t('config.mail.testmail_header') || 'Send Test Mail'}
                    </Button>
                  </Stack>
                </CardContent>
              </Card>
            </AccordionDetails>
          </Accordion>
        </Grid>

        <Grid item xs={12}>
          <Accordion>
            <AccordionSummary expandIcon={<ExpandMoreIcon />}>
              <Typography variant="h5">
                {t('integrations.social_media.title') || 'Social Media Integration'}
              </Typography>
            </AccordionSummary>
            <AccordionDetails>
              <SocialMediaConfig />
            </AccordionDetails>
          </Accordion>
        </Grid>

        <Grid item xs={12}>
          <Accordion>
            <AccordionSummary expandIcon={<ExpandMoreIcon />}>
              <Typography variant="h5">
                {t('integrations.cloud_services.title') || 'Cloud Services Configuration'}
              </Typography>
            </AccordionSummary>
            <AccordionDetails>
              <CloudConfig />
            </AccordionDetails>
          </Accordion>
        </Grid>

        <Grid item xs={12}>
          <Accordion>
            <AccordionSummary expandIcon={<ExpandMoreIcon />}>
              <Typography variant="h5">
                {t('integrations.vpn.title') || 'VPN Configuration'}
              </Typography>
            </AccordionSummary>
            <AccordionDetails>
              <VPNConfig />
            </AccordionDetails>
          </Accordion>
        </Grid>

        <Grid item xs={12}>
          <Accordion>
            <AccordionSummary expandIcon={<ExpandMoreIcon />}>
              <Typography variant="h5">
                {t('network.rsync_config.title') || 'rsync Server Configuration'}
              </Typography>
            </AccordionSummary>
            <AccordionDetails>
              <Card>
                <CardContent>
                  <Stack spacing={3}>
                    <TextField
                      fullWidth
                      variant="outlined"
                      label={t('config.rsync_server_label') || 'Address of the rsync-server'}
                      value={rsyncFormData.conf_RSYNC_SERVER || ''}
                      onChange={(e) => setRsyncFormData({ ...rsyncFormData, conf_RSYNC_SERVER: e.target.value })}
                      InputProps={{
                        startAdornment: (
                          <InputAdornment position="start">
                            <ServerIcon sx={{ color: 'text.secondary' }} />
                          </InputAdornment>
                        ),
                      }}
                    />

                    <TextField
                      fullWidth
                      variant="outlined"
                      type="number"
                      label={t('config.rsync_port_label') || 'Port of the rsync-server'}
                      value={rsyncFormData.conf_RSYNC_PORT || ''}
                      onChange={(e) => setRsyncFormData({ ...rsyncFormData, conf_RSYNC_PORT: e.target.value })}
                      InputProps={{
                        startAdornment: (
                          <InputAdornment position="start">
                            <PortIcon sx={{ color: 'text.secondary' }} />
                          </InputAdornment>
                        ),
                      }}
                    />

                    <TextField
                      fullWidth
                      variant="outlined"
                      label={t('config.rsync_user_label') || 'Username for the rsync-server'}
                      value={rsyncFormData.conf_RSYNC_USER || ''}
                      onChange={(e) => setRsyncFormData({ ...rsyncFormData, conf_RSYNC_USER: e.target.value })}
                      InputProps={{
                        startAdornment: (
                          <InputAdornment position="start">
                            <PersonIcon sx={{ color: 'text.secondary' }} />
                          </InputAdornment>
                        ),
                      }}
                    />

                    <TextField
                      fullWidth
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
                      InputProps={{
                        startAdornment: (
                          <InputAdornment position="start">
                            <LockIcon sx={{ color: 'text.secondary' }} />
                          </InputAdornment>
                        ),
                      }}
                    />

                    <TextField
                      fullWidth
                      variant="outlined"
                      label={t('config.rsync_module_label1') || 'Module name on the rsync server'}
                      value={rsyncFormData.conf_RSYNC_SERVER_MODULE || ''}
                      onChange={(e) => setRsyncFormData({ ...rsyncFormData, conf_RSYNC_SERVER_MODULE: e.target.value })}
                    />

                    {rsyncSaving && (
                      <Box sx={{ display: 'flex', alignItems: 'center', gap: 1 }}>
                        <CircularProgress size={16} />
                        <Typography variant="body2" color="text.secondary">
                          {t('config.saving') || 'Saving...'}
                        </Typography>
                      </Box>
                    )}
                  </Stack>
                </CardContent>
              </Card>
            </AccordionDetails>
          </Accordion>
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

export default Integrations;

