import React from 'react';
import { Box, Typography, Divider } from '@mui/material';
import { useLanguage } from '../contexts/LanguageContext';

function Footer() {
  const { t } = useLanguage();
  const footerText = t('view.footer.footer') || '';

  if (!footerText) return null;

  return (
    <Box
      component="footer"
      sx={{
        mt: 'auto',
        py: 3,
        px: 2,
        borderTop: 1,
        borderColor: 'divider',
      }}
    >
      <Divider sx={{ mb: 2 }} />
      <Typography 
        variant="body2" 
        color="text.secondary"
        sx={{ 
          textAlign: 'center',
        }}
        dangerouslySetInnerHTML={{ __html: footerText }}
      />
    </Box>
  );
}

export default Footer;
