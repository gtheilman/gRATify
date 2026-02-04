<script setup>
// Projector view: shows the student URL as text + QR, with fullscreen support.
import { computed, nextTick, onBeforeUnmount, onMounted, ref } from 'vue'
import { useRoute } from 'vue-router'
import { useAssessmentsStore } from '@/stores/assessments'

const route = useRoute()
const assessmentsStore = useAssessmentsStore()

const assessment = ref(null)
const shortUrl = ref('')
const qrDataUrl = ref('')
const loading = ref(true)
const bitlyError = ref('')
const showBitlyError = ref(false)
const urlFontSize = ref(56) // px, will be adjusted to fit container
const urlEl = ref(null)

let qrLibPromise

const loadQrLib = async () => {
  // Lazy-load the browser build to avoid Vite trying to bundle the Node target.
  qrLibPromise ??= import('qrcode/lib/browser').then(mod => mod.default || mod)
  
  return qrLibPromise
}

const fullScreenActive = ref(false)

const handleFsChange = () => {
  fullScreenActive.value = Boolean(document.fullscreenElement)
}

const handleKeydown = event => {
  if (event.defaultPrevented)
  {return}
  if (event.metaKey || event.ctrlKey || event.altKey)
  {return}
  if (event.key !== 'f' && event.key !== 'F')
  {return}
  event.preventDefault()
  toggleFullscreen()
}

const toggleFullscreen = async () => {
  try {
    if (!document.fullscreenElement) {
      await document.documentElement.requestFullscreen()
    }
    else {
      await document.exitFullscreen()
    }
  }
  catch (e) {
    // ignore
  }
}

const generateQr = async text => {
  try {
    const qr = await loadQrLib()

    qrDataUrl.value = await qr.toDataURL(text || '')
  }
  catch (e) {
    qrDataUrl.value = ''
  }
}

const shrinkUrlToFit = async () => {
  await nextTick()

  const el = urlEl.value
  if (!el)
  {return}

  const parentWidth = el.parentElement?.clientWidth ?? el.clientWidth
  if (!parentWidth)
  {return}

  const maxSize = 150
  const minSize = 16

  // Measure at max size, then scale proportionally so it fills the space without wrapping.
  urlFontSize.value = maxSize
  await nextTick()

  const contentWidth = el.scrollWidth || 1
  const scale = Math.min(1, (parentWidth * 0.98) / contentWidth)

  urlFontSize.value = Math.max(minSize, Math.floor(maxSize * scale))
}

const handleResize = () => {
  shrinkUrlToFit()
}

