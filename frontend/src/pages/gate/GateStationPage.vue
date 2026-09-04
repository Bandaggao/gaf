<script setup>
import { ref, computed, onMounted, onUnmounted, nextTick } from 'vue'
import { getGateKey, setGateKey, clearGateKey, scanGateToken } from '@/api/gate'

const gateKeyInput = ref('')
const configured = ref(!!getGateKey())
const scanInput = ref('')
const scanInputEl = ref(null)
const submitting = ref(false)
const result = ref(null)
const recent = ref([])
const clock = ref(new Date())

let resultTimer = null
let clockTimer = null
let focusTimer = null

const resultTone = computed(() => {
  if (!result.value) return 'idle'
  if (result.value.status === 'ok') return 'success'
  if (result.value.status === 'already_scanned') return 'warning'
  return 'error'
})

const resultTitle = computed(() => {
  if (!result.value) return 'Ready to scan'
  if (result.value.status === 'ok') return 'Welcome'
  if (result.value.status === 'already_scanned') return 'Already recorded'
  return 'Scan failed'
})

const resultMessage = computed(() => {
  if (!result.value) return 'Point the student QR at the scanner'
  return result.value.message || ''
})

function saveKey() {
  const key = gateKeyInput.value.trim()
  if (!key) return
  setGateKey(key)
  configured.value = true
  nextTick(focusScanInput)
}

function disconnect() {
  clearGateKey()
  configured.value = false
  gateKeyInput.value = ''
  result.value = null
  recent.value = []
}

function focusScanInput() {
  scanInputEl.value?.focus()
}

async function handleScanSubmit() {
  const token = scanInput.value.trim()
  scanInput.value = ''

  if (!token || submitting.value) {
    focusScanInput()
    return
  }

  submitting.value = true
  if (resultTimer) clearTimeout(resultTimer)

  try {
    const { data } = await scanGateToken(token)
    showResult({
      status: data.status,
      message: data.message,
      student_name: data.data?.student_name,
      student_number: data.data?.student_number,
      scanned_at: data.data?.scanned_at || data.data?.first_scanned_at,
    })
  } catch (err) {
    const status = err.response?.data?.status || 'invalid'
    const message =
      err.response?.data?.message ||
      (err.response?.status === 401
        ? 'Gate key rejected. Reconnect with the correct key.'
        : 'Could not reach the server.')

    if (err.response?.status === 401) {
      clearGateKey()
      configured.value = false
    }

    showResult({
      status,
      message,
      student_name: err.response?.data?.data?.student_name,
      student_number: err.response?.data?.data?.student_number,
    })
  } finally {
    submitting.value = false
    focusScanInput()
  }
}

function showResult(payload) {
  result.value = payload

  if (payload.student_name) {
    recent.value = [
      {
        name: payload.student_name,
        number: payload.student_number,
        status: payload.status,
        at: new Date().toLocaleTimeString([], { hour: '2-digit', minute: '2-digit', second: '2-digit' }),
      },
      ...recent.value,
    ].slice(0, 8)
  }

  resultTimer = setTimeout(() => {
    result.value = null
    focusScanInput()
  }, 3500)
}

function onWindowFocus() {
  if (configured.value) focusScanInput()
}

onMounted(() => {
  clockTimer = setInterval(() => {
    clock.value = new Date()
  }, 1000)

  focusTimer = setInterval(() => {
    if (configured.value && document.activeElement !== scanInputEl.value) {
      focusScanInput()
    }
  }, 1500)

  window.addEventListener('focus', onWindowFocus)
  nextTick(focusScanInput)
})

onUnmounted(() => {
  if (resultTimer) clearTimeout(resultTimer)
  if (clockTimer) clearInterval(clockTimer)
  if (focusTimer) clearInterval(focusTimer)
  window.removeEventListener('focus', onWindowFocus)
})
</script>

