import client, { setAuthToken, clearAuthToken } from './client'

export function login(credentials) {
  return client.post('/login', credentials)
}

export async function loginAndStore(credentials) {
  const { data } = await client.post('/login', credentials)
  setAuthToken(data.token)
  return data
}

export function logout() {
  return client.post('/logout')
}

export function fetchUser() {
  return client.get('/user')
}

export { clearAuthToken }
