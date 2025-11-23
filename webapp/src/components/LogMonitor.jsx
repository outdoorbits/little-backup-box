import React, { useState, useEffect } from 'react';
import {
  Box,
  Card,
  CardContent,
  Typography,
  Button,
  FormControlLabel,
  Checkbox,
  Paper,
  Stack,
  Divider,
  IconButton,
} from '@mui/material';
import DeleteIcon from '@mui/icons-material/Delete';
import RefreshIcon from '@mui/icons-material/Refresh';
import { useConfig } from '../contexts/ConfigContext';
import { useLanguage } from '../contexts/LanguageContext';
import api from '../utils/api';

function LogMonitor() {
  const { constants } = useConfig();
  const { t } = useLanguage();
  const [logContent, setLogContent] = useState('');
  const [autoRefresh, setAutoRefresh] = useState(true);

  useEffect(() => {
    if (autoRefresh) {
      const interval = setInterval(refreshLog, 2000);
      return () => clearInterval(interval);
    }
  }, [autoRefresh]);

  const refreshLog = async () => {
    try {
      const logFile = constants?.const_LOGFILE || '/var/www/little-backup-box/tmp/little-backup-box.log';
      const response = await fetch(`/api/log?file=${encodeURIComponent(logFile)}`);
      if (response.ok) {
        const text = await response.text();
        setLogContent(text);
      }
    } catch (error) {
      console.error('Failed to refresh log:', error);
    }
  };

  const deleteLog = async () => {
    try {
      const logFile = constants?.const_LOGFILE || '/var/www/little-backup-box/tmp/little-backup-box.log';
      await api.post('/log/delete', { file: logFile });
      setLogContent('');
    } catch (error) {
      console.error('Failed to delete log:', error);
    }
  };

  return (
    <Card sx={{ mt: 3, mb: 2 }}>
      <CardContent>
        <Box sx={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center', mb: 2 }}>
          <Typography variant="h6">
            {t('log.logmonitor')}
          </Typography>
          <Stack direction="row" spacing={1} alignItems="center">
            <Button
              variant="outlined"
              size="small"
              startIcon={<RefreshIcon />}
              onClick={refreshLog}
            >
              {t('log.refresh_button')}
            </Button>
            <IconButton
              size="small"
              color="error"
              onClick={deleteLog}
              aria-label="delete log"
            >
              <DeleteIcon />
            </IconButton>
            <FormControlLabel
              control={
                <Checkbox
                  checked={autoRefresh}
                  onChange={(e) => setAutoRefresh(e.target.checked)}
                  size="small"
                />
              }
              label="Auto-refresh"
            />
          </Stack>
        </Box>
        <Divider sx={{ mb: 2 }} />
        <Paper elevation={0} sx={{ p: 2, maxHeight: 300, overflow: 'auto' }}>
          <Typography
            component="pre"
            variant="body2"
            sx={{
              fontFamily: 'monospace',
              whiteSpace: 'pre-wrap',
              wordBreak: 'break-word',
              margin: 0,
            }}
          >
            {logContent || 'No log content'}
          </Typography>
        </Paper>
      </CardContent>
    </Card>
  );
}

export default LogMonitor;
