import axios from 'axios'
import { getAuthToken, setAuthToken, clearAuthToken, authToken } from './authToken'

const client = axios.create({
  baseURL: `${import.meta.env.VITE_API_URL || 'http://localhost:8000'}/api`,
  headers: {
    Accept: 'application/json',
    'Content-Type': 'application/json',
  },
})

client.interceptors.request.use((config) => {
  const token = getAuthToken()
  if (token) {
    config.headers.Authorization = `Bearer ${token}`
  }
  return config
})

client.interceptors.response.use(
  (response) => response,
  (error) => {
    const isLoginRequest = error.config?.url?.includes('/login')
    if (error.response?.status === 401 && !isLoginRequest) {
      clearAuthToken()
      if (!window.location.pathname.startsWith('/login')) {
        window.location.href = '/login'
      }
    }
    return Promise.reject(error)
  },
)

export { setAuthToken, getAuthToken, clearAuthToken, authToken }
export default client
