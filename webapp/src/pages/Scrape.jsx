import React, { useEffect, useState } from 'react';
import {
  Box,
  Typography,
  Card,
  CardContent,
  Button,
  Stack,
  CircularProgress,
  Alert,
} from '@mui/material';
import FolderIcon from '@mui/icons-material/Folder';
import OpenInNewIcon from '@mui/icons-material/OpenInNew';
import { useLanguage } from '../contexts/LanguageContext';

const basePath = (import.meta.env.BASE_URL || '/').replace(/\/?$/, '/');
const manifestUrl = `${basePath}scrape-manifest.json`;

function formatDate(value) {
  if (!value) return '';
  const date = new Date(value);
  if (Number.isNaN(date.getTime())) return value;
  return date.toLocaleString();
}

function Scrape() {
  const { t } = useLanguage();
  const [sites, setSites] = useState([]);
  const [error, setError] = useState(null);
  const [loading, setLoading] = useState(true);
  const [generatedAt, setGeneratedAt] = useState(null);

  useEffect(() => {
    const controller = new AbortController();

    async function loadManifest() {
      try {
        const response = await fetch(manifestUrl, { signal: controller.signal });
        if (!response.ok) {
          throw new Error(`Failed to load manifest (${response.status})`);
        }
        const data = await response.json();
        setSites(data?.sites || []);
        setGeneratedAt(data?.generatedAt || null);
      } catch (err) {
        if (err.name === 'AbortError') return;
        console.error('Failed to load scrape manifest', err);
        setError(t('scrape.error'));
      } finally {
        setLoading(false);
      }
    }

    loadManifest();
    return () => controller.abort();
  }, [t]);

  const renderContent = () => {
    if (loading) {
      return (
        <Box sx={{ display: 'flex', justifyContent: 'center', mt: 4 }}>
          <CircularProgress size={32} />
        </Box>
      );
    }

    if (error) {
      return (
        <Alert severity="error" sx={{ mt: 2 }}>
          {error}
        </Alert>
      );
    }

    if (!sites.length) {
      return (
        <Alert severity="info" sx={{ mt: 2 }}>
          {t('scrape.empty')}
        </Alert>
      );
    }

    return (
      <Stack spacing={2} sx={{ mt: 2 }}>
        {sites.map((site) => (
          <Card key={site.id} variant="outlined">
            <CardContent
              sx={{
                display: 'flex',
                flexDirection: { xs: 'column', md: 'row' },
                alignItems: { xs: 'flex-start', md: 'center' },
                gap: 2,
              }}
            >
              <Box sx={{ display: 'flex', alignItems: 'center', gap: 1 }}>
                <FolderIcon color="primary" />
                <Box>
                  <Typography variant="h6">{site.name}</Typography>
                  {site.lastModified && (
                    <Typography variant="body2" color="text.secondary">
                      {`${t('scrape.updated')} ${formatDate(site.lastModified)}`}
                    </Typography>
                  )}
                </Box>
              </Box>
              <Box sx={{ flexGrow: 1 }} />
              <Button
                variant="contained"
                endIcon={<OpenInNewIcon />}
                onClick={() => window.open(`${basePath}${site.index}`, '_blank', 'noopener')}
              >
                {t('scrape.open')}
              </Button>
            </CardContent>
          </Card>
        ))}
      </Stack>
    );
  };

  return (
    <Box sx={{ p: 3 }}>
      <Typography variant="h4" gutterBottom>
        {t('scrape.title')}
      </Typography>
      <Typography variant="body1" color="text.secondary">
        {t('scrape.description')}
      </Typography>
      {generatedAt && (
        <Typography variant="caption" color="text.secondary" sx={{ display: 'block', mt: 1 }}>
          {`${t('scrape.generated')} ${formatDate(generatedAt)}`}
        </Typography>
      )}
      {renderContent()}
    </Box>
  );
}

export default Scrape;


