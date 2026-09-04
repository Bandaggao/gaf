<script setup>
import AppShell from '@/components/layout/AppShell.vue'

const navItems = [
  { title: 'Dashboard', to: '/student/dashboard', icon: 'mdi-view-dashboard-outline' },
  { title: 'My QR Code', to: '/student/scan', icon: 'mdi-qrcode' },
  { title: 'Profile', to: '/student/profile', icon: 'mdi-account-circle-outline' },
]
</script>

<template>
  <AppShell
    portal-title="Student Portal"
    portal-subtitle="Student Portal"
    brand-icon="mdi-account-school-outline"
    :nav-items="navItems"
  />
</template>
