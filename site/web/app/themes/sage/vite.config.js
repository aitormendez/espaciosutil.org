import { defineConfig } from 'vite';
import tailwindcss from '@tailwindcss/vite';
import laravel from 'laravel-vite-plugin';
import { wordpressPlugin, wordpressThemeJson } from '@roots/vite-plugin';
import path from 'path';

export default defineConfig({
  base: '/app/themes/sage/public/build/',
  plugins: [
    tailwindcss(),
    laravel({
      input: [
        'resources/css/app.css',
        'resources/js/app.js',
        'resources/css/editor.css',
        'resources/js/editor.js',
      ],
      refresh: true,
    }),

    wordpressPlugin(),

    // Generate the theme.json file in the public/build/assets directory
    // based on the Tailwind config and the theme.json file from base theme folder
    wordpressThemeJson({
      disableTailwindColors: false,
      disableTailwindFonts: false,
      disableTailwindFontSizes: false,
    }),
  ],
  resolve: {
    alias: {
      '@scripts': path.resolve(__dirname, 'resources/js'),
      '@styles': path.resolve(__dirname, 'resources/css'),
      '@fonts': path.resolve(__dirname, 'resources/fonts'),
      '@images': path.resolve(__dirname, 'resources/images'),
    },
  },
});
