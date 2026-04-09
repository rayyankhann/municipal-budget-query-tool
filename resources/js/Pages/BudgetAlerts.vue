<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head } from '@inertiajs/vue3';

const props = defineProps({
    alerts: Array,
    summary: Object,
    isAdmin: Boolean,
});

const formatCurrency = (val) => {
    return new Intl.NumberFormat('en-US', { style: 'currency', currency: 'USD', minimumFractionDigits: 0, maximumFractionDigits: 0 }).format(val);
};

const statusColor = (percent) => {
    if (percent > 100) return { bg: 'bg-red-100 dark:bg-red-900/30', text: 'text-red-700 dark:text-red-400', badge: 'bg-red-500', label: 'Over Budget' };
    if (percent >= 90) return { bg: 'bg-amber-100 dark:bg-amber-900/30', text: 'text-amber-700 dark:text-amber-400', badge: 'bg-amber-500', label: 'Near Limit' };
    return { bg: 'bg-green-100 dark:bg-green-900/30', text: 'text-green-700 dark:text-green-400', badge: 'bg-green-500', label: 'Healthy' };
};
</script>

<template>
    <Head title="Budget Alerts" />

    <AuthenticatedLayout>
        <template #header>
            <div>
                <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Budget Alerts</h1>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Categories at or over their allocated budget</p>
            </div>
        </template>

        <!-- Summary Cards -->
        <div class="mb-8 grid grid-cols-1 gap-5 sm:grid-cols-3">
            <div class="rounded-xl border border-red-200 bg-red-50 p-5 dark:border-red-800 dark:bg-red-950">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-red-600 dark:text-red-400">Over Budget</p>
                        <p class="mt-1 text-3xl font-bold text-red-700 dark:text-red-300">{{ summary.overBudget }}</p>
                    </div>
                    <div class="flex h-12 w-12 items-center justify-center rounded-full bg-red-100 dark:bg-red-900">
                        <svg class="h-6 w-6 text-red-600 dark:text-red-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L4.082 16.5c-.77.833.192 2.5 1.732 2.5z"/></svg>
                    </div>
                </div>
            </div>
            <div class="rounded-xl border border-amber-200 bg-amber-50 p-5 dark:border-amber-800 dark:bg-amber-950">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-amber-600 dark:text-amber-400">Near Limit (90%+)</p>
                        <p class="mt-1 text-3xl font-bold text-amber-700 dark:text-amber-300">{{ summary.nearBudget }}</p>
                    </div>
                    <div class="flex h-12 w-12 items-center justify-center rounded-full bg-amber-100 dark:bg-amber-900">
                        <svg class="h-6 w-6 text-amber-600 dark:text-amber-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    </div>
                </div>
            </div>
            <div class="rounded-xl border border-green-200 bg-green-50 p-5 dark:border-green-800 dark:bg-green-950">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-green-600 dark:text-green-400">Healthy</p>
                        <p class="mt-1 text-3xl font-bold text-green-700 dark:text-green-300">{{ summary.healthy }}</p>
                    </div>
                    <div class="flex h-12 w-12 items-center justify-center rounded-full bg-green-100 dark:bg-green-900">
                        <svg class="h-6 w-6 text-green-600 dark:text-green-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    </div>
                </div>
            </div>
        </div>

        <!-- Alerts Table -->
        <div class="rounded-xl border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-800">
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b border-gray-200 bg-gray-50 dark:border-gray-700 dark:bg-gray-750">
                            <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">Status</th>
                            <th v-if="isAdmin" class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">Department</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">Category</th>
                            <th class="px-6 py-3 text-right text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">Allocated</th>
                            <th class="px-6 py-3 text-right text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">Spent</th>
                            <th class="px-6 py-3 text-right text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">% Used</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">Progress</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                        <tr v-for="alert in alerts" :key="alert.id" class="hover:bg-gray-50 dark:hover:bg-gray-700/50">
                            <td class="whitespace-nowrap px-6 py-3">
                                <span :class="[statusColor(alert.percent_used).bg, statusColor(alert.percent_used).text]" class="inline-flex rounded-full px-2.5 py-0.5 text-xs font-semibold">
                                    {{ statusColor(alert.percent_used).label }}
                                </span>
                            </td>
                            <td v-if="isAdmin" class="whitespace-nowrap px-6 py-3 text-gray-700 dark:text-gray-300">{{ alert.department }}</td>
                            <td class="whitespace-nowrap px-6 py-3 font-medium text-gray-900 dark:text-white">{{ alert.category }}</td>
                            <td class="whitespace-nowrap px-6 py-3 text-right text-gray-700 dark:text-gray-300">{{ formatCurrency(alert.allocated_amount) }}</td>
                            <td class="whitespace-nowrap px-6 py-3 text-right text-gray-700 dark:text-gray-300">{{ formatCurrency(alert.total_spent) }}</td>
                            <td class="whitespace-nowrap px-6 py-3 text-right font-semibold" :class="statusColor(alert.percent_used).text">
                                {{ alert.percent_used }}%
                            </td>
                            <td class="px-6 py-3">
                                <div class="h-2 w-24 overflow-hidden rounded-full bg-gray-200 dark:bg-gray-600">
                                    <div
                                        class="h-full rounded-full"
                                        :class="statusColor(alert.percent_used).badge"
                                        :style="{ width: Math.min(alert.percent_used, 100) + '%' }"
                                    ></div>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
