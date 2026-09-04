import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import * as authApi from '@/api/auth'
import { authToken } from '@/api/authToken'

export const useAuthStore = defineStore('auth', () => {
  const user = ref(null)
  const loading = ref(false)

  const isAuthenticated = computed(() => !!user.value && !!authToken.value)
  const role = computed(() => user.value?.role ?? null)

  function hasRole(...roles) {
    return roles.includes(role.value)
  }

  async function login(credentials) {
    loading.value = true
    try {
      const data = await authApi.loginAndStore(credentials)
      user.value = data.user
      return {
        user: data.user,
        dashboardRoute: data.dashboard_route,
      }
    } finally {
      loading.value = false
    }
  }

  async function fetchUser() {
    if (!authToken.value) {
      user.value = null
      return null
    }

    loading.value = true
    try {
      const { data } = await authApi.fetchUser()
      user.value = data.user ?? data
      return user.value
    } catch {
      authApi.clearAuthToken()
      user.value = null
      return null
    } finally {
      loading.value = false
    }
  }

  async function logout() {
    try {
      await authApi.logout()
    } finally {
      authApi.clearAuthToken()
      user.value = null
    }
  }

  return {
    user,
    loading,
    isAuthenticated,
    role,
    hasRole,
    login,
    fetchUser,
    logout,
  }
})
