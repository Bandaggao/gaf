<script setup>
import { ref, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { getStudents, createStudent, updateStudent, deleteStudent, getSections } from '@/api/admin'
import PageHeader from '@/components/ui/PageHeader.vue'
import DataCard from '@/components/ui/DataCard.vue'
import FormDialog from '@/components/ui/FormDialog.vue'
import SearchSelect from '@/components/ui/SearchSelect.vue'
import StatusChip from '@/components/ui/StatusChip.vue'

const router = useRouter()
const loading = ref(false)
const students = ref([])
const sections = ref([])
const dialog = ref(false)
const editing = ref(null)
const formRef = ref(null)
const form = ref({
  name: '',
  email: '',
  password: '',
  student_number: '',
  grade_level: '',
  section_id: null,
  student_type: 'regular',
  parent_email: '',
})

const studentTypes = [
  { title: 'Regular', value: 'regular' },
  { title: 'Irregular', value: 'irregular' },
]

const headers = [
  { title: 'Student #', key: 'student_number' },
  { title: 'Name', key: 'user.name' },
  { title: 'Email', key: 'user.email' },
  { title: 'Year Level', key: 'grade_level' },
  { title: 'Home Block', key: 'section.name' },
  { title: 'Type', key: 'student_type' },
  { title: 'Classes', key: 'enrollments_count' },
  { title: 'Actions', key: 'actions', sortable: false },
]

async function loadData() {
  loading.value = true
  try {
    const [studentsRes, sectionsRes] = await Promise.all([getStudents(), getSections()])
    students.value = studentsRes.data.data ?? studentsRes.data
    sections.value = sectionsRes.data.data ?? sectionsRes.data
  } finally {
    loading.value = false
  }
}

function openDialog(item = null) {
  editing.value = item
  form.value = item
    ? {
        name: item.user?.name ?? '',
        email: item.user?.email ?? '',
        password: '',
        student_number: item.student_number ?? '',
        grade_level: item.grade_level ?? '',
        section_id: item.section_id ?? null,
        student_type: item.student_type ?? 'regular',
        parent_email: item.parent_email ?? '',
      }
    : {
        name: '',
        email: '',
        password: '',
        student_number: '',
        grade_level: '',
        section_id: null,
        student_type: 'regular',
        parent_email: '',
      }
  dialog.value = true
}

async function save() {
  const { valid } = await formRef.value.validate()
  if (!valid) return

  if (editing.value) {
    await updateStudent(editing.value.id, form.value)
  } else {
    await createStudent(form.value)
  }
  dialog.value = false
  await loadData()
}

async function remove(item) {
  if (!confirm(`Delete student ${item.user?.name}?`)) return
  await deleteStudent(item.id)
  await loadData()
}

onMounted(loadData)
</script>

<template>
  <div>
    <PageHeader
      title="Students"
      subtitle="Manage student accounts. Enroll them in classes from the Enrollments page."
    >
      <template #actions>
        <v-btn color="primary" prepend-icon="mdi-plus" @click="openDialog()">Add Student</v-btn>
      </template>
    </PageHeader>

    <DataCard
      :headers="headers"
      :items="students"
      :loading="loading"
      search-placeholder="Search students..."
      :search-keys="['student_number', 'user.name', 'user.email', 'section.name', 'grade_level']"
    >
      <template #item.student_type="{ item }">
        <StatusChip :value="item.student_type" preset="student_type" />
      </template>
      <template #item.enrollments_count="{ item }">
        <v-chip size="small" color="info" variant="tonal">{{ item.enrollments_count ?? 0 }} classes</v-chip>
      </template>
      <template #item.actions="{ item }">
        <v-btn
          icon="mdi-school-outline"
          variant="text"
          size="small"
          title="Manage enrollments"
          @click="router.push({ name: 'admin-enrollments', query: { student: item.id } })"
        />
        <v-btn icon="mdi-pencil-outline" variant="text" size="small" @click="openDialog(item)" />
        <v-btn icon="mdi-delete-outline" variant="text" size="small" color="error" @click="remove(item)" />
      </template>
    </DataCard>

    <FormDialog v-model="dialog" :title="editing ? 'Edit Student' : 'Add Student'" @save="save">
      <v-form ref="formRef">
        <v-row dense>
          <v-col cols="12" md="6">
            <v-text-field v-model="form.name" label="Full Name" :rules="[(v) => !!v || 'Required']" />
          </v-col>
          <v-col cols="12" md="6">
            <v-text-field v-model="form.email" label="Email" type="email" :rules="[(v) => !!v || 'Required']" />
          </v-col>
          <v-col cols="12" md="6">
            <v-text-field
              v-model="form.password"
              label="Password"
              type="password"
              :rules="editing ? [] : [(v) => !!v || 'Required']"
            />
          </v-col>
          <v-col cols="12" md="6">
            <v-text-field v-model="form.student_number" label="Student Number" :rules="[(v) => !!v || 'Required']" />
          </v-col>
          <v-col cols="12" md="6">
            <v-text-field v-model="form.grade_level" label="Year Level" :rules="[(v) => !!v || 'Required']" />
          </v-col>
          <v-col cols="12" md="6">
            <SearchSelect
              v-model="form.section_id"
              :items="sections"
              item-title="name"
              item-value="id"
              label="Home Program Block"
              :rules="[(v) => !!v || 'Required']"
            />
          </v-col>
          <v-col cols="12" md="6">
            <SearchSelect
              v-model="form.student_type"
              :items="studentTypes"
              item-title="title"
              item-value="value"
              label="Student Type"
            />
          </v-col>
          <v-col cols="12" md="6">
            <v-text-field v-model="form.parent_email" label="Parent Email" type="email" />
          </v-col>
        </v-row>
      </v-form>
    </FormDialog>
  </div>
</template>
