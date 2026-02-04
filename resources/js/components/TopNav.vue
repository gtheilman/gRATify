<script setup>
import { useAuthStore } from '@/stores/auth'
import { computed, onBeforeUnmount, onMounted, ref } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useDisplay } from 'vuetify'
import { buildOfflineRetry, offlineBannerMessage } from '@/utils/offlineBanner'
import { handleLogoutAction } from '@/utils/topNavLogout'

const router = useRouter()
const route = useRoute()
const authStore = useAuthStore()
const { mdAndDown } = useDisplay()
const documentationLink = 'https://github.com/gtheilman/gratify'
const menuOpen = ref(false)
const offlineMessage = offlineBannerMessage
const retryOffline = buildOfflineRetry(router)
const migrationWarning = computed(() => authStore.migrationWarning)

const handleLogout = async () => {
  await handleLogoutAction({ logout: authStore.logout, push: router.push })
}

const navButtons = computed(() => {
  const base = [
    { label: 'Home', to: { name: 'root' } },
    { label: 'gRATs', to: { name: 'assessments' } },
    { label: 'Users', to: { name: 'users' } },
    { label: 'Change Password', to: { name: 'change-password' } },
  ]

  if (documentationLink)
  {base.push({ label: 'Documentation', href: documentationLink })}

  return base
})

const isAdmin = computed(() => {
  const role = authStore.user?.role
  const normalized = role === 'poobah' ? 'admin' : role
  
  return normalized === 'admin'
})

const visibleNavButtons = computed(() =>
  navButtons.value.filter(item => item.label !== 'Users' || isAdmin.value),
)

const isLogin = computed(() => route.name === 'login')
const isFullscreen = ref(Boolean(document.fullscreenElement))
const isOffline = ref(typeof navigator !== 'undefined' ? !navigator.onLine : false)

const updateFullscreen = () => {
  isFullscreen.value = Boolean(document.fullscreenElement)
}

const handleOnlineStatus = () => {
  isOffline.value = !navigator.onLine
}

const isMobile = computed(() => mdAndDown.value)

const versionLabel = computed(() => {
  const envVersion = import.meta.env.VITE_APP_VERSION
  
  return envVersion ? `v${envVersion}` : ''
})

onMounted(async () => {
  await authStore.ensureSession()
  document.addEventListener('fullscreenchange', updateFullscreen)
  window.addEventListener('online', handleOnlineStatus)
  window.addEventListener('offline', handleOnlineStatus)
})

onBeforeUnmount(() => {
  document.removeEventListener('fullscreenchange', updateFullscreen)
  window.removeEventListener('online', handleOnlineStatus)
  window.removeEventListener('offline', handleOnlineStatus)
})
</script>

