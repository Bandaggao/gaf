<script setup>
import { ref, onMounted } from 'vue'
import { getSubjects, createSubject, updateSubject, deleteSubject } from '@/api/admin'
import PageHeader from '@/components/ui/PageHeader.vue'
import DataCard from '@/components/ui/DataCard.vue'
import FormDialog from '@/components/ui/FormDialog.vue'

const loading = ref(false)
const subjects = ref([])
const dialog = ref(false)
const editing = ref(null)
const formRef = ref(null)
const form = ref({ name: '' })

const headers = [
  { title: 'Subject', key: 'name' },
  { title: 'Classes', key: 'teaching_assignments_count' },
  { title: 'Actions', key: 'actions', sortable: false },
]

async function loadData() {
  loading.value = true
  try {
    const { data } = await getSubjects()
    subjects.value = data.data ?? data
  } finally {
    loading.value = false
  }
}

function openDialog(item = null) {
  editing.value = item
  form.value = item ? { name: item.name } : { name: '' }
  dialog.value = true
}

async function save() {
  const { valid } = await formRef.value.validate()
  if (!valid) return

  if (editing.value) {
    await updateSubject(editing.value.id, form.value)
  } else {
    await createSubject(form.value)
  }
  dialog.value = false
  await loadData()
}

async function remove(item) {
  if (!confirm(`Delete subject ${item.name}?`)) return
  await deleteSubject(item.id)
  await loadData()
}

onMounted(loadData)
</script>

<template>
  <div>
    <PageHeader
      title="Subjects"
      subtitle="Subject catalog only. Assign teachers to program blocks in Classes."
    >
      <template #actions>
        <v-btn color="primary" prepend-icon="mdi-plus" @click="openDialog()">Add Subject</v-btn>
      </template>
    </PageHeader>

    <DataCard
      :headers="headers"
      :items="subjects"
      :loading="loading"
      search-placeholder="Search subjects..."
      :search-keys="['name']"
    >
      <template #item.teaching_assignments_count="{ item }">
        <v-chip size="small" color="info" variant="tonal">
          {{ item.teaching_assignments_count ?? 0 }} classes
        </v-chip>
      </template>
      <template #item.actions="{ item }">
        <v-btn icon="mdi-pencil-outline" variant="text" size="small" @click="openDialog(item)" />
        <v-btn icon="mdi-delete-outline" variant="text" size="small" color="error" @click="remove(item)" />
      </template>
    </DataCard>

    <FormDialog v-model="dialog" :title="editing ? 'Edit Subject' : 'Add Subject'" @save="save">
      <v-form ref="formRef">
        <v-text-field v-model="form.name" label="Subject Name" :rules="[(v) => !!v || 'Required']" />
      </v-form>
    </FormDialog>
  </div>
</template>
