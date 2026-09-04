<script setup>
import { ref, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { getDashboard } from '@/api/teacher'
import PageHeader from '@/components/ui/PageHeader.vue'
import StatCard from '@/components/ui/StatCard.vue'
import DataCard from '@/components/ui/DataCard.vue'
import StatusChip from '@/components/ui/StatusChip.vue'

const router = useRouter()
const loading = ref(true)
const stats = ref({
  sessions_today: 0,
  students_present_today: 0,
  total_sessions: 0,
  attendance_rate: 0,
})
const todaySessions = ref([])
const recentSessions = ref([])

function formatTime(value) {
  if (!value) return '—'
  return String(value).slice(0, 5)
}

function openSession(item) {
  router.push({ name: 'teacher-session-detail', params: { id: item.id } })
}

onMounted(async () => {
  try {
    const { data } = await getDashboard()
    stats.value = data.stats ?? stats.value
    todaySessions.value = data.today_sessions ?? []
    recentSessions.value = data.recent_sessions ?? []
  } finally {
    loading.value = false
  }
})
</script>

<template>
  <div>
    <PageHeader title="Teacher Dashboard" subtitle="Overview of today's sessions and attendance performance." />

    <v-row class="mb-2">
      <v-col cols="12" sm="6" md="3">
        <StatCard label="Sessions Today" :value="stats.sessions_today" icon="mdi-calendar-today-outline" color="primary" :loading="loading" />
      </v-col>
      <v-col cols="12" sm="6" md="3">
        <StatCard label="Present Today" :value="stats.students_present_today" icon="mdi-account-check-outline" color="success" :loading="loading" />
      </v-col>
      <v-col cols="12" sm="6" md="3">
        <StatCard label="Total Sessions" :value="stats.total_sessions" icon="mdi-calendar-clock-outline" color="info" :loading="loading" />
      </v-col>
      <v-col cols="12" sm="6" md="3">
        <StatCard label="Attendance Rate" :value="stats.attendance_rate" suffix="%" icon="mdi-chart-line" color="secondary" :loading="loading" />
      </v-col>
    </v-row>

    <v-alert v-if="!loading && stats.total_sessions === 0" type="info" class="mb-4">
      No sessions yet. Go to
      <router-link :to="{ name: 'teacher-sessions' }">Sessions</router-link>
      and start a class session.
    </v-alert>

    <DataCard
      class="mb-4"
      title="Today's Sessions"
      :headers="[
        { title: 'Subject', key: 'subject.name' },
        { title: 'Program Block', key: 'section.name' },
        { title: 'Time', key: 'start_time' },
        { title: 'Status', key: 'status' },
      ]"
      :items="todaySessions"
      :loading="loading"
      :searchable="false"
      @click:row="(_, { item }) => openSession(item)"
    >
      <template #toolbar>
        <v-btn variant="tonal" color="primary" size="small" :to="{ name: 'teacher-sessions' }">View all</v-btn>
      </template>
      <template #item.start_time="{ item }">{{ formatTime(item.start_time) }}</template>
      <template #item.status="{ item }">
        <StatusChip :value="item.status" preset="session" />
      </template>
    </DataCard>

    <DataCard
      v-if="recentSessions.length > 0"
      title="Past Sessions"
      :headers="[
        { title: 'Date', key: 'session_date' },
        { title: 'Subject', key: 'subject.name' },
        { title: 'Program Block', key: 'section.name' },
        { title: 'Status', key: 'status' },
      ]"
      :items="recentSessions"
      :loading="loading"
      :searchable="false"
      @click:row="(_, { item }) => openSession(item)"
    >
      <template #item.session_date="{ item }">{{ String(item.session_date).slice(0, 10) }}</template>
      <template #item.status="{ item }">
        <StatusChip :value="item.status" preset="session" />
      </template>
    </DataCard>
  </div>
</template>
