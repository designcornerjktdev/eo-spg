import react from '@vitejs/plugin-react';
import laravel from 'laravel-vite-plugin';
import {
    defineConfig
} from 'vite';
import tailwindcss from "@tailwindcss/vite";

// Subfolder deploys (VITE_APP_BASE=/eo-spg) need lazy chunks served from /eo-spg/build/.
const appBase = (process.env.VITE_APP_BASE ?? '').replace(/\/+$/, '');

export default defineConfig(({ command }) => ({
    base: command === 'build' && appBase ? `${appBase}/build/` : undefined,
    plugins: [
        laravel({
            input: ['resources/css/app.css', 'resources/js/app.tsx'],
            ssr: 'resources/js/ssr.jsx',
            refresh: true,
        }),
        react(),
        tailwindcss(),
    ],
    esbuild: {
        jsx: 'automatic',
    },
}));
