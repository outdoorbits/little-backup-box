import React, { useState, useEffect } from 'react';
import {
  Card,
  CardContent,
  Typography,
  Button,
  Stack,
  Alert,
  CircularProgress,
  Accordion,
  AccordionSummary,
  AccordionDetails,
  FormControl,
  FormLabel,
  Select,
  MenuItem,
  FormControlLabel,
  Checkbox,
  Box,
  Divider,
} from '@mui/material';
import ExpandMoreIcon from '@mui/icons-material/ExpandMore';
import ArrowForwardIcon from '@mui/icons-material/ArrowForward';
import WarningIcon from '@mui/icons-material/Warning';
import { useLanguage } from '../contexts/LanguageContext';
import api from '../utils/api';

function FileOperations() {
  const { t } = useLanguage();
  const [partitions, setPartitions] = useState([]);
  const [loading, setLoading] = useState(false);
  const [message, setMessage] = useState('');
  const [target, setTarget] = useState('usb');
  const [presetSource, setPresetSource] = useState('');
  const [presetTarget, setPresetTarget] = useState('');
  const [powerOff, setPowerOff] = useState(false);
  const [nvmeAvailable, setNvmeAvailable] = useState(false);

  useEffect(() => {
    loadPartitions();
    checkNVMe();
  }, []);

  const loadPartitions = async () => {
    try {
      const response = await api.get('/backup/partitions');
      setPartitions(response.data?.partitions || []);
    } catch (error) {
      console.error('Failed to load partitions:', error);
    }
  };

  const checkNVMe = async () => {
    try {
      const response = await api.get('/backup/services');
      setNvmeAvailable(response.data?.nvmeAvailable || false);
    } catch (error) {
      console.error('Failed to check NVMe availability:', error);
    }
  };

  const handleRename = async () => {
    setLoading(true);
    setMessage('');
    try {
      await api.post('/backup/function', {
        function: 'rename',
        target,
        presetSource,
        presetTarget,
        powerOff,
      });
      setMessage(t('maintenance.file.rename_started') || 'Rename files operation started');
    } catch (error) {
      console.error('Failed to start rename:', error);
      setMessage(t('maintenance.file.rename_error') || 'Failed to start rename operation');
    } finally {
      setLoading(false);
    }
  };

  const getTargetLabel = (targetType) => {
    const labels = {
      usb: t('box.backup.mode.usb') || 'USB storage',
      internal: t('box.backup.mode.internal') || 'Int. storage',
      nvme: t('box.backup.mode.nvme') || 'NVMe SSD',
    };
    return labels[targetType] || targetType;
  };

  return (
    <Card>
      <CardContent>
        <Accordion>
          <AccordionSummary expandIcon={<ExpandMoreIcon />}>
            <Typography variant="h5">
              {t('main.file_operations') || 'File Operations'}
            </Typography>
          </AccordionSummary>
          <AccordionDetails>
            {message && (
              <Alert 
                severity={message.includes('error') || message.includes('Error') ? 'error' : 'success'} 
                sx={{ mb: 2 }}
                onClose={() => setMessage('')}
              >
                {message}
              </Alert>
            )}

            <Alert severity="warning" icon={<WarningIcon />} sx={{ mb: 2 }}>
              {t('config.backup.rename.warning') || 'Attention! The files should then be deleted from the source. Otherwise they will be transferred again during the next backup, renamed and replacing the version from the previous backup.'}
            </Alert>

            <Stack spacing={3}>
              <FormControl fullWidth>
                <FormLabel>{t('maintenance.file.target') || 'Target Storage'}</FormLabel>
                <Select
                  value={target}
                  onChange={(e) => setTarget(e.target.value)}
                  disabled={loading}
                >
                  <MenuItem value="usb">{getTargetLabel('usb')}</MenuItem>
                  <MenuItem value="internal">{getTargetLabel('internal')}</MenuItem>
                  {nvmeAvailable && <MenuItem value="nvme">{getTargetLabel('nvme')}</MenuItem>}
                </Select>
              </FormControl>

              <FormControl fullWidth>
                <FormLabel>{t('main.backup.preset_source_label') || 'Set source partition'}</FormLabel>
                <Select
                  value={presetSource}
                  onChange={(e) => setPresetSource(e.target.value)}
                  disabled={loading}
                >
                  <MenuItem value="">{t('main.backup.preset_partition_auto') || 'automatic selection'}</MenuItem>
                  {partitions.map((p, i) => (
                    <MenuItem key={i} value={p.identifier}>
                      {p.lum} {p.identifier ? `(${p.identifier})` : ''}
                    </MenuItem>
                  ))}
                </Select>
              </FormControl>

              <FormControl fullWidth>
                <FormLabel>{t('main.backup.preset_target_label') || 'Set target partition'}</FormLabel>
                <Select
                  value={presetTarget}
                  onChange={(e) => setPresetTarget(e.target.value)}
                  disabled={loading}
                >
                  <MenuItem value="">{t('main.backup.preset_partition_auto') || 'automatic selection'}</MenuItem>
                  {partitions.map((p, i) => (
                    <MenuItem key={i} value={p.identifier}>
                      {p.lum} {p.identifier ? `(${p.identifier})` : ''}
                    </MenuItem>
                  ))}
                </Select>
              </FormControl>

              <FormControlLabel
                control={
                  <Checkbox
                    checked={powerOff}
                    onChange={(e) => setPowerOff(e.target.checked)}
                    disabled={loading}
                  />
                }
                label={t('main.backup.power_off_checkbox_label') || 'Turn off after run'}
              />

              <Divider />

              <Box>
                <Typography variant="h6" gutterBottom>
                  {t('config.backup.rename.header') || 'Rename files'}
                </Typography>
                <Typography variant="body2" color="text.secondary" sx={{ mb: 2 }}>
                  {t('config.backup.rename.desc') || 'An attempt is made to read the creation date from all media files on local storage devices. If this is possible, the file is renamed according to the following pattern: For example, "Image0123.jpg" becomes "2024-10-18_23-47-26_-_Image.jpg".'}
                </Typography>
                <Button
                  variant="outlined"
                  color="error"
                  startIcon={loading ? <CircularProgress size={20} /> : <ArrowForwardIcon />}
                  onClick={handleRename}
                  disabled={loading}
                >
                  {t('box.backup.mode.rename') || 'Rename'} → {getTargetLabel(target)}
                </Button>
              </Box>
            </Stack>
          </AccordionDetails>
        </Accordion>
      </CardContent>
    </Card>
  );
}

export default FileOperations;

