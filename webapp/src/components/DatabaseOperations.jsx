import React, { useState, useEffect } from 'react';
import {
  Typography,
  Stack,
  Alert,
  Accordion,
  AccordionSummary,
  AccordionDetails,
  FormControl,
  FormLabel,
  Select,
  MenuItem,
  FormControlLabel,
  Checkbox,
} from '@mui/material';
import ExpandMoreIcon from '@mui/icons-material/ExpandMore';
import { useLanguage } from '../contexts/LanguageContext';
import { useConfig } from '../contexts/ConfigContext';
import api from '../utils/api';

function DatabaseOperations() {
  const { t } = useLanguage();
  const { constants } = useConfig();
  const [partitions, setPartitions] = useState([]);
  const [loading, setLoading] = useState(false);
  const [message, setMessage] = useState('');
  const [target, setTarget] = useState('usb');
  const [presetSource, setPresetSource] = useState('');
  const [presetTarget, setPresetTarget] = useState('');
  const [powerOff, setPowerOff] = useState(false);
  const [nvmeAvailable, setNvmeAvailable] = useState(false);
  const [accordionExpanded, setAccordionExpanded] = useState(false);

  useEffect(() => {
    // Load accordion state from localStorage
    const savedState = localStorage.getItem('accordion-database-operations');
    if (savedState !== null) {
      setAccordionExpanded(JSON.parse(savedState));
    }
    
    loadPartitions();
    checkNVMe();
  }, []);

  const handleAccordionChange = (event, isExpanded) => {
    setAccordionExpanded(isExpanded);
    localStorage.setItem('accordion-database-operations', JSON.stringify(isExpanded));
  };

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

  const handleOperation = async (operation) => {
    setLoading(true);
    setMessage('');
    try {
      await api.post('/backup/function', {
        function: operation,
        target,
        presetSource,
        presetTarget,
        powerOff,
      });
      setMessage(t('maintenance.database.operation_started') || `${operation} operation started`);
    } catch (error) {
      console.error(`Failed to start ${operation}:`, error);
      setMessage(t('maintenance.database.operation_error') || `Failed to start ${operation} operation`);
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
    <Accordion 
      expanded={accordionExpanded}
      onChange={handleAccordionChange}
    >
      <AccordionSummary expandIcon={<ExpandMoreIcon />}>
        <Typography variant="h5">
          {t('maintenance.database.section') || 'Database Operations'}
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

            <Stack spacing={3}>
              <FormControl sx={{ maxWidth: 400 }}>
                <FormLabel>{t('maintenance.database.target') || 'Target Storage'}</FormLabel>
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

              <FormControl sx={{ maxWidth: 400 }}>
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

              <FormControl sx={{ maxWidth: 400 }}>
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

            </Stack>
      </AccordionDetails>
    </Accordion>
  );
}

export default DatabaseOperations;