const displayUrl = computed(() => (shortUrl.value || '').replace(/^https?:\/\//, ''))
const copyStatus = ref('')

const coloredUrl = computed(() => displayUrl.value.split('').map(char => {
  if (/\d/.test(char))
  {return { char, class: 'url-num' }}
  if (/[A-Z]/.test(char))
  {return { char, class: 'url-upper' }}
  if (/[a-z]/.test(char))
  {return { char, class: 'url-lower' }}
  
  return { char, class: 'url-other' }
}))

const copyUrl = async () => {
  if (!shortUrl.value)
  {return}
  try {
    await navigator.clipboard.writeText(shortUrl.value)
    copyStatus.value = 'Copied'
  }
  catch {
    copyStatus.value = 'Copy failed'
  }
  setTimeout(() => {
    copyStatus.value = ''
  }, 1200)
}

onMounted(async () => {
  try {
    if (!assessmentsStore.assessments.length)
    {await assessmentsStore.fetchAssessments()}
    await assessmentsStore.loadAssessment(route.params.id)
    assessment.value = assessmentsStore.currentAssessment
    if (assessment.value) {
      // Prefer stored short URLs but fall back to the direct client URL.
      const clientUrl = `${window.location.origin}/client/${assessment.value.password || ''}`
      const candidateUrl = assessment.value.short_url || clientUrl

      shortUrl.value = candidateUrl
      await generateQr(candidateUrl)
      if (assessment.value.bitly_error) {
        bitlyError.value = assessment.value.bitly_error
        showBitlyError.value = true
      }
    }
    shrinkUrlToFit()
  }
  finally {
    loading.value = false
    window.addEventListener('resize', handleResize)
    document.addEventListener('fullscreenchange', handleFsChange)
    window.addEventListener('keydown', handleKeydown)
  }
})

onBeforeUnmount(() => {
  window.removeEventListener('resize', handleResize)
  document.removeEventListener('fullscreenchange', handleFsChange)
  window.removeEventListener('keydown', handleKeydown)
})
</script>

<template>
  <VContainer fluid
              class="py-10 projector-page"
              :class="[{ 'fullscreen-active': fullScreenActive }]"
  >
    <VRow justify="center">
      <VCol cols="12">
        <div class="menu-spacer" />
        <VCard
          class="text-center py-6 px-6 projector-card surface-glass surface-glass-blur card-radius-lg elevate-soft fade-slide-up"
          elevation="0"
        >
          <div class="copy-url">
            <VTooltip location="bottom">
              <template #activator="{ props }">
                <VBtn
                  v-bind="props"
                  icon
                  size="small"
                  variant="text"
                  color="secondary"
                  :disabled="!shortUrl"
                  @click="copyUrl"
                >
                  <VIcon :icon="copyStatus === 'Copied' ? 'tabler-check' : 'tabler-copy'" />
                </VBtn>
              </template>
              <span>{{ copyStatus || 'Copy URL' }}</span>
            </VTooltip>
          </div>
          <VCardTitle class="justify-center text-h4 mb-6 title-dark">
            Questions
          </VCardTitle>
          <VCardText>
            <div class="projector-url mb-6">
              <span ref="urlEl"
                    :style="{ fontSize: `${urlFontSize}px` }"
              >
                <template v-if="shortUrl">
                  <span
                    v-for="(segment, idx) in coloredUrl"
                    :key="idx"
                    class="url-ch"
                    :class="[segment.class]"
                  >{{ segment.char }}</span>
                </template>
                <template v-else-if="loading">
                  <span class="url-loading">Loading link…</span>
                </template>
                <template v-else>
                  N/A
                </template>
              </span>
            </div>

            <div v-if="loading"
                 class="d-flex justify-center flex-column align-center gap-3"
            >
              <VProgressCircular indeterminate
                                 color="primary"
                                 size="52"
              />
              <div class="text-medium-emphasis">
                Generating QR code…
              </div>
            </div>
            <div v-else-if="qrDataUrl"
                 class="d-flex justify-center"
            >
              <div class="qr-wrapper">
                <img :src="qrDataUrl"
                     alt="gRAT QR code"
                     class="qr-img"
                >
              </div>
            </div>
            <div v-else
                 class="text-medium-emphasis"
            >
              QR code unavailable.
            </div>
          </VCardText>
          <div class="progress-link">
            <RouterLink
              :to="{ name: 'assessment-progress', params: { id: route.params.id } }"
              class="progress-link__anchor"
            >
              View progress for this gRAT
            </RouterLink>
          </div>
        </VCard>
      </VCol>
    </VRow>

    <VBtn
      class="fullscreen-toggle"
      size="large"
      color="primary"
      variant="tonal"
      @click="toggleFullscreen"
    >
      {{ fullScreenActive ? 'Exit Fullscreen' : 'Enter Fullscreen' }}
    </VBtn>

    <VDialog
      v-model="showBitlyError"
      max-width="520"
    >
      <VCard>
        <VCardTitle class="text-h6">
          Short URL unavailable
        </VCardTitle>
        <VCardText class="text-body-2">
          <p class="mb-2">
            A short-link provider is configured, but we couldn’t generate a short link. The long URL below still works, so you can keep using it with your class.
          </p>
          <p class="text-caption mb-2">
            Some providers won’t shorten localhost/127.0.0.1 URLs; if you’re testing locally, use the long URL.
          </p>
          <p class="font-mono text-caption break-all mb-3">
            {{ shortUrl }}
          </p>
          <p v-if="bitlyError"
             class="text-caption text-error mb-0"
          >
            Error: {{ bitlyError }}
          </p>
        </VCardText>
        <VCardActions class="justify-end">
          <VBtn
            color="primary"
            variant="elevated"
            @click="showBitlyError = false"
          >
            Dismiss
          </VBtn>
        </VCardActions>
      </VCard>
    </VDialog>
  </VContainer>
</template>

<style scoped>
.projector-page {
  padding: 24px 0 16px;
  background: radial-gradient(circle at 20% 20%, rgba(59, 130, 246, 0.18), transparent 32%),
    radial-gradient(circle at 80% 10%, rgba(6, 182, 212, 0.16), transparent 30%),
    #0b1220;
  min-height: 100vh;
  display: flex;
  align-items: center;
}

.projector-card {
  max-width: 1800px;
  width: min(99vw, 1800px);
  margin: 0 auto;
  background: #ffffff;
  color: #0b1220;
  border: 1px solid #f3f4f6;
  box-shadow: 0 12px 32px rgba(0, 0, 0, 0.08);
  padding: clamp(12px, 5vw, 64px);
  min-height: 90vh;
  display: flex;
  flex-direction: column;
  justify-content: center;
  position: relative;
}

.copy-url {
  position: absolute;
  top: 12px;
  right: 12px;
  opacity: 0.6;
}

.copy-url:hover {
  opacity: 1;
}

.progress-link {
  margin-top: 12px;
  font-size: 0.75rem;
  text-align: center;
}

.progress-link__anchor {
  color: rgba(11, 18, 32, 0.7);
  text-decoration: underline;
  text-underline-offset: 3px;
}

.progress-link__anchor:hover {
  color: rgba(11, 18, 32, 0.95);
}

.projector-url {
  font-weight: 800;
  font-family: 'Consolas', 'Inconsolata', 'SFMono-Regular', Menlo, Monaco, 'Fira Code', 'Source Code Pro', monospace;
  letter-spacing: 0.04em;
  white-space: nowrap;
  overflow: hidden;
  line-height: 1.05;
  padding-bottom: clamp(20px, 8vw, 64px);
  text-shadow: 0 4px 16px rgba(0, 0, 0, 0.35), 0 0 1px rgba(255, 255, 255, 0.35);
}

.projector-url span {
  display: inline-block;
  max-width: 100%;
}

.url-loading {
  color: rgba(0, 0, 0, 0.5);
  letter-spacing: 0.04em;
}

.url-ch {
  transition: color 0.2s ease;
}

.url-lower {
  color: #000000;
}

.url-upper {
  color: #16a34a; /* green */
}

.url-num {
  color: #e53935; /* red */
}

.url-other {
  color: #000000;
}

.fullscreen-toggle {
  position: fixed;
  bottom: 16px;
  left: 16px;
  z-index: 10;
}

.projector-card :deep(.v-card-title) {
  font-size: clamp(2rem, 4vw, 3rem);
  font-weight: 700;
  color: #0b1220;
}

.title-dark {
  color: #000000 !important;
}

.projector-password {
  font-size: 2.25rem;
  font-weight: 700;
  letter-spacing: 0.06em;
}

.mono {
  font-family: 'Consolas', 'Inconsolata', 'SFMono-Regular', Menlo, Monaco, 'Fira Code', 'Source Code Pro', monospace;
}

.qr-wrapper {
  position: relative;
  display: block;
  width: clamp(288px, 48vw, 672px); /* 20% larger */
  padding: clamp(14px, 3.6vw, 24px);
}

.qr-img {
  display: block;
  width: 100%;
  height: auto;
  filter: drop-shadow(0 10px 24px rgba(0, 0, 0, 0.35));
  background: #fff;
  padding: 12px;
  border-radius: 16px;
  box-shadow: 0 14px 36px rgba(0, 0, 0, 0.35);
}

.qr-wrapper::before {
  content: '';
  position: absolute;
  inset: 0;
  z-index: -1;
  border-radius: 20px;
  background: radial-gradient(circle, rgba(255, 255, 255, 0.14), transparent 60%);
}

.menu-spacer {
  height: 60px;
  width: 100%;
}

.fullscreen-active .menu-spacer {
  display: none;
}

@media (max-width: 768px) {
  .projector-card {
    padding: clamp(16px, 4vw, 32px) !important;
  }

  .v-card-title {
    font-size: clamp(1.25rem, 5vw, 1.75rem) !important;
  }
}

@media (max-width: 480px) {
  .projector-page {
    padding-top: 80px;
  }
}
</style>
