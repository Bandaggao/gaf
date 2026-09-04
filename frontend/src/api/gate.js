import axios from 'axios'

const GATE_KEY_STORAGE = 'gafs_gate_scanner_key'

const gateClient = axios.create({
  baseURL: `${import.meta.env.VITE_API_URL || 'http://localhost:8000'}/api`,
  headers: {
    Accept: 'application/json',
    'Content-Type': 'application/json',
  },
})

export function getGateKey() {
  return localStorage.getItem(GATE_KEY_STORAGE) || ''
}

export function setGateKey(key) {
  localStorage.setItem(GATE_KEY_STORAGE, key)
}

export function clearGateKey() {
  localStorage.removeItem(GATE_KEY_STORAGE)
}

export function scanGateToken(token) {
  const key = getGateKey()
  return gateClient.post(
    '/gate/scan',
    { token },
    { headers: { 'X-Gate-Key': key } },
  )
}
