<script setup>
import { ref, onMounted, watch } from 'vue'
import { useRoute } from 'vue-router'
import {
  getStudents,
  getSections,
  getEnrollmentOptions,
  getStudentEnrollments,
  saveStudentEnrollments,
  deleteStudentEnrollment,
  bulkEnrollBlock,
  importEnrollments,
} from '@/api/admin'
import PageHeader from '@/components/ui/PageHeader.vue'
import SearchSelect from '@/components/ui/SearchSelect.vue'
import DataCard from '@/components/ui/DataCard.vue'
import StatusChip from '@/components/ui/StatusChip.vue'

const route = useRoute()
const loading = ref(false)
const students = ref([])
const sections = ref([])
const classOptions = ref([])
const selectedStudentId = ref(null)
const enrollments = ref([])
const selectedClassIds = ref([])
const enrollmentType = ref('regular')
const bulkSectionId = ref(null)
const importFile = ref(null)
const importResult = ref(null)
const snackbar = ref(false)
const snackbarText = ref('')

const enrollmentTypes = [
  { title: 'Regular', value: 'regular' },
  { title: 'Irregular', value: 'irregular' },
]

async function loadData() {
  loading.value = true
  try {
    const [studentsRes, sectionsRes, optionsRes] = await Promise.all([
      getStudents(),
      getSections(),
      getEnrollmentOptions(),
    ])
    students.value = studentsRes.data.data ?? studentsRes.data
    sections.value = sectionsRes.data.data ?? sectionsRes.data
    classOptions.value = optionsRes.data.data ?? optionsRes.data

    if (route.query.student) {
      selectedStudentId.value = Number(route.query.student)
      await loadEnrollments()
    }
  } finally {
    loading.value = false
  }
}

async function loadEnrollments() {
  if (!selectedStudentId.value) {
    enrollments.value = []
    selectedClassIds.value = []
    return
  }

  loading.value = true
  try {
    const { data } = await getStudentEnrollments(selectedStudentId.value)
    enrollments.value = data.data ?? data
    selectedClassIds.value = enrollments.value.map((e) => e.teaching_assignment_id)

    const student = students.value.find((s) => s.id === selectedStudentId.value)
    if (student) {
      enrollmentType.value = student.student_type === 'irregular' ? 'irregular' : 'regular'
    }
  } finally {
    loading.value = false
  }
}

async function saveEnrollments() {
  if (!selectedStudentId.value || !selectedClassIds.value.length) return

  await saveStudentEnrollments(selectedStudentId.value, {
    teaching_assignment_ids: selectedClassIds.value,
    enrollment_type: enrollmentType.value,
  })

  snackbarText.value = 'Enrollments saved.'
  snackbar.value = true
  await loadEnrollments()
}

async function removeEnrollment(enrollment) {
  await deleteStudentEnrollment(selectedStudentId.value, enrollment.id)
  await loadEnrollments()
}

async function runBulkEnroll() {
  if (!bulkSectionId.value) return

  const { data } = await bulkEnrollBlock(bulkSectionId.value)
  snackbarText.value = data.message
  snackbar.value = true
  await loadData()
}

async function runImport() {
  if (!importFile.value?.length) return

  const { data } = await importEnrollments(importFile.value[0])
  importResult.value = data.data
  snackbarText.value = data.message
  snackbar.value = true
  importFile.value = null
  await loadData()
}

function studentLabel(student) {
  return `${student.user?.name} (${student.student_number})`
}

watch(() => route.query.student, (id) => {
  if (id) {
    selectedStudentId.value = Number(id)
    loadEnrollments()
  }
})

onMounted(loadData)
</script>

