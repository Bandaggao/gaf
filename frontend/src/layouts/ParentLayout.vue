<script setup>
import AppShell from '@/components/layout/AppShell.vue'

const navItems = [
  { title: 'Dashboard', to: '/parent/dashboard', icon: 'mdi-view-dashboard-outline' },
]
</script>

<template>
  <AppShell
    portal-title="Parent Portal"
    portal-subtitle="Parent Portal"
    brand-icon="mdi-account-child-outline"
    :nav-items="navItems"
  />
</template>
