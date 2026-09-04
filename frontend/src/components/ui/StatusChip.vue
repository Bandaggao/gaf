<script setup>
import { computed } from 'vue'

const props = defineProps({
  value: { type: String, default: '' },
  preset: {
    type: String,
    default: 'default',
    validator: (v) => ['default', 'session', 'attendance', 'student_type', 'enrollment'].includes(v),
  },
})

const presets = {
  session: {
    active: { color: 'success', label: 'Active' },
    closed: { color: 'grey', label: 'Closed' },
    expired: { color: 'warning', label: 'Expired' },
  },
  attendance: {
    present: { color: 'success', label: 'Present' },
    late: { color: 'warning', label: 'Late' },
    absent: { color: 'error', label: 'Absent' },
  },
  student_type: {
    regular: { color: 'primary', label: 'Regular' },
    irregular: { color: 'warning', label: 'Irregular' },
  },
  enrollment: {
    regular: { color: 'primary', label: 'Regular' },
    irregular: { color: 'warning', label: 'Irregular' },
  },
}

const chip = computed(() => {
  const key = String(props.value || '').toLowerCase()
  const map = presets[props.preset] || {}
  return map[key] || { color: 'default', label: props.value || '—' }
})
</script>

<template>
  <v-chip size="small" :color="chip.color" variant="tonal" class="text-capitalize">
    {{ chip.label }}
  </v-chip>
</template>
