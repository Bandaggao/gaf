<script setup>
import { ref, onMounted } from 'vue'
import { getDashboard } from '@/api/student'
import PageHeader from '@/components/ui/PageHeader.vue'
import StatCard from '@/components/ui/StatCard.vue'
import DataCard from '@/components/ui/DataCard.vue'
import StatusChip from '@/components/ui/StatusChip.vue'

const loading = ref(true)
const stats = ref({ present: 0, absent: 0, late: 0, rate: 0, total: 0 })
const recent = ref([])

onMounted(async () => {
  try {
    const { data } = await getDashboard()
    stats.value = data.stats ?? stats.value
    recent.value = data.recent ?? []
  } finally {
    loading.value = false
  }
})

const statCards = [
  { key: 'present', label: 'Present', color: 'success', icon: 'mdi-check-circle-outline' },
  { key: 'absent', label: 'Absent', color: 'error', icon: 'mdi-close-circle-outline' },
  { key: 'late', label: 'Late', color: 'warning', icon: 'mdi-clock-alert-outline' },
  { key: 'rate', label: 'Attendance Rate', color: 'primary', icon: 'mdi-chart-donut', suffix: '%' },
]
</script>

<template>
  <div>
    <PageHeader title="My Attendance" subtitle="Track your attendance records across enrolled classes." />

    <v-alert v-if="!loading && stats.total === 0" type="info" class="mb-4">
      No attendance records yet. Scan your teacher's session QR from
      <router-link :to="{ name: 'student-scan' }">Scan Attendance</router-link>.
    </v-alert>

    <v-row>
      <v-col v-for="card in statCards" :key="card.key" cols="6" md="3">
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

    <DataCard
      class="mt-4"
      title="Recent Records"
      :headers="[
        { title: 'Date', key: 'date' },
        { title: 'Subject', key: 'subject' },
        { title: 'Program Block', key: 'section' },
        { title: 'Status', key: 'status' },
      ]"
      :items="recent"
      :loading="loading"
      search-placeholder="Search records..."
      :search-keys="['date', 'subject', 'section', 'status']"
    >
      <template #item.status="{ item }">
        <StatusChip :value="item.status" preset="attendance" />
      </template>
    </DataCard>
  </div>
</template>
