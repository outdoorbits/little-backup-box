import React, { useState, useEffect } from 'react';
import {
  Box,
  Typography,
  Stack,
  FormControl,
  InputLabel,
  Select,
  MenuItem,
  FormControlLabel,
  Checkbox,
  Slider,
  RadioGroup,
  Radio,
  FormLabel,
  Divider,
  Button,
  Alert,
  CircularProgress,
} from '@mui/material';
import RefreshIcon from '@mui/icons-material/Refresh';
import { useLanguage } from '../contexts/LanguageContext';
import { useConfig } from '../contexts/ConfigContext';
import api from '../utils/api';

function DisplayConfig() {
  const { t } = useLanguage();
  const { config, updateConfig } = useConfig();
  const [formData, setFormData] = useState({});
  const [i2cDevices, setI2cDevices] = useState('');
  const [loadingI2c, setLoadingI2c] = useState(false);
  const [message, setMessage] = useState('');

  useEffect(() => {
    if (config) {
      const displayConfig = {
        conf_DISP: config.conf_DISP || '0',
        conf_DISP_FONT_SIZE: config.conf_DISP_FONT_SIZE || '12',
        conf_DISP_FRAME_TIME: config.conf_DISP_FRAME_TIME || '1',
        conf_DISP_CONTRAST: config.conf_DISP_CONTRAST || '255',
        conf_DISP_IP_REPEAT: config.conf_DISP_IP_REPEAT || '0',
        conf_DISP_SHOW_STATUSBAR: config.conf_DISP_SHOW_STATUSBAR || '0',
        conf_DISP_FRAME_TIME_IP: config.conf_DISP_FRAME_TIME_IP || '2.0',
        conf_DISP_COLOR_TEXT: config.conf_DISP_COLOR_TEXT || 'grey',
        conf_DISP_COLOR_HIGH: config.conf_DISP_COLOR_HIGH || 'white',
        conf_DISP_COLOR_ALERT: config.conf_DISP_COLOR_ALERT || 'orange',
        conf_DISP_COLOR_BACKGROUND: config.conf_DISP_COLOR_BACKGROUND || 'black',
        conf_DISP_BACKLIGHT_ENABLED: config.conf_DISP_BACKLIGHT_ENABLED || '0',
        conf_DISP_BACKLIGHT_PIN: config.conf_DISP_BACKLIGHT_PIN || '0',
        conf_DISP_ROTATE: config.conf_DISP_ROTATE || '0',
        conf_DISP_DRIVER: config.conf_DISP_DRIVER || 'SSD1306',
        conf_DISP_CONNECTION: config.conf_DISP_CONNECTION || 'I2C',
        conf_DISP_I2C_ADDRESS: config.conf_DISP_I2C_ADDRESS || '0x3c',
        conf_DISP_SPI_PORT: config.conf_DISP_SPI_PORT || '0',
        conf_DISP_RESOLUTION_X: config.conf_DISP_RESOLUTION_X || '128',
        conf_DISP_RESOLUTION_Y: config.conf_DISP_RESOLUTION_Y || '64',
        conf_DISP_OFFSET_X: config.conf_DISP_OFFSET_X || '0',
        conf_DISP_OFFSET_Y: config.conf_DISP_OFFSET_Y || '0',
        conf_DISP_COLOR_MODEL: config.conf_DISP_COLOR_MODEL || '1',
        conf_DISP_COLOR_BGR: config.conf_DISP_COLOR_BGR || '0',
        conf_DISP_COLOR_INVERSE: config.conf_DISP_COLOR_INVERSE || '0',
      };
      setFormData(displayConfig);
    }
  }, [config]);

  useEffect(() => {
    if (formData.conf_DISP_CONNECTION === 'I2C') {
      detectI2cDevices();
    }
  }, [formData.conf_DISP_CONNECTION]);

  const detectI2cDevices = async () => {
    setLoadingI2c(true);
    try {
      const response = await api.get('/setup/display/i2c-detect');
      setI2cDevices(response.data.output || '');
    } catch (error) {
      console.error('Failed to detect I2C devices:', error);
      setI2cDevices('');
    } finally {
      setLoadingI2c(false);
    }
  };

  const handleChange = (key, value) => {
    const newFormData = { ...formData, [key]: value };
    setFormData(newFormData);
    
    const updatedConfig = { ...config };
    Object.keys(newFormData).forEach(k => {
      updatedConfig[k] = newFormData[k];
    });
    
    updateConfig(updatedConfig).catch(error => {
      console.error('Failed to save display config:', error);
      setMessage('Error saving display settings');
      setTimeout(() => setMessage(''), 3000);
    });
  };

  const displayColors = ['blue', 'green', 'red', 'white', 'yellow', 'orange', 'lightgrey', 'grey', 'black'];
  const fontSizes = [10, 12, 14, 16];
  const frameTimes = ['0.1', '0.25', '0.5', '1', '1.5', '2', '2.5', '3'];
  const frameTimesIP = ['1', '1.5', '2', '2.5', '3', '4', '5', '7', '10'];
  const drivers = ['none', 'SSD1306', 'SSD1309', 'SSD1322', 'SSD1331', 'SH1106', 'ST7735', 'ST7735 WAVESHARE LCD display HAT'];
  const connections = ['I2C', 'SPI'];
  const i2cAddresses = ['0x3c', '0x3d'];
  const spiPorts = ['0', '1'];
  const resolutionsX = [96, 128, 160, 250];
  const resolutionsY = [32, 64, 80, 122, 128];
  const offsets = Array.from({ length: 61 }, (_, i) => i - 30);
  const colorModels = ['1', 'RGB', 'RGBA'];
  const backlightPins = [
    { value: '0', label: '-' },
    { value: '18', label: 'GPIO 18' },
    { value: '24', label: 'GPIO 24' },
  ];
  const rotations = [
    { value: '0', label: '0°' },
    { value: '2', label: '180°' },
  ];

  const isI2cDeviceAvailable = (address) => {
    if (!i2cDevices) return false;
    return i2cDevices.includes(` ${address}`);
  };

  return (
    <Box>
      {message && (
        <Alert severity={message.includes('Error') ? 'error' : 'success'} sx={{ mb: 2 }} onClose={() => setMessage('')}>
          {message}
        </Alert>
      )}

      <Stack spacing={3}>
        <Box>
          <Typography variant="h6" gutterBottom>
            {t('config.display.behavior_header') || 'Display behavior'}
          </Typography>
          <Divider sx={{ my: 1 }} />
          <Stack spacing={2} sx={{ mt: 2 }}>
            <FormControlLabel
              control={
                <Checkbox
                  checked={formData.conf_DISP === '1' || formData.conf_DISP === true}
                  onChange={(e) => handleChange('conf_DISP', e.target.checked ? '1' : '0')}
                />
              }
              label={t('config.display.activate_label') || 'Enable the display connected to GPIO'}
            />

            <FormControl fullWidth>
              <InputLabel>{t('config.display.font_size_label') || 'Font size'}</InputLabel>
              <Select
                value={formData.conf_DISP_FONT_SIZE || '12'}
                onChange={(e) => handleChange('conf_DISP_FONT_SIZE', e.target.value)}
                label={t('config.display.font_size_label') || 'Font size'}
              >
                {fontSizes.map((size) => (
                  <MenuItem key={size} value={String(size)}>
                    {size}
                  </MenuItem>
                ))}
              </Select>
            </FormControl>

            <FormControl fullWidth>
              <InputLabel>{t('config.display.frame_time_label') || 'Minimum display time per frame in seconds'}</InputLabel>
              <Select
                value={formData.conf_DISP_FRAME_TIME || '1'}
                onChange={(e) => handleChange('conf_DISP_FRAME_TIME', e.target.value)}
                label={t('config.display.frame_time_label') || 'Minimum display time per frame in seconds'}
              >
                {frameTimes.map((time) => (
                  <MenuItem key={time} value={time}>
                    {time}
                  </MenuItem>
                ))}
              </Select>
            </FormControl>

            <Box>
              <Typography gutterBottom>
                {t('config.display.contrast_label') || 'Contrast'} ({formData.conf_DISP_CONTRAST || '255'})
              </Typography>
              <Slider
                value={Number(formData.conf_DISP_CONTRAST) || 255}
                onChange={(e, value) => handleChange('conf_DISP_CONTRAST', String(value))}
                min={1}
                max={255}
                step={1}
                marks={[
                  { value: 1, label: '1' },
                  { value: 128, label: '128' },
                  { value: 255, label: '255' },
                ]}
              />
            </Box>

            <FormControlLabel
              control={
                <Checkbox
                  checked={formData.conf_DISP_IP_REPEAT === '1' || formData.conf_DISP_IP_REPEAT === true}
                  onChange={(e) => handleChange('conf_DISP_IP_REPEAT', e.target.checked ? '1' : '0')}
                />
              }
              label={t('config.display.ip_label') || 'Print IP every minute.'}
            />

            <FormControlLabel
              control={
                <Checkbox
                  checked={formData.conf_DISP_SHOW_STATUSBAR === '1' || formData.conf_DISP_SHOW_STATUSBAR === true}
                  onChange={(e) => handleChange('conf_DISP_SHOW_STATUSBAR', e.target.checked ? '1' : '0')}
                />
              }
              label={t('config.display.statusbar.label') || 'Show status bar'}
            />

            <FormControl fullWidth>
              <InputLabel>{t('config.display.frame_time_ip_label') || 'Minimum display time of the IP in seconds'}</InputLabel>
              <Select
                value={formData.conf_DISP_FRAME_TIME_IP || '2.0'}
                onChange={(e) => handleChange('conf_DISP_FRAME_TIME_IP', e.target.value)}
                label={t('config.display.frame_time_ip_label') || 'Minimum display time of the IP in seconds'}
              >
                {frameTimesIP.map((time) => (
                  <MenuItem key={time} value={time}>
                    {time}
                  </MenuItem>
                ))}
              </Select>
            </FormControl>
          </Stack>
        </Box>

        <Box>
          <Typography variant="h6" gutterBottom>
            {t('config.display.colors_header') || 'Color selection when using a color display'}
          </Typography>
          <Divider sx={{ my: 1 }} />
          <Stack spacing={2} sx={{ mt: 2 }}>
            <FormControl fullWidth>
              <InputLabel>{t('config.display.color.text_label') || 'Default font color'}</InputLabel>
              <Select
                value={formData.conf_DISP_COLOR_TEXT || 'grey'}
                onChange={(e) => handleChange('conf_DISP_COLOR_TEXT', e.target.value)}
                label={t('config.display.color.text_label') || 'Default font color'}
              >
                {displayColors.map((color) => (
                  <MenuItem key={color} value={color}>
                    {t(`config.display.color.${color}`) || color}
                  </MenuItem>
                ))}
              </Select>
            </FormControl>

            <FormControl fullWidth>
              <InputLabel>{t('config.display.color.high_label') || 'Font color for highlighted text'}</InputLabel>
              <Select
                value={formData.conf_DISP_COLOR_HIGH || 'white'}
                onChange={(e) => handleChange('conf_DISP_COLOR_HIGH', e.target.value)}
                label={t('config.display.color.high_label') || 'Font color for highlighted text'}
              >
                {displayColors.map((color) => (
                  <MenuItem key={color} value={color}>
                    {t(`config.display.color.${color}`) || color}
                  </MenuItem>
                ))}
              </Select>
            </FormControl>

            <FormControl fullWidth>
              <InputLabel>{t('config.display.color.alert_label') || 'Font color for warnings'}</InputLabel>
              <Select
                value={formData.conf_DISP_COLOR_ALERT || 'orange'}
                onChange={(e) => handleChange('conf_DISP_COLOR_ALERT', e.target.value)}
                label={t('config.display.color.alert_label') || 'Font color for warnings'}
              >
                {displayColors.map((color) => (
                  <MenuItem key={color} value={color}>
                    {t(`config.display.color.${color}`) || color}
                  </MenuItem>
                ))}
              </Select>
            </FormControl>

            <FormControl fullWidth>
              <InputLabel>{t('config.display.color.background_label') || 'Background color'}</InputLabel>
              <Select
                value={formData.conf_DISP_COLOR_BACKGROUND || 'black'}
                onChange={(e) => handleChange('conf_DISP_COLOR_BACKGROUND', e.target.value)}
                label={t('config.display.color.background_label') || 'Background color'}
              >
                {displayColors.map((color) => (
                  <MenuItem key={color} value={color}>
                    {t(`config.display.color.${color}`) || color}
                  </MenuItem>
                ))}
              </Select>
            </FormControl>
          </Stack>
        </Box>

        <Box>
          <Typography variant="h6" gutterBottom>
            {t('config.display.backlight_header') || 'Display backlight'}
          </Typography>
          <Divider sx={{ my: 1 }} />
          <Stack spacing={2} sx={{ mt: 2 }}>
            <FormControlLabel
              control={
                <Checkbox
                  checked={formData.conf_DISP_BACKLIGHT_ENABLED === '1' || formData.conf_DISP_BACKLIGHT_ENABLED === true}
                  onChange={(e) => handleChange('conf_DISP_BACKLIGHT_ENABLED', e.target.checked ? '1' : '0')}
                />
              }
              label={t('config.display.backlight_enabled_label') || 'Activate display backlight (if available)'}
            />

            <FormControl fullWidth>
              <InputLabel>{t('config.display.backlight_pin_label') || 'Backlight GPIO pin (BCM notation)'}</InputLabel>
              <Select
                value={formData.conf_DISP_BACKLIGHT_PIN || '0'}
                onChange={(e) => handleChange('conf_DISP_BACKLIGHT_PIN', e.target.value)}
                label={t('config.display.backlight_pin_label') || 'Backlight GPIO pin (BCM notation)'}
              >
                {backlightPins.map((pin) => (
                  <MenuItem key={pin.value} value={pin.value}>
                    {pin.label}
                  </MenuItem>
                ))}
              </Select>
            </FormControl>
          </Stack>
        </Box>

        <Box>
          <Typography variant="h6" gutterBottom>
            {t('config.display.rotate_header') || 'Rotate screen'}
          </Typography>
          <Divider sx={{ my: 1 }} />
          <Stack spacing={2} sx={{ mt: 2 }}>
            <FormControl fullWidth>
              <InputLabel>{t('config.display.rotate_label') || 'Display rotation'}</InputLabel>
              <Select
                value={formData.conf_DISP_ROTATE || '0'}
                onChange={(e) => handleChange('conf_DISP_ROTATE', e.target.value)}
                label={t('config.display.rotate_label') || 'Display rotation'}
              >
                {rotations.map((rotation) => (
                  <MenuItem key={rotation.value} value={rotation.value}>
                    {rotation.label}
                  </MenuItem>
                ))}
              </Select>
            </FormControl>
          </Stack>
        </Box>

        <Box>
          <Typography variant="h6" gutterBottom>
            {t('config.hardware.section') || 'Hardware'}
          </Typography>
          <Divider sx={{ my: 1 }} />
          <Stack spacing={2} sx={{ mt: 2 }}>
            <FormControl fullWidth>
              <InputLabel>{t('config.display.driver_label') || 'Display driver'}</InputLabel>
              <Select
                value={formData.conf_DISP_DRIVER || 'SSD1306'}
                onChange={(e) => handleChange('conf_DISP_DRIVER', e.target.value)}
                label={t('config.display.driver_label') || 'Display driver'}
              >
                {drivers.map((driver) => (
                  <MenuItem key={driver} value={driver}>
                    {driver}
                  </MenuItem>
                ))}
              </Select>
            </FormControl>

            <FormControl fullWidth>
              <InputLabel>{t('config.display.connection_label') || 'Display interface'}</InputLabel>
              <Select
                value={formData.conf_DISP_CONNECTION || 'I2C'}
                onChange={(e) => handleChange('conf_DISP_CONNECTION', e.target.value)}
                label={t('config.display.connection_label') || 'Display interface'}
              >
                {connections.map((connection) => (
                  <MenuItem key={connection} value={connection}>
                    {connection}
                  </MenuItem>
                ))}
              </Select>
            </FormControl>

            {formData.conf_DISP_CONNECTION === 'I2C' && (
              <Box>
                <Box sx={{ display: 'flex', alignItems: 'center', gap: 1, mb: 2 }}>
                  <Typography variant="subtitle1">
                    {t('config.display.i2c_header') || 'I2C display configuration'}
                  </Typography>
                  <Button
                    size="small"
                    startIcon={loadingI2c ? <CircularProgress size={16} /> : <RefreshIcon />}
                    onClick={detectI2cDevices}
                    disabled={loadingI2c}
                  >
                    {t('config.display.detect_devices') || 'Detect devices'}
                  </Button>
                </Box>
                <FormControl component="fieldset">
                  <FormLabel component="legend">{t('config.display.i2c_address_label') || 'i2c address of the display'}</FormLabel>
                  <RadioGroup
                    value={formData.conf_DISP_I2C_ADDRESS || '0x3c'}
                    onChange={(e) => handleChange('conf_DISP_I2C_ADDRESS', e.target.value)}
                  >
                    {i2cAddresses.map((address) => (
                      <FormControlLabel
                        key={address}
                        value={address}
                        control={<Radio />}
                        label={
                          <Box sx={{ display: 'flex', alignItems: 'center', gap: 1 }}>
                            <Typography>{address}</Typography>
                            {isI2cDeviceAvailable(address) && (
                              <Typography variant="caption" color="success.main">
                                - {t('config.display.device_available') || 'Device detected'}
                              </Typography>
                            )}
                          </Box>
                        }
                      />
                    ))}
                  </RadioGroup>
                </FormControl>
              </Box>
            )}

            {formData.conf_DISP_CONNECTION === 'SPI' && (
              <Box>
                <Typography variant="subtitle1" gutterBottom>
                  {t('config.display.spi_header') || 'SPI display configuration'}
                </Typography>
                <FormControl component="fieldset">
                  <FormLabel component="legend">{t('config.display.spi_port_label') || 'SPI port'}</FormLabel>
                  <RadioGroup
                    value={formData.conf_DISP_SPI_PORT || '0'}
                    onChange={(e) => handleChange('conf_DISP_SPI_PORT', e.target.value)}
                  >
                    {spiPorts.map((port) => (
                      <FormControlLabel
                        key={port}
                        value={port}
                        control={<Radio />}
                        label={port}
                      />
                    ))}
                  </RadioGroup>
                </FormControl>
              </Box>
            )}
          </Stack>
        </Box>

        <Box>
          <Typography variant="h6" gutterBottom>
            {t('config.display.additional_settings_header') || 'Additional display settings'}
          </Typography>
          <Divider sx={{ my: 1 }} />
          <Stack spacing={2} sx={{ mt: 2 }}>
            <FormControl fullWidth>
              <InputLabel>{t('config.display.resolution_x_label') || 'Horizontal resolution (pixels)'}</InputLabel>
              <Select
                value={formData.conf_DISP_RESOLUTION_X || '128'}
                onChange={(e) => handleChange('conf_DISP_RESOLUTION_X', e.target.value)}
                label={t('config.display.resolution_x_label') || 'Horizontal resolution (pixels)'}
              >
                {resolutionsX.map((res) => (
                  <MenuItem key={res} value={String(res)}>
                    {res}
                  </MenuItem>
                ))}
              </Select>
            </FormControl>

            <FormControl fullWidth>
              <InputLabel>{t('config.display.resolution_y_label') || 'Vertical resolution (pixels)'}</InputLabel>
              <Select
                value={formData.conf_DISP_RESOLUTION_Y || '64'}
                onChange={(e) => handleChange('conf_DISP_RESOLUTION_Y', e.target.value)}
                label={t('config.display.resolution_y_label') || 'Vertical resolution (pixels)'}
              >
                {resolutionsY.map((res) => (
                  <MenuItem key={res} value={String(res)}>
                    {res}
                  </MenuItem>
                ))}
              </Select>
            </FormControl>

            <FormControl fullWidth>
              <InputLabel>{t('config.display.offset_x_label') || 'Display shift horizontally (in pixels)'}</InputLabel>
              <Select
                value={formData.conf_DISP_OFFSET_X || '0'}
                onChange={(e) => handleChange('conf_DISP_OFFSET_X', e.target.value)}
                label={t('config.display.offset_x_label') || 'Display shift horizontally (in pixels)'}
              >
                {offsets.map((offset) => (
                  <MenuItem key={offset} value={String(offset)}>
                    {offset}
                  </MenuItem>
                ))}
              </Select>
            </FormControl>

            <FormControl fullWidth>
              <InputLabel>{t('config.display.offset_y_label') || 'Display shift vertically (in pixels)'}</InputLabel>
              <Select
                value={formData.conf_DISP_OFFSET_Y || '0'}
                onChange={(e) => handleChange('conf_DISP_OFFSET_Y', e.target.value)}
                label={t('config.display.offset_y_label') || 'Display shift vertically (in pixels)'}
              >
                {offsets.map((offset) => (
                  <MenuItem key={offset} value={String(offset)}>
                    {offset}
                  </MenuItem>
                ))}
              </Select>
            </FormControl>

            <FormControl fullWidth>
              <InputLabel>{t('config.display.color.model_label') || 'Color'}</InputLabel>
              <Select
                value={formData.conf_DISP_COLOR_MODEL || '1'}
                onChange={(e) => handleChange('conf_DISP_COLOR_MODEL', e.target.value)}
                label={t('config.display.color.model_label') || 'Color'}
              >
                {colorModels.map((model) => (
                  <MenuItem key={model} value={model}>
                    {t(`config.display.color.model_${model}`) || model}
                  </MenuItem>
                ))}
              </Select>
            </FormControl>

            <FormControl fullWidth>
              <InputLabel>{t('config.display.color.notation_label') || 'Color notation'}</InputLabel>
              <Select
                value={formData.conf_DISP_COLOR_BGR === '1' || formData.conf_DISP_COLOR_BGR === true ? 'BGR' : 'RGB'}
                onChange={(e) => handleChange('conf_DISP_COLOR_BGR', e.target.value === 'BGR' ? '1' : '0')}
                label={t('config.display.color.notation_label') || 'Color notation'}
              >
                <MenuItem value="RGB">
                  {t('config.display.color.notation_RGB') || 'RGB (red-green-blue)'}
                </MenuItem>
                <MenuItem value="BGR">
                  {t('config.display.color.notation_BGR') || 'BGR (blue-green-red)'}
                </MenuItem>
              </Select>
            </FormControl>

            <FormControlLabel
              control={
                <Checkbox
                  checked={formData.conf_DISP_COLOR_INVERSE === '1' || formData.conf_DISP_COLOR_INVERSE === true}
                  onChange={(e) => handleChange('conf_DISP_COLOR_INVERSE', e.target.checked ? '1' : '0')}
                />
              }
              label={t('config.display.color.inverse_label') || 'Inverted colors'}
            />
          </Stack>
        </Box>
      </Stack>
    </Box>
  );
}

export default DisplayConfig;

