import React, { useState, useEffect } from 'react';
import { Alert, Box } from '@mui/material';
import api from '../utils/api';

function DisplayStatus() {
  const [status, setStatus] = useState('');

  useEffect(() => {
    const interval = setInterval(async () => {
      try {
        const response = await api.get('/display/status');
        setStatus(response.data.status);
      } catch (error) {
        console.error('Failed to get display status:', error);
      }
    }, 1000);

    return () => clearInterval(interval);
  }, []);

  if (!status) return null;

  return (
    <Box sx={{ px: 2, pt: 2 }}>
      <Alert severity="info">
        {status}
      </Alert>
    </Box>
  );
}

export default DisplayStatus;
