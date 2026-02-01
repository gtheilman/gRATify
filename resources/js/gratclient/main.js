// Bootstrap the lightweight student client app with axios + router.
import { createApp } from 'vue'
import App from './App.vue'
import axios from 'axios'
import router from './router'
import 'katex/dist/katex.min.css'

const mountEl = document.querySelector('#app')

axios.defaults.baseURL = import.meta.env.VITE_SERVER_URL || '/'

// Load Font Awesome only for the client app (used in feedback/markdown).
const ensureFontAwesome = () => {
  const existing = document.querySelector('link[data-fa-cdn]')
  if (existing)
    return
  const link = document.createElement('link')

  link.rel = 'stylesheet'
  link.href = 'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css'
  link.crossOrigin = 'anonymous'
  link.referrerPolicy = 'no-referrer'
  link.setAttribute('data-fa-cdn', 'true')
  document.head.appendChild(link)
}

ensureFontAwesome()

const app = createApp(App, { ...(mountEl?.dataset || {}) })

app.config.globalProperties.$axios = axios

app.use(router).mount('#app')
