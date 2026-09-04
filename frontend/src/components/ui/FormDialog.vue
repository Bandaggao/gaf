<script setup>
defineProps({
  modelValue: { type: Boolean, required: true },
  title: { type: String, required: true },
  maxWidth: { type: [String, Number], default: 560 },
  saving: { type: Boolean, default: false },
  saveLabel: { type: String, default: 'Save' },
})

const emit = defineEmits(['update:modelValue', 'save'])

function close() {
  emit('update:modelValue', false)
}
</script>

<template>
  <v-dialog :model-value="modelValue" :max-width="maxWidth" @update:model-value="emit('update:modelValue', $event)">
    <v-card class="app-card">
      <v-card-title class="text-h6 font-weight-bold px-6 pt-6">{{ title }}</v-card-title>
      <v-card-text class="px-6 pb-2">
        <slot />
      </v-card-text>
      <v-card-actions class="px-6 pb-6">
        <v-spacer />
        <v-btn variant="text" @click="close">Cancel</v-btn>
        <v-btn color="primary" :loading="saving" @click="emit('save')">{{ saveLabel }}</v-btn>
      </v-card-actions>
    </v-card>
  </v-dialog>
</template>