<template>
  <div v-if="!isLogin && !isFullscreen">
    <VAppBar
      flat
      color="surface"
      class="border-b"
    >
      <VToolbarTitle class="font-weight-semibold">
        <span class="app-title">
          <span class="app-title-accent-alt">☑</span>
          <span class="app-title-accent">gRAT</span><span class="app-title-accent-alt">ify</span>
        </span>
        <span v-if="versionLabel"
              class="text-body-2 text-medium-emphasis ms-2"
        >({{ versionLabel }})</span>
      </VToolbarTitle>
      <VSpacer />
      <template v-if="authStore.user">
        <template v-if="!isMobile">
          <VBtn
            v-for="item in visibleNavButtons"
            :key="item.label"
            variant="text"
            color="primary"
            size="small"
            :class="item.label === 'gRATs' ? 'text-none' : 'text-capitalize'"
            :to="item.href ? undefined : item.to"
            :href="item.href"
            :target="item.href ? '_blank' : undefined"
            :rel="item.href ? 'noopener' : undefined"
          >
            {{ item.label }}
          </VBtn>
          <VBtn
            variant="outlined"
            color="primary"
            size="small"
            class="ms-2 me-3 text-capitalize"
            @click="handleLogout"
          >
            Logout
          </VBtn>
        </template>
        <template v-else>
          <VMenu
            v-model="menuOpen"
            transition="fade-transition"
            location="bottom end"
            offset="8"
          >
            <template #activator="{ props }">
              <VBtn
                icon
                color="primary"
                variant="text"
                v-bind="props"
                class="me-2"
                aria-label="Open navigation menu"
              >
                <VIcon icon="tabler-menu-2" />
              </VBtn>
            </template>
            <VList density="compact"
                   nav
            >
              <VListItem
                v-for="item in visibleNavButtons"
                :key="item.label"
                :to="item.href ? undefined : item.to"
                :href="item.href"
                :target="item.href ? '_blank' : undefined"
                :rel="item.href ? 'noopener' : undefined"
                @click="menuOpen = false"
              >
                <VListItemTitle>{{ item.label }}</VListItemTitle>
              </VListItem>
              <VListItem
                @click="() => { handleLogout(); menuOpen = false }"
              >
                <VListItemTitle>Logout</VListItemTitle>
              </VListItem>
            </VList>
          </VMenu>
        </template>
      </template>
      <template v-else>
        <template v-if="!isMobile">
          <div class="guest-nav">
            <VBtn
              variant="text"
              color="primary"
              :to="{ name: 'root' }"
            >
              Home
            </VBtn>
            <VBtn
              variant="outlined"
              color="primary"
              class="ml-2"
              :to="{ name: 'login' }"
            >
              Login
            </VBtn>
          </div>
        </template>
        <template v-else>
          <VMenu
            v-model="menuOpen"
            transition="fade-transition"
            location="bottom end"
            offset="8"
          >
            <template #activator="{ props }">
              <VBtn
                icon
                color="primary"
                variant="text"
                v-bind="props"
                class="me-2"
              >
                <VIcon icon="tabler-menu-2" />
              </VBtn>
            </template>
            <VList density="compact"
                   nav
            >
              <VListItem :to="{ name: 'root' }"
                         @click="menuOpen = false"
              >
                <VListItemTitle>Home</VListItemTitle>
              </VListItem>
              <VListItem :to="{ name: 'login' }"
                         @click="menuOpen = false"
              >
                <VListItemTitle>Login</VListItemTitle>
              </VListItem>
            </VList>
          </VMenu>
        </template>
      </template>
    </VAppBar>
    <VAlert
      v-if="migrationWarning && authStore.user"
      type="warning"
      density="comfortable"
      class="migration-banner"
      border="start"
    >
      Database migrations are required. Run <strong>php artisan migrate</strong>.
      <span v-if="documentationLink">
        See the README on
        <a :href="documentationLink"
           target="_blank"
           rel="noopener noreferrer"
        >GitHub</a>.
      </span>
    </VAlert>
    <VAlert
      v-if="isOffline"
      type="warning"
      density="comfortable"
      class="offline-banner"
      border="start"
      closable
      @click:close="isOffline = false"
    >
      {{ offlineMessage }}
      <VBtn variant="text"
            size="small"
            class="ms-2"
            @click="retryOffline"
      >
        Retry
      </VBtn>
    </VAlert>
  </div>
</template>

<style scoped>
.app-title {
  font-family: 'Nunito', 'Quicksand', 'Poppins', 'Avenir Next', 'Helvetica Neue', Arial, sans-serif;
  font-weight: 800;
}
.app-title-accent {
  color: #7A4A9E;
}
.app-title-accent-alt {
  color: #9ACD32;
}
.text-none {
  text-transform: none;
}
</style>

<style scoped>
.offline-banner {
  position: sticky;
  top: 64px;
  z-index: 5;
}

.migration-banner {
  margin: 0;
}

.guest-nav {
  margin-right: 20px;
  display: flex;
  align-items: center;
}
</style>
