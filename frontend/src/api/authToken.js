import { ref } from 'vue'

// Shared reactive token — used by axios client and Pinia store
export const authToken = ref(null)

const TOKEN_KEY = 'gafs_auth_token'

export function setAuthToken(token) {
  authToken.value = token || null
  try {
    if (token) {
      localStorage.setItem(TOKEN_KEY, token)
    } else {
      localStorage.removeItem(TOKEN_KEY)
    }
  } catch {
    // localStorage may be blocked in some Dev Tunnel contexts
  }
}

export function getAuthToken() {
  if (authToken.value) {
    return authToken.value
  }

  try {
    const stored = localStorage.getItem(TOKEN_KEY)
    if (stored) {
      authToken.value = stored
    }
    return stored
  } catch {
    return null
  }
}

export function clearAuthToken() {
  setAuthToken(null)
}

// Restore token on app load
getAuthToken()
