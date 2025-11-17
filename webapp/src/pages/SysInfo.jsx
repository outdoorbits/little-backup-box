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
  Button,
  Grid,
  List,
  ListItem,
  ListItemText,
  Divider,
  Chip,
  IconButton,
  Tooltip,
  Collapse,
} from '@mui/material';
import RefreshIcon from '@mui/icons-material/Refresh';
import ContentCopyIcon from '@mui/icons-material/ContentCopy';
import ExpandMoreIcon from '@mui/icons-material/ExpandMore';
import ExpandLessIcon from '@mui/icons-material/ExpandLess';
import { useLanguage } from '../contexts/LanguageContext';
import api from '../utils/api';

function SysInfo() {
  const { t } = useLanguage();
  const [systemInfo, setSystemInfo] = useState(null);
  const [cameras, setCameras] = useState([]);
  const [expandedCameras, setExpandedCameras] = useState({});
  const [copiedText, setCopiedText] = useState('');

  useEffect(() => {
    loadAll();
  }, []);

  const loadAll = () => {
    loadSystemInfo();
    loadCameras();
  };

  const loadSystemInfo = async () => {
    try {
      const response = await api.get('/sysinfo/system');
      setSystemInfo(response.data);
    } catch (error) {
      console.error('Failed to load system info:', error);
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

  const handleCopyToClipboard = async (text) => {
    try {
      await navigator.clipboard.writeText(text);
      setCopiedText(text);
      setTimeout(() => setCopiedText(''), 2000);
    } catch (error) {
      console.error('Failed to copy to clipboard:', error);
    }
  };

  const toggleCameraExpanded = (index) => {
    setExpandedCameras(prev => ({
      ...prev,
      [index]: !prev[index]
    }));
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
                {t('sysinfo.cameras') || 'Cameras/smartphones'}
              </Typography>
              <Divider sx={{ my: 2 }} />
              {cameras.length > 0 ? (
                <List disablePadding>
                  {cameras.map((camera, i) => {
                    const isExpanded = expandedCameras[i];
                    return (
                      <React.Fragment key={i}>
                        <ListItem 
                          disablePadding
                          sx={{ flexDirection: 'column', alignItems: 'stretch' }}
                        >
                          <Box sx={{ display: 'flex', alignItems: 'center', width: '100%' }}>
                            <ListItemText
                              primary={camera.model}
                              secondary={camera.port}
                              sx={{ flex: 1 }}
                            />
                            <IconButton
                              size="small"
                              onClick={() => toggleCameraExpanded(i)}
                            >
                              {isExpanded ? <ExpandLessIcon /> : <ExpandMoreIcon />}
                            </IconButton>
                          </Box>
                          <Collapse in={isExpanded} timeout="auto" unmountOnExit>
                            <Box sx={{ pl: 2, pr: 2, pb: 1 }}>
                              {camera.serial && (
                                <Box sx={{ mb: 2 }}>
                                  <Typography variant="subtitle2" sx={{ fontWeight: 'bold', mb: 0.5 }}>
                                    {t('sysinfo.camera_serial') || 'Serial number'}:
                                  </Typography>
                                  <Typography variant="body2">
                                    {camera.serial}
                                  </Typography>
                                </Box>
                              )}
                              {camera.storages && camera.storages.length > 0 && (
                                <Box>
                                  <Typography variant="subtitle2" sx={{ fontWeight: 'bold', mb: 1 }}>
                                    {t('sysinfo.camera_storages') || 'Storage paths'}:
                                  </Typography>
                                  {camera.storages.map((storage, storageIndex) => {
                                    const modelPattern = `${camera.model}:!${storage}`;
                                    const specificPattern = camera.serial 
                                      ? `${camera.model}_${camera.serial}:!${storage}`
                                      : null;
                                    return (
                                      <Box key={storageIndex} sx={{ mb: 2, p: 1, bgcolor: 'background.default', borderRadius: 1 }}>
                                        <Typography variant="body2" sx={{ mb: 1, fontFamily: 'monospace' }}>
                                          {storage}
                                        </Typography>
                                        <Box sx={{ ml: 1 }}>
                                          <Box sx={{ display: 'flex', alignItems: 'center', mb: 1 }}>
                                            <Typography variant="caption" sx={{ flex: 1 }}>
                                              {t('config.backup.camera.model_folders_header') || 'Folders to sync by camera model'}:
                                            </Typography>
                                            <Tooltip title={copiedText === modelPattern ? 'Copied!' : 'Copy to clipboard'}>
                                              <IconButton
                                                size="small"
                                                onClick={() => handleCopyToClipboard(modelPattern)}
                                              >
                                                <ContentCopyIcon fontSize="small" />
                                              </IconButton>
                                            </Tooltip>
                                          </Box>
                                          <Typography variant="body2" sx={{ fontFamily: 'monospace', mb: 1, ml: 1 }}>
                                            {modelPattern}
                                          </Typography>
                                          {specificPattern && (
                                            <>
                                              <Box sx={{ display: 'flex', alignItems: 'center', mb: 1 }}>
                                                <Typography variant="caption" sx={{ flex: 1 }}>
                                                  {t('config.backup.camera.specific_device_folders_header') || 'Folders to sync from specific camera'}:
                                                </Typography>
                                                <Tooltip title={copiedText === specificPattern ? 'Copied!' : 'Copy to clipboard'}>
                                                  <IconButton
                                                    size="small"
                                                    onClick={() => handleCopyToClipboard(specificPattern)}
                                                  >
                                                    <ContentCopyIcon fontSize="small" />
                                                  </IconButton>
                                                </Tooltip>
                                              </Box>
                                              <Typography variant="body2" sx={{ fontFamily: 'monospace', ml: 1 }}>
                                                {specificPattern}
                                              </Typography>
                                            </>
                                          )}
                                        </Box>
                                      </Box>
                                    );
                                  })}
                                </Box>
                              )}
                            </Box>
                          </Collapse>
                        </ListItem>
                        {i < cameras.length - 1 && <Divider />}
                      </React.Fragment>
                    );
                  })}
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
