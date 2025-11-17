import React, { useState, useEffect } from 'react';
import {
  Select,
  MenuItem,
  FormControl,
  InputLabel,
  FormHelperText,
} from '@mui/material';
import api from '../utils/api';

function DeviceSelector({
  storageType = '',
  listPartitions = true,
  skipMounted = false,
  ignoreFs = false,
  value,
  onChange,
  label,
  nullName = '-',
  nullValue = '-',
  helperText,
  ...props
}) {
  const [devices, setDevices] = useState([]);
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    loadDevices();
  }, [storageType, listPartitions, skipMounted, ignoreFs]);

  const loadDevices = async () => {
    setLoading(true);
    try {
      const params = new URLSearchParams({
        storageType: storageType || '',
        listPartitions: listPartitions.toString(),
        skipMounted: skipMounted.toString(),
        ignoreFs: ignoreFs.toString(),
      });

      const response = await api.get(`/tools/devices?${params}`);
      setDevices(response.data.devices || []);
    } catch (error) {
      console.error('Failed to load devices:', error);
      setDevices([]);
    } finally {
      setLoading(false);
    }
  };

  const formatDeviceLabel = (device) => {
    if (device.identifier && device.identifier !== device.lum) {
      return `${device.lum} (${device.identifier})`;
    }
    return device.lum;
  };

  return (
    <FormControl fullWidth {...props}>
      {label && <InputLabel>{label}</InputLabel>}
      <Select
        value={value || nullValue}
        onChange={(e) => onChange && onChange(e.target.value)}
        label={label}
        disabled={loading}
      >
        <MenuItem value={nullValue}>{nullName}</MenuItem>
        {devices.map((device, index) => {
          const deviceValue = device.identifier || device.lum;
          return (
            <MenuItem key={index} value={deviceValue}>
              {formatDeviceLabel(device)}
            </MenuItem>
          );
        })}
      </Select>
      {helperText && <FormHelperText>{helperText}</FormHelperText>}
    </FormControl>
  );
}

export default DeviceSelector;

