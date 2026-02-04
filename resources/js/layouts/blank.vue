<script setup>
const { injectSkinClasses } = useSkins()

// ℹ️ This will inject classes in body tag for accurate styling
injectSkinClasses()

// SECTION: Loading Indicator
const isFallbackStateActive = ref(false)
const refLoadingIndicator = ref(null)
import TopNav from '@/components/TopNav.vue'
import { useRoute } from 'vue-router'

const route = useRoute()
const showTopNav = computed(() => route.name !== 'login')

watch([
  isFallbackStateActive,
  refLoadingIndicator,
], () => {
  if (isFallbackStateActive.value && refLoadingIndicator.value)
  {refLoadingIndicator.value.fallbackHandle()}
  if (!isFallbackStateActive.value && refLoadingIndicator.value)
  {refLoadingIndicator.value.resolveHandle()}
}, { immediate: true })
// !SECTION
</script>

<template>
  <AppLoadingIndicator ref="refLoadingIndicator" />

  <div
    class="layout-wrapper layout-blank"
    data-allow-mismatch
  >
    <TopNav v-if="showTopNav" />
    <RouterView #="{Component}">
      <Suspense
        :timeout="0"
        @fallback="isFallbackStateActive = true"
        @resolve="isFallbackStateActive = false"
      >
        <Component :is="Component" />
      </Suspense>
    </RouterView>
  </div>
</template>

<style>
.layout-wrapper.layout-blank {
  flex-direction: column;
}
</style>
