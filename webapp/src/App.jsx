import React, { useEffect } from 'react';
import { Routes, Route, useNavigate, useLocation } from 'react-router-dom';
import Layout from './components/Layout';
import Backup from './pages/Backup';
import UserInterface from './pages/UserInterface';
import Filesystem from './pages/Filesystem';
import System from './pages/System';
import Maintenance from './pages/Maintenance';
import ServiceConnections from './pages/ServiceConnections';
import Network from './pages/Network';
import ScrapedUI from './pages/ScrapedUI';

function RedirectHandler() {
  const navigate = useNavigate();
  const location = useLocation();

  useEffect(() => {
    const params = new URLSearchParams(location.search);
    const redirect = params.get('redirect');
    if (redirect) {
      params.delete('redirect');
      const newSearch = params.toString();
      navigate(redirect + (newSearch ? '?' + newSearch : ''), { replace: true });
    }
  }, [location.search, navigate]);

  return null;
}

function App() {
  return (
    <Layout>
      <RedirectHandler />
      <Routes>
        <Route path="/" element={<Backup />} />
        <Route path="/setup" element={<UserInterface />} />
        <Route path="/tools" element={<Filesystem />} />
        <Route path="/sysinfo" element={<System />} />
        <Route path="/network" element={<Network />} />
        <Route path="/maintenance" element={<Maintenance />} />
        <Route path="/integrations" element={<ServiceConnections />} />
        <Route path="/scrape" element={<ScrapedUI />} />
      </Routes>
    </Layout>
  );
}

export default App;


