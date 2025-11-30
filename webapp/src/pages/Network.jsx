import React, { useState, useEffect } from 'react';
import {
  Box,
  Typography,
  Select,
  MenuItem,
  FormControl,
  InputLabel,
  Button,
  Alert,
  Snackbar,
  CircularProgress,
  Stack,
  Tabs,
  Tab,
  Link,
} from '@mui/material';
import RefreshIcon from '@mui/icons-material/Refresh';
import CheckCircleIcon from '@mui/icons-material/CheckCircle';
import CancelIcon from '@mui/icons-material/Cancel';
import { useLanguage } from '../contexts/LanguageContext';
import { useConfig } from '../contexts/ConfigContext';
import api from '../utils/api';
import VPNConfig from '../components/VPNConfig';

// Complete list of countries from ISO 3166 (matching original PHP implementation)
const COMPLETE_COUNTRIES = [
  { code: 'AD', name: 'Andorra' },
  { code: 'AE', name: 'United Arab Emirates' },
  { code: 'AF', name: 'Afghanistan' },
  { code: 'AG', name: 'Antigua & Barbuda' },
  { code: 'AI', name: 'Anguilla' },
  { code: 'AL', name: 'Albania' },
  { code: 'AM', name: 'Armenia' },
  { code: 'AO', name: 'Angola' },
  { code: 'AQ', name: 'Antarctica' },
  { code: 'AR', name: 'Argentina' },
  { code: 'AS', name: 'Samoa (American)' },
  { code: 'AT', name: 'Austria' },
  { code: 'AU', name: 'Australia' },
  { code: 'AW', name: 'Aruba' },
  { code: 'AX', name: 'Åland Islands' },
  { code: 'AZ', name: 'Azerbaijan' },
  { code: 'BA', name: 'Bosnia & Herzegovina' },
  { code: 'BB', name: 'Barbados' },
  { code: 'BD', name: 'Bangladesh' },
  { code: 'BE', name: 'Belgium' },
  { code: 'BF', name: 'Burkina Faso' },
  { code: 'BG', name: 'Bulgaria' },
  { code: 'BH', name: 'Bahrain' },
  { code: 'BI', name: 'Burundi' },
  { code: 'BJ', name: 'Benin' },
  { code: 'BL', name: 'St Barthelemy' },
  { code: 'BM', name: 'Bermuda' },
  { code: 'BN', name: 'Brunei' },
  { code: 'BO', name: 'Bolivia' },
  { code: 'BQ', name: 'Caribbean NL' },
  { code: 'BR', name: 'Brazil' },
  { code: 'BS', name: 'Bahamas' },
  { code: 'BT', name: 'Bhutan' },
  { code: 'BV', name: 'Bouvet Island' },
  { code: 'BW', name: 'Botswana' },
  { code: 'BY', name: 'Belarus' },
  { code: 'BZ', name: 'Belize' },
  { code: 'CA', name: 'Canada' },
  { code: 'CC', name: 'Cocos (Keeling) Islands' },
  { code: 'CD', name: 'Congo (Dem. Rep.)' },
  { code: 'CF', name: 'Central African Rep.' },
  { code: 'CG', name: 'Congo (Rep.)' },
  { code: 'CH', name: 'Switzerland' },
  { code: 'CI', name: 'Côte d\'Ivoire' },
  { code: 'CK', name: 'Cook Islands' },
  { code: 'CL', name: 'Chile' },
  { code: 'CM', name: 'Cameroon' },
  { code: 'CN', name: 'China' },
  { code: 'CO', name: 'Colombia' },
  { code: 'CR', name: 'Costa Rica' },
  { code: 'CU', name: 'Cuba' },
  { code: 'CV', name: 'Cape Verde' },
  { code: 'CW', name: 'Curaçao' },
  { code: 'CX', name: 'Christmas Island' },
  { code: 'CY', name: 'Cyprus' },
  { code: 'CZ', name: 'Czech Republic' },
  { code: 'DE', name: 'Germany' },
  { code: 'DJ', name: 'Djibouti' },
  { code: 'DK', name: 'Denmark' },
  { code: 'DM', name: 'Dominica' },
  { code: 'DO', name: 'Dominican Republic' },
  { code: 'DZ', name: 'Algeria' },
  { code: 'EC', name: 'Ecuador' },
  { code: 'EE', name: 'Estonia' },
  { code: 'EG', name: 'Egypt' },
  { code: 'EH', name: 'Western Sahara' },
  { code: 'ER', name: 'Eritrea' },
  { code: 'ES', name: 'Spain' },
  { code: 'ET', name: 'Ethiopia' },
  { code: 'FI', name: 'Finland' },
  { code: 'FJ', name: 'Fiji' },
  { code: 'FK', name: 'Falkland Islands' },
  { code: 'FM', name: 'Micronesia' },
  { code: 'FO', name: 'Faroe Islands' },
  { code: 'FR', name: 'France' },
  { code: 'GA', name: 'Gabon' },
  { code: 'GB', name: 'Britain (UK)' },
  { code: 'GD', name: 'Grenada' },
  { code: 'GE', name: 'Georgia' },
  { code: 'GF', name: 'French Guiana' },
  { code: 'GG', name: 'Guernsey' },
  { code: 'GH', name: 'Ghana' },
  { code: 'GI', name: 'Gibraltar' },
  { code: 'GL', name: 'Greenland' },
  { code: 'GM', name: 'Gambia' },
  { code: 'GN', name: 'Guinea' },
  { code: 'GP', name: 'Guadeloupe' },
  { code: 'GQ', name: 'Equatorial Guinea' },
  { code: 'GR', name: 'Greece' },
  { code: 'GS', name: 'South Georgia & the South Sandwich Islands' },
  { code: 'GT', name: 'Guatemala' },
  { code: 'GU', name: 'Guam' },
  { code: 'GW', name: 'Guinea-Bissau' },
  { code: 'GY', name: 'Guyana' },
  { code: 'HK', name: 'Hong Kong' },
  { code: 'HM', name: 'Heard Island & McDonald Islands' },
  { code: 'HN', name: 'Honduras' },
  { code: 'HR', name: 'Croatia' },
  { code: 'HT', name: 'Haiti' },
  { code: 'HU', name: 'Hungary' },
  { code: 'ID', name: 'Indonesia' },
  { code: 'IE', name: 'Ireland' },
  { code: 'IL', name: 'Israel' },
  { code: 'IM', name: 'Isle of Man' },
  { code: 'IN', name: 'India' },
  { code: 'IO', name: 'British Indian Ocean Territory' },
  { code: 'IQ', name: 'Iraq' },
  { code: 'IR', name: 'Iran' },
  { code: 'IS', name: 'Iceland' },
  { code: 'IT', name: 'Italy' },
  { code: 'JE', name: 'Jersey' },
  { code: 'JM', name: 'Jamaica' },
  { code: 'JO', name: 'Jordan' },
  { code: 'JP', name: 'Japan' },
  { code: 'KE', name: 'Kenya' },
  { code: 'KG', name: 'Kyrgyzstan' },
  { code: 'KH', name: 'Cambodia' },
  { code: 'KI', name: 'Kiribati' },
  { code: 'KM', name: 'Comoros' },
  { code: 'KN', name: 'St Kitts & Nevis' },
  { code: 'KP', name: 'Korea (North)' },
  { code: 'KR', name: 'Korea (South)' },
  { code: 'KW', name: 'Kuwait' },
  { code: 'KY', name: 'Cayman Islands' },
  { code: 'KZ', name: 'Kazakhstan' },
  { code: 'LA', name: 'Laos' },
  { code: 'LB', name: 'Lebanon' },
  { code: 'LC', name: 'St Lucia' },
  { code: 'LI', name: 'Liechtenstein' },
  { code: 'LK', name: 'Sri Lanka' },
  { code: 'LR', name: 'Liberia' },
  { code: 'LS', name: 'Lesotho' },
  { code: 'LT', name: 'Lithuania' },
  { code: 'LU', name: 'Luxembourg' },
  { code: 'LV', name: 'Latvia' },
  { code: 'LY', name: 'Libya' },
  { code: 'MA', name: 'Morocco' },
  { code: 'MC', name: 'Monaco' },
  { code: 'MD', name: 'Moldova' },
  { code: 'ME', name: 'Montenegro' },
  { code: 'MF', name: 'St Martin (French)' },
  { code: 'MG', name: 'Madagascar' },
  { code: 'MH', name: 'Marshall Islands' },
  { code: 'MK', name: 'North Macedonia' },
  { code: 'ML', name: 'Mali' },
  { code: 'MM', name: 'Myanmar (Burma)' },
  { code: 'MN', name: 'Mongolia' },
  { code: 'MO', name: 'Macau' },
  { code: 'MP', name: 'Northern Mariana Islands' },
  { code: 'MQ', name: 'Martinique' },
  { code: 'MR', name: 'Mauritania' },
  { code: 'MS', name: 'Montserrat' },
  { code: 'MT', name: 'Malta' },
  { code: 'MU', name: 'Mauritius' },
  { code: 'MV', name: 'Maldives' },
  { code: 'MW', name: 'Malawi' },
  { code: 'MX', name: 'Mexico' },
  { code: 'MY', name: 'Malaysia' },
  { code: 'MZ', name: 'Mozambique' },
  { code: 'NA', name: 'Namibia' },
  { code: 'NC', name: 'New Caledonia' },
  { code: 'NE', name: 'Niger' },
  { code: 'NF', name: 'Norfolk Island' },
  { code: 'NG', name: 'Nigeria' },
  { code: 'NI', name: 'Nicaragua' },
  { code: 'NL', name: 'Netherlands' },
  { code: 'NO', name: 'Norway' },
  { code: 'NP', name: 'Nepal' },
  { code: 'NR', name: 'Nauru' },
  { code: 'NU', name: 'Niue' },
  { code: 'NZ', name: 'New Zealand' },
  { code: 'OM', name: 'Oman' },
  { code: 'PA', name: 'Panama' },
  { code: 'PE', name: 'Peru' },
  { code: 'PF', name: 'French Polynesia' },
  { code: 'PG', name: 'Papua New Guinea' },
  { code: 'PH', name: 'Philippines' },
  { code: 'PK', name: 'Pakistan' },
  { code: 'PL', name: 'Poland' },
  { code: 'PM', name: 'St Pierre & Miquelon' },
  { code: 'PN', name: 'Pitcairn' },
  { code: 'PR', name: 'Puerto Rico' },
  { code: 'PS', name: 'Palestine' },
  { code: 'PT', name: 'Portugal' },
  { code: 'PW', name: 'Palau' },
  { code: 'PY', name: 'Paraguay' },
  { code: 'QA', name: 'Qatar' },
  { code: 'RE', name: 'Réunion' },
  { code: 'RO', name: 'Romania' },
  { code: 'RS', name: 'Serbia' },
  { code: 'RU', name: 'Russia' },
  { code: 'RW', name: 'Rwanda' },
  { code: 'SA', name: 'Saudi Arabia' },
  { code: 'SB', name: 'Solomon Islands' },
  { code: 'SC', name: 'Seychelles' },
  { code: 'SD', name: 'Sudan' },
  { code: 'SE', name: 'Sweden' },
  { code: 'SG', name: 'Singapore' },
  { code: 'SH', name: 'St Helena' },
  { code: 'SI', name: 'Slovenia' },
  { code: 'SJ', name: 'Svalbard & Jan Mayen' },
  { code: 'SK', name: 'Slovakia' },
  { code: 'SL', name: 'Sierra Leone' },
  { code: 'SM', name: 'San Marino' },
  { code: 'SN', name: 'Senegal' },
  { code: 'SO', name: 'Somalia' },
  { code: 'SR', name: 'Suriname' },
  { code: 'SS', name: 'South Sudan' },
  { code: 'ST', name: 'Sao Tome & Principe' },
  { code: 'SV', name: 'El Salvador' },
  { code: 'SX', name: 'St Maarten (Dutch)' },
  { code: 'SY', name: 'Syria' },
  { code: 'SZ', name: 'Eswatini (Swaziland)' },
  { code: 'TC', name: 'Turks & Caicos Is' },
  { code: 'TD', name: 'Chad' },
  { code: 'TF', name: 'French S. Terr.' },
  { code: 'TG', name: 'Togo' },
  { code: 'TH', name: 'Thailand' },
  { code: 'TJ', name: 'Tajikistan' },
  { code: 'TK', name: 'Tokelau' },
  { code: 'TL', name: 'East Timor' },
  { code: 'TM', name: 'Turkmenistan' },
  { code: 'TN', name: 'Tunisia' },
  { code: 'TO', name: 'Tonga' },
  { code: 'TR', name: 'Turkey' },
  { code: 'TT', name: 'Trinidad & Tobago' },
  { code: 'TV', name: 'Tuvalu' },
  { code: 'TW', name: 'Taiwan' },
  { code: 'TZ', name: 'Tanzania' },
  { code: 'UA', name: 'Ukraine' },
  { code: 'UG', name: 'Uganda' },
  { code: 'UM', name: 'US minor outlying islands' },
  { code: 'US', name: 'United States' },
  { code: 'UY', name: 'Uruguay' },
  { code: 'UZ', name: 'Uzbekistan' },
  { code: 'VA', name: 'Vatican City' },
  { code: 'VC', name: 'St Vincent' },
  { code: 'VE', name: 'Venezuela' },
  { code: 'VG', name: 'Virgin Islands (UK)' },
  { code: 'VI', name: 'Virgin Islands (US)' },
  { code: 'VN', name: 'Vietnam' },
  { code: 'VU', name: 'Vanuatu' },
  { code: 'WF', name: 'Wallis & Futuna' },
  { code: 'WS', name: 'Samoa (western)' },
  { code: 'YE', name: 'Yemen' },
  { code: 'YT', name: 'Mayotte' },
  { code: 'ZA', name: 'South Africa' },
  { code: 'ZM', name: 'Zambia' },
  { code: 'ZW', name: 'Zimbabwe' },
];

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
  const [currentTab, setCurrentTab] = useState(0);

  useEffect(() => {
    // Load selected tab from localStorage
    const savedTab = localStorage.getItem('network-tab');
    if (savedTab !== null) {
      try {
        const tabIndex = parseInt(savedTab, 10);
        if (tabIndex >= 0 && tabIndex <= 2) {
          setCurrentTab(tabIndex);
        }
      } catch (e) {
        console.error('Failed to parse saved tab:', e);
      }
    }
    
    loadInitialData();
  }, []);

  const handleTabChange = (event, newValue) => {
    setCurrentTab(newValue);
    localStorage.setItem('network-tab', newValue.toString());
  };

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
      const countries = response.data.countries || [];
      // Use complete list as fallback if API returns empty or fails
      setWifiCountries(countries.length > 0 ? countries : COMPLETE_COUNTRIES);
    } catch (error) {
      console.error('Failed to load WiFi countries:', error);
      // Use complete list as fallback on error
      setWifiCountries(COMPLETE_COUNTRIES);
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

  function TabPanel({ children, value, index, ...other }) {
    return (
      <div
        role="tabpanel"
        hidden={value !== index}
        id={`network-tabpanel-${index}`}
        aria-labelledby={`network-tab-${index}`}
        {...other}
      >
        {value === index && <Box sx={{ pt: 3 }}>{children}</Box>}
      </div>
    );
  }

  return (
    <Box>
      <Box sx={{ borderBottom: 1, borderColor: 'divider', mb: 2 }}>
        <Tabs value={currentTab} onChange={handleTabChange} aria-label="network configuration tabs">
          <Tab 
            label={t('network.wifi_config.title') || 'WiFi Configuration'} 
            id="network-tab-0"
            aria-controls="network-tabpanel-0"
          />
          <Tab 
            label={t('network.network_info.title') || 'Network Information'} 
            id="network-tab-1"
            aria-controls="network-tabpanel-1"
          />
          <Tab 
            label={t('integrations.vpn.title') || 'VPN Configuration'} 
            id="network-tab-2"
            aria-controls="network-tabpanel-2"
          />
        </Tabs>
      </Box>

      <TabPanel value={currentTab} index={0}>
        <Stack spacing={3}>
          <FormControl sx={{ maxWidth: 400 }}>
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
            <Box>
              <Stack spacing={2}>
                {networkInfo.wifi.map((wifiItem, index) => (
                  <Box key={index}>
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
                  </Box>
                ))}
              </Stack>
            </Box>
          )}
        </Stack>
      </TabPanel>

      <TabPanel value={currentTab} index={1}>
        <Stack spacing={3}>
          <Box>
            <Typography variant="h2" gutterBottom>
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

          <Box>
            <Box sx={{ display: 'flex', alignItems: 'center', justifyContent: 'space-between', mb: 2 }}>
              <Typography variant="h2" gutterBottom>
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
          )}
        </Stack>
      </TabPanel>

      <TabPanel value={currentTab} index={2}>
        <VPNConfig />
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

export default Network;

