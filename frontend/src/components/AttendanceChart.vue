<script setup>
import { computed } from 'vue'
import { Line } from 'vue-chartjs'
import {
  Chart as ChartJS,
  CategoryScale,
  LinearScale,
  PointElement,
  LineElement,
  Title,
  Tooltip,
  Legend,
} from 'chart.js'

ChartJS.register(CategoryScale, LinearScale, PointElement, LineElement, Title, Tooltip, Legend)

const props = defineProps({
  labels: { type: Array, default: () => [] },
  present: { type: Array, default: () => [] },
  absent: { type: Array, default: () => [] },
  title: { type: String, default: 'Attendance Trend' },
})

const chartData = computed(() => ({
  labels: props.labels,
  datasets: [
    {
      label: 'Present',
      data: props.present,
      borderColor: '#2E7D32',
      backgroundColor: 'rgba(46, 125, 50, 0.1)',
      tension: 0.3,
    },
    {
      label: 'Absent',
      data: props.absent,
      borderColor: '#C62828',
      backgroundColor: 'rgba(198, 40, 40, 0.1)',
      tension: 0.3,
    },
  ],
}))

const chartOptions = {
  responsive: true,
  maintainAspectRatio: false,
  plugins: {
    legend: { position: 'bottom' },
    title: { display: true, text: props.title },
  },
  scales: {
    y: { beginAtZero: true, ticks: { stepSize: 1 } },
  },
}
</script>

<template>
  <v-card>
    <v-card-text>
      <div style="height: 300px">
        <Line :data="chartData" :options="chartOptions" />
      </div>
    </v-card-text>
  </v-card>
</template>
