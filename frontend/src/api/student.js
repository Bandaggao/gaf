import client from './client'

export function getDashboard() {
  return client.get('/student/dashboard')
}

export function getProfile() {
  return client.get('/student/profile')
}

export function getMyQr() {
  return client.get('/student/qr')
}
