import React, { useState, useEffect } from 'react';
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
  Alert,
  Snackbar,
  CircularProgress,
  Grid,
  Stack,
  Divider,
  Accordion,
  AccordionSummary,
  AccordionDetails,
  Link,
} from '@mui/material';
import ExpandMoreIcon from '@mui/icons-material/ExpandMore';
import RefreshIcon from '@mui/icons-material/Refresh';
import CheckCircleIcon from '@mui/icons-material/CheckCircle';
import CancelIcon from '@mui/icons-material/Cancel';
import { useLanguage } from '../contexts/LanguageContext';
import { useConfig } from '../contexts/ConfigContext';
import api from '../utils/api';

function Network() {
  const { t } = useLanguage();
  const { updateConfig } = useConfig();
  const [wifiCountries, setWifiCountries] = useState([]);
  const [currentWifiCountry, setCurrentWifiCountry] = useState('');
  const [networkInfo, setNetworkInfo] = useState({
    ips: [],
    internetStatus: false,
    qrLinks: [],
    wifi: [],
  });
  const [message, setMessage] = useState('');
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    loadInitialData();
  }, []);

  const loadInitialData = async () => {
    setLoading(true);
    try {
      await Promise.all([
        loadWifiCountries(),
        loadCurrentWifiCountry(),
        loadNetworkInfo(),
        loadWifiInfo(),
      ]);
    } catch (error) {
      console.error('Failed to load initial data:', error);
    } finally {
      setLoading(false);
    }
  };

  const loadWifiCountries = async () => {
    try {
      const response = await api.get('/setup/wifi-countries');
      setWifiCountries(response.data.countries || []);
    } catch (error) {
      console.error('Failed to load WiFi countries:', error);
      setWifiCountries([]);
    }
  };

  const loadCurrentWifiCountry = async () => {
    try {
      const response = await api.get('/setup/wifi-country');
      setCurrentWifiCountry(response.data.country || '');
    } catch (error) {
      console.error('Failed to load current WiFi country:', error);
      setCurrentWifiCountry('');
    }
  };

  const loadNetworkInfo = async () => {
    try {
      const [ipsResponse, internetResponse, qrResponse] = await Promise.all([
        api.get('/network/ips'),
        api.get('/network/internet-status'),
        api.get('/network/qr-links'),
      ]);
      setNetworkInfo(prev => ({
        ...prev,
        ips: ipsResponse.data.ips || [],
        internetStatus: internetResponse.data.online || false,
        qrLinks: qrResponse.data.qrLinks || [],
      }));
    } catch (error) {
      console.error('Failed to load network info:', error);
    }
  };

  const loadWifiInfo = async () => {
    try {
      const response = await api.get('/sysinfo/wifi');
      setNetworkInfo(prev => ({
        ...prev,
        wifi: response.data.wifi || [],
      }));
    } catch (error) {
      console.error('Failed to load WiFi info:', error);
    }
  };

  const handleWifiCountryChange = async (countryCode) => {
    try {
      await updateConfig({ conf_WIFI_COUNTRY: countryCode });
      setCurrentWifiCountry(countryCode);
      setMessage(t('config.message_settings_saved') || 'Settings saved');
    } catch (error) {
      console.error('Failed to save WiFi country:', error);
      setMessage('Error saving WiFi country');
    }
  };


  if (loading) {
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
                {t('network.wifi_config.title') || 'WiFi Configuration'}
              </Typography>
            </AccordionSummary>
            <AccordionDetails>
              <Card>
                <CardContent>
                  <Stack spacing={3}>
                    <FormControl fullWidth>
                      <InputLabel>{t('config.wifi_country_header') || 'WiFi country'}</InputLabel>
                      <Select
                        value={currentWifiCountry}
                        onChange={(e) => handleWifiCountryChange(e.target.value)}
                        label={t('config.wifi_country_header') || 'WiFi country'}
                      >
                        {wifiCountries.map((country) => (
                          <MenuItem key={country.code} value={country.code}>
                            {country.code} {country.name}
                          </MenuItem>
                        ))}
                      </Select>
                    </FormControl>
                    <Typography variant="body2" color="text.secondary">
                      {t('config.wifi_country_label') || 'Select the country in which this Little Backup Box is to be used'}
                    </Typography>

                    {networkInfo.wifi.length > 0 && (
                      <>
                        <Divider />
                        <Box>
                          <Typography variant="h6" gutterBottom>
                            {t('sysinfo.wifi.header') || 'WiFi Interfaces'}
                          </Typography>
                          {networkInfo.wifi.map((wifiItem, index) => (
                            <Box key={index} sx={{ mb: 2 }}>
                              <Typography variant="subtitle2" sx={{ fontWeight: 'bold', mb: 1 }}>
                                {wifiItem.interface}
                              </Typography>
                              <Box
                                component="pre"
                                sx={{
                                  fontFamily: 'monospace',
                                  fontSize: '0.75rem',
                                  whiteSpace: 'pre-wrap',
                                  wordBreak: 'break-word',
                                  m: 0,
                                  p: 1,
                                  bgcolor: 'background.default',
                                  borderRadius: 1,
                                }}
                              >
                                {wifiItem.info}
                              </Box>
                              {index < networkInfo.wifi.length - 1 && <Divider sx={{ mt: 2 }} />}
                            </Box>
                          ))}
                        </Box>
                      </>
                    )}
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
                {t('network.network_info.title') || 'Network Information'}
              </Typography>
            </AccordionSummary>
            <AccordionDetails>
              <Card>
                <CardContent>
                  <Stack spacing={3}>
                    <Box>
                      <Typography variant="h6" gutterBottom>
                        {t('network.internet_status') || 'Internet Status'}
                      </Typography>
                      <Box sx={{ display: 'flex', alignItems: 'center', gap: 1 }}>
                        {networkInfo.internetStatus ? (
                          <>
                            <CheckCircleIcon color="success" />
                            <Typography variant="body1" color="success.main">
                              {t('network.online') || 'Online'}
                            </Typography>
                          </>
                        ) : (
                          <>
                            <CancelIcon color="error" />
                            <Typography variant="body1" color="error.main">
                              {t('network.offline') || 'Offline'}
                            </Typography>
                          </>
                        )}
                      </Box>
                    </Box>

                    <Divider />

                    <Box>
                      <Box sx={{ display: 'flex', alignItems: 'center', justifyContent: 'space-between', mb: 2 }}>
                        <Typography variant="h6" gutterBottom>
                          {t('network.ip_addresses') || 'IP Addresses'}
                        </Typography>
                        <Button
                          variant="outlined"
                          size="small"
                          startIcon={<RefreshIcon />}
                          onClick={loadNetworkInfo}
                        >
                          {t('network.refresh') || 'Refresh'}
                        </Button>
                      </Box>
                      {networkInfo.ips.length > 0 ? (
                        <Stack spacing={2}>
                          {networkInfo.ips.map((ip, index) => (
                            <Box key={index}>
                              <Typography variant="body1" sx={{ mb: 1, fontFamily: 'monospace' }}>
                                {ip}
                              </Typography>
                              <Stack direction="row" spacing={1} sx={{ flexWrap: 'wrap', gap: 1 }}>
                                <Link href={`https://${ip}`} target="_blank" rel="noopener noreferrer">
                                  https://{ip}
                                </Link>
                                <Link href={`http://${ip}:8080`} target="_blank" rel="noopener noreferrer">
                                  http://{ip}:8080
                                </Link>
                                <Link href={`smb://${ip}`} target="_blank" rel="noopener noreferrer">
                                  smb://{ip}
                                </Link>
                                <Link href={`ftp://lbb@${ip}`} target="_blank" rel="noopener noreferrer">
                                  ftp://lbb@{ip}
                                </Link>
                              </Stack>
                            </Box>
                          ))}
                        </Stack>
                      ) : (
                        <Typography variant="body2" color="text.secondary">
                          {t('network.no_ips') || 'No IP addresses found'}
                        </Typography>
                      )}
                    </Box>

                    {networkInfo.qrLinks.length > 0 && (
                      <>
                        <Divider />
                        <Box>
                          <Typography variant="h6" gutterBottom>
                            {t('network.qr_codes') || 'QR Codes'}
                          </Typography>
                          <Box sx={{ display: 'flex', flexWrap: 'wrap', gap: 2 }}>
                            {networkInfo.qrLinks.map((qrLink, index) => (
                              <Box
                                key={index}
                                dangerouslySetInnerHTML={{ __html: qrLink }}
                              />
                            ))}
                          </Box>
                        </Box>
                      </>
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

export default Network;

