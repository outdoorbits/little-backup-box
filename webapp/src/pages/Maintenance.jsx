import React from 'react';
import { Box, Typography, Grid } from '@mui/material';
import { useLanguage } from '../contexts/LanguageContext';
import UpdateManager from '../components/UpdateManager';
import SettingsOperations from '../components/SettingsOperations';

function Maintenance() {
  const { t } = useLanguage();

  return (
    <Box>
      <Grid container spacing={3}>
        <Grid item xs={12}>
          <UpdateManager />
        </Grid>

        <Grid item xs={12}>
          <SettingsOperations />
        </Grid>
      </Grid>
    </Box>
  );
}

export default Maintenance;

