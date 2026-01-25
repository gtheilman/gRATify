import { onBeforeUnmount, onMounted, ref } from 'vue'

export const useOffline = () => {
  const isOffline = ref(typeof navigator !== 'undefined' ? !navigator.onLine : false)

  const update = () => {
    isOffline.value = !navigator.onLine
  }

  onMounted(() => {
    window.addEventListener('online', update)
    window.addEventListener('offline', update)
  })

  onBeforeUnmount(() => {
    window.removeEventListener('online', update)
    window.removeEventListener('offline', update)
  })

  return { isOffline }
}
