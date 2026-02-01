import laravel from 'laravel-vite-plugin'
import fs from 'node:fs'
import { fileURLToPath } from 'node:url'
import vue from '@vitejs/plugin-vue'
import vueJsx from '@vitejs/plugin-vue-jsx'
import AutoImport from 'unplugin-auto-import/vite'
import Components from 'unplugin-vue-components/vite'
import { defineConfig } from 'vite'
import VueDevTools from 'vite-plugin-vue-devtools'
import vuetify from 'vite-plugin-vuetify'
import svgLoader from 'vite-svg-loader'

// https://vitejs.dev/config/
const isProd = process.env.NODE_ENV === 'production'
const envExamplePath = fileURLToPath(new URL('./.env.example', import.meta.url))

const readGratifyVersion = () => {
  try {
    const content = fs.readFileSync(envExamplePath, 'utf8')
    const line = content.split('\n').find(row => row.startsWith('GRATIFY_VERSION='))
    if (!line) return ''
    const value = line.split('=')[1] ?? ''
    
    return value.replace(/^"(.+)"$/, '$1').trim()
  } catch {
    return ''
  }
}

const gratifyVersion = readGratifyVersion()

export default defineConfig({
  plugins: [// Docs: https://github.com/posva/unplugin-vue-router
  // ℹ️ This plugin should be placed before vue plugin
    vue({
      template: {
        compilerOptions: {
          isCustomElement: tag => tag === 'swiper-container' || tag === 'swiper-slide',
        },

        transformAssetUrls: {
          base: null,
          includeAbsolute: false,
        },
      },
    }),
    laravel({
      input: ['resources/js/main.js', 'resources/js/gratclient/main.js'],
      refresh: true,
    }),
    vueJsx(), // Docs: https://github.com/vuetifyjs/vuetify-loader/tree/master/packages/vite-plugin
    vuetify({
      styles: {
        configFile: 'resources/styles/variables/_vuetify.scss',
      },
    }),
    // Docs: https://github.com/antfu/unplugin-vue-components#unplugin-vue-components
    Components({
      // Restrict auto-import scanning to keep client bundle lean
      dirs: [
        'resources/js/components',
        'resources/js/gratclient/components',
      ],
      dts: true,
      resolvers: [],
    }), // Docs: https://github.com/antfu/unplugin-auto-import#unplugin-auto-import
    AutoImport({
      imports: ['vue', '@vueuse/core', '@vueuse/math', 'vue-i18n', 'pinia'],
      // Restrict auto-import scanning to the gratclient and shared utils only.
      dirs: [
        './resources/js/gratclient/utils',
        './resources/js/gratclient/composables',
        './resources/js/utils/',
      ],
      vueTemplate: true,

      // ℹ️ Disabled to avoid confusion & accidental usage
      ignore: ['useCookies', 'useStorage'],
      eslintrc: {
        enabled: true,
        filepath: './.eslintrc-auto-import.json',
      },
    }),
    svgLoader(),
    // Avoid shipping devtools into production bundles.
    !isProd && VueDevTools(),
  ],
  define: {
    'process.env': {},
    __GRATIFY_VERSION__: JSON.stringify(gratifyVersion),
  },
  resolve: {
    alias: {
      '@core-scss': fileURLToPath(new URL('./resources/styles/@core', import.meta.url)),
      '@': fileURLToPath(new URL('./resources/js', import.meta.url)),
      '@themeConfig': fileURLToPath(new URL('./themeConfig.js', import.meta.url)),
      '@core': fileURLToPath(new URL('./resources/js/@core', import.meta.url)),
      '@layouts': fileURLToPath(new URL('./resources/js/@layouts', import.meta.url)),
      '@images': fileURLToPath(new URL('./resources/images/', import.meta.url)),
      '@styles': fileURLToPath(new URL('./resources/styles/', import.meta.url)),
      '@configured-variables': fileURLToPath(new URL('./resources/styles/variables/_template.scss', import.meta.url)),
      '@db': fileURLToPath(new URL('./resources/js/plugins/fake-api/handlers/', import.meta.url)),
      '@api-utils': fileURLToPath(new URL('./resources/js/plugins/fake-api/utils/', import.meta.url)),
    },
  },
  build: {
    chunkSizeWarningLimit: 5000,
    rollupOptions: {
      output: {
        // Keep core libs in stable vendor chunks to improve caching and avoid
        // shipping admin-only deps to the student-first load path.
        manualChunks: id => {
          if (!id.includes('node_modules')) return undefined
          if (id.includes('vue')) return 'vue'
          if (id.includes('vuetify')) return 'vuetify'
          if (id.includes('swiper')) return 'swiper'
          if (id.includes('katex') || id.includes('asciimath')) return 'math'
          if (id.includes('chart.js') || id.includes('apexcharts')) return 'charts'
          
          return undefined
        },
      },
    },
  },
  optimizeDeps: {
    exclude: ['vuetify'],
    // Keep pre-bundling focused on the two app entries to avoid pulling admin-only
    // deps into the client dev server.
    entries: [
      './resources/js/main.js',
      './resources/js/gratclient/main.js',
    ],
  },
})
