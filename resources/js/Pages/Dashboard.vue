<template>
    <AppLayout title="Dashboard">
        <div>
            <h3 class="text-base font-semibold text-gray-900">Last 30 days</h3>
            <dl class="mt-5 grid grid-cols-1 gap-5 sm:grid-cols-2">
                <div v-for="item in stats" :key="item.name"
                    class="overflow-hidden rounded-lg bg-white px-4 py-5 shadow sm:p-6">
                    <dt class="truncate text-sm font-medium text-gray-500">{{ item.name }}</dt>
                    <dd class="mt-1 text-3xl font-semibold tracking-tight text-gray-900">{{ item.stat }}</dd>
                </div>
            </dl>

            <div class="mt-8" style="height: 600px">
                <Line :data="chartData" :options="chartOptions" />
            </div>
        </div>
    </AppLayout>
</template>

<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { Line } from 'vue-chartjs';
import { Chart as ChartJS, CategoryScale, LinearScale, PointElement, LineElement, Title, Tooltip, Legend, Filler } from 'chart.js';
import { defineProps } from 'vue';

const props = defineProps({
    stats: {
        type: Array,
        required: true
    },
    chartData: {
        type: Object,
        required: true
    }
});

ChartJS.register(CategoryScale, LinearScale, PointElement, LineElement, Title, Tooltip, Legend, Filler);

const chartData = {
    labels: props.chartData.dates,
    datasets: [
        {
            label: 'Messages',
            backgroundColor: 'rgba(99, 102, 241, 0.1)',
            borderColor: '#6366f1',
            borderWidth: 2,
            data: props.chartData.counts,
            fill: true,
            tension: 0.4,
            pointRadius: 4,
            pointBackgroundColor: '#6366f1',
            pointBorderColor: '#fff',
            pointBorderWidth: 2,
            pointHoverRadius: 6,
            pointHoverBackgroundColor: '#6366f1',
            pointHoverBorderColor: '#fff',
            pointHoverBorderWidth: 2
        }
    ]
}

const chartOptions = {
    responsive: true,
    maintainAspectRatio: false,
    plugins: {
        legend: {
            display: true,
            labels: {
                usePointStyle: true,
                pointStyle: 'circle',
                padding: 20,
                font: {
                    size: 12
                }
            }
        },
        title: {
            display: true,
            text: 'Message Activity - Last 30 Days',
            font: {
                size: 16,
                weight: 'bold'
            },
            padding: {
                bottom: 30
            }
        },
        tooltip: {
            backgroundColor: 'rgba(255, 255, 255, 0.9)',
            titleColor: '#6366f1',
            bodyColor: '#666',
            bodyFont: {
                size: 13
            },
            borderColor: '#e5e7eb',
            borderWidth: 1,
            padding: 12,
            displayColors: false
        }
    },
    scales: {
        y: {
            beginAtZero: true,
            grid: {
                color: 'rgba(0, 0, 0, 0.05)',
                drawBorder: false
            },
            ticks: {
                padding: 10,
                color: '#666'
            },
        },
        x: {
            grid: {
                display: false
            },
            ticks: {
                padding: 10,
                color: '#666'
            },
        }
    },
    interaction: {
        intersect: false,
        mode: 'index'
    }
}
</script>
