import React, { createContext, useContext, useState, useEffect } from 'react';
import api from '../utils/api';

const ConfigContext = createContext();

export function useConfig() {
  const context = useContext(ConfigContext);
  if (!context) {
    throw new Error('useConfig must be used within ConfigProvider');
  }
  return context;
}

export function ConfigProvider({ children }) {
  const [config, setConfig] = useState(null);
  const [constants, setConstants] = useState(null);
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    loadConfig();
  }, []);

  const loadConfig = async () => {
    try {
      const response = await api.get('/config');
      setConfig(response.data.config);
      setConstants(response.data.constants);
    } catch (error) {
      console.error('Failed to load config:', error);
    } finally {
      setLoading(false);
    }
  };

  const updateConfig = async (updates) => {
    try {
      await api.post('/config/save', updates);
      await loadConfig();
    } catch (error) {
      console.error('Failed to update config:', error);
      throw error;
    }
  };

  const value = {
    config,
    constants,
    loading,
    updateConfig,
    reloadConfig: loadConfig,
  };

  return <ConfigContext.Provider value={value}>{children}</ConfigContext.Provider>;
}

