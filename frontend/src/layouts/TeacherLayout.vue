<script setup>
import AppShell from '@/components/layout/AppShell.vue'

const navItems = [
  { title: 'Dashboard', to: '/teacher/dashboard', icon: 'mdi-view-dashboard-outline' },
  { title: 'Sessions', to: '/teacher/sessions', icon: 'mdi-calendar-clock-outline' },
  { title: 'Profile', to: '/teacher/profile', icon: 'mdi-account-circle-outline' },
]
</script>

<template>
  <AppShell
    portal-title="Teacher Portal"
    portal-subtitle="Teacher Portal"
    brand-icon="mdi-school-outline"
    :nav-items="navItems"
  />
</template>
