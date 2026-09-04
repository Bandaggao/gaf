<script setup>
import { computed } from 'vue'

const props = defineProps({
  modelValue: { type: [String, Number, Array, null], default: null },
  items: { type: Array, default: () => [] },
  label: { type: String, default: '' },
  itemTitle: { type: [String, Function], default: 'title' },
  itemValue: { type: String, default: 'value' },
  multiple: { type: Boolean, default: false },
  chips: { type: Boolean, default: true },
  closableChips: { type: Boolean, default: true },
  clearable: { type: Boolean, default: true },
  disabled: { type: Boolean, default: false },
  rules: { type: Array, default: () => [] },
  placeholder: { type: String, default: 'Search...' },
  noDataText: { type: String, default: 'No matches found' },
})

const emit = defineEmits(['update:modelValue'])

const value = computed({
  get: () => props.modelValue,
  set: (next) => emit('update:modelValue', next),
})
</script>

<template>
  <v-autocomplete
    v-model="value"
    :items="items"
    :label="label"
    :item-title="itemTitle"
    :item-value="itemValue"
    :multiple="multiple"
    :chips="multiple && chips"
    :closable-chips="multiple && closableChips"
    :clearable="clearable"
    :disabled="disabled"
    :rules="rules"
    :placeholder="placeholder"
    :no-data-text="noDataText"
    prepend-inner-icon="mdi-magnify"
    hide-details="auto"
    auto-select-first
  >
    <template v-if="$slots.item" #item="slotProps">
      <slot name="item" v-bind="slotProps" />
    </template>
    <template v-if="$slots.selection" #selection="slotProps">
      <slot name="selection" v-bind="slotProps" />
    </template>
  </v-autocomplete>
</template>
