import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import vue from '@vitejs/plugin-vue';
import svgLoader from 'vite-svg-loader'
import dotenv from 'dotenv'
import path from 'path';

dotenv.config()

const extendedViteDevServerOptions = {
    hmr: {
        host: 'localhost',
    }
}

if (process.env.VITE_URL) {
    extendedViteDevServerOptions.hmr = {
        protocol: 'wss',
        host: new URL(process.env.VITE_URL).hostname,
        clientPort: 443,
        strictPort: true,
    }
    extendedViteDevServerOptions.cors = true;
}

export default defineConfig({
    server: {
         ...extendedViteDevServerOptions,
    },
    resolve: {
        alias: {
            'ziggy-js': path.resolve('./vendor/tightenco/ziggy'),
        },
    },
    plugins: [
        laravel({
            input: 'resources/js/app.js',
            refresh: true,
        }),
        vue({
            template: {
                transformAssetUrls: {
                    base: null,
                    includeAbsolute: false,
                },
            },
        }),
        svgLoader(),
    ],
});
