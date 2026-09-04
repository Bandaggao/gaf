import 'vuetify/styles'
import { createVuetify } from 'vuetify'
import * as components from 'vuetify/components'
import * as directives from 'vuetify/directives'
import '@mdi/font/css/materialdesignicons.css'

export default createVuetify({
  components,
  directives,
  defaults: {
    VBtn: {
      rounded: 'lg',
      elevation: 0,
    },
    VCard: {
      rounded: 'xl',
      elevation: 0,
    },
    VTextField: {
      variant: 'outlined',
      density: 'comfortable',
      rounded: 'lg',
      color: 'primary',
    },
    VSelect: {
      variant: 'outlined',
      density: 'comfortable',
      rounded: 'lg',
      color: 'primary',
    },
    VAutocomplete: {
      variant: 'outlined',
      density: 'comfortable',
      rounded: 'lg',
      color: 'primary',
    },
    VFileInput: {
      variant: 'outlined',
      density: 'comfortable',
      rounded: 'lg',
      color: 'primary',
    },
    VChip: {
      rounded: 'lg',
    },
    VAlert: {
      rounded: 'lg',
      variant: 'tonal',
    },
    VDataTable: {
      hover: true,
    },
  },
  theme: {
    defaultTheme: 'light',
    themes: {
      light: {
        colors: {
          primary: '#2563EB',
          secondary: '#059669',
          accent: '#7C3AED',
          info: '#0284C7',
          success: '#16A34A',
          warning: '#D97706',
          error: '#DC2626',
          background: '#F4F6FB',
          surface: '#FFFFFF',
        },
      },
    },
  },
})
