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

const ensureTrailingSlash = (path) => (path.endsWith('/') ? path : `${path}/`);
const basePath = ensureTrailingSlash(import.meta.env.BASE_URL || '/');
const buildLangUrl = (lang) => `${basePath}lang/${lang}.json`;

const detectBrowserLanguage = () => {
  const supportedLanguages = ['en', 'de', 'es', 'fr', 'fi'];
  const browserLanguages = navigator.languages || [navigator.language];
  
  for (const browserLang of browserLanguages) {
    const langCode = browserLang.split('-')[0].toLowerCase();
    if (supportedLanguages.includes(langCode)) {
      return langCode;
    }
  }
  
  return 'en';
};

export function LanguageProvider({ children }) {
  const { config } = useConfig();
  const [translations, setTranslations] = useState({});
  const [language, setLanguage] = useState(() => {
    const savedLanguage = localStorage.getItem('lbb-language');
    if (savedLanguage === '' || savedLanguage === null) {
      return '';
    }
    return savedLanguage || 'en';
  });
  const [hasInitialized, setHasInitialized] = useState(false);

  useEffect(() => {
    const handleLanguageChange = (event) => {
      const newLanguage = event.detail || event.target?.value;
      if (newLanguage !== language) {
        setLanguage(newLanguage);
        localStorage.setItem('lbb-language', newLanguage);
      }
    };

    const handleStorageChange = (e) => {
      if (e.key === 'lbb-language') {
        const newLanguage = e.newValue;
        if (newLanguage !== language) {
          setLanguage(newLanguage || '');
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
    
    if (savedLanguage === null) {
      const defaultLanguage = config?.conf_LANGUAGE || '';
      if (defaultLanguage === '') {
        setLanguage('');
        localStorage.setItem('lbb-language', '');
      } else {
        setLanguage(defaultLanguage);
        localStorage.setItem('lbb-language', defaultLanguage);
      }
    } else if (savedLanguage !== language) {
      setLanguage(savedLanguage);
    }
    
    setHasInitialized(true);
  }, [config, language, hasInitialized]);

  useEffect(() => {
    const langToLoad = language === '' ? detectBrowserLanguage() : language;
    loadTranslations(langToLoad);
    document.documentElement.setAttribute('lang', langToLoad);
    if (language !== '') {
      localStorage.setItem('lbb-language', language);
    }
  }, [language]);

  const loadTranslations = async (lang) => {
    try {
      const response = await fetch(buildLangUrl(lang));
      if (response.ok) {
        const data = await response.json();
        setTranslations(data);
        console.log('Translations loaded for language:', lang);
      } else {
        console.warn(`Failed to load ${lang}.json, falling back to en.json`);
        const fallback = await fetch(buildLangUrl('en'));
        if (fallback.ok) {
          const data = await fallback.json();
          setTranslations(data);
          console.log('Fallback translations (en) loaded');
        }
      }
    } catch (error) {
      console.error('Failed to load translations:', error);
      try {
        const fallback = await fetch(buildLangUrl('en'));
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

