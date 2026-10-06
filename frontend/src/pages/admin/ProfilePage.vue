<script setup>
import { ref } from 'vue'
import { updateAdminProfile } from '@/api/admin'
import { useAuthStore } from '@/stores/auth'
import PageHeader from '@/components/ui/PageHeader.vue'

const auth = useAuthStore()
const formRef = ref(null)
const name = ref(auth.user?.name ?? '')
const saving = ref(false)
const message = ref('')
const error = ref('')

async function saveProfile() {
  const { valid } = await formRef.value.validate()
  if (!valid) return

  saving.value = true
  message.value = ''
  error.value = ''

  try {
    const { data } = await updateAdminProfile({ name: name.value.trim() })
    auth.user = data.user
    name.value = data.user.name
    message.value = 'Admin name updated.'
  } catch (err) {
    error.value = err.response?.data?.errors?.name?.[0]
      || err.response?.data?.message
      || 'Unable to update the admin name.'
  } finally {
    saving.value = false
  }
}
</script>

<template>
  <div>
    <PageHeader title="Admin Profile" subtitle="Update the name displayed on your admin account." />

    <v-card class="app-card" max-width="680">
      <v-card-title class="font-weight-bold">Account Details</v-card-title>
      <v-card-text>
        <v-form ref="formRef" @submit.prevent="saveProfile">
          <v-text-field
            v-model="name"
            label="Admin name"
            autocomplete="name"
            :rules="[
              (value) => !!value?.trim() || 'Name is required',
              (value) => value?.trim().length <= 255 || 'Name must be 255 characters or fewer',
            ]"
          />

          <v-alert v-if="message" type="success" variant="tonal" class="mb-4">
            {{ message }}
          </v-alert>
          <v-alert v-if="error" type="error" variant="tonal" class="mb-4">
            {{ error }}
          </v-alert>

          <v-btn type="submit" color="primary" prepend-icon="mdi-content-save" :loading="saving">
            Save name
          </v-btn>
        </v-form>
      </v-card-text>
    </v-card>
  </div>
</template>