import React, { useState, useEffect } from 'react';
import {
  Box,
  Typography,
  TextField,
  Button,
  Grid,
  Card,
  CardMedia,
  CardContent,
  Paper,
  Stack,
  IconButton,
} from '@mui/material';
import GridViewIcon from '@mui/icons-material/GridView';
import ViewListIcon from '@mui/icons-material/ViewList';
import { useLanguage } from '../contexts/LanguageContext';
import { useConfig } from '../contexts/ConfigContext';
import api from '../utils/api';

function View() {
  const { t } = useLanguage();
  const { constants } = useConfig();
  const [storagePath, setStoragePath] = useState('');
  const [images, setImages] = useState([]);
  const [viewMode, setViewMode] = useState('grid');
  const [selectedImage, setSelectedImage] = useState(null);

  useEffect(() => {
    if (storagePath) {
      loadImages();
    }
  }, [storagePath]);

  const loadImages = async () => {
    try {
      const response = await api.get('/view/images', {
        params: { storagePath },
      });
      setImages(response.data.images || []);
    } catch (error) {
      console.error('Failed to load images:', error);
    }
  };

  const handleInit = async () => {
    try {
      await api.get('/view/init', { params: { mountpoint: storagePath } });
      loadImages();
    } catch (error) {
      console.error('Failed to initialize view:', error);
    }
  };

  return (
    <Box>
      <Stack 
        direction={{ xs: 'column', sm: 'row' }}
        spacing={2}
        sx={{ mb: 3 }}
        alignItems={{ xs: 'stretch', sm: 'center' }}
      >
        <TextField
          fullWidth
          label={t('view.filter.medium') || 'Storage medium'}
          value={storagePath}
          onChange={(e) => setStoragePath(e.target.value)}
        />
        <Button 
          variant="contained" 
          onClick={handleInit}
        >
          {t('view.images.back_to_grid') || 'Initialize'}
        </Button>
        <IconButton
          onClick={() => setViewMode(viewMode === 'grid' ? 'single' : 'grid')}
          color="primary"
          aria-label="toggle view mode"
        >
          {viewMode === 'grid' ? <ViewListIcon /> : <GridViewIcon />}
        </IconButton>
      </Stack>

      {viewMode === 'grid' ? (
        <Grid container spacing={2}>
          {images.map((image) => (
            <Grid item xs={6} sm={4} md={3} lg={2} key={image.ID}>
              <Card
                sx={{ 
                  cursor: 'pointer',
                }}
                onClick={() => {
                  setSelectedImage(image);
                  setViewMode('single');
                }}
              >
                <CardMedia
                  component="img"
                  height="200"
                  image={`${storagePath}/${image.Directory}/${image.File_Name}`}
                  alt={image.File_Name}
                />
                <CardContent>
                  <Typography variant="caption" noWrap>
                    {image.File_Name}
                  </Typography>
                </CardContent>
              </Card>
            </Grid>
          ))}
        </Grid>
      ) : (
        selectedImage && (
          <Paper sx={{ p: 3 }}>
            <Box sx={{ textAlign: 'center' }}>
              <img
                src={`${storagePath}/${selectedImage.Directory}/${selectedImage.File_Name}`}
                alt={selectedImage.File_Name}
                style={{ 
                  maxWidth: '100%', 
                  height: 'auto',
                }}
              />
              <Box sx={{ mt: 3 }}>
                <Typography variant="h6" gutterBottom>
                  {selectedImage.File_Name}
                </Typography>
                <Typography variant="body2" color="text.secondary">
                  {selectedImage.Create_Date}
                </Typography>
              </Box>
            </Box>
          </Paper>
        )
      )}

      <Card sx={{ mt: 3 }}>
        <CardContent>
          <Typography 
            variant="body2" 
            dangerouslySetInnerHTML={{ 
              __html: t('view.footer.footer') || 'Warning, only images that have thumbnails are displayed here. Generate them either automatically (Settings, Backup) or via the Backup page.<br>\nSee the <a href="https://github.com/outdoorbits/little-backup-box/wiki/05a.-view-image-viewer">Wiki</a> for more information.'
            }}
          />
        </CardContent>
      </Card>
    </Box>
  );
}

export default View;