<template>
  <div>
    <PageHeader
      title="Enrollments"
      subtitle="Enroll students in classes. Bulk-enroll regular blocks or import irregular enrollments via CSV."
    />

    <v-row>
      <v-col cols="12" lg="5">
        <v-card class="app-card mb-4">
          <v-card-title class="font-weight-bold">Bulk Enroll Program Block</v-card-title>
          <v-card-text>
            <p class="text-body-2 text-medium-emphasis mb-4">
              Enrolls all students in a block into every class offered for that block.
            </p>
            <SearchSelect
              v-model="bulkSectionId"
              :items="sections"
              item-title="name"
              item-value="id"
              label="Program Block"
            />
            <v-btn color="primary" class="mt-4" :disabled="!bulkSectionId" prepend-icon="mdi-account-multiple-plus-outline" @click="runBulkEnroll">
              Bulk Enroll Block
            </v-btn>
          </v-card-text>
        </v-card>

        <v-card class="app-card">
          <v-card-title class="font-weight-bold">Import CSV</v-card-title>
          <v-card-text>
            <v-chip class="mb-4" size="small" color="info" variant="tonal">
              student_number, subject, teacher_email, block_name
            </v-chip>
            <v-file-input
              v-model="importFile"
              label="CSV file"
              accept=".csv,text/csv"
              prepend-icon="mdi-file-upload-outline"
            />
            <v-btn color="primary" :disabled="!importFile?.length" prepend-icon="mdi-upload" @click="runImport">
              Import
            </v-btn>
            <v-alert v-if="importResult" type="info" class="mt-4">
              <div class="d-flex flex-wrap ga-2 mb-2">
                <v-chip size="small" color="success" variant="tonal">Imported: {{ importResult.imported }}</v-chip>
                <v-chip size="small" color="warning" variant="tonal">Skipped: {{ importResult.skipped }}</v-chip>
              </div>
              <div v-if="importResult.errors?.length">
                <div v-for="(err, i) in importResult.errors" :key="i" class="text-caption">{{ err }}</div>
              </div>
            </v-alert>
          </v-card-text>
        </v-card>
      </v-col>

      <v-col cols="12" lg="7">
        <v-card class="app-card mb-4">
          <v-card-text>
            <v-row dense>
              <v-col cols="12">
                <SearchSelect
                  v-model="selectedStudentId"
                  :items="students"
                  :item-title="studentLabel"
                  item-value="id"
                  label="Student"
                  @update:model-value="loadEnrollments"
                />
              </v-col>
              <v-col cols="12" md="6">
                <SearchSelect
                  v-model="enrollmentType"
                  :items="enrollmentTypes"
                  item-title="title"
                  item-value="value"
                  label="Enrollment Type"
                />
              </v-col>
              <v-col cols="12">
                <SearchSelect
                  v-model="selectedClassIds"
                  :items="classOptions"
                  item-title="label"
                  item-value="id"
                  label="Enrolled Classes"
                  multiple
                  :disabled="!selectedStudentId"
                />
              </v-col>
            </v-row>
            <v-btn
              color="primary"
              class="mt-2"
              prepend-icon="mdi-content-save-outline"
              :disabled="!selectedStudentId || !selectedClassIds.length"
              @click="saveEnrollments"
            >
              Save Enrollments
            </v-btn>
          </v-card-text>
        </v-card>

        <DataCard
          v-if="enrollments.length"
          title="Current Enrollments"
          :headers="[
            { title: 'Subject', key: 'teaching_assignment.subject.name' },
            { title: 'Teacher', key: 'teaching_assignment.teacher.user.name' },
            { title: 'Block', key: 'teaching_assignment.section.name' },
            { title: 'Type', key: 'enrollment_type' },
            { title: '', key: 'actions', sortable: false },
          ]"
          :items="enrollments"
          :loading="loading"
          :searchable="false"
          density="compact"
        >
          <template #item.enrollment_type="{ item }">
            <StatusChip :value="item.enrollment_type" preset="enrollment" />
          </template>
          <template #item.actions="{ item }">
            <v-btn icon="mdi-delete-outline" variant="text" size="small" color="error" @click="removeEnrollment(item)" />
          </template>
        </DataCard>
      </v-col>
    </v-row>

    <v-snackbar v-model="snackbar" :timeout="3000" color="success">{{ snackbarText }}</v-snackbar>
  </div>
</template>
