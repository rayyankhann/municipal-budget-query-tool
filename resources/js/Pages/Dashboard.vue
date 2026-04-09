<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link } from '@inertiajs/vue3';
import { Bar, Line } from 'vue-chartjs';
import {
    Chart as ChartJS,
    Title,
    Tooltip,
    Legend,
    BarElement,
    LineElement,
    PointElement,
    CategoryScale,
    LinearScale,
    Filler,
} from 'chart.js';

ChartJS.register(Title, Tooltip, Legend, BarElement, LineElement, PointElement, CategoryScale, LinearScale, Filler);

const props = defineProps({
    stats: Object,
    spendByGroup: Array,
    quarterlySpend: Array,
    topVendors: Array,
    userRole: String,
    departmentName: String,
    isAdmin: Boolean,
});

const formatCurrency = (val) => {
    return new Intl.NumberFormat('en-US', { style: 'currency', currency: 'USD', minimumFractionDigits: 0, maximumFractionDigits: 0 }).format(val);
};

const barChartData = {
    labels: props.spendByGroup.map(i => i.label),
    datasets: [{
        label: 'Total Spend',
        data: props.spendByGroup.map(i => parseFloat(i.total)),
        backgroundColor: [
            'rgba(59, 130, 246, 0.8)',
            'rgba(16, 185, 129, 0.8)',
            'rgba(245, 158, 11, 0.8)',
            'rgba(239, 68, 68, 0.8)',
            'rgba(139, 92, 246, 0.8)',
            'rgba(236, 72, 153, 0.8)',
        ],
        borderRadius: 6,
    }],
};

const barChartOptions = {
    responsive: true,
    maintainAspectRatio: false,
    plugins: {
        legend: { display: false },
        tooltip: {
            callbacks: {
                label: (ctx) => formatCurrency(ctx.raw),
            },
        },
    },
    scales: {
        y: {
            ticks: {
                callback: (val) => formatCurrency(val),
            },
        },
    },
};

const lineChartData = {
    labels: props.quarterlySpend.map(i => `Q${i.quarter}`),
    datasets: [{
        label: 'Quarterly Spend',
        data: props.quarterlySpend.map(i => parseFloat(i.total)),
        borderColor: 'rgb(59, 130, 246)',
        backgroundColor: 'rgba(59, 130, 246, 0.1)',
        fill: true,
        tension: 0.3,
        pointBackgroundColor: 'rgb(59, 130, 246)',
        pointRadius: 5,
    }],
};

const lineChartOptions = {
    responsive: true,
    maintainAspectRatio: false,
    plugins: {
        legend: { display: false },
        tooltip: {
            callbacks: {
                label: (ctx) => formatCurrency(ctx.raw),
            },
        },
    },
    scales: {
        y: {
            ticks: {
                callback: (val) => formatCurrency(val),
            },
        },
    },
};
</script>

<template>
    <Head title="Dashboard" />

    <AuthenticatedLayout>
        <template #header>
            <div>
                <h1 class="text-2xl font-bold text-gray-900">Dashboard</h1>
                <p class="mt-1 text-sm text-gray-500">{{ departmentName }} &mdash; Fiscal Year 2024</p>
            </div>
        </template>

        <!-- Stat Cards -->
        <div class="mb-8 grid grid-cols-1 gap-5 sm:grid-cols-3">
            <div class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm">
                <p class="text-sm font-medium text-gray-500">Total Budget Allocated</p>
                <p class="mt-2 text-3xl font-bold text-gray-900">{{ formatCurrency(stats.totalAllocated) }}</p>
            </div>
            <div class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm">
                <p class="text-sm font-medium text-gray-500">Total Spent</p>
                <p class="mt-2 text-3xl font-bold text-gray-900">{{ formatCurrency(stats.totalSpent) }}</p>
            </div>
            <div class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm">
                <p class="text-sm font-medium text-gray-500">Budget Used</p>
                <p class="mt-2 text-3xl font-bold" :class="stats.percentUsed > 100 ? 'text-red-600' : stats.percentUsed > 90 ? 'text-amber-600' : 'text-green-600'">
                    {{ stats.percentUsed }}%
                </p>
                <div class="mt-2 h-2 w-full overflow-hidden rounded-full bg-gray-200">
                    <div
                        class="h-full rounded-full transition-all"
                        :class="stats.percentUsed > 100 ? 'bg-red-500' : stats.percentUsed > 90 ? 'bg-amber-500' : 'bg-green-500'"
                        :style="{ width: Math.min(stats.percentUsed, 100) + '%' }"
                    ></div>
                </div>
            </div>
        </div>

        <!-- Charts Row -->
        <div class="mb-8 grid grid-cols-1 gap-6 lg:grid-cols-2">
            <div class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm">
                <h3 class="mb-4 text-lg font-semibold text-gray-800">
                    {{ isAdmin ? 'Spend by Department' : 'Spend by Category' }}
                </h3>
                <div class="h-72">
                    <Bar :data="barChartData" :options="barChartOptions" />
                </div>
            </div>
            <div class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm">
                <h3 class="mb-4 text-lg font-semibold text-gray-800">Quarterly Spend Trend</h3>
                <div class="h-72">
                    <Line :data="lineChartData" :options="lineChartOptions" />
                </div>
            </div>
        </div>

        <!-- Top Vendors + Quick Link -->
        <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
            <div class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm">
                <h3 class="mb-4 text-lg font-semibold text-gray-800">Top Vendors by Spend</h3>
                <div class="space-y-3">
                    <div v-for="vendor in topVendors" :key="vendor.vendor" class="flex items-center justify-between">
                        <span class="text-sm text-gray-700">{{ vendor.vendor }}</span>
                        <span class="text-sm font-semibold text-gray-900">{{ formatCurrency(vendor.total) }}</span>
                    </div>
                </div>
            </div>
            <div class="flex items-center justify-center rounded-xl border border-gray-200 bg-gradient-to-br from-blue-50 to-indigo-50 p-6 shadow-sm">
                <div class="text-center">
                    <svg class="mx-auto h-12 w-12 text-blue-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16l2.879-2.879m0 0a3 3 0 104.243-4.242 3 3 0 00-4.243 4.242zM21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    <h3 class="mt-3 text-lg font-semibold text-gray-800">Ask a Budget Question</h3>
                    <p class="mt-1 text-sm text-gray-500">Use natural language to query budget data powered by AI</p>
                    <Link
                        :href="route('query')"
                        class="mt-4 inline-flex items-center rounded-lg bg-blue-600 px-5 py-2.5 text-sm font-medium text-white shadow-sm transition hover:bg-blue-700"
                    >
                        Go to Budget Query
                    </Link>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
