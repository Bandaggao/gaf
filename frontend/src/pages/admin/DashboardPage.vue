<script setup>
import { ref, onMounted } from 'vue'
import { getDashboard } from '@/api/admin'
import AttendanceChart from '@/components/AttendanceChart.vue'
import PageHeader from '@/components/ui/PageHeader.vue'
import StatCard from '@/components/ui/StatCard.vue'

const loading = ref(true)
const stats = ref({ students: 0, teachers: 0, sessions_today: 0, attendance_rate: 0 })
const chart = ref({ labels: [], present: [], absent: [] })

onMounted(async () => {
  try {
    const { data } = await getDashboard()
    stats.value = data.stats ?? stats.value
    chart.value = data.chart ?? chart.value
  } finally {
    loading.value = false
  }
})

const statCards = [
  { key: 'students', label: 'Students', icon: 'mdi-account-school-outline', color: 'primary' },
  { key: 'teachers', label: 'Teachers', icon: 'mdi-human-male-board', color: 'secondary' },
  { key: 'sessions_today', label: 'Sessions Today', icon: 'mdi-calendar-clock-outline', color: 'info' },
  { key: 'attendance_rate', label: 'Attendance Rate', icon: 'mdi-chart-line', color: 'success', suffix: '%' },
]
</script>

<template>
  <div>
    <PageHeader title="Admin Dashboard" subtitle="School-wide attendance overview and trends." />

    <v-row>
      <v-col v-for="card in statCards" :key="card.key" cols="12" sm="6" md="3">
        <StatCard
          :label="card.label"
          :value="stats[card.key] ?? 0"
          :suffix="card.suffix || ''"
          :icon="card.icon"
          :color="card.color"
          :loading="loading"
        />
      </v-col>
    </v-row>

    <v-card class="app-card mt-4">
      <v-card-title class="font-weight-bold">Attendance Trend</v-card-title>
      <v-card-text>
        <AttendanceChart :labels="chart.labels" :present="chart.present" :absent="chart.absent" />
      </v-card-text>
    </v-card>
  </div>
</template>
