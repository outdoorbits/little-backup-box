import { defineConfig } from 'vite';
import react from '@vitejs/plugin-react';
import path from 'path';

export default defineConfig(({ mode }) => {
  const basePath = process.env.VITE_BASE_PATH || '/';
  
  return {
    base: basePath,
    plugins: [react()],
    server: {
      port: 5173,
      proxy: {
        '/api': {
          target: 'http://localhost:3000',
          changeOrigin: true,
        },
        '/css': {
          target: 'http://localhost:3000',
          changeOrigin: true,
        },
        '/js': {
          target: 'http://localhost:3000',
          changeOrigin: true,
        },
        '/img': {
          target: 'http://localhost:3000',
          changeOrigin: true,
        },
        '/lang': {
          target: 'http://localhost:3000',
          changeOrigin: true,
        },
        '/favicon.ico': {
          target: 'http://localhost:3000',
          changeOrigin: true,
        },
      },
    },
    build: {
      outDir: 'dist',
      assetsDir: 'assets',
    },
    resolve: {
      alias: {
        '@': path.resolve(__dirname, './src'),
      },
    },
  };
});

