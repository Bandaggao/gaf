<script setup>
import { ref } from 'vue'
import { useRoute } from 'vue-router'
import { useAuthStore } from '@/stores/auth'
import { roleHome } from '@/constants/roles'

const route = useRoute()
const auth = useAuthStore()

const email = ref('')
const password = ref('')
const error = ref(null)
const submitting = ref(false)
const formRef = ref(null)

async function handleLogin() {
  const { valid } = await formRef.value.validate()
  if (!valid) return

  error.value = null
  submitting.value = true

  try {
    const { user } = await auth.login({
      email: email.value,
      password: password.value,
    })

    if (!user?.role) {
      error.value = 'Login succeeded but user role is missing.'
      return
    }

    const redirectPath = route.query.redirect || roleHome[user.role]
    if (!redirectPath) {
      error.value = 'Unknown user role. Cannot redirect.'
      return
    }

    window.location.assign(redirectPath)
  } catch (err) {
    const data = err.response?.data
    error.value =
      data?.errors?.email?.[0] ||
      data?.message ||
      'Unable to sign in. Check your email and password.'
    submitting.value = false
  }
}
</script>

<template>
  <v-card class="login-card mx-auto" max-width="440" width="100%">
    <v-card-text class="pa-8">
      <div class="text-center mb-8">
        <v-avatar color="primary" variant="tonal" size="64" rounded="xl" class="mb-4">
          <v-icon icon="mdi-shield-check-outline" size="34" />
        </v-avatar>
        <h1 class="text-h5 font-weight-bold mb-2">GAFS A-Watch</h1>
        <p class="text-body-2 text-medium-emphasis mb-0">
          Gamu Agri-Fishery School Attendance System
        </p>
      </div>

      <v-form ref="formRef" @submit.prevent="handleLogin">
        <v-text-field
          v-model="email"
          label="Email"
          type="email"
          prepend-inner-icon="mdi-email-outline"
          :rules="[(v) => !!v || 'Email is required']"
          autocomplete="username"
          class="mb-2"
        />
        <v-text-field
          v-model="password"
          label="Password"
          type="password"
          prepend-inner-icon="mdi-lock-outline"
          :rules="[(v) => !!v || 'Password is required']"
          autocomplete="current-password"
          class="mb-2"
        />

        <v-alert v-if="error" type="error" class="mb-4">{{ error }}</v-alert>

        <v-btn type="submit" color="primary" block size="large" :loading="submitting">
          Sign In
        </v-btn>
      </v-form>
    </v-card-text>
  </v-card>
</template>
