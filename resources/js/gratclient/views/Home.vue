<template>
  <div id="main">
    <div v-if="assessmentLoaded">
      <Assessment
        :presentation=presentation
        :user_id=user_id
        :password=password
      ></Assessment>
    </div>
    <div v-else>
      <div class="id-card">
        <form @submit.prevent>
          <table>
            <tbody>
              <tr v-if="configError">
                <td><span class="error">{{ configError }}</span></td>
              </tr>
              <tr>
                <td><span class="indexHeader">Enter the identifier provided by your instructor</span></td>
              </tr>
              <tr v-if="errorMessage">
                <td><div class="error" aria-live="polite">{{ errorMessage }}</div></td>
              </tr>
              <tr>
                <td>
                  <input
                    type="text"
                    @keyup.enter="getAssessment"
                    name="user_id"
                    placeholder="Your Identifier"
                    v-model="user_id"
                    :disabled="disabled"
                  >
                </td>
              </tr>
              <tr v-show="disabled"><td align="center">
                <div class="spinner" role="status" aria-label="Loading"></div>
                <div class="loading-text">Loading gRAT…</div>
                <div class="skeleton-card">
                  <div class="skeleton line short"></div>
                  <div class="skeleton line"></div>
                  <div class="skeleton line"></div>
                  <div class="skeleton line"></div>
                </div>
              </td></tr>
              <tr v-show="!disabled"><td><button @click="getAssessment"   class="button" :disabled="!user_id">Submit</button></td></tr>
          </tbody>
          </table>
        </form>
      </div>
    </div>

    <div v-if="toastMessage" class="toast" role="status" aria-live="polite" @click="copyErrorCode">
      {{ toastMessage }}
      <button type="button" class="copy-btn" @click.stop="copyErrorCode">Copy code</button>
    </div>
  </div>

</template>

<script>
// Student entry: caches presentation payloads and auto-resumes using stored identifiers.
import { defineAsyncComponent } from 'vue'
import axios from 'axios'
import { saveIdentifier, loadIdentifier } from '../utils/cache'

const PRESENTATION_CACHE = new Map()
const CACHE_TTL_MS = 5 * 60 * 1000

const cacheKey = (password, userId) => `${password || ''}|${userId || ''}`

const getCacheStorage = () => {
  if (typeof localStorage !== 'undefined') return localStorage
  if (typeof sessionStorage !== 'undefined') return sessionStorage
  return null
}

const readSession = key => {
  try {
    const storage = getCacheStorage()
    if (!storage) return null
    const raw = storage.getItem(key)
    return raw ? JSON.parse(raw) : null
  } catch {
    return null
  }
}

const writeSession = (key, value) => {
  try {
    const storage = getCacheStorage()
    if (!storage) return
    storage.setItem(key, JSON.stringify(value))
  } catch {
    /* ignore quota errors */
  }
}

const getCachedPresentation = (password, userId) => {
  const key = cacheKey(password, userId)
  const now = Date.now()

  // In-memory first
  const cached = PRESENTATION_CACHE.get(key)
  if (cached) {
    if ((now - cached.cachedAt) > CACHE_TTL_MS) {
      PRESENTATION_CACHE.delete(key)
    } else {
      return cached
    }
  }

  // Fallback to sessionStorage
  const persisted = readSession(key)
  if (!persisted) return null
  if ((now - persisted.cachedAt) > CACHE_TTL_MS) {
    const storage = getCacheStorage()
    storage?.removeItem(key)
    return null
  }
  // hydrate in-memory for faster next lookup
  PRESENTATION_CACHE.set(key, persisted)
  return persisted
}

