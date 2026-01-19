<script setup>
defineProps({
  message: {
    type: [String, Object],
    required: true,
  },
  retryLabel: {
    type: String,
    default: 'Retry',
  },
  showRefresh: {
    type: Boolean,
    default: false,
  },
  refreshLabel: {
    type: String,
    default: 'Refresh page',
  },
})

const emit = defineEmits(['close', 'retry', 'refresh'])
</script>

<template>
  <VAlert
    type="error"
    closable
    class="mb-4"
    @click:close="emit('close')"
  >
    <div class="d-flex flex-column gap-2">
      <div>{{ message }}</div>
      <div class="d-flex flex-wrap gap-2 align-center">
        <VBtn
          v-if="retryLabel"
          variant="text"
          color="primary"
          size="small"
          @click="emit('retry')"
        >
          {{ retryLabel }}
        </VBtn>
        <VBtn
          v-if="showRefresh"
          variant="text"
          color="secondary"
          size="small"
          @click="emit('refresh')"
        >
          {{ refreshLabel }}
        </VBtn>
        <slot name="actions" />
      </div>
    </div>
  </VAlert>
</template>
