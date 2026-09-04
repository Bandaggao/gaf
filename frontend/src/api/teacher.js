import client from './client'

export function getDashboard() {
  return client.get('/teacher/dashboard')
}

export function getProfile() {
  return client.get('/teacher/profile')
}

export function getSessionOptions() {
  return client.get('/teacher/session-options')
}

export function getSessions() {
  return client.get('/teacher/sessions')
}

export function createSession(data) {
  return client.post('/teacher/sessions', data)
}

export function getSession(id) {
  return client.get(`/teacher/sessions/${id}`)
}

export function closeSession(id) {
  return client.post(`/teacher/sessions/${id}/close`)
}

export function getSessionRoster(id) {
  return client.get(`/teacher/sessions/${id}/roster`)
}

export function updateAttendance(sessionId, data) {
  return client.post(`/teacher/sessions/${sessionId}/attendance`, data)
}