const setCachedPresentation = (password, userId, data) => {
  const key = cacheKey(password, userId)
  const payload = {
    data,
    cachedAt: Date.now()
  }
  PRESENTATION_CACHE.set(key, payload)
  writeSession(key, payload)
}
export default {
  name: 'HomeView',
  data () {
    return {
      assessmentLoaded: false,
      password: null,
      presentation: [],
      user_id: null,
      disabled: false,
      configError: '',
      errorMessage: '',
      errorCode: '',
      autoResumeAttempted: false,
      toastMessage: ''
    }
  },
  components: {
    Assessment: defineAsyncComponent(() => import('../components/Assessment.vue'))
  },
  props: [],
  created () {
    if (this.user_id !== null) {
      this.showIdBox = false
    }
  },

  methods: {
    tryAutoResume () {
      if (this.autoResumeAttempted) return
      this.autoResumeAttempted = true
      this.password = this.$route.params.password
      // Restore the last identifier for this assessment when available.
      const cachedIdentifier = loadIdentifier(this.password)
      if (cachedIdentifier) {
        this.user_id = cachedIdentifier
        this.fetchPresentation(this.user_id)
      }
    },
    getAssessment () {
      if (!this.serverUrl) {
        this.configError = 'Configuration missing, contact instructor.'
        return
      }
      if (this.user_id) {
        this.fetchPresentation(this.user_id)
      } else {
        alert('Please enter your identifier')
      }
    },
    async fetchPresentation (userId) {
      this.disabled = true
      this.errorMessage = ''
      this.password = this.$route.params.password

      // Render from cache immediately, then refresh from the server.
      const cached = getCachedPresentation(this.password, userId)
      if (cached?.data) {
        this.hydratePresentation(cached.data)
        saveIdentifier(this.password, userId)
      }

      try {
        const response = await axios.get(`/api/presentations/store/${this.password}/${userId}`)
        const sorted = response.data.assessment.questions.sort((x, y) => x.sequence - y.sequence)
        response.data.assessment.questions = sorted
        setCachedPresentation(this.password, userId, response.data)
        this.hydratePresentation(response.data)
        saveIdentifier(this.password, userId)
      } catch (error) {
        this.errorCode = `ERR-${Date.now().toString(36)}`
        const status = error?.response?.status
        if (status === 404) {
          this.errorMessage = 'gRAT not found. Check the code or link.'
        } else if (status === 403) {
          this.errorMessage = 'This gRAT is closed or inactive.'
        } else if (status === 429) {
          this.errorMessage = 'Too many requests. Please wait a moment and try again.'
        } else {
          this.errorMessage = `Request failed (code ${this.errorCode}). Show this error to your instructor.`
        }
        this.toastMessage = `Error ${this.errorCode}. Click to copy.`
        // eslint-disable-next-line no-console
        console.error(`gRAT fetch failed (${this.errorCode})`, error)
        this.disabled = false
      }
    },
    hydratePresentation (data) {
      if (!data?.assessment?.questions) return
      this.presentation = data
      this.assessmentLoaded = true
      this.disabled = false
    },
    copyErrorCode () {
      if (!this.errorCode) return
      if (navigator?.clipboard?.writeText) {
        navigator.clipboard.writeText(this.errorCode).catch(() => {})
      }
    }
  },
  computed: {
    serverUrl () {
      const env = (globalThis.importMetaEnv ?? (typeof import.meta !== 'undefined' ? import.meta.env : {})) || {}
      return env?.VITE_SERVER_URL || '/'
    }
  },
  mounted () {
    this.tryAutoResume()
  },
  watch: {
    toastMessage (val) {
      if (val) {
        setTimeout(() => { this.toastMessage = '' }, 5000)
      }
    }
  }
}
</script>

<style scoped>
html,
body {
  position: relative;
  height: 100%;
}

body {
  background: white;
  font-family: Helvetica Neue, Helvetica, Arial, sans-serif;
  font-size: clamp(1rem, 4vw, 1.5rem);
  color: #000;
  margin: 0;
  padding: 0;
}

#main {
  position: fixed;
  top: 50%;
  left: 50%;
  /* bring your own prefixes */
  transform: translate(-50%, -50%);

}

table {
  width: 100%;
  border-collapse: collapse;
}
.indexHeader {
  font-size: clamp(1.1rem, 3vw, 1.6rem);
  display: inline-block;
  max-width: 100%;
  word-break: break-word;
  padding-bottom: 8px;
  border-bottom: 1px solid rgba(15, 23, 42, 0.08);
}

