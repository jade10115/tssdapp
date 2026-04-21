import { defineConfig } from 'vite';
import vue from '@vitejs/plugin-vue';
import path from 'path';

export default defineConfig({
  plugins: [vue()],   // 👈 no basicSsl
  resolve: {
    alias: { '@': path.resolve(__dirname, './src') },
  },
  server: {
    host: '127.0.0.1',
    port: 5173,
    // no https: true
  },
  base: '/',
  build: {
    outDir: 'dist',
    emptyOutDir: true,
  },
});