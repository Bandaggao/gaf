<script setup>
import { ref, onMounted } from 'vue'
import { getProfile } from '@/api/student'
import PageHeader from '@/components/ui/PageHeader.vue'
import DataCard from '@/components/ui/DataCard.vue'
import StatusChip from '@/components/ui/StatusChip.vue'

const loading = ref(true)
const user = ref(null)
const student = ref(null)
const classmates = ref(0)
const enrollments = ref([])

onMounted(async () => {
  try {
    const { data } = await getProfile()
    user.value = data.user ?? null
    student.value = data.student ?? null
    classmates.value = data.classmates ?? 0
    enrollments.value = data.enrollments ?? []
  } finally {
    loading.value = false
  }
})
</script>

<template>
  <div>
    <PageHeader title="My Profile" subtitle="Your account details and enrolled classes." />

    <v-row>
      <v-col cols="12" md="5">
        <v-card class="app-card mb-4" :loading="loading">
          <v-card-title class="font-weight-bold">Account Details</v-card-title>
          <v-card-text v-if="user">
            <div class="info-grid">
              <div><span class="text-medium-emphasis">Name</span><strong>{{ user.name }}</strong></div>
              <div><span class="text-medium-emphasis">Email</span><strong>{{ user.email }}</strong></div>
              <div><span class="text-medium-emphasis">Role</span><v-chip size="small" color="primary" variant="tonal">Student</v-chip></div>
            </div>
          </v-card-text>
        </v-card>

        <v-card class="app-card" :loading="loading">
          <v-card-title class="font-weight-bold">School Details</v-card-title>
          <v-card-text v-if="student">
            <div class="info-grid">
              <div><span class="text-medium-emphasis">Student Number</span><strong>{{ student.student_number || '—' }}</strong></div>
              <div><span class="text-medium-emphasis">Year Level</span><v-chip size="small" variant="tonal">Year {{ student.grade_level || '—' }}</v-chip></div>
              <div><span class="text-medium-emphasis">Home Program Block</span><strong>{{ student.section?.name || '—' }}</strong></div>
              <div><span class="text-medium-emphasis">Student Type</span><StatusChip :value="student.student_type" preset="student_type" /></div>
              <div><span class="text-medium-emphasis">Classmates</span><strong>{{ classmates }}</strong></div>
              <div><span class="text-medium-emphasis">Parent Email</span><strong>{{ student.parent_email || '—' }}</strong></div>
            </div>
          </v-card-text>
        </v-card>
      </v-col>

      <v-col cols="12" md="7">
        <DataCard
          title="My Enrolled Classes"
          :headers="[
            { title: 'Subject', key: 'subject' },
            { title: 'Teacher', key: 'teacher_name' },
            { title: 'Program Block', key: 'block' },
            { title: 'Type', key: 'enrollment_type' },
          ]"
          :items="enrollments"
          :loading="loading"
          search-placeholder="Search classes..."
          :search-keys="['subject', 'teacher_name', 'block', 'enrollment_type']"
        >
          <template #item.enrollment_type="{ item }">
            <StatusChip :value="item.enrollment_type" preset="enrollment" />
          </template>
        </DataCard>
      </v-col>
    </v-row>
  </div>
</template>

<style scoped>
.info-grid {
  display: grid;
  gap: 14px;
}

.info-grid div {
  display: flex;
  flex-direction: column;
  gap: 4px;
}
</style>
