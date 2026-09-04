<script setup>
import { ref, onMounted, computed } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { getSession, closeSession, getSessionRoster, updateAttendance } from '@/api/teacher'
import PageHeader from '@/components/ui/PageHeader.vue'
import StatusChip from '@/components/ui/StatusChip.vue'

const route = useRoute()
const router = useRouter()
const loading = ref(true)
const rosterLoading = ref(true)
const closing = ref(false)
const session = ref(null)
const roster = ref([])
const rosterSummary = ref({})
const markingStudentId = ref(null)
const search = ref('')

const isActive = computed(() => session.value?.status === 'active')

const rosterHeaders = [
  { title: 'Student', key: 'student_name' },
  { title: 'Gate Entry', key: 'gate_entry_today' },
  { title: 'Attendance', key: 'attendance_status' },
  { title: 'Actions', key: 'actions', sortable: false },
]

const filteredRoster = computed(() => {
  const q = search.value.trim().toLowerCase()
  if (!q) return roster.value
  return roster.value.filter(
    (r) =>
      r.student_name?.toLowerCase().includes(q) ||
      r.student_number?.toLowerCase().includes(q),
  )
})

function formatDate(value) {
  if (!value) return '—'
  return String(value).slice(0, 10)
}

function formatTime(value) {
  if (!value) return '—'
  return String(value).slice(0, 5)
}

function formatDateTime(value) {
  if (!value) return '—'
  return new Date(value).toLocaleString()
}

async function loadSession() {
  loading.value = true
  try {
    const { data } = await getSession(route.params.id)
    session.value = data.data ?? data
  } finally {
    loading.value = false
  }
}

async function loadRoster() {
  rosterLoading.value = true
  try {
    const { data } = await getSessionRoster(route.params.id)
    roster.value = data.data ?? []
    rosterSummary.value = data.summary ?? {}
  } finally {
    rosterLoading.value = false
  }
}

async function markAttendance(student, status) {
  markingStudentId.value = student.student_id
  try {
    await updateAttendance(route.params.id, {
      student_id: student.student_id,
      status,
    })
    await loadRoster()
  } finally {
    markingStudentId.value = null
  }
}

async function handleClose() {
  closing.value = true
  try {
    await closeSession(route.params.id)
    await loadSession()
    await loadRoster()
  } finally {
    closing.value = false
  }
}

onMounted(async () => {
  await loadSession()
  await loadRoster()
})
</script>

