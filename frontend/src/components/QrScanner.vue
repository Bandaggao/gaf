<script setup>
import { ref, watch, onMounted, onBeforeUnmount } from 'vue'
import { Html5Qrcode } from 'html5-qrcode'

const emit = defineEmits(['scan', 'error'])

const props = defineProps({
  active: { type: Boolean, default: true },
})

const scannerId = `qr-scanner-${Math.random().toString(36).slice(2)}`
const scanning = ref(false)
const error = ref(null)
let scanner = null

async function startScanner() {
  if (!props.active || scanning.value) return

  error.value = null
  scanner = new Html5Qrcode(scannerId)
  const scanConfig = { fps: 10, qrbox: { width: 250, height: 250 } }
  const onDecoded = (decodedText) => emit('scan', decodedText)

  try {
    await scanner.start(
      { facingMode: 'environment' },
      scanConfig,
      onDecoded,
      () => {},
    )
    scanning.value = true
  } catch (err) {
    try {
      const cameras = await Html5Qrcode.getCameras()
      if (!cameras.length) throw err

      await scanner.start(cameras[0].id, scanConfig, onDecoded, () => {})
      scanning.value = true
    } catch (fallbackError) {
      error.value = fallbackError?.message || err?.message || 'Unable to start camera'
    }
  }

  if (error.value) {
    emit('error', error.value)
  }
}

async function stopScanner() {
  if (scanner && scanning.value) {
    try {
      await scanner.stop()
      scanner.clear()
    } catch {
      // ignore cleanup errors
    }
    scanning.value = false
  }
}

watch(
  () => props.active,
  (active) => {
    if (active) {
      startScanner()
    } else {
      stopScanner()
    }
  },
)

onMounted(() => {
  if (props.active) startScanner()
})

onBeforeUnmount(() => {
  stopScanner()
})
</script>

<template>
  <v-card class="app-card" variant="outlined">
    <v-card-text class="pa-4">
      <div :id="scannerId" class="qr-scanner" />
      <v-alert v-if="error" type="error" class="mt-4">{{ error }}</v-alert>
      <div v-if="!scanning && !error" class="text-center py-8 text-medium-emphasis">
        <v-progress-circular indeterminate color="primary" class="mb-3" />
        <div>Starting camera...</div>
      </div>
    </v-card-text>
  </v-card>
</template>

<style scoped>
.qr-scanner {
  width: 100%;
  max-width: 420px;
  margin: 0 auto;
  border-radius: 16px;
  overflow: hidden;
}
</style>
