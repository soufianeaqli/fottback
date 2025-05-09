import { defineConfig } from 'vite'
import react from '@vitejs/plugin-react'

// https://vitejs.dev/config/
export default defineConfig({
  plugins: [react()],
  server: {
    port: 3000,
    proxy: {
      '/api': {
        target: 'http://localhost:8000',
        changeOrigin: true,
        secure: false,
        configure: (proxy, options) => {
          proxy.on('proxyReq', (proxyReq, req, res) => {
            proxyReq.setHeader('connection', 'keep-alive');
            proxyReq.setHeader('keep-alive', 'timeout=5');
          });
        }
      }
    },
    hmr: {
      protocol: 'ws',
      host: 'localhost',
      clientPort: 3000
    }
  }
})
