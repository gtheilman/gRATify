<script setup>
import { useTheme } from 'vuetify'
import ScrollToTop from '@core/components/ScrollToTop.vue'
import TopNav from '@/components/TopNav.vue'
import { useRoute } from 'vue-router'
import initCore from '@core/initCore'
import {
  initConfigStore,
  useConfigStore,
} from '@core/stores/config'
import { hexToRgb } from '@core/utils/colorConverter'

const { global } = useTheme()

// ℹ️ Sync current theme with initial loader theme
initCore()
initConfigStore()

const configStore = useConfigStore()
const route = useRoute()
const showTopNav = computed(() => route.meta.hideTopNav !== true)
const gratifyVersion = __GRATIFY_VERSION__ || ''
const showVersion = computed(() => route.name === 'root' && gratifyVersion)
</script>

<template>
    <VLocaleProvider :rtl="configStore.isAppRTL">
      <!-- ℹ️ This is required to set the background color of active nav link based on currently active global theme's primary -->
      <VApp :style="`--v-global-theme-primary: ${hexToRgb(global.current.value.colors.primary)}`">
        <a href="#main-content" class="skip-link">Skip to main content</a>
        <TopNav v-if="showTopNav" />
        <main id="main-content" role="main">
          <RouterView />
        </main>
        <div v-if="showVersion" class="app-version text-body-2 text-medium-emphasis">
          {{ gratifyVersion }}
        </div>

        <ScrollToTop />
      </VApp>
    </VLocaleProvider>
</template>

<style scoped>
.skip-link {
  position: absolute;
  top: -40px;
  left: 8px;
  padding: 8px 12px;
  background: #fff;
  color: #000;
  border: 1px solid #000;
  z-index: 10000;
}
.skip-link:focus {
  top: 8px;
}
.app-version {
  text-align: center;
  padding: 8px 0 16px;
}
</style>