.id-card {
  background: #fff;
  border: 1px solid #e5e7eb;
  box-shadow: 0 12px 30px rgba(15, 23, 42, 0.12);
  border-radius: 16px;
  padding: clamp(16px, 4vw, 28px);
  max-width: 520px;
  margin: 0 auto;
}

input[type=text] {
  border-radius: 10px;
  font-size: clamp(1rem, 3.2vw, 1.4rem);
  background: #fff;
  color: #000;
  border: 1px solid #cbd5e1;
  box-shadow: inset 0 1px 2px rgba(15, 23, 42, 0.08);
  padding: 0.6em 0.9em;
  margin: 0.85em 0;
  cursor: text;
  caret-color: #000;
  width: 100%;
  box-sizing: border-box;

}
input[type=password] {
  border-radius: 10px;
  font-size: 4vw;

}
.button {
  display: inline-block;
  padding: 0.3em 1.2em;
  margin: 0 0.3em 0.3em 0;
  border-radius: 2em;
  box-sizing: border-box;
  text-decoration: none;
  font-family: 'Roboto', sans-serif;
  font-weight: 300;
  color: #FFFFFF;
  background: linear-gradient(180deg, #61c2ff 0%, #3f9dd8 100%);
  border: 2px solid rgba(0, 0, 0, 0.06);
  text-align: center;
  transition: all 0.2s;
  font-size: clamp(1rem, 3vw, 1.35rem);
}

.button:hover {
  background: linear-gradient(180deg, #4fb6f2 0%, #2f8fc8 100%);
  cursor: pointer;
}

@media (max-width: 640px) {
  .button {
    width: 100%;
    text-align: center;
    margin: 0.4em 0;
    padding: 0.5em 1.2em;
  }
}

.spinner {
  width: clamp(64px, 20vw, 80px);
  height: clamp(64px, 20vw, 80px);
  border: 10px solid #e0e0e0;
  border-top-color: #4eb5f1;
  border-radius: 50%;
  animation: spin 1s linear infinite;
  margin: 20px auto;
}

input[type=text]:focus {
  outline: none;
  border-color: #3b82f6;
  box-shadow:
    inset 0 1px 2px rgba(15, 23, 42, 0.08),
    0 0 0 3px rgba(59, 130, 246, 0.16);
  transition: box-shadow 150ms ease, border-color 150ms ease;
}

input[type=text]::placeholder {
  color: #6b7280;
  font-size: clamp(0.95rem, 3vw, 1.2rem);
}

@keyframes spin {
  to { transform: rotate(360deg); }
}

.loading-text {
  margin-top: 8px;
  font-size: clamp(0.9rem, 2.5vw, 1rem);
  color: #555;
}

.error {
  color: #b00;
  font-weight: bold;
}

.skeleton-card {
  margin-top: 12px;
  padding: 12px;
  border: 1px solid #eee;
  border-radius: 8px;
  width: 80%;
  margin-left: auto;
  margin-right: auto;
}
.skeleton {
  background: linear-gradient(90deg, #f0f0f0 25%, #e0e0e0 37%, #f0f0f0 63%);
  border-radius: 4px;
  height: 14px;
  margin: 8px 0;
  background-size: 400% 100%;
  animation: shimmer 1.4s ease infinite;
}
.skeleton.short { width: 50%; }
.skeleton.line { width: 100%; }

@keyframes shimmer {
  0% { background-position: 100% 0; }
  100% { background-position: 0 0; }
}

.toast {
  position: fixed;
  bottom: 16px;
  right: 16px;
  background: #333;
  color: #fff;
  padding: 12px 16px;
  border-radius: 8px;
  box-shadow: 0 4px 12px rgba(0,0,0,0.2);
  display: flex;
  align-items: center;
  gap: 8px;
  cursor: pointer;
}
.copy-btn {
  background: #fff;
  color: #333;
  border: none;
  border-radius: 6px;
  padding: 4px 8px;
  cursor: pointer;
  font-size: 12px;
}

@media all and (max-width: 30em) {
  button {
    display: block;
    margin: 0.2em auto;
  }

  .indexHeader {
    border-bottom-color: rgba(15, 23, 42, 0.05);
  }
}

</style>
