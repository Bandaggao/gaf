<script setup>
import { ref, computed, onMounted } from 'vue'
import {
  getTeachingAssignments,
  createTeachingAssignmentsBulk,
  syncTeacherAssignments,
  deleteTeachingAssignment,
  getTeachers,
  getSubjects,
  getSections,
} from '@/api/admin'
import PageHeader from '@/components/ui/PageHeader.vue'
import FormDialog from '@/components/ui/FormDialog.vue'
import SearchSelect from '@/components/ui/SearchSelect.vue'

const loading = ref(false)
const saving = ref(false)
const assignments = ref([])
const teachers = ref([])
const subjects = ref([])
const sections = ref([])
const dialog = ref(false)
const managingTeacher = ref(null)
const formRef = ref(null)
const form = ref({
  teacher_id: null,
  lines: [{ subject_id: null, section_id: null }],
})

const headers = [
  { title: 'Teacher', key: 'teacher_name' },
  { title: 'Classes', key: 'classes' },
  { title: 'Total Classes', key: 'class_count' },
  { title: 'Actions', key: 'actions', sortable: false },
]

const groupedTeachers = computed(() => {
  const map = new Map()

  for (const assignment of assignments.value) {
    const teacherId = assignment.teacher_id
    if (!map.has(teacherId)) {
      map.set(teacherId, {
        teacher_id: teacherId,
        teacher_name: assignment.teacher?.user?.name ?? 'Unknown',
        classes: [],
      })
    }
    map.get(teacherId).classes.push(assignment)
  }

  return Array.from(map.values()).map((group) => ({
    ...group,
    class_count: group.classes.length,
    search_text: [
      group.teacher_name,
      ...group.classes.map((c) => `${c.subject?.name} ${c.section?.name}`),
    ].join(' ').toLowerCase(),
  }))
})

function sectionLabel(section) {
  return `${section.name} (Year ${section.grade_level})`
}

function classChipLabel(assignment) {
  return `${assignment.subject?.name} · ${assignment.section?.name}`
}

async function loadData() {
  loading.value = true
  try {
    const [assignmentsRes, teachersRes, subjectsRes, sectionsRes] = await Promise.all([
      getTeachingAssignments(),
      getTeachers(),
      getSubjects(),
      getSections(),
    ])
    assignments.value = assignmentsRes.data.data ?? assignmentsRes.data
    teachers.value = teachersRes.data.data ?? teachersRes.data
    subjects.value = subjectsRes.data.data ?? subjectsRes.data
    sections.value = sectionsRes.data.data ?? sectionsRes.data
  } finally {
    loading.value = false
  }
}

function emptyLine() {
  return { subject_id: subjects.value[0]?.id ?? null, section_id: sections.value[0]?.id ?? null }
}

function openDialog(teacherGroup = null) {
  managingTeacher.value = teacherGroup

  if (teacherGroup) {
    form.value = {
      teacher_id: teacherGroup.teacher_id,
      lines: teacherGroup.classes.map((c) => ({
        subject_id: c.subject_id,
        section_id: c.section_id,
      })),
    }
  } else {
    form.value = {
      teacher_id: teachers.value[0]?.id ?? null,
      lines: [emptyLine()],
    }
  }

  dialog.value = true
}

function addLine() {
  form.value.lines.push(emptyLine())
}

function removeLine(index) {
  if (form.value.lines.length === 1) return
  form.value.lines.splice(index, 1)
}

function validLines() {
  return form.value.lines.filter((line) => line.subject_id && line.section_id)
}

async function save() {
  const { valid } = await formRef.value.validate()
  if (!valid) return

  const lines = validLines()
  if (!lines.length) return

  saving.value = true
  try {
    if (managingTeacher.value) {
      await syncTeacherAssignments(form.value.teacher_id, { assignments: lines })
    } else {
      await createTeachingAssignmentsBulk({
        teacher_id: form.value.teacher_id,
        assignments: lines,
      })
    }
    dialog.value = false
    await loadData()
  } catch (err) {
    alert(err.response?.data?.message || 'Failed to save classes.')
  } finally {
    saving.value = false
  }
}

async function removeClass(assignment) {
  const label = classChipLabel(assignment)
  if (!confirm(`Remove class: ${label}?`)) return
  await deleteTeachingAssignment(assignment.id)
  await loadData()
}

const search = ref('')

const displayGroups = computed(() => {
  const query = search.value.trim().toLowerCase()
  if (!query) return groupedTeachers.value
  return groupedTeachers.value.filter((g) => g.search_text.includes(query))
})

