<script setup>
import AppShell from '@/components/layout/AppShell.vue'

const navItems = [
  { title: 'Dashboard', to: '/admin/dashboard', icon: 'mdi-view-dashboard-outline' },
  { title: 'Students', to: '/admin/students', icon: 'mdi-account-school-outline' },
  { title: 'Teachers', to: '/admin/teachers', icon: 'mdi-human-male-board' },
  { title: 'Program Blocks', to: '/admin/sections', icon: 'mdi-google-classroom' },
  { title: 'Subjects', to: '/admin/subjects', icon: 'mdi-book-open-variant' },
  { title: 'Classes', to: '/admin/assignments', icon: 'mdi-account-multiple-check-outline' },
  { title: 'Enrollments', to: '/admin/enrollments', icon: 'mdi-school-outline' },
  { title: 'Reports', to: '/admin/reports', icon: 'mdi-file-chart-outline' },
]
</script>

<template>
  <AppShell
    portal-title="Administration"
    portal-subtitle="Administration"
    brand-icon="mdi-shield-account-outline"
    :nav-items="navItems"
  />
</template>