<template>
  <div class="gate-station" :class="`gate-station--${resultTone}`">
    <!-- Setup: enter gate API key once on this laptop -->
    <div v-if="!configured" class="gate-setup">
      <div class="gate-setup-card">
        <div class="gate-badge">Gate Station</div>
        <h1>Connect scanner laptop</h1>
        <p>
          Enter the <strong>GATE_SCANNER_KEY</strong> from the backend
          <code>.env</code> file. This laptop will keep the key and stay ready for USB QR scans.
        </p>

        <v-text-field
          v-model="gateKeyInput"
          label="Gate scanner key"
          type="password"
          variant="outlined"
          hide-details
          class="mb-4"
          @keyup.enter="saveKey"
        />

        <v-btn color="primary" size="large" block :disabled="!gateKeyInput.trim()" @click="saveKey">
          Start Gate Station
        </v-btn>

        <p class="hint">
          Tip: open this page fullscreen, plug in the USB scanner, then scan student phones.
        </p>
      </div>
    </div>

    <!-- Live station -->
    <template v-else>
      <header class="gate-header">
        <div>
          <div class="gate-badge">GAFS A-Watch · Gate Station</div>
          <div class="gate-clock">
            {{ clock.toLocaleDateString(undefined, { weekday: 'long', month: 'short', day: 'numeric' }) }}
            ·
            {{ clock.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit', second: '2-digit' }) }}
          </div>
        </div>
        <v-btn variant="text" color="inherit" size="small" @click="disconnect">Disconnect</v-btn>
      </header>

      <main class="gate-main">
        <div class="gate-status-card">
          <v-icon size="72" class="mb-4">
            {{
              resultTone === 'success'
                ? 'mdi-check-circle'
                : resultTone === 'warning'
                  ? 'mdi-information'
                  : resultTone === 'error'
                    ? 'mdi-close-circle'
                    : 'mdi-qrcode-scan'
            }}
          </v-icon>

          <h1>{{ resultTitle }}</h1>
          <p class="gate-message">{{ resultMessage }}</p>

          <template v-if="result?.student_name">
            <div class="student-name">{{ result.student_name }}</div>
            <div class="student-number">{{ result.student_number }}</div>
          </template>

          <div v-else-if="!result" class="waiting-pulse">Waiting for next scan…</div>

          <v-progress-circular v-if="submitting" indeterminate color="white" class="mt-6" />
        </div>

        <!-- Hidden input — USB scanners type here then press Enter -->
        <form class="scan-trap" @submit.prevent="handleScanSubmit">
          <input
            ref="scanInputEl"
            v-model="scanInput"
            class="scan-input"
            autocomplete="off"
            autocapitalize="off"
            spellcheck="false"
            aria-label="Scanner input"
          />
        </form>
      </main>

      <aside class="gate-recent" v-if="recent.length">
        <h2>Recent scans</h2>
        <ul>
          <li v-for="(item, index) in recent" :key="`${item.at}-${index}`">
            <span class="dot" :class="`dot--${item.status}`" />
            <span class="name">{{ item.name }}</span>
            <span class="meta">{{ item.number }} · {{ item.at }}</span>
          </li>
        </ul>
      </aside>
    </template>
  </div>
</template>

<style scoped>
.gate-station {
  min-height: 100vh;
  background:
    radial-gradient(circle at top, rgba(255, 255, 255, 0.12), transparent 40%),
    linear-gradient(160deg, #0f172a 0%, #1e293b 55%, #0b1220 100%);
  color: #f8fafc;
  padding: 24px;
  display: flex;
  flex-direction: column;
  transition: background 0.35s ease;
}

.gate-station--success {
  background: linear-gradient(160deg, #065f46 0%, #047857 45%, #064e3b 100%);
}

.gate-station--warning {
  background: linear-gradient(160deg, #92400e 0%, #b45309 45%, #78350f 100%);
}

.gate-station--error {
  background: linear-gradient(160deg, #7f1d1d 0%, #b91c1c 45%, #450a0a 100%);
}

.gate-setup {
  min-height: calc(100vh - 48px);
  display: grid;
  place-items: center;
}

.gate-setup-card {
  width: min(480px, 100%);
  background: rgba(255, 255, 255, 0.96);
  color: #0f172a;
  border-radius: 24px;
  padding: 32px;
  box-shadow: 0 20px 60px rgba(0, 0, 0, 0.35);
}

.gate-setup-card h1 {
  font-size: 1.75rem;
  margin: 8px 0 12px;
}

.gate-setup-card p {
  color: #475569;
  margin-bottom: 20px;
  line-height: 1.5;
}

.gate-setup-card code {
  background: #e2e8f0;
  padding: 1px 6px;
  border-radius: 6px;
  font-size: 0.9em;
}

.hint {
  margin-top: 16px !important;
  font-size: 0.9rem;
}

.gate-badge {
  display: inline-flex;
  align-items: center;
  padding: 4px 10px;
  border-radius: 999px;
  background: rgba(255, 255, 255, 0.14);
  font-size: 0.8rem;
  letter-spacing: 0.04em;
  text-transform: uppercase;
  font-weight: 600;
}

.gate-setup-card .gate-badge {
  background: #dbeafe;
  color: #1d4ed8;
}

.gate-header {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  gap: 16px;
  margin-bottom: 24px;
}

.gate-clock {
  margin-top: 8px;
  opacity: 0.85;
  font-size: 1rem;
}

.gate-main {
  flex: 1;
  display: grid;
  place-items: center;
  padding: 12px 0 24px;
}

.gate-status-card {
  text-align: center;
  max-width: 820px;
}

.gate-status-card h1 {
  font-size: clamp(2.4rem, 6vw, 4.5rem);
  font-weight: 700;
  line-height: 1.05;
  margin: 0 0 12px;
}

.gate-message {
  font-size: clamp(1.1rem, 2.5vw, 1.5rem);
  opacity: 0.9;
  margin: 0 0 20px;
}

.student-name {
  font-size: clamp(2rem, 5vw, 3.5rem);
  font-weight: 700;
  margin-top: 8px;
}

.student-number {
  font-size: 1.25rem;
  opacity: 0.85;
  margin-top: 4px;
}

.waiting-pulse {
  margin-top: 24px;
  font-size: 1.15rem;
  opacity: 0.75;
  animation: pulse 1.8s ease-in-out infinite;
}

@keyframes pulse {
  0%,
  100% {
    opacity: 0.45;
  }
  50% {
    opacity: 1;
  }
}

.scan-trap {
  position: absolute;
  width: 1px;
  height: 1px;
  overflow: hidden;
  clip: rect(0, 0, 0, 0);
}

.scan-input {
  opacity: 0;
  width: 1px;
  height: 1px;
  border: 0;
  padding: 0;
}

.gate-recent {
  background: rgba(15, 23, 42, 0.35);
  border: 1px solid rgba(255, 255, 255, 0.12);
  border-radius: 18px;
  padding: 16px 20px;
  max-width: 720px;
  width: 100%;
  margin: 0 auto;
}

.gate-recent h2 {
  font-size: 0.85rem;
  text-transform: uppercase;
  letter-spacing: 0.06em;
  opacity: 0.7;
  margin: 0 0 12px;
}

.gate-recent ul {
  list-style: none;
  padding: 0;
  margin: 0;
  display: grid;
  gap: 8px;
}

.gate-recent li {
  display: grid;
  grid-template-columns: 12px 1fr auto;
  gap: 10px;
  align-items: center;
}

.dot {
  width: 10px;
  height: 10px;
  border-radius: 50%;
  background: #94a3b8;
}

.dot--ok {
  background: #4ade80;
}

.dot--already_scanned {
  background: #fbbf24;
}

.dot--invalid,
.dot--error {
  background: #f87171;
}

.name {
  font-weight: 600;
}

.meta {
  opacity: 0.7;
  font-size: 0.9rem;
}
</style>
