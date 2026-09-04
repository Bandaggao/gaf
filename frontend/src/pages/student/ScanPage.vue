<script setup>
import { ref, onMounted, onUnmounted } from 'vue'
import { getMyQr } from '@/api/student'
import PageHeader from '@/components/ui/PageHeader.vue'

const loading = ref(true)
const error = ref(null)
const qr = ref(null)
let refreshTimer = null

async function loadQr() {
  error.value = null
  try {
    const { data } = await getMyQr()
    qr.value = data
  } catch (err) {
    error.value = err.response?.data?.message || 'Could not load your QR code. Please try again.'
  } finally {
    loading.value = false
  }
}

function scheduleRefresh() {
  // Refresh at midnight so the QR stays valid
  const now = new Date()
  const midnight = new Date(now)
  midnight.setHours(24, 0, 5, 0) // 00:00:05 next day
  const msUntilMidnight = midnight - now
  refreshTimer = setTimeout(() => {
    loadQr()
    scheduleRefresh()
  }, msUntilMidnight)
}

onMounted(() => {
  loadQr()
  scheduleRefresh()
})

onUnmounted(() => {
  if (refreshTimer) clearTimeout(refreshTimer)
})
</script>

<template>
  <div>
    <PageHeader
      title="My QR Code"
      subtitle="Show this QR code to the gate scanner when entering school. It refreshes every day."
    />

    <v-card class="app-card" :loading="loading" style="max-width: 480px; margin: 0 auto;">
      <v-card-text class="text-center pa-6">
        <template v-if="loading">
          <v-progress-circular indeterminate size="48" color="primary" class="my-8" />
          <p class="text-medium-emphasis mt-2">Generating your QR code...</p>
        </template>

        <template v-else-if="error">
          <v-alert type="error" class="mb-4">{{ error }}</v-alert>
          <v-btn color="primary" variant="tonal" @click="loadQr">Retry</v-btn>
        </template>

        <template v-else-if="qr">
          <!-- QR display -->
          <div class="qr-display mx-auto mb-4" v-html="qr.qr_svg" />

          <p class="text-h6 font-weight-bold mb-1">{{ qr.student_name }}</p>
          <p class="text-body-2 text-medium-emphasis mb-3">{{ qr.student_number }}</p>

          <v-chip color="success" variant="tonal" size="small" prepend-icon="mdi-calendar-check">
            Valid today: {{ qr.valid_for }}
          </v-chip>

          <v-divider class="my-4" />

          <v-alert type="info" variant="tonal" density="compact" class="text-left text-body-2">
            Scan once at the entrance gate. Your teacher will mark you absent if you miss a class
            even after entering school.
          </v-alert>
        </template>
      </v-card-text>
    </v-card>
  </div>
</template>

<style scoped>
.qr-display {
  max-width: 260px;
  padding: 16px;
  border-radius: 20px;
  background: #fff;
  border: 1px solid var(--app-border, #e5e7eb);
  display: inline-block;
}

.qr-display :deep(svg) {
  width: 100%;
  height: auto;
  display: block;
}
</style>
