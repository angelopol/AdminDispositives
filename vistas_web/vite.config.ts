import { defineConfig } from 'vite';
import react from '@vitejs/plugin-react';

// Ajusta targetBackend si tu Laravel corre en otro puerto
const targetBackend = 'http://localhost:8000';

export default defineConfig({
  plugins: [react()],
  server: {
    port: 5173,
    host: 'localhost',
    open: true,
    proxy: {
      '/api': {
        target: targetBackend,
        changeOrigin: true,
        secure: false,
      },
    },
  },
  build: {
    outDir: 'dist',
    emptyOutDir: true,
  },
});
