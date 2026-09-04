<script setup>
defineProps({
  label: { type: String, required: true },
  value: { type: [String, Number], default: 0 },
  suffix: { type: String, default: '' },
  icon: { type: String, default: 'mdi-chart-box-outline' },
  color: { type: String, default: 'primary' },
  loading: { type: Boolean, default: false },
})
</script>

<template>
  <v-card class="app-card" :loading="loading">
    <v-card-text class="d-flex align-center ga-4 pa-5">
      <v-avatar :color="color" variant="tonal" size="48" rounded="lg">
        <v-icon :icon="icon" />
      </v-avatar>
      <div>
        <div class="text-caption text-medium-emphasis mb-1">{{ label }}</div>
        <div class="text-h5 font-weight-bold">{{ value }}{{ suffix }}</div>
      </div>
    </v-card-text>
  </v-card>
</template>