<template>
  <div>
    <PageHeader
      :title="session?.subject?.name ?? 'Session Detail'"
      :subtitle="session ? `${session.section?.name ?? ''} · ${formatDate(session.session_date)}` : ''"
    >
      <template #actions>
        <v-btn variant="text" prepend-icon="mdi-arrow-left" @click="router.push({ name: 'teacher-sessions' })">
          Back
        </v-btn>
        <v-btn
          v-if="isActive"
          color="error"
          variant="tonal"
          prepend-icon="mdi-stop-circle-outline"
          :loading="closing"
          @click="handleClose"
        >
          Close Session
        </v-btn>
      </template>
    </PageHeader>

    <!-- Session info row -->
    <v-card v-if="session" class="app-card mb-4" :loading="loading">
      <v-card-text>
        <div class="d-flex flex-wrap align-center ga-3 mb-3">
          <StatusChip :value="session.status" preset="session" />
          <v-chip v-if="session.closed_at" size="small" variant="tonal">
            Closed {{ formatDateTime(session.closed_at) }}
          </v-chip>
        </div>
        <div class="info-grid">
          <div><span class="text-medium-emphasis">Subject</span><strong>{{ session.subject?.name ?? '—' }}</strong></div>
          <div><span class="text-medium-emphasis">Program Block</span><strong>{{ session.section?.name ?? '—' }}</strong></div>
          <div><span class="text-medium-emphasis">Date</span><strong>{{ formatDate(session.session_date) }}</strong></div>
          <div><span class="text-medium-emphasis">Time</span><strong>{{ formatTime(session.start_time) }} – {{ formatTime(session.end_time) }}</strong></div>
        </div>
      </v-card-text>
    </v-card>

    <!-- Summary chips -->
    <div v-if="!rosterLoading && Object.keys(rosterSummary).length" class="d-flex flex-wrap ga-2 mb-4">
      <v-chip variant="tonal" color="primary" prepend-icon="mdi-account-group-outline">
        {{ rosterSummary.total }} enrolled
      </v-chip>
      <v-chip variant="tonal" color="info" prepend-icon="mdi-door-open">
        {{ rosterSummary.at_school }} at school today
      </v-chip>
      <v-chip variant="tonal" color="success" prepend-icon="mdi-check-circle-outline">
        {{ rosterSummary.present }} present
      </v-chip>
      <v-chip variant="tonal" color="error" prepend-icon="mdi-close-circle-outline">
        {{ rosterSummary.absent }} absent
      </v-chip>
      <v-chip v-if="rosterSummary.not_yet_marked > 0" variant="tonal" color="warning" prepend-icon="mdi-clock-outline">
        {{ rosterSummary.not_yet_marked }} not yet marked
      </v-chip>
    </div>

    <!-- Roster table -->
    <v-card class="app-card">
      <v-card-title class="d-flex flex-wrap align-center ga-3 py-4 px-5">
        <span class="text-subtitle-1 font-weight-bold">Attendance Roster</span>
        <v-spacer />
        <v-text-field
          v-model="search"
          placeholder="Search students..."
          prepend-inner-icon="mdi-magnify"
          hide-details
          density="compact"
          style="max-width: 260px"
          clearable
        />
        <v-btn
          v-if="!rosterLoading"
          variant="text"
          icon="mdi-refresh"
          @click="loadRoster"
        />
      </v-card-title>
      <v-divider />

      <v-data-table
        :headers="rosterHeaders"
        :items="filteredRoster"
        :loading="rosterLoading"
        item-value="student_id"
      >
        <!-- Gate entry column -->
        <template #item.gate_entry_today="{ item }">
          <div class="d-flex align-center ga-1">
            <v-icon
              :color="item.gate_entry_today ? 'success' : 'error'"
              size="18"
            >
              {{ item.gate_entry_today ? 'mdi-door-open' : 'mdi-door-closed' }}
            </v-icon>
            <span :class="item.gate_entry_today ? 'text-success' : 'text-medium-emphasis'" class="text-body-2">
              {{ item.gate_entry_today ? 'At school' : 'Not scanned' }}
            </span>
            <span v-if="item.gate_scanned_at" class="text-caption text-medium-emphasis">
              ({{ new Date(item.gate_scanned_at).toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' }) }})
            </span>
          </div>
        </template>

        <!-- Attendance status column -->
        <template #item.attendance_status="{ item }">
          <StatusChip
            v-if="item.attendance_status"
            :value="item.attendance_status"
            preset="attendance"
          />
          <v-chip v-else size="small" variant="outlined" color="warning">
            Pending
          </v-chip>
        </template>

        <!-- Actions column -->
        <template #item.actions="{ item }">
          <div class="d-flex ga-1">
            <!-- Mark absent (only if not already absent) -->
            <v-btn
              v-if="item.attendance_status !== 'absent'"
              size="small"
              color="error"
              variant="tonal"
              :loading="markingStudentId === item.student_id"
              :disabled="markingStudentId !== null && markingStudentId !== item.student_id"
              @click="markAttendance(item, 'absent')"
            >
              Mark Absent
            </v-btn>

            <!-- Undo absent → present (only if gate entry exists) -->
            <v-btn
              v-if="item.attendance_status === 'absent' && item.gate_entry_today"
              size="small"
              color="success"
              variant="tonal"
              :loading="markingStudentId === item.student_id"
              :disabled="markingStudentId !== null && markingStudentId !== item.student_id"
              @click="markAttendance(item, 'present')"
            >
              Undo
            </v-btn>

            <!-- Mark present (only if not at school, no attendance yet) -->
            <v-btn
              v-if="!item.gate_entry_today && item.attendance_status !== 'present'"
              size="small"
              color="success"
              variant="text"
              :loading="markingStudentId === item.student_id"
              :disabled="markingStudentId !== null && markingStudentId !== item.student_id"
              @click="markAttendance(item, 'present')"
            >
              Mark Present
            </v-btn>
          </div>
        </template>

        <template #no-data>
          <div class="text-center py-8 text-medium-emphasis">
            No enrolled students found.
          </div>
        </template>
      </v-data-table>
    </v-card>

    <!-- Close session info banner -->
    <v-alert
      v-if="isActive && roster.length"
      type="info"
      variant="tonal"
      class="mt-4"
      density="compact"
    >
      When you close this session, students who entered school today but are not yet marked will be
      automatically set to <strong>Present</strong>. Students who did not scan at the gate will be
      set to <strong>Absent</strong>.
    </v-alert>
  </div>
</template>

<style scoped>
.info-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(180px, 1fr));
  gap: 14px;
}

.info-grid div {
  display: flex;
  flex-direction: column;
  gap: 4px;
}
</style>
