<script setup>
import { ref, onMounted } from 'vue'
import { getProfile } from '@/api/teacher'
import PageHeader from '@/components/ui/PageHeader.vue'
import DataCard from '@/components/ui/DataCard.vue'

const loading = ref(true)
const user = ref(null)
const sections = ref([])
const assignments = ref([])

onMounted(async () => {
  try {
    const { data } = await getProfile()
    user.value = data.user ?? null
    sections.value = data.sections ?? []
    assignments.value = data.assignments ?? []
  } finally {
    loading.value = false
  }
})
</script>

<template>
  <div>
    <PageHeader title="My Profile" subtitle="Your teaching assignments and program blocks." />

    <v-row>
      <v-col cols="12" md="5">
        <v-card class="app-card" :loading="loading">
          <v-card-title class="font-weight-bold">Account Details</v-card-title>
          <v-card-text v-if="user">
            <div class="info-grid">
              <div><span class="text-medium-emphasis">Name</span><strong>{{ user.name }}</strong></div>
              <div><span class="text-medium-emphasis">Email</span><strong>{{ user.email }}</strong></div>
              <div><span class="text-medium-emphasis">Role</span><v-chip size="small" color="primary" variant="tonal">Teacher</v-chip></div>
            </div>
          </v-card-text>
        </v-card>

        <v-card v-if="sections.length" class="app-card mt-4" :loading="loading">
          <v-card-title class="font-weight-bold">My Program Blocks</v-card-title>
          <v-card-text class="d-flex flex-wrap ga-2">
            <v-chip
              v-for="section in sections"
              :key="section.id"
              variant="tonal"
              color="secondary"
            >
              {{ section.name }} (Year {{ section.grade_level }})
            </v-chip>
          </v-card-text>
        </v-card>
      </v-col>

      <v-col cols="12" md="7">
        <DataCard
          title="My Classes"
          :headers="[
            { title: 'Subject', key: 'subject' },
            { title: 'Program Block', key: 'section' },
            { title: 'Year Level', key: 'grade_level' },
          ]"
          :items="assignments"
          :loading="loading"
          search-placeholder="Search classes..."
          :search-keys="['subject', 'section', 'grade_level']"
        >
          <template #item.grade_level="{ item }">
            <v-chip size="small" variant="tonal">Year {{ item.grade_level }}</v-chip>
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
