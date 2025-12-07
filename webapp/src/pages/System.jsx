import React, { useState, useEffect } from 'react';
import {
  Box,
  Typography,
  Table,
  TableBody,
  TableCell,
  TableRow,
  Button,
  Grid,
  Chip,
  IconButton,
  Tooltip,
  Accordion,
  AccordionSummary,
  AccordionDetails,
} from '@mui/material';
import RefreshIcon from '@mui/icons-material/Refresh';
import ContentCopyIcon from '@mui/icons-material/ContentCopy';
import ExpandMoreIcon from '@mui/icons-material/ExpandMore';
import { useLanguage } from '../contexts/LanguageContext';
import { useDrawer } from '../contexts/DrawerContext';
import { drawerWidth, drawerCollapsedWidth } from '../components/Menu';
import api from '../utils/api';

function System() {
  const { t } = useLanguage();
  const { desktopOpen } = useDrawer();
  const [systemInfo, setSystemInfo] = useState(null);
  const [cameras, setCameras] = useState([]);
  const [copiedText, setCopiedText] = useState('');
  const [expandedCameras, setExpandedCameras] = useState({});
  const currentDrawerWidth = desktopOpen ? drawerWidth : drawerCollapsedWidth;

  useEffect(() => {
    // Load accordion states from localStorage
    const savedStates = localStorage.getItem('accordion-sysinfo-cameras');
    if (savedStates !== null) {
      try {
        setExpandedCameras(JSON.parse(savedStates));
      } catch (e) {
        console.error('Failed to parse saved camera accordion states:', e);
      }
    }
    
    loadAll();
  }, []);

  const handleCameraAccordionChange = (index, isExpanded) => {
    const newExpanded = { ...expandedCameras, [index]: isExpanded };
    setExpandedCameras(newExpanded);
    localStorage.setItem('accordion-sysinfo-cameras', JSON.stringify(newExpanded));
  };

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


  return (
    <Box>
      <Grid container spacing={3}>
        {systemInfo && (
          <Grid item xs={12}>
            <Typography variant="h2" gutterBottom>
              {t('sysinfo.system') || 'System'}
            </Typography>
            <Box sx={{ mt: 2 }}>
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
            </Box>
          </Grid>
        )}

        <Grid item xs={12}>
          <Typography variant="h2" gutterBottom>
            {t('sysinfo.cameras') || 'Cameras/smartphones'}
          </Typography>
          <Box sx={{ mt: 2 }}>
            {cameras.length > 0 ? (
              cameras.map((camera, i) => (
                <Accordion 
                  key={i}
                  expanded={expandedCameras[i] || false}
                  onChange={(event, isExpanded) => handleCameraAccordionChange(i, isExpanded)}
                >
                  <AccordionSummary
                    expandIcon={<ExpandMoreIcon />}
                    aria-controls={`camera-${i}-content`}
                    id={`camera-${i}-header`}
                  >
                    <Box sx={{ display: 'flex', flexDirection: 'column', width: '100%' }}>
                      <Typography variant="body1" sx={{ fontWeight: 'medium' }}>
                        {camera.model}
                      </Typography>
                      <Typography variant="body2" color="text.secondary">
                        {camera.port}
                      </Typography>
                    </Box>
                  </AccordionSummary>
                  <AccordionDetails>
                    {camera.serial && (
                      <Box sx={{ mb: 2 }}>
                        <Typography variant="h3" sx={{ fontWeight: 'bold', mb: 0.5 }}>
                          {t('sysinfo.camera_serial') || 'Serial number'}:
                        </Typography>
                        <Typography variant="body2">
                          {camera.serial}
                        </Typography>
                      </Box>
                    )}
                    {camera.storages && camera.storages.length > 0 && (
                      <Box>
                        <Typography variant="h3" sx={{ fontWeight: 'bold', mb: 1 }}>
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
                  </AccordionDetails>
                </Accordion>
              ))
            ) : (
              <Typography variant="body2" color="text.secondary">
                No cameras detected
              </Typography>
            )}
          </Box>
        </Grid>

      </Grid>

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

export default System;
