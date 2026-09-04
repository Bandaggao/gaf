<script setup>
import { computed, ref } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/auth'

const props = defineProps({
  portalTitle: { type: String, required: true },
  portalSubtitle: { type: String, default: '' },
  navItems: { type: Array, required: true },
  brandIcon: { type: String, default: 'mdi-school' },
})

const drawer = ref(true)
const route = useRoute()
const router = useRouter()
const auth = useAuthStore()

const initials = computed(() => {
  const name = auth.user?.name || '?'
  return name
    .split(' ')
    .map((part) => part[0])
    .join('')
    .slice(0, 2)
    .toUpperCase()
})

const currentTitle = computed(() => {
  const match = props.navItems.find((item) => route.path.startsWith(item.to))
  return match?.title || props.portalTitle
})

async function handleLogout() {
  await auth.logout()
  router.push({ name: 'login' })
}
</script>

<template>
  <v-navigation-drawer v-model="drawer" app width="280" class="app-card app-card--flat">
    <div class="app-sidebar-brand">
      <div class="d-flex align-center ga-3 mb-1">
        <v-avatar color="primary" variant="tonal" size="42" rounded="lg">
          <v-icon :icon="brandIcon" />
        </v-avatar>
        <div>
          <div class="app-sidebar-brand__title">GAFS A-Watch</div>
          <div class="app-sidebar-brand__subtitle">{{ portalSubtitle || portalTitle }}</div>
        </div>
      </div>
    </div>

    <v-divider />

    <v-list nav density="comfortable" class="app-nav px-3 py-3">
      <v-list-item
        v-for="item in navItems"
        :key="item.to"
        :to="item.to"
        :prepend-icon="item.icon"
        :title="item.title"
        rounded="lg"
      />
    </v-list>
  </v-navigation-drawer>

  <v-app-bar app flat class="app-topbar" height="72">
    <v-app-bar-nav-icon @click="drawer = !drawer" />
    <v-app-bar-title class="font-weight-bold">{{ currentTitle }}</v-app-bar-title>
    <v-spacer />
    <v-chip class="mr-2 glass-chip d-none d-sm-flex" prepend-icon="mdi-account-circle" variant="tonal" color="primary">
      {{ auth.user?.name }}
    </v-chip>
    <v-menu location="bottom end">
      <template #activator="{ props: menuProps }">
        <v-btn v-bind="menuProps" icon variant="text">
          <v-avatar color="primary" size="36">
            <span class="text-caption font-weight-bold">{{ initials }}</span>
          </v-avatar>
        </v-btn>
      </template>
      <v-list density="compact" min-width="180">
        <v-list-item :title="auth.user?.name" :subtitle="auth.user?.email" />
        <v-divider />
        <v-list-item prepend-icon="mdi-logout" title="Sign out" @click="handleLogout" />
      </v-list>
    </v-menu>
  </v-app-bar>

  <v-main class="app-main">
    <v-container fluid class="pa-4 pa-md-6">
      <div class="app-page">
        <router-view />
      </div>
    </v-container>
  </v-main>
</template>
