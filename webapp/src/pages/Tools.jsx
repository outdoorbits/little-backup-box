import React, { useState, useEffect } from 'react';
import {
  Box,
  Typography,
  Card,
  CardContent,
  Button,
  Alert,
  Paper,
  List,
  ListItem,
  ListItemText,
  Divider,
} from '@mui/material';
import { useLanguage } from '../contexts/LanguageContext';
import api from '../utils/api';

function Tools() {
  const { t } = useLanguage();
  const [mounts, setMounts] = useState('');
  const [devices, setDevices] = useState([]);
  const [message, setMessage] = useState('');

  useEffect(() => {
    loadMounts();
    loadDevices();
  }, []);

  const loadMounts = async () => {
    try {
      const response = await api.get('/tools/mounts');
      setMounts(response.data.mountsList || '');
    } catch (error) {
      console.error('Failed to load mounts:', error);
    }
  };

  const loadDevices = async () => {
    try {
      const response = await api.get('/tools/devices');
      setDevices(response.data.devices || []);
    } catch (error) {
      console.error('Failed to load devices:', error);
    }
  };

  const handleMount = async (role, storage, deviceIdentifier) => {
    try {
      await api.post('/tools/mount', { role, storage, deviceIdentifierPreset: deviceIdentifier });
      setMessage(t('tools.mount.b') || 'Mount successful');
      loadMounts();
    } catch (error) {
      console.error('Failed to mount:', error);
      setMessage('Mount failed');
    }
  };

  const handleUmount = async (role, storage) => {
    try {
      await api.post('/tools/umount', { role, storage });
      setMessage(t('tools.umount_b') || 'Unmount successful');
      loadMounts();
    } catch (error) {
      console.error('Failed to umount:', error);
      setMessage('Unmount failed');
    }
  };

  return (
    <Box>
      {message && (
        <Alert 
          severity={message.includes('failed') || message.includes('error') ? 'error' : 'success'} 
          sx={{ mb: 3 }}
          onClose={() => setMessage('')}
        >
          {message}
        </Alert>
      )}

      <Card sx={{ mb: 3 }}>
        <CardContent>
          <Typography variant="h6" gutterBottom>
            {t('tools.mount.header') || 'Mount storage'}
          </Typography>
          <Divider sx={{ my: 2 }} />
          <Paper elevation={0}>
            <Typography 
              component="pre" 
              variant="body2" 
              sx={{
                fontFamily: 'monospace',
                whiteSpace: 'pre-wrap',
                margin: 0,
              }}
            >
              {mounts || 'No mounts'}
            </Typography>
          </Paper>
        </CardContent>
      </Card>

      <Card>
        <CardContent>
          <Typography variant="h6" gutterBottom>
            {t('tools.devices') || 'Devices'}
          </Typography>
          <Divider sx={{ my: 2 }} />
          {devices.length > 0 ? (
            <List disablePadding>
              {devices.map((device, i) => (
                <React.Fragment key={i}>
                  <ListItem disablePadding>
                    <ListItemText
                      primary={device.lum}
                      secondary={device.identifier || ''}
                    />
                  </ListItem>
                  {i < devices.length - 1 && <Divider />}
                </React.Fragment>
              ))}
            </List>
          ) : (
            <Typography variant="body2" color="text.secondary">
              No devices found
            </Typography>
          )}
        </CardContent>
      </Card>
    </Box>
  );
}

export default Tools;
