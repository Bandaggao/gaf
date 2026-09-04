<script setup>
import { ref, onMounted, computed } from 'vue'
import { getDashboard } from '@/api/parent'

const loading = ref(true)
const dashboard = ref(null)

const children = computed(() => {
  const summary = dashboard.value?.children_summary ?? []
  const recent = dashboard.value?.recent_attendance ?? []

  return summary.map(({ student, stats }) => ({
    id: student.id,
    name: student.user?.name ?? 'Student',
    section: student.section?.name ?? '',
    grade_level: student.grade_level,
    stats,
    recent: recent
      .filter((record) => record.student_id === student.id)
      .map((record) => ({
        id: record.id,
        date: record.class_session?.session_date,
        subject: record.class_session?.subject?.name,
        status: record.status,
      })),
  }))
})

onMounted(async () => {
  try {
    const { data } = await getDashboard()
    dashboard.value = data
  } finally {
    loading.value = false
  }
})
</script>

<template>
  <div>
    <h1 class="text-h5 mb-4">Children's Attendance</h1>

    <v-row>
      <v-col v-for="child in children" :key="child.id" cols="12" md="6">
        <v-card :loading="loading">
          <v-card-title>{{ child.name }}</v-card-title>
          <v-card-subtitle>{{ child.section }} · Grade {{ child.grade_level }}</v-card-subtitle>
          <v-card-text>
            <v-row>
              <v-col cols="4" class="text-center">
                <div class="text-caption">Present</div>
                <div class="text-h6 text-success">{{ child.stats?.present ?? 0 }}</div>
              </v-col>
              <v-col cols="4" class="text-center">
                <div class="text-caption">Absent</div>
                <div class="text-h6 text-error">{{ child.stats?.absent ?? 0 }}</div>
              </v-col>
              <v-col cols="4" class="text-center">
                <div class="text-caption">Late</div>
                <div class="text-h6 text-warning">{{ child.stats?.late ?? 0 }}</div>
              </v-col>
            </v-row>
          </v-card-text>
          <v-data-table
            v-if="child.recent?.length"
            :headers="[
              { title: 'Date', key: 'date' },
              { title: 'Subject', key: 'subject' },
              { title: 'Status', key: 'status' },
            ]"
            :items="child.recent"
            density="compact"
            item-value="id"
          />
        </v-card>
      </v-col>
    </v-row>

    <v-alert v-if="!loading && !children.length" type="info" variant="tonal" class="mt-4">
      No linked students found.
    </v-alert>
  </div>
</template>
