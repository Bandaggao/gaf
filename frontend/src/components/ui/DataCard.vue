<script setup>
import { computed, ref } from 'vue'

const emit = defineEmits(['click:row'])

const props = defineProps({
  title: { type: String, default: '' },
  headers: { type: Array, default: () => [] },
  items: { type: Array, default: () => [] },
  loading: { type: Boolean, default: false },
  itemValue: { type: String, default: 'id' },
  searchPlaceholder: { type: String, default: 'Search...' },
  searchable: { type: Boolean, default: true },
  searchKeys: { type: Array, default: () => [] },
  density: { type: String, default: 'comfortable' },
})

const search = ref('')

function itemText(item) {
  if (!props.searchKeys.length) {
    return JSON.stringify(item).toLowerCase()
  }

  return props.searchKeys
    .map((key) => {
      const parts = key.split('.')
      let value = item
      for (const part of parts) {
        value = value?.[part]
      }
      return value
    })
    .filter(Boolean)
    .join(' ')
    .toLowerCase()
}

const filteredItems = computed(() => {
  const query = search.value.trim().toLowerCase()
  if (!query) return props.items
  return props.items.filter((item) => itemText(item).includes(query))
})
</script>

<template>
  <v-card class="app-card">
    <v-card-title v-if="title || searchable || $slots.toolbar" class="d-flex flex-wrap align-center ga-3 py-4 px-5">
      <span v-if="title" class="text-subtitle-1 font-weight-bold">{{ title }}</span>
      <v-spacer />
      <slot name="toolbar" />
      <v-text-field
        v-if="searchable"
        v-model="search"
        :placeholder="searchPlaceholder"
        prepend-inner-icon="mdi-magnify"
        hide-details
        density="compact"
        style="max-width: 280px"
        clearable
      />
    </v-card-title>

    <v-divider v-if="title || searchable || $slots.toolbar" />

    <v-data-table
      :headers="headers"
      :items="filteredItems"
      :loading="loading"
      :item-value="itemValue"
      :density="density"
      class="data-card-table"
      @click:row="(...args) => emit('click:row', ...args)"
    >
      <template v-for="(_, name) in $slots" #[name]="slotProps">
        <slot v-if="name !== 'toolbar'" :name="name" v-bind="slotProps" />
      </template>
    </v-data-table>
  </v-card>
</template>

<style scoped>
.data-card-table :deep(thead th) {
  font-size: 0.78rem;
  text-transform: uppercase;
  letter-spacing: 0.04em;
  color: rgba(var(--v-theme-on-surface), 0.55);
}
</style>
