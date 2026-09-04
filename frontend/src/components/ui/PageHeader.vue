<script setup>
defineProps({
  title: { type: String, required: true },
  subtitle: { type: String, default: '' },
})
</script>

<template>
  <div class="d-flex flex-column flex-sm-row align-sm-center justify-space-between gap-4 mb-6">
    <div>
      <h1 class="text-h5 font-weight-bold mb-1">{{ title }}</h1>
      <p v-if="subtitle" class="text-body-2 text-medium-emphasis mb-0">{{ subtitle }}</p>
    </div>
    <div v-if="$slots.actions" class="d-flex flex-wrap gap-2">
      <slot name="actions" />
    </div>
  </div>
</template>
