import React, { useState, useEffect } from 'react';
import {
  Card,
  CardContent,
  Typography,
  TextField,
  Button,
  Checkbox,
  FormControlLabel,
  Stack,
  Divider,
  Accordion,
  AccordionSummary,
  AccordionDetails,
  Dialog,
  DialogTitle,
  DialogContent,
  DialogActions,
  Select,
  MenuItem,
  FormControl,
  InputLabel,
  Alert,
  CircularProgress,
} from '@mui/material';
import ExpandMoreIcon from '@mui/icons-material/ExpandMore';
import SaveIcon from '@mui/icons-material/Save';
import SendIcon from '@mui/icons-material/Send';
import { useLanguage } from '../contexts/LanguageContext';
import { useConfig } from '../contexts/ConfigContext';
import api from '../utils/api';

function SocialMediaConfig() {
  const { t } = useLanguage();
  const { config, updateConfig } = useConfig();
  const [formData, setFormData] = useState({});
  const [message, setMessage] = useState('');
  const [loading, setLoading] = useState(false);
  const [telegramDialogOpen, setTelegramDialogOpen] = useState(false);
  const [matrixDialogOpen, setMatrixDialogOpen] = useState(false);
  const [telegramChats, setTelegramChats] = useState([]);
  const [matrixRooms, setMatrixRooms] = useState([]);
  const [selectedTelegramChat, setSelectedTelegramChat] = useState(null);
  const [selectedMatrixRoom, setSelectedMatrixRoom] = useState(null);

  useEffect(() => {
    if (config) {
      const socialConfig = {
        conf_SOCIAL_PUBLISH_DATE: config.conf_SOCIAL_PUBLISH_DATE || '0',
        conf_SOCIAL_PUBLISH_FILENAME: config.conf_SOCIAL_PUBLISH_FILENAME || '0',
        conf_SOCIAL_TELEGRAM_TOKEN: config.conf_SOCIAL_TELEGRAM_TOKEN || '',
        conf_SOCIAL_TELEGRAM_CHAT_ID: config.conf_SOCIAL_TELEGRAM_CHAT_ID || '',
        conf_SOCIAL_TELEGRAM_CHAT_IDENTIFIER: config.conf_SOCIAL_TELEGRAM_CHAT_IDENTIFIER || '',
        conf_SOCIAL_MASTODON_BASE_URL: config.conf_SOCIAL_MASTODON_BASE_URL || '',
        conf_SOCIAL_MASTODON_TOKEN: config.conf_SOCIAL_MASTODON_TOKEN || '',
        conf_SOCIAL_BLUESKY_API_BASE_URL: config.conf_SOCIAL_BLUESKY_API_BASE_URL || 'https://bsky.social',
        conf_SOCIAL_BLUESKY_IDENTIFIER: config.conf_SOCIAL_BLUESKY_IDENTIFIER || '',
        conf_SOCIAL_BLUESKY_APP_PASSWORD: config.conf_SOCIAL_BLUESKY_APP_PASSWORD && config.conf_SOCIAL_BLUESKY_APP_PASSWORD.trim() ? (() => {
          try {
            return atob(config.conf_SOCIAL_BLUESKY_APP_PASSWORD);
          } catch (e) {
            return '';
          }
        })() : '',
        conf_SOCIAL_MATRIX_HOMESERVER: config.conf_SOCIAL_MATRIX_HOMESERVER || '',
        conf_SOCIAL_MATRIX_TOKEN: config.conf_SOCIAL_MATRIX_TOKEN || '',
        conf_SOCIAL_MATRIX_ROOM_ID: config.conf_SOCIAL_MATRIX_ROOM_ID || '',
        conf_SOCIAL_MATRIX_ROOM_IDENTIFIER: config.conf_SOCIAL_MATRIX_ROOM_IDENTIFIER || '',
      };
      setFormData(socialConfig);
    }
  }, [config]);

  const handleSave = async () => {
    try {
      const configToSave = {
        ...formData,
        conf_SOCIAL_BLUESKY_APP_PASSWORD: formData.conf_SOCIAL_BLUESKY_APP_PASSWORD ? btoa(formData.conf_SOCIAL_BLUESKY_APP_PASSWORD) : '',
      };
      await updateConfig(configToSave);
      setMessage(t('config.message_settings_saved') || 'Settings saved');
    } catch (error) {
      console.error('Failed to save social media settings:', error);
      setMessage('Error saving social media settings');
    }
  };

  const handleTest = async (service) => {
    setLoading(true);
    try {
      await api.post(`/social/test/${service}`, formData);
      setMessage(t(`config.social.${service}.test_success`) || `Test ${service} connection successful`);
    } catch (error) {
      const errorMessage = error.response?.data?.error || error.message || `Failed to test ${service} connection`;
      setMessage(errorMessage);
    } finally {
      setLoading(false);
    }
  };

  const handleOpenTelegramDialog = async () => {
    if (!formData.conf_SOCIAL_TELEGRAM_TOKEN?.trim()) {
      setMessage(t('config.social.telegram.error_invalid_token') || 'Please enter Telegram token first');
      return;
    }
    try {
      const response = await api.post('/social/telegram/chat-id', {
        token: formData.conf_SOCIAL_TELEGRAM_TOKEN,
      });
      if (response.data.ok && response.data.chats) {
        setTelegramChats(response.data.chats);
        setTelegramDialogOpen(true);
      } else {
        setMessage(response.data.error || 'Failed to fetch chats');
      }
    } catch (error) {
      setMessage(error.response?.data?.error || 'Failed to fetch Telegram chats');
    }
  };

  const handleSelectTelegramChat = () => {
    if (selectedTelegramChat) {
      setFormData({
        ...formData,
        conf_SOCIAL_TELEGRAM_CHAT_ID: selectedTelegramChat.id,
        conf_SOCIAL_TELEGRAM_CHAT_IDENTIFIER: `${selectedTelegramChat.name} (${selectedTelegramChat.type})`,
      });
      setTelegramDialogOpen(false);
    }
  };

  const handleTestTelegramMessage = async () => {
    if (!selectedTelegramChat) {
      setMessage(t('config.social.telegram.select_chat_first') || 'Please select a chat first');
      return;
    }
    try {
      const response = await fetch(`https://api.telegram.org/bot${encodeURIComponent(formData.conf_SOCIAL_TELEGRAM_TOKEN)}/sendMessage`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: new URLSearchParams({
          chat_id: selectedTelegramChat.id,
          text: t('config.social.telegram.test_message') || 'Test message from Little-Backup-Box via bot',
        }),
      });
      const data = await response.json();
      if (data.ok) {
        setMessage(t('config.social.telegram.test_message_sent_to') || 'Test message sent to ' + selectedTelegramChat.id);
      } else {
        setMessage(t('config.social.telegram.test_message_error') || 'Error sending test message');
      }
    } catch (error) {
      setMessage(t('config.social.telegram.test_message_error') || 'Error sending test message');
    }
  };

  const handleOpenMatrixDialog = async () => {
    if (!formData.conf_SOCIAL_MATRIX_HOMESERVER?.trim()) {
      setMessage(t('config.social.matrix.error.not_set_homeserver') || 'Please enter Matrix homeserver first');
      return;
    }
    if (!formData.conf_SOCIAL_MATRIX_TOKEN?.trim()) {
      setMessage(t('config.social.matrix.error.not_set_token') || 'Please enter Matrix token first');
      return;
    }
    try {
      const response = await api.post('/social/matrix/room-id', {
        homeserver: formData.conf_SOCIAL_MATRIX_HOMESERVER,
        token: formData.conf_SOCIAL_MATRIX_TOKEN,
      });
      if (response.data.ok && response.data.rooms) {
        setMatrixRooms(response.data.rooms);
        setMatrixDialogOpen(true);
      } else {
        setMessage(response.data.error || 'Failed to fetch rooms');
      }
    } catch (error) {
      setMessage(error.response?.data?.error || 'Failed to fetch Matrix rooms');
    }
  };

  const handleSelectMatrixRoom = () => {
    if (selectedMatrixRoom) {
      const identifier = selectedMatrixRoom.canonical_alias
        ? `${selectedMatrixRoom.name || selectedMatrixRoom.canonical_alias} (${selectedMatrixRoom.canonical_alias})`
        : selectedMatrixRoom.name || '';
      setFormData({
        ...formData,
        conf_SOCIAL_MATRIX_ROOM_ID: selectedMatrixRoom.room_id,
        conf_SOCIAL_MATRIX_ROOM_IDENTIFIER: identifier,
      });
      setMatrixDialogOpen(false);
    }
  };

  const handleTestMatrixMessage = async () => {
    if (!selectedMatrixRoom) {
      setMessage(t('config.social.matrix.select_room_first') || 'Please select a room first');
      return;
    }
    try {
      await api.post('/social/matrix/test-message', {
        homeserver: formData.conf_SOCIAL_MATRIX_HOMESERVER,
        token: formData.conf_SOCIAL_MATRIX_TOKEN,
        room_id: selectedMatrixRoom.room_id,
        text: t('config.social.matrix.test_message') || 'Matrix test message from Little Backup Box',
      });
      const label = selectedMatrixRoom.name || selectedMatrixRoom.canonical_alias || selectedMatrixRoom.room_id;
      setMessage(t('config.social.matrix.test_message_sent_to') || 'Test message sent to ' + label);
    } catch (error) {
      setMessage(t('config.social.matrix.test_message_error') || 'Error sending test message');
    }
  };

  if (!config) {
    return (
      <Card>
        <CardContent>
          <CircularProgress />
        </CardContent>
      </Card>
    );
  }

  return (
    <Card>
      <CardContent>
        <Stack spacing={3}>
          <Typography variant="h6">
            {t('config.social.general.header') || 'General Settings'}
          </Typography>
          <FormControlLabel
            control={
              <Checkbox
                checked={formData.conf_SOCIAL_PUBLISH_DATE === '1'}
                onChange={(e) => setFormData({ ...formData, conf_SOCIAL_PUBLISH_DATE: e.target.checked ? '1' : '0' })}
              />
            }
            label={t('config.social.general.date_label') || 'Publish recording date'}
          />
          <FormControlLabel
            control={
              <Checkbox
                checked={formData.conf_SOCIAL_PUBLISH_FILENAME === '1'}
                onChange={(e) => setFormData({ ...formData, conf_SOCIAL_PUBLISH_FILENAME: e.target.checked ? '1' : '0' })}
              />
            }
            label={t('config.social.general.filename_label') || 'Publish file name'}
          />

          <Divider />

          <Accordion>
            <AccordionSummary expandIcon={<ExpandMoreIcon />}>
              <Typography variant="h6">
                {t('config.social.telegram.header') || 'Telegram'}
              </Typography>
            </AccordionSummary>
            <AccordionDetails>
              <Stack spacing={2}>
                <Typography variant="body2" color="text.secondary" dangerouslySetInnerHTML={{ __html: t('config.social.telegram.install_desc') || '' }} />
                <TextField
                  fullWidth
                  label={t('config.social.telegram.token_label') || 'Token'}
                  value={formData.conf_SOCIAL_TELEGRAM_TOKEN || ''}
                  onChange={(e) => setFormData({ ...formData, conf_SOCIAL_TELEGRAM_TOKEN: e.target.value })}
                />
                <TextField
                  fullWidth
                  label={t('config.social.telegram.chat_id_label') || 'Chat ID'}
                  value={formData.conf_SOCIAL_TELEGRAM_CHAT_ID && formData.conf_SOCIAL_TELEGRAM_CHAT_IDENTIFIER
                    ? `${formData.conf_SOCIAL_TELEGRAM_CHAT_ID}: ${formData.conf_SOCIAL_TELEGRAM_CHAT_IDENTIFIER}`
                    : formData.conf_SOCIAL_TELEGRAM_CHAT_ID || ''}
                  disabled
                />
                <Button
                  variant="outlined"
                  onClick={handleOpenTelegramDialog}
                  disabled={!formData.conf_SOCIAL_TELEGRAM_TOKEN?.trim()}
                >
                  {t('config.social.telegram.button_select_chat') || 'Select chat'}
                </Button>
                <Button
                  variant="outlined"
                  startIcon={<SendIcon />}
                  onClick={() => handleTest('telegram')}
                  disabled={loading || !formData.conf_SOCIAL_TELEGRAM_TOKEN?.trim()}
                >
                  {t('integrations.social_media.test_telegram') || 'Test Connection'}
                </Button>
              </Stack>
            </AccordionDetails>
          </Accordion>

          <Accordion>
            <AccordionSummary expandIcon={<ExpandMoreIcon />}>
              <Typography variant="h6">
                {t('config.social.mastodon.header') || 'Mastodon'}
              </Typography>
            </AccordionSummary>
            <AccordionDetails>
              <Stack spacing={2}>
                <Typography variant="body2" color="text.secondary" dangerouslySetInnerHTML={{ __html: t('config.social.mastodon.install_desc') || '' }} />
                <TextField
                  fullWidth
                  label={t('config.social.mastodon.base_url_label') || 'Mastodon base URL'}
                  value={formData.conf_SOCIAL_MASTODON_BASE_URL || ''}
                  onChange={(e) => setFormData({ ...formData, conf_SOCIAL_MASTODON_BASE_URL: e.target.value })}
                />
                <TextField
                  fullWidth
                  label={t('config.social.mastodon.token_label') || 'Access token'}
                  value={formData.conf_SOCIAL_MASTODON_TOKEN || ''}
                  onChange={(e) => setFormData({ ...formData, conf_SOCIAL_MASTODON_TOKEN: e.target.value })}
                />
                <Button
                  variant="outlined"
                  startIcon={<SendIcon />}
                  onClick={() => handleTest('mastodon')}
                  disabled={loading || !formData.conf_SOCIAL_MASTODON_BASE_URL?.trim() || !formData.conf_SOCIAL_MASTODON_TOKEN?.trim()}
                >
                  {t('integrations.social_media.test_mastodon') || 'Test Connection'}
                </Button>
              </Stack>
            </AccordionDetails>
          </Accordion>

          <Accordion>
            <AccordionSummary expandIcon={<ExpandMoreIcon />}>
              <Typography variant="h6">
                {t('config.social.bluesky.header') || 'Bluesky'}
              </Typography>
            </AccordionSummary>
            <AccordionDetails>
              <Stack spacing={2}>
                <Typography variant="body2" color="text.secondary" dangerouslySetInnerHTML={{ __html: t('config.social.bluesky.install_desc') || '' }} />
                <TextField
                  fullWidth
                  label={t('config.social.bluesky.base_url_label') || 'API Base URL'}
                  value={formData.conf_SOCIAL_BLUESKY_API_BASE_URL || ''}
                  onChange={(e) => setFormData({ ...formData, conf_SOCIAL_BLUESKY_API_BASE_URL: e.target.value })}
                />
                <TextField
                  fullWidth
                  label={t('config.social.bluesky.identifier_label') || 'Identifier (Email or Handle)'}
                  value={formData.conf_SOCIAL_BLUESKY_IDENTIFIER || ''}
                  onChange={(e) => setFormData({ ...formData, conf_SOCIAL_BLUESKY_IDENTIFIER: e.target.value })}
                />
                <TextField
                  fullWidth
                  type="password"
                  label={t('config.social.bluesky.app_password_label') || 'App Password'}
                  value={formData.conf_SOCIAL_BLUESKY_APP_PASSWORD || ''}
                  onChange={(e) => setFormData({ ...formData, conf_SOCIAL_BLUESKY_APP_PASSWORD: e.target.value })}
                />
                <Button
                  variant="outlined"
                  startIcon={<SendIcon />}
                  onClick={() => handleTest('bluesky')}
                  disabled={loading || !formData.conf_SOCIAL_BLUESKY_API_BASE_URL?.trim() || !formData.conf_SOCIAL_BLUESKY_IDENTIFIER?.trim() || !formData.conf_SOCIAL_BLUESKY_APP_PASSWORD?.trim()}
                >
                  {t('integrations.social_media.test_bluesky') || 'Test Connection'}
                </Button>
              </Stack>
            </AccordionDetails>
          </Accordion>

          <Accordion>
            <AccordionSummary expandIcon={<ExpandMoreIcon />}>
              <Typography variant="h6">
                {t('config.social.matrix.header') || 'Matrix'}
              </Typography>
            </AccordionSummary>
            <AccordionDetails>
              <Stack spacing={2}>
                <Typography variant="body2" color="text.secondary" dangerouslySetInnerHTML={{ __html: t('config.social.matrix.install_desc') || '' }} />
                <TextField
                  fullWidth
                  label={t('config.social.matrix.homeserver_label') || 'Matrix homeserver'}
                  value={formData.conf_SOCIAL_MATRIX_HOMESERVER || ''}
                  onChange={(e) => setFormData({ ...formData, conf_SOCIAL_MATRIX_HOMESERVER: e.target.value })}
                />
                <TextField
                  fullWidth
                  label={t('config.social.matrix.token_label') || 'Access token'}
                  value={formData.conf_SOCIAL_MATRIX_TOKEN || ''}
                  onChange={(e) => setFormData({ ...formData, conf_SOCIAL_MATRIX_TOKEN: e.target.value })}
                />
                <TextField
                  fullWidth
                  label={t('config.social.matrix.room_id_label') || 'Matrix room ID'}
                  value={formData.conf_SOCIAL_MATRIX_ROOM_ID && formData.conf_SOCIAL_MATRIX_ROOM_IDENTIFIER
                    ? `${formData.conf_SOCIAL_MATRIX_ROOM_ID}: ${formData.conf_SOCIAL_MATRIX_ROOM_IDENTIFIER}`
                    : formData.conf_SOCIAL_MATRIX_ROOM_ID || ''}
                  disabled
                />
                <Button
                  variant="outlined"
                  onClick={handleOpenMatrixDialog}
                  disabled={!formData.conf_SOCIAL_MATRIX_HOMESERVER?.trim() || !formData.conf_SOCIAL_MATRIX_TOKEN?.trim()}
                >
                  {t('config.social.matrix.button_select_room') || 'Select room'}
                </Button>
                <Button
                  variant="outlined"
                  startIcon={<SendIcon />}
                  onClick={() => handleTest('matrix')}
                  disabled={loading || !formData.conf_SOCIAL_MATRIX_HOMESERVER?.trim() || !formData.conf_SOCIAL_MATRIX_TOKEN?.trim()}
                >
                  {t('integrations.social_media.test_matrix') || 'Test Connection'}
                </Button>
              </Stack>
            </AccordionDetails>
          </Accordion>

          <Divider />

          <Button
            variant="contained"
            startIcon={<SaveIcon />}
            onClick={handleSave}
          >
            {t('config.save_button') || 'Save'}
          </Button>
        </Stack>

        <Dialog open={telegramDialogOpen} onClose={() => setTelegramDialogOpen(false)} maxWidth="sm" fullWidth>
          <DialogTitle>{t('config.social.telegram.select_chat') || 'Select chat'}</DialogTitle>
          <DialogContent>
            <Typography variant="body2" color="text.secondary" sx={{ mb: 2 }}>
              {t('config.social.telegram.table_headers') || 'Display: Name (Type) — chat_id'}
            </Typography>
            <FormControl fullWidth>
              <Select
                value={selectedTelegramChat?.id || ''}
                onChange={(e) => {
                  const chat = telegramChats.find(c => String(c.id) === String(e.target.value));
                  setSelectedTelegramChat(chat);
                }}
                displayEmpty
              >
                <MenuItem value="">
                  <em>Select a chat</em>
                </MenuItem>
                {telegramChats.map((chat) => (
                  <MenuItem key={chat.id} value={chat.id}>
                    {chat.name} ({chat.type}) — {chat.id}
                  </MenuItem>
                ))}
              </Select>
            </FormControl>
          </DialogContent>
          <DialogActions>
            <Button onClick={() => setTelegramDialogOpen(false)}>
              {t('config.social.telegram.close') || 'Close'}
            </Button>
            <Button onClick={handleTestTelegramMessage} disabled={!selectedTelegramChat}>
              {t('config.social.telegram.test_message_send') || 'Send test message'}
            </Button>
            <Button onClick={handleSelectTelegramChat} variant="contained" disabled={!selectedTelegramChat}>
              {t('config.social.telegram.accept') || 'Accept'}
            </Button>
          </DialogActions>
        </Dialog>

        <Dialog open={matrixDialogOpen} onClose={() => setMatrixDialogOpen(false)} maxWidth="sm" fullWidth>
          <DialogTitle>{t('config.social.matrix.select_room') || 'Select Matrix room'}</DialogTitle>
          <DialogContent>
            <Typography variant="body2" color="text.secondary" sx={{ mb: 2 }}>
              {t('config.social.matrix.table_headers') || 'Name — Alias — Room ID'}
            </Typography>
            <FormControl fullWidth>
              <Select
                value={selectedMatrixRoom?.room_id || ''}
                onChange={(e) => {
                  const room = matrixRooms.find(r => r.room_id === e.target.value);
                  setSelectedMatrixRoom(room);
                }}
                displayEmpty
              >
                <MenuItem value="">
                  <em>Select a room</em>
                </MenuItem>
                {matrixRooms.map((room) => (
                  <MenuItem key={room.room_id} value={room.room_id}>
                    {room.canonical_alias
                      ? `${room.name || room.canonical_alias} — ${room.canonical_alias} — ${room.room_id}`
                      : `${room.name || t('config.social.matrix.without_name') || 'without name'} — ${room.room_id}`}
                  </MenuItem>
                ))}
              </Select>
            </FormControl>
          </DialogContent>
          <DialogActions>
            <Button onClick={() => setMatrixDialogOpen(false)}>
              {t('config.social.matrix.close') || 'Close'}
            </Button>
            <Button onClick={handleTestMatrixMessage} disabled={!selectedMatrixRoom}>
              {t('config.social.matrix.test_message_send') || 'Send test message'}
            </Button>
            <Button onClick={handleSelectMatrixRoom} variant="contained" disabled={!selectedMatrixRoom}>
              {t('config.social.matrix.accept') || 'Apply'}
            </Button>
          </DialogActions>
        </Dialog>
      </CardContent>
    </Card>
  );
}

export default SocialMediaConfig;

