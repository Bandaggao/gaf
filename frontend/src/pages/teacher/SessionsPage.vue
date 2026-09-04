<script setup>
import { ref, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { getSessions, createSession, getSessionOptions } from '@/api/teacher'
import PageHeader from '@/components/ui/PageHeader.vue'
import DataCard from '@/components/ui/DataCard.vue'
import FormDialog from '@/components/ui/FormDialog.vue'
import SearchSelect from '@/components/ui/SearchSelect.vue'
import StatusChip from '@/components/ui/StatusChip.vue'

const router = useRouter()
const loading = ref(false)
const sessions = ref([])
const classes = ref([])
const dialog = ref(false)
const formRef = ref(null)
const form = ref({
  teaching_assignment_id: null,
  session_date: new Date().toISOString().slice(0, 10),
  start_time: '08:00',
  end_time: '09:00',
})

const headers = [
  { title: 'Date', key: 'session_date' },
  { title: 'Subject', key: 'subject.name' },
  { title: 'Program Block', key: 'section.name' },
  { title: 'Start', key: 'start_time' },
  { title: 'End', key: 'end_time' },
  { title: 'Status', key: 'status' },
  { title: 'Actions', key: 'actions', sortable: false },
]

async function loadData() {
  loading.value = true
  try {
    const [sessionsRes, optionsRes] = await Promise.all([getSessions(), getSessionOptions()])
    sessions.value = sessionsRes.data.data ?? sessionsRes.data
    classes.value = optionsRes.data.classes ?? []
  } finally {
    loading.value = false
  }
}

function openDialog() {
  form.value.teaching_assignment_id = classes.value[0]?.id ?? null
  dialog.value = true
}

async function save() {
  const { valid } = await formRef.value.validate()
  if (!valid) return

  const { data } = await createSession(form.value)
  dialog.value = false
  await loadData()

  const sessionId = data.data?.id ?? data.id
  if (sessionId) {
    router.push({ name: 'teacher-session-detail', params: { id: sessionId } })
  }
}

onMounted(loadData)
</script>

<template>
  <div>
    <PageHeader title="Class Sessions" subtitle="Start attendance sessions for your assigned classes.">
      <template #actions>
        <v-btn color="primary" prepend-icon="mdi-plus" @click="openDialog">New Session</v-btn>
      </template>
    </PageHeader>

    <DataCard
      :headers="headers"
      :items="sessions"
      :loading="loading"
      search-placeholder="Search sessions..."
      :search-keys="['subject.name', 'section.name', 'session_date', 'status']"
    >
      <template #item.session_date="{ item }">
        {{ String(item.session_date).slice(0, 10) }}
      </template>
      <template #item.status="{ item }">
        <StatusChip :value="item.status" preset="session" />
      </template>
      <template #item.actions="{ item }">
        <v-btn
          variant="tonal"
          color="primary"
          size="small"
          @click="router.push({ name: 'teacher-session-detail', params: { id: item.id } })"
        >
          Open
        </v-btn>
      </template>
    </DataCard>

    <FormDialog v-model="dialog" title="Start Class Session" save-label="Start Session" @save="save">
      <v-form ref="formRef">
        <SearchSelect
          v-model="form.teaching_assignment_id"
          :items="classes"
          item-title="label"
          item-value="id"
          label="Class"
          :rules="[(v) => !!v || 'Required']"
          :disabled="!classes.length"
        />
        <v-row dense class="mt-2">
          <v-col cols="12">
            <v-text-field v-model="form.session_date" label="Date" type="date" :rules="[(v) => !!v || 'Required']" />
          </v-col>
          <v-col cols="6">
            <v-text-field v-model="form.start_time" label="Start Time" type="time" :rules="[(v) => !!v || 'Required']" />
          </v-col>
          <v-col cols="6">
            <v-text-field v-model="form.end_time" label="End Time" type="time" :rules="[(v) => !!v || 'Required']" />
          </v-col>
        </v-row>
        <v-alert v-if="!classes.length" type="warning" class="mt-4">
          Ask an admin to assign classes to your account.
        </v-alert>
      </v-form>
    </FormDialog>
  </div>
</template>
