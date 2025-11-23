import React, { createContext, useContext, useState, useEffect } from 'react';

const DrawerContext = createContext();

export const useDrawer = () => {
  const context = useContext(DrawerContext);
  if (!context) {
    throw new Error('useDrawer must be used within a DrawerProvider');
  }
  return context;
};

export const DrawerProvider = ({ children }) => {
  const [desktopOpen, setDesktopOpen] = useState(() => {
    const saved = localStorage.getItem('lbb-drawer-open');
    return saved !== null ? saved === 'true' : true;
  });

  const toggleDrawer = () => {
    const newState = !desktopOpen;
    setDesktopOpen(newState);
    localStorage.setItem('lbb-drawer-open', String(newState));
    window.dispatchEvent(new CustomEvent('drawerChange', { detail: newState }));
  };

  useEffect(() => {
    const handleDrawerChange = (event) => {
      setDesktopOpen(event.detail);
    };

    window.addEventListener('drawerChange', handleDrawerChange);
    return () => {
      window.removeEventListener('drawerChange', handleDrawerChange);
    };
  }, []);

  return (
    <DrawerContext.Provider value={{ desktopOpen, toggleDrawer }}>
      {children}
    </DrawerContext.Provider>
  );
};




