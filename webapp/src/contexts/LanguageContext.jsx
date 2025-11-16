import React, { createContext, useContext, useState, useEffect } from 'react';
import { useConfig } from './ConfigContext';

const LanguageContext = createContext();

export function useLanguage() {
  const context = useContext(LanguageContext);
  if (!context) {
    throw new Error('useLanguage must be used within LanguageProvider');
  }
  return context;
}

export function LanguageProvider({ children }) {
  const { config } = useConfig();
  const [translations, setTranslations] = useState({});
  const [language, setLanguage] = useState(() => {
    const savedLanguage = localStorage.getItem('lbb-language');
    return savedLanguage || 'en';
  });
  const [hasInitialized, setHasInitialized] = useState(false);

  useEffect(() => {
    const handleLanguageChange = (event) => {
      const newLanguage = event.detail || event.target?.value;
      if (newLanguage && newLanguage !== language) {
        setLanguage(newLanguage);
        localStorage.setItem('lbb-language', newLanguage);
      }
    };

    const handleStorageChange = (e) => {
      if (e.key === 'lbb-language') {
        const newLanguage = e.newValue;
        if (newLanguage && newLanguage !== language) {
          setLanguage(newLanguage);
        }
      }
    };

    window.addEventListener('languageChange', handleLanguageChange);
    window.addEventListener('storage', handleStorageChange);
    return () => {
      window.removeEventListener('languageChange', handleLanguageChange);
      window.removeEventListener('storage', handleStorageChange);
    };
  }, [language]);

  useEffect(() => {
    if (hasInitialized) return;
    
    const savedLanguage = localStorage.getItem('lbb-language');
    
    if (!savedLanguage) {
      const defaultLanguage = config?.conf_LANGUAGE || 'en';
      setLanguage(defaultLanguage);
      localStorage.setItem('lbb-language', defaultLanguage);
    } else if (savedLanguage !== language) {
      setLanguage(savedLanguage);
    }
    
    setHasInitialized(true);
  }, [config, language, hasInitialized]);

  useEffect(() => {
    loadTranslations(language);
    document.documentElement.setAttribute('lang', language);
    localStorage.setItem('lbb-language', language);
  }, [language]);

  const loadTranslations = async (lang) => {
    try {
      const response = await fetch(`/lang/${lang}.json`);
      if (response.ok) {
        const data = await response.json();
        setTranslations(data);
        console.log('Translations loaded for language:', lang);
      } else {
        console.warn(`Failed to load ${lang}.json, falling back to en.json`);
        const fallback = await fetch('/lang/en.json');
        if (fallback.ok) {
          const data = await fallback.json();
          setTranslations(data);
          console.log('Fallback translations (en) loaded');
        }
      }
    } catch (error) {
      console.error('Failed to load translations:', error);
      try {
        const fallback = await fetch('/lang/en.json');
        if (fallback.ok) {
          const data = await fallback.json();
          setTranslations(data);
          console.log('Fallback translations (en) loaded after error');
        } else {
          console.error('Failed to load fallback translations');
        }
      } catch (err) {
        console.error('Failed to load fallback translations:', err);
      }
    }
  };

  const t = (key) => {
    const keys = key.split('.');
    let value = translations;
    for (const k of keys) {
      value = value?.[k];
      if (value === undefined) return key;
    }
    return value || key;
  };

  const value = {
    language,
    setLanguage,
    t,
    translations,
  };

  return <LanguageContext.Provider value={value}>{children}</LanguageContext.Provider>;
}

