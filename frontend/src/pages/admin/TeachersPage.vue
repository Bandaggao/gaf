<script setup>
import { ref, onMounted } from 'vue'
import { getTeachers, createTeacher, updateTeacher, deleteTeacher } from '@/api/admin'
import PageHeader from '@/components/ui/PageHeader.vue'
import DataCard from '@/components/ui/DataCard.vue'
import FormDialog from '@/components/ui/FormDialog.vue'

const loading = ref(false)
const teachers = ref([])
const dialog = ref(false)
const editing = ref(null)
const formRef = ref(null)
const form = ref({ name: '', email: '', password: '' })

const headers = [
  { title: 'Name', key: 'user.name' },
  { title: 'Email', key: 'user.email' },
  { title: 'Classes', key: 'assignments' },
  { title: 'Actions', key: 'actions', sortable: false },
]

function assignmentCount(item) {
  return item.teaching_assignments?.length ?? 0
}

async function loadData() {
  loading.value = true
  try {
    const { data } = await getTeachers()
    teachers.value = data.data ?? data
  } finally {
    loading.value = false
  }
}

function openDialog(item = null) {
  editing.value = item
  form.value = item
    ? { name: item.user?.name ?? '', email: item.user?.email ?? '', password: '' }
    : { name: '', email: '', password: '' }
  dialog.value = true
}

async function save() {
  const { valid } = await formRef.value.validate()
  if (!valid) return

  if (editing.value) {
    await updateTeacher(editing.value.id, form.value)
  } else {
    await createTeacher(form.value)
  }
  dialog.value = false
  await loadData()
}

async function remove(item) {
  if (!confirm(`Delete teacher ${item.user?.name}?`)) return
  await deleteTeacher(item.id)
  await loadData()
}

onMounted(loadData)
</script>

<template>
  <div>
    <PageHeader title="Teachers" subtitle="Manage teacher accounts and view their assigned classes.">
      <template #actions>
        <v-btn color="primary" prepend-icon="mdi-plus" @click="openDialog()">Add Teacher</v-btn>
      </template>
    </PageHeader>

    <DataCard
      :headers="headers"
      :items="teachers"
      :loading="loading"
      search-placeholder="Search teachers..."
      :search-keys="['user.name', 'user.email']"
    >
      <template #item.assignments="{ item }">
        <v-chip size="small" :color="assignmentCount(item) ? 'primary' : 'default'" variant="tonal">
          {{ assignmentCount(item) }} class{{ assignmentCount(item) === 1 ? '' : 'es' }}
        </v-chip>
      </template>
      <template #item.actions="{ item }">
        <v-btn icon="mdi-pencil-outline" variant="text" size="small" @click="openDialog(item)" />
        <v-btn icon="mdi-delete-outline" variant="text" size="small" color="error" @click="remove(item)" />
      </template>
    </DataCard>

    <FormDialog v-model="dialog" :title="editing ? 'Edit Teacher' : 'Add Teacher'" @save="save">
      <v-form ref="formRef">
        <v-text-field v-model="form.name" label="Full Name" :rules="[(v) => !!v || 'Required']" />
        <v-text-field v-model="form.email" label="Email" type="email" :rules="[(v) => !!v || 'Required']" class="mt-2" />
        <v-text-field
          v-model="form.password"
          label="Password"
          type="password"
          :rules="editing ? [] : [(v) => !!v || 'Required']"
          class="mt-2"
        />
      </v-form>
    </FormDialog>
  </div>
</template>
