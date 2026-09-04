<script setup>
import { ref } from 'vue'
import { getReports, downloadReport } from '@/api/admin'
import PageHeader from '@/components/ui/PageHeader.vue'
import DataCard from '@/components/ui/DataCard.vue'

const loading = ref(false)
const reports = ref([])
const filters = ref({
  from: '',
  to: '',
  section_id: null,
})

const headers = [
  { title: 'Date', key: 'date' },
  { title: 'Program Block', key: 'section' },
  { title: 'Subject', key: 'subject' },
  { title: 'Present', key: 'present' },
  { title: 'Absent', key: 'absent' },
  { title: 'Late', key: 'late' },
]

async function fetchReports() {
  loading.value = true
  try {
    const { data } = await getReports(filters.value)
    reports.value = data.data ?? data
  } finally {
    loading.value = false
  }
}

async function exportPdf() {
  const { data } = await downloadReport(filters.value)
  const url = window.URL.createObjectURL(new Blob([data], { type: 'application/pdf' }))
  const link = document.createElement('a')
  link.href = url
  link.download = 'attendance-report.pdf'
  link.click()
  window.URL.revokeObjectURL(url)
}
</script>

<template>
  <div>
    <PageHeader title="Attendance Reports" subtitle="Generate and export attendance summaries by date range." />

    <v-card class="app-card mb-4">
      <v-card-text>
        <v-row dense>
          <v-col cols="12" md="4">
            <v-text-field v-model="filters.from" label="From" type="date" prepend-inner-icon="mdi-calendar-start" />
          </v-col>
          <v-col cols="12" md="4">
            <v-text-field v-model="filters.to" label="To" type="date" prepend-inner-icon="mdi-calendar-end" />
          </v-col>
          <v-col cols="12" md="4" class="d-flex align-center ga-2">
            <v-btn color="primary" prepend-icon="mdi-chart-box-outline" :loading="loading" @click="fetchReports">
              Generate
            </v-btn>
            <v-btn variant="tonal" prepend-icon="mdi-download" @click="exportPdf">Export PDF</v-btn>
          </v-col>
        </v-row>
      </v-card-text>
    </v-card>

    <DataCard
      :headers="headers"
      :items="reports"
      :loading="loading"
      search-placeholder="Search report rows..."
      :search-keys="['date', 'section', 'subject']"
    />
  </div>
</template>