onMounted(loadData)
</script>

<template>
  <div>
    <PageHeader
      title="Classes"
      subtitle="One row per teacher. Add multiple subject and program block pairs without repeating the teacher name."
    >
      <template #actions>
        <v-btn color="primary" prepend-icon="mdi-plus" @click="openDialog()">Assign Classes</v-btn>
      </template>
    </PageHeader>

    <v-card class="app-card">
      <v-card-title class="d-flex flex-wrap align-center ga-3 py-4 px-5">
        <span class="text-subtitle-1 font-weight-bold">Teachers &amp; Classes</span>
        <v-spacer />
        <v-text-field
          v-model="search"
          placeholder="Search teachers or classes..."
          prepend-inner-icon="mdi-magnify"
          hide-details
          density="compact"
          style="max-width: 280px"
          clearable
        />
      </v-card-title>
      <v-divider />

      <v-data-table
        :headers="headers"
        :items="displayGroups"
        :loading="loading"
        item-value="teacher_id"
        class="data-card-table"
      >
        <template #item.classes="{ item }">
          <div class="d-flex flex-wrap ga-2 py-1">
            <v-chip
              v-for="assignment in item.classes"
              :key="assignment.id"
              size="small"
              color="primary"
              variant="tonal"
              closable
              @click:close="removeClass(assignment)"
            >
              {{ classChipLabel(assignment) }}
            </v-chip>
          </div>
        </template>
        <template #item.class_count="{ item }">
          <v-chip size="small" variant="outlined">{{ item.class_count }}</v-chip>
        </template>
        <template #item.actions="{ item }">
          <v-btn
            variant="tonal"
            color="primary"
            size="small"
            prepend-icon="mdi-pencil-outline"
            @click="openDialog(item)"
          >
            Manage
          </v-btn>
        </template>
        <template #no-data>
          <div class="text-center py-8 text-medium-emphasis">
            No classes assigned yet. Click "Assign Classes" to add subject and block pairs for a teacher.
          </div>
        </template>
      </v-data-table>
    </v-card>

    <FormDialog
      v-model="dialog"
      :title="managingTeacher ? 'Manage Teacher Classes' : 'Assign Classes to Teacher'"
      :saving="saving"
      :save-label="managingTeacher ? 'Save Changes' : 'Add Classes'"
      @save="save"
    >
      <v-form ref="formRef">
        <SearchSelect
          v-model="form.teacher_id"
          :items="teachers"
          item-title="user.name"
          item-value="id"
          label="Teacher"
          :rules="[(v) => !!v || 'Required']"
          :disabled="!!managingTeacher || !teachers.length"
        />

        <p class="text-body-2 text-medium-emphasis mt-4 mb-2">
          Add one or more subject + program block pairs for this teacher.
        </p>

        <v-card
          v-for="(line, index) in form.lines"
          :key="index"
          variant="outlined"
          class="mb-3 pa-3"
          rounded="lg"
        >
          <div class="d-flex align-center justify-space-between mb-2">
            <v-chip size="x-small" variant="tonal">Class {{ index + 1 }}</v-chip>
            <v-btn
              v-if="form.lines.length > 1"
              icon="mdi-close"
              variant="text"
              size="small"
              @click="removeLine(index)"
            />
          </div>
          <v-row dense>
            <v-col cols="12" md="6">
              <SearchSelect
                v-model="line.subject_id"
                :items="subjects"
                item-title="name"
                item-value="id"
                label="Subject"
                :rules="[(v) => !!v || 'Required']"
                :disabled="!subjects.length"
              />
            </v-col>
            <v-col cols="12" md="6">
              <SearchSelect
                v-model="line.section_id"
                :items="sections"
                :item-title="sectionLabel"
                item-value="id"
                label="Program Block"
                :rules="[(v) => !!v || 'Required']"
                :disabled="!sections.length"
              />
            </v-col>
          </v-row>
        </v-card>

        <v-btn variant="tonal" color="primary" prepend-icon="mdi-plus" @click="addLine">
          Add Another Class
        </v-btn>

        <v-alert
          v-if="!teachers.length || !subjects.length || !sections.length"
          type="warning"
          class="mt-4"
        >
          Create teachers, subjects, and program blocks before assigning classes.
        </v-alert>
      </v-form>
    </FormDialog>
  </div>
</template>

<style scoped>
.data-card-table :deep(thead th) {
  font-size: 0.78rem;
  text-transform: uppercase;
  letter-spacing: 0.04em;
  color: rgba(var(--v-theme-on-surface), 0.55);
}
</style>
