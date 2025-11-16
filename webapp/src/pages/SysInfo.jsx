import React, { useState, useEffect } from 'react';
import {
  Box,
  Typography,
  Card,
  CardContent,
  Table,
  TableBody,
  TableCell,
  TableRow,
  TableHead,
  Paper,
  Button,
  Grid,
  List,
  ListItem,
  ListItemText,
  Divider,
  Chip,
} from '@mui/material';
import RefreshIcon from '@mui/icons-material/Refresh';
import { useLanguage } from '../contexts/LanguageContext';
import api from '../utils/api';

function parseLsblkOutput(output) {
  if (!output || !output.trim()) {
    return { headers: [], rows: [] };
  }

  const lines = output.trim().split('\n').filter(line => line.trim());
  if (lines.length === 0) {
    return { headers: [], rows: [] };
  }

  const headerLine = lines[0];
  const dataLines = lines.slice(1);

  const headers = headerLine.split('\t').map(h => h.trim()).filter(h => h);

  if (headers.length === 0) {
    return { headers: [], rows: [] };
  }

  const rows = dataLines.map(line => {
    const values = line.split('\t').map(v => v.trim());
    const row = {};
    headers.forEach((header, index) => {
      row[header] = values[index] || '';
    });
    return row;
  });

  return { headers, rows };
}

