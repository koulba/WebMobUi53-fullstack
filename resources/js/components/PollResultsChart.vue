<script setup>
  import { computed } from 'vue';
  import { Bar } from 'vue-chartjs';
  import {
    Chart as ChartJS,
    Title,
    Tooltip,
    Legend,
    BarElement,
    CategoryScale,
    LinearScale,
  } from 'chart.js';

  ChartJS.register(Title, Tooltip, Legend, BarElement, CategoryScale, LinearScale);

  const props = defineProps({
    options: { type: Array, required: true },
  });

  const chartData = computed(() => ({
    labels: props.options.map(o => o.label),
    datasets: [{
      label: 'Votes',
      data: props.options.map(o => o.votes_count),
      backgroundColor: '#2563eb',
    }],
  }));

  const chartOptions = {
    responsive: true,
    maintainAspectRatio: false,
    plugins: {
      legend: { display: false },
    },
    scales: {
      y: {
        beginAtZero: true,
        ticks: { precision: 0 },
      },
    },
  };
</script>

<template>
  <div class="h-64">
    <Bar :data="chartData" :options="chartOptions" />
  </div>
</template>
