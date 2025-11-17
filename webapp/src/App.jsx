import React, { useEffect } from 'react';
import { Routes, Route, useNavigate, useLocation } from 'react-router-dom';
import Layout from './components/Layout';
import Home from './pages/Home';
import Setup from './pages/Setup';
import Tools from './pages/Tools';
import SysInfo from './pages/SysInfo';
import Maintenance from './pages/Maintenance';
import Integrations from './pages/Integrations';
import Scrape from './pages/Scrape';

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
        <Route path="/" element={<Home />} />
        <Route path="/setup" element={<Setup />} />
        <Route path="/tools" element={<Tools />} />
        <Route path="/sysinfo" element={<SysInfo />} />
        <Route path="/maintenance" element={<Maintenance />} />
        <Route path="/integrations" element={<Integrations />} />
        <Route path="/scrape" element={<Scrape />} />
      </Routes>
    </Layout>
  );
}

export default App;