function SysInfo() {
  const { t } = useLanguage();
  const [systemInfo, setSystemInfo] = useState(null);
  const [diskSpace, setDiskSpace] = useState({ headers: [], rows: [] });
  const [devices, setDevices] = useState({ headers: [], rows: [] });
  const [deviceStates, setDeviceStates] = useState([]);
  const [cameras, setCameras] = useState([]);
  const [wifi, setWifi] = useState([]);

  useEffect(() => {
    loadAll();
  }, []);

  const loadAll = () => {
    loadSystemInfo();
    loadDiskSpace();
    loadDevices();
    loadDeviceStates();
    loadCameras();
    loadWifi();
  };

  const loadSystemInfo = async () => {
    try {
      const response = await api.get('/sysinfo/system');
      setSystemInfo(response.data);
    } catch (error) {
      console.error('Failed to load system info:', error);
    }
  };

  const loadDiskSpace = async () => {
    try {
      const response = await api.get('/sysinfo/diskspace');
      if (response.data && response.data.output) {
        const parsed = parseLsblkOutput(response.data.output);
        setDiskSpace(parsed);
      } else {
        console.error('Invalid response format for disk space:', response.data);
        setDiskSpace({ headers: [], rows: [] });
      }
    } catch (error) {
      console.error('Failed to load disk space:', error);
      setDiskSpace({ headers: [], rows: [] });
    }
  };

  const loadDevices = async () => {
    try {
      const response = await api.get('/sysinfo/devices');
      if (response.data && response.data.output) {
        const parsed = parseLsblkOutput(response.data.output);
        setDevices(parsed);
      } else {
        console.error('Invalid response format for devices:', response.data);
        setDevices({ headers: [], rows: [] });
      }
    } catch (error) {
      console.error('Failed to load devices:', error);
      setDevices({ headers: [], rows: [] });
    }
  };

  const loadDeviceStates = async () => {
    try {
      const response = await api.get('/sysinfo/device-states');
      setDeviceStates(response.data.deviceStates || []);
    } catch (error) {
      console.error('Failed to load device states:', error);
    }
  };

  const loadCameras = async () => {
    try {
      const response = await api.get('/sysinfo/cameras');
      setCameras(response.data.cameras || []);
    } catch (error) {
      console.error('Failed to load cameras:', error);
    }
  };

  const loadWifi = async () => {
    try {
      const response = await api.get('/sysinfo/wifi');
      setWifi(response.data.wifi || []);
    } catch (error) {
      console.error('Failed to load WiFi info:', error);
    }
  };

  return (
    <Box>
      <Box 
        sx={{ 
          display: 'flex', 
          justifyContent: 'flex-end', 
          alignItems: 'center', 
          mb: 3,
        }}
      >
        <Button
          variant="contained"
          startIcon={<RefreshIcon />}
          onClick={loadAll}
        >
          {t('sysinfo.refresh_button') || 'Refresh'}
        </Button>
      </Box>

      <Grid container spacing={3}>
        {systemInfo && (
          <Grid item xs={12}>
            <Card>
              <CardContent>
                <Typography variant="h6" gutterBottom>
                  {t('sysinfo.system') || 'System'}
                </Typography>
                <Divider sx={{ my: 2 }} />
                <Table size="small">
                  <TableBody>
                    <TableRow>
                      <TableCell>
                        {t('sysinfo.model') || 'Model'}:
                      </TableCell>
                      <TableCell>{systemInfo.model}</TableCell>
                    </TableRow>
                    {systemInfo.temp !== null && (
                      <TableRow>
                        <TableCell>{t('sysinfo.temp') || 'Temperature'}:</TableCell>
                        <TableCell>
                          <Chip 
                            label={`${systemInfo.temp}°C`} 
                            size="small" 
                            color={systemInfo.temp > 70 ? 'error' : systemInfo.temp > 60 ? 'warning' : 'default'}
                          />
                        </TableCell>
                      </TableRow>
                    )}
                    {systemInfo.cpuusage !== null && (
                      <TableRow>
                        <TableCell>{t('sysinfo.cpuload') || 'CPU Load'}:</TableCell>
                        <TableCell>
                          <Chip 
                            label={`${systemInfo.cpuusage}%`} 
                            size="small"
                            color={systemInfo.cpuusage > 80 ? 'error' : systemInfo.cpuusage > 60 ? 'warning' : 'default'}
                          />
                        </TableCell>
                      </TableRow>
                    )}
                    {systemInfo.memRam && (
                      <TableRow>
                        <TableCell>{t('sysinfo.memory_ram') || 'RAM'}:</TableCell>
                        <TableCell>{systemInfo.memRam}</TableCell>
                      </TableRow>
                    )}
                    {systemInfo.memSwap && (
                      <TableRow>
                        <TableCell>{t('sysinfo.memory_swap') || 'Swap'}:</TableCell>
                        <TableCell>{systemInfo.memSwap}</TableCell>
                      </TableRow>
                    )}
                    <TableRow>
                      <TableCell>{t('sysinfo.conditions') || 'Conditions'}:</TableCell>
                      <TableCell>
                        <Chip 
                          label={systemInfo.abnormalConditions} 
                          size="small"
                          color={systemInfo.abnormalConditions === 'None' ? 'success' : 'warning'}
                        />
                      </TableCell>
                    </TableRow>
                  </TableBody>
                </Table>
              </CardContent>
            </Card>
          </Grid>
        )}

        <Grid item xs={12}>
          <Card>
            <CardContent>
              <Typography variant="h6" gutterBottom>
                {t('sysinfo.diskspace') || 'Disk Space'}
              </Typography>
              <Divider sx={{ my: 2 }} />
              {diskSpace.headers.length > 0 ? (
                <Paper elevation={0} sx={{ overflowX: 'auto' }}>
                  <Table size="small">
                    <TableHead>
                      <TableRow>
                        {diskSpace.headers.map((header, index) => (
                          <TableCell key={index} sx={{ fontWeight: 'bold' }}>
                            {header}
                          </TableCell>
                        ))}
                      </TableRow>
                    </TableHead>
                    <TableBody>
                      {diskSpace.rows.map((row, rowIndex) => (
                        <TableRow key={rowIndex}>
                          {diskSpace.headers.map((header, colIndex) => (
                            <TableCell key={colIndex}>
                              {row[header] || '-'}
                            </TableCell>
                          ))}
                        </TableRow>
                      ))}
                    </TableBody>
                  </Table>
                </Paper>
              ) : (
                <Typography variant="body2" color="text.secondary">
                  Loading...
                </Typography>
              )}
            </CardContent>
          </Card>
        </Grid>

        <Grid item xs={12}>
          <Card>
            <CardContent>
              <Typography variant="h6" gutterBottom>
                {t('sysinfo.devices') || 'Devices'}
              </Typography>
              <Divider sx={{ my: 2 }} />
              {devices.headers.length > 0 ? (
                <Paper elevation={0} sx={{ overflowX: 'auto' }}>
                  <Table size="small">
                    <TableHead>
                      <TableRow>
                        {devices.headers.map((header, index) => (
                          <TableCell key={index} sx={{ fontWeight: 'bold' }}>
                            {header}
                          </TableCell>
                        ))}
                      </TableRow>
                    </TableHead>
                    <TableBody>
                      {devices.rows.map((row, rowIndex) => (
                        <TableRow key={rowIndex}>
                          {devices.headers.map((header, colIndex) => (
                            <TableCell key={colIndex}>
                              {row[header] || '-'}
                            </TableCell>
                          ))}
                        </TableRow>
                      ))}
                    </TableBody>
                  </Table>
                </Paper>
              ) : (
                <Typography variant="body2" color="text.secondary">
                  Loading...
                </Typography>
              )}
            </CardContent>
          </Card>
        </Grid>

        <Grid item xs={12}>
          <Card>
            <CardContent>
              <Typography variant="h6" gutterBottom>
                {t('sysinfo.cameras') || 'Cameras'}
              </Typography>
              <Divider sx={{ my: 2 }} />
              {cameras.length > 0 ? (
                <List disablePadding>
                  {cameras.map((camera, i) => (
                    <React.Fragment key={i}>
                      <ListItem disablePadding>
                        <ListItemText
                          primary={camera.model}
                          secondary={camera.port}
                        />
                      </ListItem>
                      {i < cameras.length - 1 && <Divider />}
                    </React.Fragment>
                  ))}
                </List>
              ) : (
                <Typography variant="body2" color="text.secondary">
                  No cameras detected
                </Typography>
              )}
            </CardContent>
          </Card>
        </Grid>
      </Grid>
    </Box>
  );
}

export default SysInfo;
