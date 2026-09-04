<script setup>
import { ref, onMounted } from 'vue'
import { getSections, createSection, updateSection, deleteSection } from '@/api/admin'
import PageHeader from '@/components/ui/PageHeader.vue'
import DataCard from '@/components/ui/DataCard.vue'
import FormDialog from '@/components/ui/FormDialog.vue'

const loading = ref(false)
const sections = ref([])
const dialog = ref(false)
const editing = ref(null)
const formRef = ref(null)
const form = ref({ name: '', grade_level: '' })

const headers = [
  { title: 'Block Name', key: 'name' },
  { title: 'Year Level', key: 'grade_level' },
  { title: 'Actions', key: 'actions', sortable: false },
]

async function loadData() {
  loading.value = true
  try {
    const { data } = await getSections()
    sections.value = data.data ?? data
  } finally {
    loading.value = false
  }
}

function openDialog(item = null) {
  editing.value = item
  form.value = item ? { name: item.name, grade_level: item.grade_level } : { name: '', grade_level: '' }
  dialog.value = true
}

async function save() {
  const { valid } = await formRef.value.validate()
  if (!valid) return

  if (editing.value) {
    await updateSection(editing.value.id, form.value)
  } else {
    await createSection(form.value)
  }
  dialog.value = false
  await loadData()
}

async function remove(item) {
  if (!confirm(`Delete program block ${item.name}?`)) return
  await deleteSection(item.id)
  await loadData()
}

onMounted(loadData)
</script>

<template>
  <div>
    <PageHeader
      title="Program Blocks"
      subtitle="Home blocks for students (e.g. BSIT 2-A). Classes are linked to these blocks."
    >
      <template #actions>
        <v-btn color="primary" prepend-icon="mdi-plus" @click="openDialog()">Add Program Block</v-btn>
      </template>
    </PageHeader>

    <DataCard
      :headers="headers"
      :items="sections"
      :loading="loading"
      search-placeholder="Search program blocks..."
      :search-keys="['name', 'grade_level']"
    >
      <template #item.grade_level="{ item }">
        <v-chip size="small" variant="tonal" color="secondary">Year {{ item.grade_level }}</v-chip>
      </template>
      <template #item.actions="{ item }">
        <v-btn icon="mdi-pencil-outline" variant="text" size="small" @click="openDialog(item)" />
        <v-btn icon="mdi-delete-outline" variant="text" size="small" color="error" @click="remove(item)" />
      </template>
    </DataCard>

    <FormDialog v-model="dialog" :title="editing ? 'Edit Program Block' : 'Add Program Block'" @save="save">
      <v-form ref="formRef">
        <v-text-field v-model="form.name" label="Block Name (e.g. BSIT 2-A)" :rules="[(v) => !!v || 'Required']" />
        <v-text-field v-model="form.grade_level" label="Year Level" :rules="[(v) => !!v || 'Required']" class="mt-2" />
      </v-form>
    </FormDialog>
  </div>
</template>
