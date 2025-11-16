import React, { useState } from 'react';
import {
  Card,
  CardContent,
  Typography,
  Stack,
  Alert,
  Accordion,
  AccordionSummary,
  AccordionDetails,
  Button,
  Box,
} from '@mui/material';
import ExpandMoreIcon from '@mui/icons-material/ExpandMore';
import DownloadIcon from '@mui/icons-material/Download';
import UploadIcon from '@mui/icons-material/Upload';
import { useLanguage } from '../contexts/LanguageContext';
import api from '../utils/api';

function SettingsOperations() {
  const { t } = useLanguage();
  const [loading, setLoading] = useState(false);
  const [message, setMessage] = useState('');
  const [fileInput, setFileInput] = useState(null);

  const handleDownload = async () => {
    setLoading(true);
    setMessage('');
    try {
      const response = await api.get('/setup/download-settings', {
        responseType: 'blob',
      });
      
      const url = window.URL.createObjectURL(new Blob([response.data]));
      const link = document.createElement('a');
      link.href = url;
      link.setAttribute('download', 'lbb-settings.zip');
      document.body.appendChild(link);
      link.click();
      link.remove();
      window.URL.revokeObjectURL(url);
      
      setMessage(t('maintenance.settings.download_success') || 'Settings downloaded successfully');
    } catch (error) {
      console.error('Failed to download settings:', error);
      setMessage(t('maintenance.settings.download_error') || 'Failed to download settings');
    } finally {
      setLoading(false);
    }
  };

  const handleFileChange = (event) => {
    setFileInput(event.target.files[0]);
    setMessage('');
  };

  const handleUpload = async () => {
    if (!fileInput) {
      setMessage(t('maintenance.settings.no_file_selected') || 'Please select a file');
      return;
    }

    if (!fileInput.name.toLowerCase().endsWith('.zip')) {
      setMessage(t('maintenance.settings.invalid_file_type') || 'Please select a ZIP file');
      return;
    }

    setLoading(true);
    setMessage('');
    
    try {
      const formData = new FormData();
      formData.append('settings', fileInput);
      
      const response = await api.post('/setup/upload-settings', formData, {
        headers: {
          'Content-Type': 'multipart/form-data',
        },
      });
      
      setMessage(t('maintenance.settings.upload_success') || 'Settings uploaded successfully');
      setFileInput(null);
      const fileInputElement = document.getElementById('settings-file-input');
      if (fileInputElement) {
        fileInputElement.value = '';
      }
    } catch (error) {
      console.error('Failed to upload settings:', error);
      setMessage(t('maintenance.settings.upload_error') || 'Failed to upload settings');
    } finally {
      setLoading(false);
    }
  };

  return (
    <Card>
      <CardContent>
        <Accordion defaultExpanded>
          <AccordionSummary expandIcon={<ExpandMoreIcon />}>
            <Typography variant="h5">
              {t('config.save_settings_section') || 'Download / Upload settings'}
            </Typography>
          </AccordionSummary>
          <AccordionDetails>
            {message && (
              <Alert 
                severity={message.includes('error') || message.includes('Error') || message.includes('invalid') || message.includes('Please') ? 'error' : 'success'} 
                sx={{ mb: 2 }}
                onClose={() => setMessage('')}
              >
                {message}
              </Alert>
            )}

            <Stack spacing={3}>
              <Box>
                <Typography variant="h6" gutterBottom>
                  {t('config.save_settings_download_header') || 'Download'}
                </Typography>
                <Typography variant="body2" color="text.secondary" paragraph>
                  {t('config.save_settings_download_text') || 'Get a zip-archive with your settings'}
                </Typography>
                <Button
                  variant="outlined"
                  startIcon={loading ? null : <DownloadIcon />}
                  onClick={handleDownload}
                  disabled={loading}
                >
                  {t('config.save_settings_download_link_text') || 'Download settings'}
                </Button>
              </Box>

              <Box>
                <Typography variant="h6" gutterBottom>
                  {t('config.save_settings_upload_header') || 'Upload'}
                </Typography>
                <Stack spacing={2}>
                  <input
                    accept=".zip"
                    style={{ display: 'none' }}
                    id="settings-file-input"
                    type="file"
                    onChange={handleFileChange}
                  />
                  <label htmlFor="settings-file-input">
                    <Button
                      variant="outlined"
                      component="span"
                      disabled={loading}
                    >
                      {t('config.save_settings_upload_label') || 'Select the settings-file'}
                    </Button>
                  </label>
                  {fileInput && (
                    <Typography variant="body2" color="text.secondary">
                      {fileInput.name}
                    </Typography>
                  )}
                  <Button
                    variant="contained"
                    startIcon={loading ? null : <UploadIcon />}
                    onClick={handleUpload}
                    disabled={loading || !fileInput}
                  >
                    {t('config.save_settings_upload_button') || 'Upload settings'}
                  </Button>
                </Stack>
              </Box>
            </Stack>
          </AccordionDetails>
        </Accordion>
      </CardContent>
    </Card>
  );
}

export default SettingsOperations;

