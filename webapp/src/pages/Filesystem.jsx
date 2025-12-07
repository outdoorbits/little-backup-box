import React, { useState, useEffect } from 'react';
import {
  Box,
  Typography,
  Button,
  Alert,
  Paper,
  Divider,
  Select,
  MenuItem,
  FormControl,
  FormLabel,
  InputLabel,
  Dialog,
  DialogTitle,
  DialogContent,
  DialogContentText,
  DialogActions,
  Grid,
  ListSubheader,
  Table,
  TableBody,
  TableCell,
  TableRow,
  TableHead,
  Tabs,
  Tab,
} from '@mui/material';
import RefreshIcon from '@mui/icons-material/Refresh';
import { useLanguage } from '../contexts/LanguageContext';
import { useDrawer } from '../contexts/DrawerContext';
import { drawerWidth, drawerCollapsedWidth } from '../components/Menu';
import api from '../utils/api';
import DeviceSelector from '../components/DeviceSelector';
import LogMonitor from '../components/LogMonitor';

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

function Filesystem() {
  const { t } = useLanguage();
  const { desktopOpen } = useDrawer();
  const currentDrawerWidth = desktopOpen ? drawerWidth : drawerCollapsedWidth;
  const [mounts, setMounts] = useState('');
  const [mountableStorages, setMountableStorages] = useState([]);
  const [selectedSource, setSelectedSource] = useState('');
  const [selectedTarget, setSelectedTarget] = useState('');
  const [sourcePartition, setSourcePartition] = useState('');
  const [targetPartition, setTargetPartition] = useState('');
  const [sourcePartitions, setSourcePartitions] = useState([]);
  const [targetPartitions, setTargetPartitions] = useState([]);
  const [message, setMessage] = useState('');
  const [messageSeverity, setMessageSeverity] = useState('success');
  
  const [fsckPartition, setFsckPartition] = useState('-');
  const [formatPartition, setFormatPartition] = useState('-');
  const [formatFstype, setFormatFstype] = useState('-');
  const [f3Device, setF3Device] = useState('-');
  const [f3Action, setF3Action] = useState('-');
  
  const [formatConfirmOpen, setFormatConfirmOpen] = useState(false);
  const [f3ConfirmOpen, setF3ConfirmOpen] = useState(false);
  const [fsckRepairConfirmOpen, setFsckRepairConfirmOpen] = useState(false);
  const [currentTab, setCurrentTab] = useState(0);

  const [diskSpace, setDiskSpace] = useState({ headers: [], rows: [] });
  const [devices, setDevices] = useState({ headers: [], rows: [] });
  const [deviceStates, setDeviceStates] = useState([]);

  useEffect(() => {
    // Load selected tab from localStorage
    const savedTab = localStorage.getItem('filesystem-tab');
    if (savedTab !== null) {
      try {
        const tabIndex = parseInt(savedTab, 10);
        if (tabIndex >= 0 && tabIndex <= 4) {
          setCurrentTab(tabIndex);
        }
      } catch (e) {
        console.error('Failed to parse saved tab:', e);
      }
    }
    
    loadAll();
  }, []);

  const handleTabChange = (event, newValue) => {
    setCurrentTab(newValue);
    localStorage.setItem('filesystem-tab', newValue.toString());
  };

  const loadAll = () => {
    loadMounts();
    loadMountableStorages();
    loadDiskSpace();
    loadDevices();
    loadDeviceStates();
  };

  useEffect(() => {
    if (selectedSource === 'usb' || selectedSource === 'nvme') {
      loadPartitionsForStorage('source', selectedSource);
    } else {
      setSourcePartitions([]);
    }
  }, [selectedSource]);

  useEffect(() => {
    if (selectedTarget === 'usb' || selectedTarget === 'nvme') {
      loadPartitionsForStorage('target', selectedTarget);
    } else {
      setTargetPartitions([]);
    }
  }, [selectedTarget]);

  const loadMounts = async () => {
    try {
      const response = await api.get('/tools/mounts');
      setMounts(response.data.mountsList || '');
    } catch (error) {
      console.error('Failed to load mounts:', error);
    }
  };

  const loadMountableStorages = async () => {
    try {
      const response = await api.get('/tools/mountable-storages');
      setMountableStorages(response.data.mountableStorages || []);
    } catch (error) {
      console.error('Failed to load mountable storages:', error);
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

  const loadPartitionsForStorage = async (role, storageType) => {
    try {
      const params = new URLSearchParams({
        storageType: storageType || '',
        listPartitions: 'true',
        skipMounted: 'true',
        ignoreFs: 'false',
      });

      const response = await api.get(`/tools/devices?${params}`);
      const partitions = response.data.devices || [];
      
      if (role === 'source') {
        setSourcePartitions(partitions);
      } else {
        setTargetPartitions(partitions);
      }
    } catch (error) {
      console.error('Failed to load partitions:', error);
      if (role === 'source') {
        setSourcePartitions([]);
      } else {
        setTargetPartitions([]);
      }
    }
  };

  const isMounted = (role, storage) => {
    const storageKey = `${role}_${storage}`;
    return mounts.includes(` ${storageKey} `);
  };

  const getStorageLabel = (storage) => {
    if (storage.startsWith('cloud:')) {
      return storage.substring(6);
    }
    if (storage === 'nvme') {
      return t('tools.mount.nvme') || 'NVMe SSD';
    }
    if (storage === 'usb') {
      return t('tools.mount.usb') || 'USB';
    }
    return storage;
  };

  const getGroupLabel = (group, t) => {
    const labels = {
      usb: t('main.backup.usb_devices') || 'USB Devices',
      nvme: t('tools.mount.nvme') || 'NVMe SSD',
      cloud: t('box.backup.mode.cloud') || 'Cloud',
    };
    return labels[group] || group;
  };

  const groupStoragesByType = (storages) => {
    const grouped = {
      usb: [],
      nvme: [],
      cloud: [],
    };

    storages.forEach(storage => {
      if (storage === 'usb') {
        grouped.usb.push(storage);
      } else if (storage === 'nvme') {
        grouped.nvme.push(storage);
      } else if (storage.startsWith('cloud:')) {
        grouped.cloud.push(storage);
      }
    });

    return grouped;
  };

  const hasMultipleDevices = (storageType, partitions) => {
    if (storageType !== 'usb' && storageType !== 'nvme') {
      return false;
    }
    return partitions.length > 1;
  };

  const handleMount = async (role, storage, deviceIdentifier) => {
    try {
      await api.post('/tools/mount', { 
        role, 
        storage, 
        deviceIdentifierPreset: deviceIdentifier || '' 
      });
      setMessage(t('tools.mount.b') || 'Mount successful');
      setMessageSeverity('success');
      loadMounts();
    } catch (error) {
      console.error('Failed to mount:', error);
      setMessage(error.response?.data?.error || 'Mount failed');
      setMessageSeverity('error');
    }
  };

  const handleUmount = async (role, storage) => {
    try {
      await api.post('/tools/umount', { role, storage });
      setMessage(t('tools.umount_b') || 'Unmount successful');
      setMessageSeverity('success');
      loadMounts();
    } catch (error) {
      console.error('Failed to umount:', error);
      setMessage(error.response?.data?.error || 'Unmount failed');
      setMessageSeverity('error');
    }
  };

  const handleSourceMount = async () => {
    if (!selectedSource) {
      setMessage('Please select a source storage');
      setMessageSeverity('error');
      return;
    }

    const deviceIdentifier = hasMultipleDevices(selectedSource, sourcePartitions) 
      ? sourcePartition 
      : '';
    
    if (isMounted('source', selectedSource)) {
      await handleUmount('source', selectedSource);
    } else {
      await handleMount('source', selectedSource, deviceIdentifier);
    }
  };

  const handleTargetMount = async () => {
    if (!selectedTarget) {
      setMessage('Please select a target storage');
      setMessageSeverity('error');
      return;
    }

    const deviceIdentifier = hasMultipleDevices(selectedTarget, targetPartitions) 
      ? targetPartition 
      : '';
    
    if (isMounted('target', selectedTarget)) {
      await handleUmount('target', selectedTarget);
    } else {
      await handleMount('target', selectedTarget, deviceIdentifier);
    }
  };

  const handleFsckCheck = async () => {
    if (fsckPartition === '-') {
      setMessage('Please select a partition');
      setMessageSeverity('error');
      return;
    }
    
    try {
      await api.post('/tools/fsck/check', { partition: fsckPartition });
      setMessage(t('tools.fsck_check_m') || 'Check initiated');
      setMessageSeverity('success');
    } catch (error) {
      console.error('Failed to execute fsck check:', error);
      setMessage(error.response?.data?.error || 'fsck check failed');
      setMessageSeverity('error');
    }
  };

  const handleFsckRepair = async () => {
    if (fsckPartition === '-') {
      setMessage('Please select a partition');
      setMessageSeverity('error');
      return;
    }
    
    setFsckRepairConfirmOpen(true);
  };

  const confirmFsckRepair = async () => {
    setFsckRepairConfirmOpen(false);
    
    try {
      await api.post('/tools/fsck/repair', { partition: fsckPartition });
      setMessage(t('tools.fsck_autorepair_m') || 'Repair initiated');
      setMessageSeverity('success');
    } catch (error) {
      console.error('Failed to execute fsck repair:', error);
      setMessage(error.response?.data?.error || 'fsck repair failed');
      setMessageSeverity('error');
    }
  };

  const handleFormat = () => {
    if (formatPartition === '-' || formatFstype === '-') {
      setMessage('Please select partition and filesystem type');
      setMessageSeverity('error');
      return;
    }
    
    setFormatConfirmOpen(true);
  };

  const confirmFormat = async () => {
    setFormatConfirmOpen(false);
    
    try {
      await api.post('/tools/format', { 
        partition: formatPartition, 
        fstype: formatFstype 
      });
      setMessage(t('tools.format_b') || 'Format initiated');
      setMessageSeverity('success');
    } catch (error) {
      console.error('Failed to execute format:', error);
      setMessage(error.response?.data?.error || 'Format failed');
      setMessageSeverity('error');
    }
  };

  const handleF3 = () => {
    if (f3Device === '-' || f3Action === '-') {
      setMessage('Please select device and action');
      setMessageSeverity('error');
      return;
    }
    
    setF3ConfirmOpen(true);
  };

  const confirmF3 = async () => {
    setF3ConfirmOpen(false);
    
    try {
      await api.post('/tools/f3', { 
        device: f3Device, 
        action: f3Action 
      });
      setMessage(t('tools.f3.b') || 'F3 test initiated');
      setMessageSeverity('success');
    } catch (error) {
      console.error('Failed to execute f3:', error);
      setMessage(error.response?.data?.error || 'F3 test failed');
      setMessageSeverity('error');
    }
  };


  const filesystemTypes = [
    { value: 'FAT32', label: 'FAT32 (Windows®)' },
    { value: 'exFAT', label: 'exFAT (Windows®)' },
    { value: 'NTFS (compression enabled)', label: 'NTFS (compression enabled) (Windows®)' },
    { value: 'NTFS (no compression)', label: 'NTFS (no compression) (Windows®)' },
    { value: 'Ext4', label: 'Ext4 (Linux)' },
    { value: 'Ext3', label: 'Ext3 (Linux)' },
    { value: 'HFS Plus', label: 'HFS Plus (Mac)' },
    { value: 'HFS', label: 'HFS (Mac)' },
  ];

  const f3Actions = [
    { value: 'f3probe_non_destructive', label: t('tools.f3.probe_non_destructive') || 'try to preserve data (slow)' },
    { value: 'f3probe_destructive', label: t('tools.f3.probe_destructive') || 'ERASE data (faster)' },
  ];

  function TabPanel({ children, value, index, ...other }) {
    // Add bottom padding when refresh button is sticky (all tabs)
    const needsBottomPadding = value === index;
    return (
      <div
        role="tabpanel"
        hidden={value !== index}
        id={`filesystem-tabpanel-${index}`}
        aria-labelledby={`filesystem-tab-${index}`}
        {...other}
      >
        {value === index && <Box sx={{ pt: 3, pb: needsBottomPadding ? 10 : 0 }}>{children}</Box>}
      </div>
    );
  }

  return (
    <Box>
      {message && (
        <Alert 
          severity={messageSeverity}
          sx={{ mb: 3 }}
          onClose={() => setMessage('')}
        >
          {message}
        </Alert>
      )}

      <Box sx={{ borderBottom: 1, borderColor: 'divider', mb: 3 }}>
        <Tabs value={currentTab} onChange={handleTabChange} aria-label="filesystem tabs">
          <Tab 
            label={t('sysinfo.info') || 'Info'} 
            id="filesystem-tab-0"
            aria-controls="filesystem-tabpanel-0"
          />
          <Tab 
            label={t('tools.mount.header') || 'Mount storage'} 
            id="filesystem-tab-1"
            aria-controls="filesystem-tabpanel-1"
          />
          <Tab 
            label={t('tools.repair') || 'Repair'} 
            id="filesystem-tab-2"
            aria-controls="filesystem-tabpanel-2"
          />
          <Tab 
            label={t('cmd.format.header') || 'Format device'} 
            id="filesystem-tab-3"
            aria-controls="filesystem-tabpanel-3"
          />
          <Tab 
            label={t('cmd.f3.header') || 'Verify USB drive capacity'} 
            id="filesystem-tab-4"
            aria-controls="filesystem-tabpanel-4"
          />
        </Tabs>
      </Box>

      <TabPanel value={currentTab} index={0}>
        <Grid container spacing={3}>
          <Grid item xs={12}>
            <Box>
              <Typography variant="h2" gutterBottom>
                {t('sysinfo.diskspace') || 'Disk Space'}
              </Typography>
              <Box sx={{ mt: 2 }}>
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
              </Box>
            </Box>
          </Grid>

          <Grid item xs={12}>
            <Box>
              <Typography variant="h2" gutterBottom>
                {t('sysinfo.devices') || 'Devices'}
              </Typography>
              <Box sx={{ mt: 2 }}>
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
              </Box>
            </Box>
          </Grid>

          <Grid item xs={12}>
            <Box>
              <Typography variant="h2" gutterBottom>
                {t('sysinfo.device_states') || 'Device status'}
              </Typography>
              <Box sx={{ mt: 2 }}>
                {deviceStates.length > 0 ? (
                <Box>
                  {deviceStates.map((device, deviceIndex) => (
                    <Box key={deviceIndex} sx={{ mb: 2 }}>
                      <Typography variant="h3" sx={{ fontWeight: 'bold', mb: 1 }}>
                        {device.lum}
                        {device.identifier && (
                          <Typography component="span" variant="body2" sx={{ ml: 1, color: 'text.secondary' }}>
                            ({device.identifier})
                          </Typography>
                        )}
                      </Typography>
                      {device.states && device.states.length > 0 ? (
                        <Box component="pre" sx={{ 
                          fontFamily: 'monospace', 
                          fontSize: '0.75rem',
                          whiteSpace: 'pre-wrap',
                          wordBreak: 'break-word',
                          m: 0,
                          p: 1,
                          bgcolor: 'background.default',
                          borderRadius: 1,
                        }}>
                          {device.states.map((state, stateIndex) => {
                            const paddedValue = String(state.value || '').padEnd(35);
                            return (
                              <Box key={stateIndex} component="span" sx={{ display: 'block' }}>
                                {paddedValue}{state.measured}
                              </Box>
                            );
                          })}
                        </Box>
                      ) : (
                        <Typography variant="body2" color="text.secondary">
                          -
                        </Typography>
                      )}
                      {deviceIndex < deviceStates.length - 1 && <Divider sx={{ mt: 2 }} />}
                    </Box>
                  ))}
                </Box>
              ) : (
                <Typography variant="body2" color="text.secondary">
                  {deviceStates.length === 0 ? 'No device states available' : 'Loading...'}
                </Typography>
              )}
              </Box>
            </Box>
          </Grid>
        </Grid>
        <LogMonitor />
      </TabPanel>

      <TabPanel value={currentTab} index={1}>
        <Box>
          <Typography variant="h2" gutterBottom>
            {t('tools.mount.header') || 'Mount storage'}
          </Typography>
        <Box sx={{ mt: 2 }}>
          <Paper elevation={0} sx={{ mb: 3, p: 2, bgcolor: 'background.default' }}>
          <Typography 
            component="pre" 
            variant="body2" 
            sx={{
              fontFamily: 'monospace',
              whiteSpace: 'pre-wrap',
              margin: 0,
              fontSize: '0.875rem',
            }}
          >
            {mounts || 'No mounts'}
          </Typography>
        </Paper>
        
        <Grid container spacing={3}>
          <Grid item xs={12} md={6}>
            <FormControl fullWidth>
              <FormLabel>{t('tools.mount.source') || 'Source'}</FormLabel>
              <Select
                value={selectedSource}
                onChange={(e) => {
                  setSelectedSource(e.target.value);
                  setSourcePartition('');
                }}
              >
                <MenuItem value="">
                  <em>None</em>
                </MenuItem>
                {(() => {
                  const grouped = groupStoragesByType(mountableStorages);
                  const result = [];
                  
                  if (grouped.usb.length > 0) {
                    result.push(
                      <ListSubheader key="header-usb">
                        {getGroupLabel('usb', t)}
                      </ListSubheader>
                    );
                    grouped.usb.forEach(storage => {
                      const mounted = isMounted('source', storage);
                      result.push(
                        <MenuItem key={storage} value={storage}>
                          {getStorageLabel(storage)}
                          {mounted && ` (${t('tools.mount.mounted') || 'mounted'})`}
                        </MenuItem>
                      );
                    });
                  }
                  
                  if (grouped.nvme.length > 0) {
                    result.push(
                      <ListSubheader key="header-nvme">
                        {getGroupLabel('nvme', t)}
                      </ListSubheader>
                    );
                    grouped.nvme.forEach(storage => {
                      const mounted = isMounted('source', storage);
                      result.push(
                        <MenuItem key={storage} value={storage}>
                          {getStorageLabel(storage)}
                          {mounted && ` (${t('tools.mount.mounted') || 'mounted'})`}
                        </MenuItem>
                      );
                    });
                  }
                  
                  if (grouped.cloud.length > 0) {
                    result.push(
                      <ListSubheader key="header-cloud">
                        {getGroupLabel('cloud', t)}
                      </ListSubheader>
                    );
                    grouped.cloud.forEach(storage => {
                      const mounted = isMounted('source', storage);
                      result.push(
                        <MenuItem key={storage} value={storage}>
                          {getStorageLabel(storage)}
                          {mounted && ` (${t('tools.mount.mounted') || 'mounted'})`}
                        </MenuItem>
                      );
                    });
                  }
                  
                  return result;
                })()}
              </Select>
            </FormControl>
            
            {selectedSource && hasMultipleDevices(selectedSource, sourcePartitions) && (
              <FormControl fullWidth sx={{ mt: 2 }}>
                <FormLabel>{t('tools.mount.select_partition_label') || 'Partition'}</FormLabel>
                <Select
                  value={sourcePartition}
                  onChange={(e) => setSourcePartition(e.target.value)}
                >
                  <MenuItem value="">
                    {t('main.backup.preset_partition_auto') || 'automatic selection'}
                  </MenuItem>
                  {sourcePartitions.map((partition, i) => (
                    <MenuItem key={i} value={partition.identifier || partition.lum}>
                      {partition.lum} {partition.identifier ? `(${partition.identifier})` : ''}
                    </MenuItem>
                  ))}
                </Select>
              </FormControl>
            )}
            
            <Button
              variant={selectedSource && isMounted('source', selectedSource) ? 'outlined' : 'contained'}
              color={selectedSource?.startsWith('cloud:') ? 'primary' : 'secondary'}
              fullWidth
              sx={{ mt: 2 }}
              onClick={handleSourceMount}
              disabled={!selectedSource}
            >
              {selectedSource && isMounted('source', selectedSource)
                ? `${t('tools.umount_b') || 'Remove'}: ${getStorageLabel(selectedSource)} ${t('tools.mount.source') || 'source'}`
                : `${t('tools.mount.b') || 'Mount'}: ${selectedSource ? getStorageLabel(selectedSource) : ''} ${t('tools.mount.source') || 'source'}`
              }
            </Button>
          </Grid>

          <Grid item xs={12} md={6}>
            <FormControl fullWidth>
              <FormLabel>{t('tools.mount.target') || 'Target'}</FormLabel>
              <Select
                value={selectedTarget}
                onChange={(e) => {
                  setSelectedTarget(e.target.value);
                  setTargetPartition('');
                }}
              >
                <MenuItem value="">
                  <em>None</em>
                </MenuItem>
                {(() => {
                  const grouped = groupStoragesByType(mountableStorages);
                  const result = [];
                  
                  if (grouped.usb.length > 0) {
                    result.push(
                      <ListSubheader key="header-usb">
                        {getGroupLabel('usb', t)}
                      </ListSubheader>
                    );
                    grouped.usb.forEach(storage => {
                      const mounted = isMounted('target', storage);
                      result.push(
                        <MenuItem key={storage} value={storage}>
                          {getStorageLabel(storage)}
                          {mounted && ` (${t('tools.mount.mounted') || 'mounted'})`}
                        </MenuItem>
                      );
                    });
                  }
                  
                  if (grouped.nvme.length > 0) {
                    result.push(
                      <ListSubheader key="header-nvme">
                        {getGroupLabel('nvme', t)}
                      </ListSubheader>
                    );
                    grouped.nvme.forEach(storage => {
                      const mounted = isMounted('target', storage);
                      result.push(
                        <MenuItem key={storage} value={storage}>
                          {getStorageLabel(storage)}
                          {mounted && ` (${t('tools.mount.mounted') || 'mounted'})`}
                        </MenuItem>
                      );
                    });
                  }
                  
                  if (grouped.cloud.length > 0) {
                    result.push(
                      <ListSubheader key="header-cloud">
                        {getGroupLabel('cloud', t)}
                      </ListSubheader>
                    );
                    grouped.cloud.forEach(storage => {
                      const mounted = isMounted('target', storage);
                      result.push(
                        <MenuItem key={storage} value={storage}>
                          {getStorageLabel(storage)}
                          {mounted && ` (${t('tools.mount.mounted') || 'mounted'})`}
                        </MenuItem>
                      );
                    });
                  }
                  
                  return result;
                })()}
              </Select>
            </FormControl>
            
            {selectedTarget && hasMultipleDevices(selectedTarget, targetPartitions) && (
              <FormControl fullWidth sx={{ mt: 2 }}>
                <FormLabel>{t('tools.mount.select_partition_label') || 'Partition'}</FormLabel>
                <Select
                  value={targetPartition}
                  onChange={(e) => setTargetPartition(e.target.value)}
                >
                  <MenuItem value="">
                    {t('main.backup.preset_partition_auto') || 'automatic selection'}
                  </MenuItem>
                  {targetPartitions.map((partition, i) => (
                    <MenuItem key={i} value={partition.identifier || partition.lum}>
                      {partition.lum} {partition.identifier ? `(${partition.identifier})` : ''}
                    </MenuItem>
                  ))}
                </Select>
              </FormControl>
            )}
            
            <Button
              variant={selectedTarget && isMounted('target', selectedTarget) ? 'outlined' : 'contained'}
              color={selectedTarget?.startsWith('cloud:') ? 'primary' : 'secondary'}
              fullWidth
              sx={{ mt: 2 }}
              onClick={handleTargetMount}
              disabled={!selectedTarget}
            >
              {selectedTarget && isMounted('target', selectedTarget)
                ? `${t('tools.umount_b') || 'Remove'}: ${getStorageLabel(selectedTarget)} ${t('tools.mount.target') || 'target'}`
                : `${t('tools.mount.b') || 'Mount'}: ${selectedTarget ? getStorageLabel(selectedTarget) : ''} ${t('tools.mount.target') || 'target'}`
              }
            </Button>
          </Grid>
        </Grid>
        </Box>
        </Box>
        <LogMonitor />
      </TabPanel>

      <TabPanel value={currentTab} index={2}>
        <Box>
          <Typography variant="h2" gutterBottom>
            {t('tools.repair') || 'Repair'}
          </Typography>
        <Box sx={{ mt: 2 }}>
          <Alert severity="warning" sx={{ mb: 2 }}>
          {t('cmd.fsck.warning') || 'Do not use unless you know what you are doing. It could damage your storage!'}
        </Alert>
        <Box sx={{ mb: 2 }}>
          <Typography variant="h3" gutterBottom>
            Procedure:
          </Typography>
          <Box component="ol" sx={{ pl: 3, mb: 0 }}>
            <li>Check your power supply. Power interruption may damage storage beyond repair.</li>
            <li>Make sure that no backup job is running.</li>
            <li>Do not click any buttons until all check and repair operations are completed. Do not start any processes!</li>
            <li>Run filesystem check first.</li>
            <li>If you see any errors in the log, run filesystem repair.</li>
          </Box>
        </Box>
        <Box sx={{ display: 'flex', flexDirection: 'column', gap: 2 }}>
          <DeviceSelector
            listPartitions={true}
            skipMounted={false}
            ignoreFs={false}
            value={fsckPartition}
            onChange={setFsckPartition}
            label={t('tools.select_partition') || 'Select partition'}
          />
          <Box sx={{ display: 'flex', gap: 2 }}>
            <Button
              variant="outlined"
              onClick={handleFsckCheck}
              disabled={fsckPartition === '-'}
            >
              {t('tools.fsck_check_b') || 'Check'}
            </Button>
            <Button
              variant="contained"
              color="error"
              onClick={handleFsckRepair}
              disabled={fsckPartition === '-'}
            >
              {t('tools.fsck_autorepair_b') || 'Repair'}
            </Button>
          </Box>
        </Box>
        </Box>
        </Box>
        <LogMonitor />
      </TabPanel>

      <TabPanel value={currentTab} index={3}>
        <Box>
          <Typography variant="h2" gutterBottom>
            {t('cmd.format.header') || 'Format device'}
          </Typography>
        <Box sx={{ mt: 2 }}>
          <Alert severity="warning" sx={{ mb: 2 }}>
          {t('cmd.format.warning') || 'Do not use this function if you do not know what you are doing. This will erase your storage and any lost data cannot be recovered.'}
        </Alert>
        <Box sx={{ mb: 2 }}>
          <Typography variant="h3" gutterBottom>
            Options:
          </Typography>
          <Box component="ul" sx={{ pl: 3, mb: 0 }}>
            <li><strong>FAT32 (Windows®)</strong>: Widespread, file size is limited to 4GB.</li>
            <li><strong>exFAT (Windows®)</strong>: Less common, no file size limit.</li>
            <li><strong>NTFS (Windows®)</strong>: Limited compatibility with many devices, no file size limitation.
              <Box component="ul" sx={{ pl: 3, mt: 0.5 }}>
                <li>NTFS (compression enabled): Saves disk space, but can significantly reduce performance.</li>
                <li>NTFS (no compression)</li>
              </Box>
            </li>
            <li><strong>Ext4 (Linux)</strong>: Current Linux file system</li>
            <li><strong>Ext3 (Linux)</strong>: Predecessor of Ext4, only recommended if the memory also needs to be compatible with older devices that do not yet support Ext4.</li>
          </Box>
        </Box>
        <Box sx={{ display: 'flex', flexDirection: 'column', gap: 2 }}>
          <DeviceSelector
            listPartitions={true}
            skipMounted={false}
            ignoreFs={true}
            value={formatPartition}
            onChange={setFormatPartition}
            label={t('tools.select_partition') || 'Select partition'}
          />
          <FormControl sx={{ maxWidth: 400 }}>
            <InputLabel>{t('tools.select_format_fstype') || 'File system type'}</InputLabel>
            <Select
              value={formatFstype}
              onChange={(e) => setFormatFstype(e.target.value)}
              label={t('tools.select_format_fstype') || 'File system type'}
            >
              <MenuItem value="-">-</MenuItem>
              {filesystemTypes.map((type) => (
                <MenuItem key={type.value} value={type.value}>
                  {type.label}
                </MenuItem>
              ))}
            </Select>
          </FormControl>
          <Box sx={{ display: 'flex', gap: 2 }}>
            <Button
              variant="contained"
              color="error"
              onClick={handleFormat}
              disabled={formatPartition === '-' || formatFstype === '-'}
            >
              {t('tools.format_b') || 'format disc!'}
            </Button>
          </Box>
        </Box>
        </Box>
        </Box>
        <LogMonitor />
      </TabPanel>

      <TabPanel value={currentTab} index={4}>
        <Box>
          <Typography variant="h2" gutterBottom>
            {t('cmd.f3.header') || 'Verify USB drive capacity'}
          </Typography>
        <Box sx={{ mt: 2 }}>
          <Alert severity="warning" sx={{ mb: 2 }}>
          {t('cmd.f3.warning_non_destructive') || 'Do not use this function if you do not know what you are doing. This may erase your storage, and lost data cannot be recovered.'}
        </Alert>
        <Box sx={{ mb: 2 }}>
          <Typography variant="h3" gutterBottom>
            Options:
          </Typography>
          <Box component="ul" sx={{ pl: 3, mb: 0 }}>
            <li><strong>ERASE data (faster)</strong>: f3probe runs without attempting to keep the data.</li>
            <li><strong>try to preserve data (slow)</strong>: f3probe is running, attempting to preserve the data.</li>
          </Box>
        </Box>
        <Box sx={{ display: 'flex', flexDirection: 'column', gap: 2 }}>
          <DeviceSelector
            listPartitions={false}
            skipMounted={false}
            ignoreFs={false}
            value={f3Device}
            onChange={setF3Device}
            label={t('tools.select_partition') || 'Select partition'}
          />
          <FormControl sx={{ maxWidth: 400 }}>
            <InputLabel>{t('tools.f3.select_action') || 'Select test'}</InputLabel>
            <Select
              value={f3Action}
              onChange={(e) => setF3Action(e.target.value)}
              label={t('tools.f3.select_action') || 'Select test'}
            >
              <MenuItem value="-">-</MenuItem>
              {f3Actions.map((action) => (
                <MenuItem key={action.value} value={action.value}>
                  {action.label}
                </MenuItem>
              ))}
            </Select>
          </FormControl>
          <Box sx={{ display: 'flex', gap: 2 }}>
            <Button
              variant="contained"
              color="error"
              onClick={handleF3}
              disabled={f3Device === '-' || f3Action === '-'}
            >
              {t('tools.f3.b') || 'start test'}
            </Button>
          </Box>
        </Box>
        </Box>
        </Box>
        <LogMonitor />
      </TabPanel>

      <Dialog open={formatConfirmOpen} onClose={() => setFormatConfirmOpen(false)}>
        <DialogTitle>{t('cmd.format.header') || 'Format device'}</DialogTitle>
        <DialogContent>
          <DialogContentText>
            {t('cmd.format.warning') || 'Danger! This will erase all data on the selected device!'}
            <br />
            <strong>{formatPartition} → {formatFstype}</strong>
          </DialogContentText>
        </DialogContent>
        <DialogActions>
          <Button onClick={() => setFormatConfirmOpen(false)}>
            {t('main.cancel_button') || 'Cancel'}
          </Button>
          <Button onClick={confirmFormat} color="error" variant="contained">
            {t('menu.yes') || 'Yes'}
          </Button>
        </DialogActions>
      </Dialog>

      <Dialog open={f3ConfirmOpen} onClose={() => setF3ConfirmOpen(false)}>
        <DialogTitle>{t('cmd.f3.header') || 'Check USB device for authenticity'}</DialogTitle>
        <DialogContent>
          <DialogContentText>
            {f3Action === 'f3probe_destructive' 
              ? (t('cmd.f3.warning_destructive') || 'Attention, ALL DATA on the data medium will be deleted!')
              : (t('cmd.f3.warning_non_destructive') || 'Attention, ALL DATA on the data medium could be deleted!')
            }
            <br />
            <strong>{f3Device}</strong>
          </DialogContentText>
        </DialogContent>
        <DialogActions>
          <Button onClick={() => setF3ConfirmOpen(false)}>
            {t('main.cancel_button') || 'Cancel'}
          </Button>
          <Button onClick={confirmF3} color="error" variant="contained">
            {t('menu.yes') || 'Yes'}
          </Button>
        </DialogActions>
      </Dialog>

      <Dialog open={fsckRepairConfirmOpen} onClose={() => setFsckRepairConfirmOpen(false)}>
        <DialogTitle>{t('cmd.fsck.header') || 'File system check'}</DialogTitle>
        <DialogContent>
          <DialogContentText>
            {t('cmd.fsck.warning') || 'The file system could be damaged!'}
            <br />
            <strong>{fsckPartition}</strong>
          </DialogContentText>
        </DialogContent>
        <DialogActions>
          <Button onClick={() => setFsckRepairConfirmOpen(false)}>
            {t('main.cancel_button') || 'Cancel'}
          </Button>
          <Button onClick={confirmFsckRepair} color="error" variant="contained">
            {t('menu.yes') || 'Yes'}
          </Button>
        </DialogActions>
      </Dialog>

      <Box
        sx={{
          position: 'fixed',
          bottom: 0,
          left: { md: `${currentDrawerWidth}px` },
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
        }}
      >
        <Button
          variant="contained"
          startIcon={<RefreshIcon />}
          onClick={loadAll}
          size="large"
        >
          {t('sysinfo.refresh_button') || 'Refresh'}
        </Button>
      </Box>
    </Box>
  );
}

export default Filesystem;
